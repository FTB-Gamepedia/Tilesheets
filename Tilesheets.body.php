<?php
/**
 * Tilesheets main body file
 *
 * @file
 * @ingroup Extensions
 * @version 1.1.1
 * @author Jinbobo <paullee05149745@gmail.com>
 * @license
 */

if ( !defined( 'MEDIAWIKI' ) ) exit;

class Tilesheets {
	static private $mQueriedItems;
	static private $mQueriedSizes;
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

		TilesheetsError::log("Preparing item: {$size}px $item ($mod)");

		$dbr = wfGetDB(DB_SLAVE);

		if (!isset(self::$mQueriedItems[$item])) {
			$results = $dbr->select('ext_tilesheet_items','*',array('item_name' => $item));
			TilesheetsError::query($dbr->lastQuery());
			if ($results === false || $results->numRows() == 0) {
				self::$mQueriedItems[$item] = null;
			} else {
				// Build table
				foreach ($results as $result) {
					self::$mQueriedItems[$item][$result->mod_name] = $result;
				}
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

		TilesheetsError::log("Outputting item: {$size}px $item ($mod)");

		if (self::$mQueriedItems[$item] == null) {
			TilesheetsError::error("Entry missing for $item!");
			return $this->errorTile($size);
		}
		if ($mod != "undefined") {
			if (!isset(self::$mQueriedItems[$item][$mod])) {
				TilesheetsError::error("Entry missing for $item ($mod)!");
				return $this->errorTile($size);
			} else {
				$x = self::$mQueriedItems[$item][$mod]->x;
				$y = self::$mQueriedItems[$item][$mod]->y;
				return $this->generateTile($mod, $size, $x, $y);
			}
		} else {
			if (count(self::$mQueriedItems[$item]) == 1) {
				$x = current(self::$mQueriedItems[$item])->x;
				$y = current(self::$mQueriedItems[$item])->y;
				$mod = current(self::$mQueriedItems[$item])->mod_name;
				TilesheetsError::warn("Mod parameter is not defined but is able to decide which entry to use! Selecting entry from $mod!");
				return $this->generateTile($mod, $size, $x, $y);
			} else {
				TilesheetsError::error("Multiple entries exist for $item and the mod parameter is not defined, cannot decide which entry to use!");
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
		Tilesheets::getModTileSizes($mod);
		if (self::$mQueriedSizes[$mod] == null) {
			TilesheetsError::error("Tilesheet for $mod is not defined!");
			return $this->errorTile($size);
		} else {
			if (!in_array($size, self::$mQueriedSizes[$mod])) {
				TilesheetsError::warn("No {$size}px tilesheet for $mod is defined! Selecting smallest size!");
				$size = min(self::$mQueriedSizes[$mod]);
			}
		}

		$file = wfFindFile("Tilesheet $mod $size.png");
		if ($file === false) {
			TilesheetsError::warn("Tilesheet $mod $size.png does not exist!");
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
		$dbr = wfGetDB(DB_SLAVE);
		if (!isset(self::$mQueriedSizes[$mod])) {
			$result = $dbr->select('ext_tilesheet_images','sizes',array("`mod`" => $mod));
			TilesheetsError::query($dbr->lastQuery());
			if ($result == false) {
				self::$mQueriedSizes[$mod] = null;
			} else {
				self::$mQueriedSizes[$mod] = explode(",", $result->current()->sizes);
			}
		}

		return self::$mQueriedSizes[$mod];
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

class TilesheetsError{
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
		MWDebug::deprecated("(Tilesheets) ".$message);
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
		MWDebug::log("(Tilesheets) ".$message);
		self::debug($message);
	}

	/**
	 * @param $message
	 */
	public static function warn($message) {
		MWDebug::warning("(Tilesheets) ".$message);
		self::debug($message, "Warning");
	}

	/**
	 * @param $message
	 */
	public static function error($message) {
		MWDebug::warning("(Tilesheets) "."Error: ".$message);
		self::debug($message, "Error");
	}

	/**
	 * @param $message
	 */
	public static function notice($message) {
		MWDebug::warning("(Tilesheets) "."Notice: ".$message);
		self::debug($message, "Notice");
	}
}
