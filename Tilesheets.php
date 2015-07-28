<?php
/**
 * Tilesheets
 *
 * @file
 * @ingroup Extensions
 * @version 1.1.3
 * @author Jinbobo <paullee05149745@gmail.com>
 * @author Peter Atashian
 * @author Telshin <timmrysk@gmail.com>
 * @author Noahm <noah@manneschmidt.net>
 * @license
 */

if( !defined( 'MEDIAWIKI' ) )
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );


$wgShowExceptionDetails = true;

$wgExtensionCredits['parserhooks'][] = array(
	'path' => __FILE__,
	'name' => 'Tilesheets',
	'descriptionmsg' => 'tilesheets-desc',
	'version' => '1.1.3',
	'author' => '[http://ftb.gamepedia.com/User:Jinbobo Jinbobo], [http://ftb.gamepedia.com/User:Retep998 Peter Atashian], [http://ftb.gamepedia.com/User:SatanicSanta Eli Foster], Telshin, Noahm',
	'url' => 'http://help.gamepedia.com/Extension:Tilesheets'
);

// Setup logging
$wgLogTypes[] = 'tilesheet';
$wgLogActionsHandlers['tilesheet/*'] = 'LogFormatter';

// Load extension files
$wgMessagesDirs['Tilesheets'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['Tilesheets'] = __DIR__ . "/Tilesheets.i18n.php";
$wgExtensionMessagesFiles['TilesheetsMagic'] = __DIR__ . "/Tilesheets.i18n.magic.php";

$wgAutoloadClasses['Tilesheets'] = __DIR__ . "/Tilesheets.body.php";
$wgAutoloadClasses['TilesheetsError'] = __DIR__ . "/Tilesheets.body.php";
$wgAutoloadClasses['TilesheetsHooks'] = __DIR__ . "/Tilesheets.hooks.php";
$wgAutoloadClasses['TilesheetsForm'] = __DIR__ . "/classes/TilesheetsForm.php";

$wgAutoloadClasses['TileList'] = __DIR__ . "/special/TileList.php";
$wgAutoloadClasses['SheetList'] = __DIR__ . "/special/SheetList.php";
$wgAutoloadClasses['CreateTileSheet'] = __DIR__ . "/special/CreateTileSheet.php";
$wgAutoloadClasses['TileManager'] = __DIR__ . "/special/TileManager.php";
$wgAutoloadClasses['SheetManager'] = __DIR__ . "/special/SheetManager.php";

$wgSpecialPages['TileList'] = "TileList";
$wgSpecialPages['SheetList'] = "SheetList";
$wgSpecialPages['CreateTileSheet'] = "CreateTileSheet";
$wgSpecialPages['TileManager'] = "TileManager";
$wgSpecialPages['SheetManager'] = "SheetManager";

$wgHooks['ParserFirstCallInit'][] = 'TilesheetsHooks::SetupParser';
$wgHooks['BeforePageDisplay'][] = 'TilesheetsHooks::BeforePageDisplay';
$wgHooks['EditPage::showEditForm:initial'][] = 'TilesheetsHooks::OutputWarnings';
$wgHooks['OreDictOutput'][] = 'TilesheetsHooks::OreDictOutput';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'TilesheetsHooks::SchemaUpdate';

$wgAvailableRights[] = 'edittilesheets';
$wgAvailableRights[] = 'importtilesheets';

$paths = [
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Tilesheets',
];
$wgResourceModules += [
	'ext.tilesheets' => $paths + [
		'styles' => 'css/tilesheets.css',
	],
	'ext.tilesheets.special' => $paths + [
		'styles' => 'css/tilesheets.special.css',
	],
];

// Default configuration
$wgTilesheetsDebug = false;
