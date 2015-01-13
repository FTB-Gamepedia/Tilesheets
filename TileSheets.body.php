<?php
/**
 * Tile Sheets main body file
 *
 * @file
 * @ingroup Extensions
 * @version 1.1.1
 * @author Jinbobo <paullee05149745@gmail.com>
 * @license
 */

if ( !defined( 'MEDIAWIKI' ) ) exit;

class TileSheet {
	/**
	 * Length of time item tile data will remain cached in memcache
	 */
	static const CACHE_DURATION = 60 * 15; // 15 min cache

	/**
	 * Per-request caching for data from the ext_tilesheet_items DB table
	 * @var	array
	 */
	static private $itemCache = [];

	/**
	 * Per-request caching for size data from the ext_tilesheet_images DB table
	 * @var	array
	 */
	static private $tileSizeCache = [];

	private $mOptions;

	/**
	 * Prepare the tile for outputting, retrieve stuff from database if not already retrieved
	 *
	 * @param $options
	 */
	public function __construct($options) {
		$this->mOptions = $options;

		// Set default values
		$item = $options['item'];
		$size = 32;
		$mod = "undefined";

		// Retrieve parser function parameters
		if (!isset($options['item'])) return;
		if (isset($options['size'])) $size = $options['size'];
		if (isset($options['mod'])) $mod = $options['mod'];

		TileSheetError::log("Preparing item: {$size}px $item ($mod)");

		// use local cache first
		if (isset(self::$itemCache[$item])) {
			return;
		}

		// fall back to memcache
		$memCache = wfGetCache( CACHE_ANYTHING );
		$cacheKey = wfMemcKey('tilesheets', 'items', $item);
		self::$itemCache[$item] = $memCache->get($cacheKey);

		if (self::$itemCache[$item] === false) {
			// fall back to DB query
			$dbr = wfGetDB(DB_SLAVE);
			$results = $dbr->select('ext_tilesheet_items','*',array('item_name' => $item));
			TileSheetError::query($dbr->lastQuery());
			if ($results !== false && $results->numRows() > 0) {
				// Build table
				self::$itemCache[$item] = [];
				foreach ($results as $result) {
					self::$itemCache[$item][$result->mod_name] = $result;
				}
				$memCache->set($cacheKey, self::$itemCache[$item], self::CACHE_DURATION);
			} else {
				self::$itemCache[$item] = false;
			}
		}
	}

	/**
	 * Output tile
	 *
	 * @return array|string
	 */
	public function output() {
		// Set default values
		$item = $this->mOptions['item'];
		$size = 32;
		$mod = "undefined";

		// Retrieve parser function parameters
		if (!isset($this->mOptions['item'])) return "";
		if (isset($this->mOptions['size'])) $size = $this->mOptions['size'];
		if (isset($this->mOptions['mod'])) $mod = $this->mOptions['mod'];

		TileSheetError::log("Outputting item: {$size}px $item ($mod)");

		if (self::$itemCache === false) {
			TileSheetError::error("Entry missing for $item!");
			return $this->errorTile($size);
		}

		if ($mod != "undefined") {
			if (!isset(self::$itemCache[$item][$mod])) {
				TileSheetError::error("Entry missing for $item ($mod)!");
				return $this->errorTile($size);
			} else {
				$x = self::$itemCache[$item][$mod]->x;
				$y = self::$itemCache[$item][$mod]->y;
				return $this->generateTile($mod, $size, $x, $y);
			}
		} else {
			if (count($this->mItems) == 1) {
				$x = current(self::$itemCache[$item])->x;
				$y = current(self::$itemCache[$item])->y;
				$mod = current(self::$itemCache[$item])->mod_name;
				TileSheetError::warn("Mod parameter is not defined but is able to decide which entry to use! Selecting entry from $mod!");
				return $this->generateTile($mod, $size, $x, $y);
			} else {
				TileSheetError::error("Multiple entries exist for $item and the mod parameter is not defined, cannot decide which entry to use!");
				return $this->errorTile($size);
			}
		}
	}

	/**
	 * Generate a tile from the parameters give, will also check if the provided size is valid.
	 *
	 * @param $mod
	 * @param $size
	 * @param $x
	 * @param $y
	 * @return array
	 */
	private function generateTile($mod, $size, $x, $y) {
		// Validate tilesheet size
		$sizes = TileSheet::getModTileSizes($mod);
		if ($sizes === false) {
			TileSheetError::error("Tilesheet for $mod is not defined!");
			return $this->errorTile($size);
		} else {
			if (!in_array($size, $sizes)) {
				TileSheetError::warn("No {$size}px tilesheet for $mod is defined! Selecting smallest size!");
				$size = min($sizes);
			}
		}

		$file = wfFindFile("Tilesheet $mod $size.png");
		if ($file === false) {
			TileSheetError::warn("Tilesheet $mod $size.png does not exist!");
			return $this->errorTile($size);
		}
		$url = $file->getUrl();
		$x *= $size;
		$y *= $size;
		return array("<span class=\"tilesheet\" style=\"background:url($url) -{$x}px -{$y}px;width:{$size}px;height:{$size}px\"><br></span>", 'noparse' => true, 'isHTML' => true);
	}

