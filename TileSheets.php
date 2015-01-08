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
$wgMessagesDirs['TileSheets'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['TileSheet'] = __DIR__ . "/TileSheets.i18n.php";
$wgExtensionMessagesFiles['TileSheetMagic'] = __DIR__ . "/TileSheets.i18n.magic.php";

$wgAutoloadClasses['TileSheet'] = __DIR__ . "/TileSheets.body.php";
$wgAutoloadClasses['TileSheetError'] = __DIR__ . "/TileSheets.body.php";
$wgAutoloadClasses['TileSheetHooks'] = __DIR__ . "/TileSheets.hooks.php";
$wgAutoloadClasses['TileSheetForm'] = __DIR__ . "/classes/TileSheetForm.php";

$wgAutoloadClasses['TileList'] = __DIR__ . "/special/TileList.php";
$wgAutoloadClasses['SheetList'] = __DIR__ . "/special/SheetList.php";
$wgAutoloadClasses['CreateTileSheet'] = __DIR__ . "/special/CreateTileSheet.php";
$wgAutoloadClasses['TileManager'] = __DIR__ . "/special/TileManager.php";
$wgAutoloadClasses['SheetManager'] = __DIR__ . "/special/SheetManager.php";

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
$wgHooks['BeforePageDisplay'][] = 'TileSheetHooks::BeforePageDisplay';
$wgHooks['EditPage::showEditForm:initial'][] = 'TileSheetHooks::OutputWarnings';
$wgHooks['OreDictOutput'][] = 'TileSheetHooks::OreDictOutput';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'TileSheetHooks::SchemaUpdate';

$paths = [
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'TileSheets',
];
$wgResourceModules += [
	'ext.tilesheets' => $paths + [
		'styles' => 'css/tilesheets.css',
	],
	'ext.tilesheets.special' => $paths + [
		'styles'   => 'css/tilesheets.special.css',
	],
];

// Default configuration
$wgTileSheetDebug = false;
