<?php
/**
 * Tile Sheets
 *
 * @file
 * @ingroup Extensions
 * @version 1.1.1
 * @author Jinbobo <paullee05149745@gmail.com>
 * @author Peter Atashian
 * @license
 */

if( !defined( 'MEDIAWIKI' ) )
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );


$wgShowExceptionDetails = true;

$wgExtensionCredits['parserhooks'][] = array(
	'path' => __FILE__,
	'name' => 'Tile Sheets',
	'descriptionmsg' => 'tilesheets-desc',
	'version' => '1.1.2',
	'author' => '[http://wiki.feed-the-beast.com/User:Jinbobo Jinbobo], [http://wiki.feed-the-beast.com/User:Retep998 Peter Atashian], [http://wiki.feed-the-beast.com/User:SatanicSanta Eli Foster]',
	'url' => 'https://github.com/Telshin/Tilesheets'
);

// Setup logging
$wgLogTypes[] = 'tilesheet';
$wgLogActionsHandlers['tilesheet/*'] = 'LogFormatter';

// Load extension files
$wgMessagesDirs['TileSheets'] = __DIR__ .'/i18n';
$wgExtensionMessagesFiles['TileSheet'] = dirname(__FILE__)."/TileSheets.i18n.php";
$wgExtensionMessagesFiles['TileSheetMagic'] = dirname(__FILE__)."/TileSheets.i18n.magic.php";

$wgAutoloadClasses['TileSheet'] = dirname(__FILE__)."/TileSheets.body.php";
$wgAutoloadClasses['TileSheetError'] = dirname(__FILE__)."/TileSheets.body.php";
$wgAutoloadClasses['TileSheetHooks'] = dirname(__FILE__)."/TileSheets.hooks.php";
$wgAutoloadClasses['TileSheetForm'] = dirname(__FILE__)."/classes/TileSheetForm.php";

$wgAutoloadClasses['TileList'] = dirname(__FILE__)."/special/TileList.php";
$wgAutoloadClasses['SheetList'] = dirname(__FILE__)."/special/SheetList.php";
$wgAutoloadClasses['CreateTileSheet'] = dirname(__FILE__)."/special/CreateTileSheet.php";
$wgAutoloadClasses['TileManager'] = dirname(__FILE__)."/special/TileManager.php";
$wgAutoloadClasses['SheetManager'] = dirname(__FILE__)."/special/SheetManager.php";

$wgSpecialPages['TileList'] = "TileList";
$wgSpecialPageGroups['TileList'] = "tilesheet";
$wgSpecialPages['SheetList'] = "SheetList";
$wgSpecialPageGroups['SheetList'] = "tilesheet";
$wgSpecialPages['CreateTileSheet'] = "CreateTileSheet";
$wgSpecialPageGroups['CreateTileSheet'] = "tilesheet";
$wgSpecialPages['TileManager'] = "TileManager";
$wgSpecialPageGroups['TileManager'] = "tilesheet";
$wgSpecialPages['SheetManager'] = "SheetManager";
$wgSpecialPageGroups['SheetManager'] = "tilesheet";

$wgHooks['ParserFirstCallInit'][] = 'TileSheetHooks::SetupParser';
$wgHooks['EditPage::showEditForm:initial'][] = 'TileSheetHooks::OutputWarnings';
$wgHooks['OreDictOutput'][] = 'TileSheetHooks::OreDictOutput';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'TileSheetHooks::SchemaUpdate';

// Default configuration
$wgTileSheetDebug = false;
