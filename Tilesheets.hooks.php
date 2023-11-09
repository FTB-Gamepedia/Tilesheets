<?php
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\EditPage__showEditForm_initialHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

/**
 * Tilesheets hooks file
 * Entrance points to the tilesheets extension
 *
 * @file
 * @ingroup Extensions
 * @version 1.1.1
 * @author Jinbobo <paullee05149745@gmail.com>
 * @license
 */

class TilesheetsHooks implements LoadExtensionSchemaUpdatesHook, ParserFirstCallInitHook, BeforePageDisplayHook, EditPage__showEditForm_initialHook {
	
	/**
	 * Setups and Modifies Database Information
	 *
	 * @access	public
	 * @param	DatabaseUpdater $updater
	 * @return	boolean	true
	 */
	public function onLoadExtensionSchemaUpdates($updater) {
		$extDir = __DIR__;

		$updater->addExtensionUpdate(['addTable', 'ext_tilesheet_items', "{$extDir}/install/sql/ext_tilesheet_items.sql", true]);
		$updater->addExtensionUpdate(['addTable', 'ext_tilesheet_images', "{$extDir}/install/sql/ext_tilesheet_images.sql", true]);
		$updater->addExtensionUpdate(['addTable', 'ext_tilesheet_languages', "{$extDir}/install/sql/ext_tilesheet_languages.sql", true]);
		$updater->addExtensionUpdate(['addTable', 'ext_tilesheet_tilelinks', "{$extDir}/install/sql/ext_tilesheet_tilelinks.sql", true]);

		$updater->addExtensionUpdate(['modifyField', 'ext_tilesheet_languages', 'description', "{$extDir}/upgrade/sql/ext_tilesheet_languages/change_description_to_text.sql", true]);
		$updater->addExtensionUpdate(['addIndex', 'ext_tilesheet_languages', 'PRIMARY', "{$extDir}/upgrade/sql/ext_tilesheet_languages/add_primary_key.sql", true]);
		$updater->addExtensionUpdate(['addField', 'ext_tilesheet_items', 'z', "{$extDir}/upgrade/sql/ext_tilesheet_items/add_z_coordinate.sql", true]);
		return true;
	}

	static private $mOreDictMainErrorOutputted = false;

	/**
	 * Entry point for parser functions.
	 *
	 * @param Parser $parser
	 * @return bool
	 */
	public function onParserFirstCallInit($parser) {
		$parser->setFunctionHook('icon', 'TilesheetsHooks::RenderParser');
		$parser->setFunctionHook('iconloc', 'TilesheetsHooks::IconLocalization');

		return true;
	}
	
	/**
	 * Handler for BeforePageDisplay hook.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 * @param $out OutputPage object
	 * @param $skin Skin being used.
	 * @return void
	 */
	public function onBeforePageDisplay($out, $skin): void {
		// Load default styling module
		$out->addModuleStyles('ext.tilesheets');
	}

	/**
	 * Generate parser function output. Called by #icon parser function, see #onParserFirstCallInit
	 *
	 * @param Parser $parser
	 * @return array Raw HTML ready for display, will not be parsed again by parser.
	 */
	public static function RenderParser(Parser $parser) {
		// Extract options
		$opts = array();
		for ($i = 1; $i < func_num_args(); $i++) {
			$opts[] = func_get_arg($i);
		}
		$options = self::ExtractOptions($opts);

		// Run main class and output
		$tile = new Tilesheets($options, $parser);
		return $tile->output($parser);
	}

	/**
	 * Gets the localized name or description for the given item/mod. Called by iconloc parser function. See #onParserFirstCallInit
	 * @param Parser $parser
	 * @param string $item The item's name.
	 * @param string $mod The mod abbreviation.
	 * @param string $type Either 'name' or 'description'. The type of data to get.
	 * @param string $language The language code. Falls back to 'en'.
	 * @return string The localized content, or the provided item's name as fall back.
	 */
	public static function IconLocalization(Parser $parser, $item, $mod, $type = 'name', $language = 'en') {
		$dbr = wfGetDB(DB_REPLICA);
		$items = $dbr->select('ext_tilesheet_items', 'entry_id', array('item_name' => $item, 'mod_name' => $mod));

		if ($items->numRows() == 0) {
			return $type == 'name' ? $item : '';
		}

		$locs = $dbr->select('ext_tilesheet_languages', '*', array('entry_id' => $items->current()->entry_id, 'lang' => $language));
		if ($locs->numRows() == 0) {
			return $type == 'name' ? $item : '';
		}

		if ($type == 'name') {
			$name = $locs->current()->display_name;
			return empty($name) ? $item : $name;
		} else if ($type == 'description') {
			return $locs->current()->description;
		} else {
			return $item;
		}
	}

	/**
	 * Helper function to extract options from raw parser function input.
	 *
	 * @param $opts
	 * @return array
	 */
	public static function ExtractOptions($opts) {
		foreach ($opts as $option) {
			$pair = explode('=', $option);
			if (count($pair) == 2) {
				if (!empty($pair[1])) {
					$name = trim($pair[0]);
					$value = trim($pair[1]);
					$results[$name] = $value;
				}
			}
		}

		if (!isset($results)) {
			$results = array();
		}

		return $results;
	}

	/**
	 * Entry point for the EditPage::showEditForm:initial hook, allows the tilesheet extension to modify the edit form. Displays errors on preview.
	 *
	 * @param EditPage $editPage
	 * @param OutputPage $out
	 * @return bool
	 */
	public function onEditPage__showEditForm_initial($editPage, $out) {
		global $wgTileSheetDebug;

		// Output errors
		$errors = new TilesheetsError($wgTileSheetDebug);
		$editPage->editFormTextAfterWarn .= $errors->output();

		return true;
	}

	/**
	 * Entry point for the OreDictOutput hook.
	 *
	 * @param string $out
	 * @param array $items
	 * @param string $params
	 * @return bool
	 */
	public static function OreDictOutput(&$out, $items, $params) {
		if (!self::$mOreDictMainErrorOutputted) {
			TilesheetsError::notice(wfMessage('tilesheets-notice-oredict')->text());
			self::$mOreDictMainErrorOutputted = true;
		}
		foreach ($items as $item) {
			if (is_object($item) && get_class($item) == 'OreDictItem') {
				$item->joinParams($params, true);
				$templateParams = $item->getParamString();
				$out .= "{{G/Cell|$templateParams}}";
				$itemNames[] = $item->getItemName();
			}
		}
		if (isset($itemNames)) {
			$itemNames = implode(",", $itemNames);
			TilesheetsError::notice(wfMessage('tilesheets-notice-return')->params($itemNames)->text());
		}

		return true;
	}
}
