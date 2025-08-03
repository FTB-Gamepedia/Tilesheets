<?php

use MediaWiki\EditPage\EditPage;
use MediaWiki\MediaWikiServices;
use OreDict\Hook\OreDictOutputHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\EditPage__showEditForm_initialHook;
use MediaWiki\Hook\ParserFirstCallInitHook;

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

class TilesheetsHooks implements ParserFirstCallInitHook, BeforePageDisplayHook, EditPage__showEditForm_initialHook, OreDictOutputHook {
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
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
		$resultItem = $dbr->newSelectQueryBuilder()
			->select('entry_id')
			->from('ext_tilesheet_items')
			->where(array('item_name' => $item, 'mod_name' => $mod))
			->fetchRow();

		if (!$resultItem) {
			return $type == 'name' ? $item : '';
		}

		$loc = $dbr->newSelectQueryBuilder()
			->select('*')
			->from('ext_tilesheet_languages')
			->where(array(
				'entry_id' => $resultItem->entry_id,
				'lang' => $language
			))
			->fetchRow();

		if (!$loc) {
			return $type == 'name' ? $item : '';
		}

		if ($type == 'name') {
			$name = $loc->display_name;
			return empty($name) ? $item : $name;
		} else if ($type == 'description') {
			return $loc->description;
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
	public function onOreDictOutput(&$out, $items, $params) {
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
