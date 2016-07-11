<?php
/**
 * Tilesheets
 *
 * @file
 * @ingroup Extensions
 * @version 3.2.0
 * @author Jinbobo <paullee05149745@gmail.com>
 * @author Peter Atashian
 * @author Telshin <timmrysk@gmail.com>
 * @author Noahm <noah@manneschmidt.net>
 * @author Cameron Chunn <cchunn@curse.com>
 * @author Eli Foster <elifosterwy@gmail.com>
 * @license
 * @package	Tilesheets
 * @link	https://github.com/HydraWiki/Tilesheets
 */

 if ( function_exists( 'wfLoadExtension' ) ) {
    wfLoadExtension( 'Tilesheets' );
    // Keep i18n globals so mergeMessageFileList.php doesn't break
    $wgMessagesDirs['Tilesheets'] = __DIR__ . '/i18n';
    $wgAutoloadClasses['TilesheetsQuerySheetsApi'] = __DIR__ . '/api/TilesheetsQuerySheetsApi.php';
    $wgAPIListModules['tilesheets'] = 'TilesheetsQuerySheetsApi';
    $wgAutoloadClasses['TilesheetsQueryTilesApi'] = __DIR__ . '/api/TilesheetsQueryTilesApi.php';
    $wgAPIListModules['tiles'] = 'TilesheetsQueryTilesApi';
    $wgAutoloadClasses['TilesheetsQueryTranslationsApi'] = __DIR__ . '/api/TilesheetsQueryTranslationsApi.php';
    $wgAPIListModules['tiletranslations'] = 'TilesheetsQueryTranslationsApi';
    $wgAutoloadClasses['TilesheetsAddTileApi'] = __DIR__ . '/api/TilesheetsAddTileApi.php';
    $wgAPIModules['addtile'] = 'TilesheetsAddTileApi';
    $wgAutoloadClasses['TilesheetsDeleteSheetApi'] = __DIR__ . '/api/TilesheetsDeleteSheetApi.php';
    $wgAPIModules['deletesheet'] = 'TilesheetsDeleteSheetApi';
    $wgAutoloadClasses['TilesheetsAddSheetApi'] = __DIR__ . '/api/TilesheetsAddSheetApi.php';
    $wgAPIModules['createsheet'] = 'TilesheetsAddSheetApi';
    $wgAutoloadClasses['TilesheetsDeleteTilesApi'] = __DIR__ . '/api/TilesheetsDeleteTilesApi.php';
    $wgAPIModules['deletetiles'] = 'TilesheetsDeleteTilesApi';
    $wgAutoloadClasses['TilesheetsEditTileApi'] = __DIR__ . '/api/TilesheetsEditTileApi.php';
    $wgAPIModules['edittile'] = 'TilesheetsEditTileApi';
    $wgAutoloadClasses['TilesheetsEditSheetApi'] = __DIR__ . '/api/TilesheetsEditSheetApi.php';
    $wgAPIModules['editsheet'] = 'TilesheetsEditSheetApi';
    $wgAutoloadClasses['TilesheetsDeleteTranslationApi'] = __DIR__ . '/api/TilesheetsDeleteTranslationApi.php';
    $wgAPIModules['deletetranslation'] = 'TilesheetsDeleteTranslationApi';
    $wgAutoloadClasses['TilesheetsTranslateTileApi'] = __DIR__ . '/api/TilesheetsTranslateTileApi.php';
    $wgAPIModules['translatetile'] = 'TilesheetsTranslateTileApi';
    wfWarn(
 	   'Deprecated PHP entry point used for Tilesheets extension. Please use wfLoadExtension instead, ' .
 	   'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
    );
    return;
 } else {
    die( 'This version of the Tilesheets extension requires MediaWiki 1.25+' );
 }
