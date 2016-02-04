<?php
/**
 * Tilesheets
 *
 * @file
 * @ingroup Extensions
 * @version 1.1.4
 * @author Jinbobo <paullee05149745@gmail.com>
 * @author Peter Atashian
 * @author Telshin <timmrysk@gmail.com>
 * @author Noahm <noah@manneschmidt.net>
 * @author Cameron Chunn <cchunn@curse.com>
 * @license
 * @package	Tilesheets
 * @link	https://github.com/CurseStaff/Tilesheets
 */

 if ( function_exists( 'wfLoadExtension' ) ) {
    wfLoadExtension( 'Tilesheets' );
    // Keep i18n globals so mergeMessageFileList.php doesn't break
    $wgMessagesDirs['Tilesheets'] = __DIR__ . '/i18n';
    $wgAutoloadClasses['TilesheetsQuerySheetsApi'] = __DIR__ . '/api/TilesheetsQuerySheetsApi.php';
    $wgAPIListModules['tilesheets'] = 'TilesheetsQuerySheetsApi';
    $wgAutoloadClasses['TilesheetsQueryTilesApi'] = __DIR__ . '/api/TilesheetsQueryTilesApi.php';
    $wgAPIListModules['tiles'] = 'TilesheetsQueryTilesApi';
    wfWarn(
 	   'Deprecated PHP entry point used for Tilesheets extension. Please use wfLoadExtension instead, ' .
 	   'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
    );
    return;
 } else {
    die( 'This version of the Tilesheets extension requires MediaWiki 1.25+' );
 }