	/**
	 * Get registered tilesheet sizes for the provided mod
	 *
	 * @param string $mod Mod to search
	 * @return mixed
	 */
	public static function getModTileSizes($mod) {
		// use local cache first
		if (isset(self::$tileSizeCache[$mod])) {
			return self::$tileSizeCache[$mod];
		}

		// fall back to memcache
		$memCache = wfGetCache( CACHE_ANYTHING );
		$cacheKey = wfMemcKey('tilesheets', 'itemsizes', $mod);
		$sizes = $memCache->get($cacheKey);

		if ($sizes === false) {
			// fall back to DB query
			$dbr = wfGetDB(DB_SLAVE);
			$result = $dbr->select('ext_tilesheet_images','sizes',array("`mod`" => $mod));
			TileSheetError::query($dbr->lastQuery());
			if ($result) {
				$sizes = explode(",", $result->current()->sizes);
				$memCache->set($cacheKey, $size, self::CACHE_DURATION);
				self::$tileSizeCache[$mod] = $sizes;
			} else {
				self::$tileSizeCache[$mod] = null;
			}
		}

		return $sizes;
	}

	/**
	 * Generate a red semi-tranparent tile denoting an error.
	 *
	 * @param int $size
	 * @return array
	 */
	private function errorTile($size = 32) {
		return array("<span class=\"tilesheet\" style=\"width:{$size}px;height:{$size}px\"><br></span>", 'noparse' => true, 'isHTML' => true);
	}

	public static function buildDiffString($diff) {
		$diffString = "";
		foreach ($diff as $field => $change) {
			$diffString .= "$field [$change[0] -> $change[1]] ";
		}
		return $diffString;
	}
}

class TileSheetError{
	static private $mDebug;
	private $mDebugMode;

	/**
	 * @param $debugMode
	 */
	public function __construct($debugMode) {
		$this->mDebugMode = $debugMode;
	}

	/**
	 * Generate errors on edit page preview
	 *
	 * @return string
	 */
	public function output() {
		if (!isset(self::$mDebug)) return "";

		$colors = array(
			"Log" => "#CEFFFD",
			"Warning" => "#FFFFB5",
			"Deprecated" => "#CCF",
			"Query" => "#D1FFB3",
			"Error" => "#FFCECE",
			"Notice" => "blue"
		);

		$textColors = array(
			"Log" => "black",
			"Warning" => "black",
			"Deprecated" => "black",
			"Query" => "black",
			"Error" => "black",
			"Notice" => "white"
		);

		$html = "<table class=\"wikitable\" style=\"width:100%;\">";
		$html .= "<caption>Tilesheet extension warnings</caption>";
		$html .= "<tr><th style=\"width:10%;\">Type</th><th>Message</th><tr>";
		$flag = true;
		foreach (self::$mDebug as $message) {
			if (!$this->mDebugMode && $message[0] != "Warning" && $message[0] != "Error" && $message[0] != "Notice") {
				continue;
			}
			$html .= "<tr><td style=\"text-align:center; background-color:{$colors[$message[0]]}; color:{$textColors[$message[0]]}; font-weight:bold;\">{$message[0]}</td><td>{$message[1]}</td></tr>";
			if ($message[0] == "Warnings" || $message[0] == "Error") $flag = false;
		}
		if ($flag) {
			$html .= "<tr><td style=\"text-align:center; background-color:blue; color:white; font-weight:bold;\">Notice</td><td>No warnings.</td></tr>";
		}
		$html .= "</table>";

		return $html;
	}

	/**
	 * @param $message
	 * @param string $type
	 */
	public static function debug($message, $type = "Log") {
		self::$mDebug[] = array($type, $message);
	}

	/**
	 * @param $message
	 */
	public static function deprecated($message) {
		MWDebug::deprecated("(TileSheets) ".$message);
		self::debug($message, "Deprecated");
	}

	/**
	 * @param $query
	 */
	public static function query($query) {
		global $wgShowSQLErrors;

		// Hide queries if debug option is not set in LocalSettings.php
		if($wgShowSQLErrors)
			self::debug($query, "Query");
	}

	/**
	 * @param $message
	 */
	public static function log($message) {
		MWDebug::log("(TileSheets) ".$message);
		self::debug($message);
	}

	/**
	 * @param $message
	 */
	public static function warn($message) {
		MWDebug::warning("(TileSheets) ".$message);
		self::debug($message, "Warning");
	}

	/**
	 * @param $message
	 */
	public static function error($message) {
		MWDebug::warning("(TileSheets) "."Error: ".$message);
		self::debug($message, "Error");
	}

	/**
	 * @param $message
	 */
	public static function notice($message) {
		MWDebug::warning("(TileSheets) "."Notice: ".$message);
		self::debug($message, "Notice");
	}
}
