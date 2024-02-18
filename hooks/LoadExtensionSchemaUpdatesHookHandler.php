<?php
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

/**
 * Handler for the "LoadExtensionSchemaUpdates" hook. 
 * 
 * This cannot use dependency injection as this hook is used in a context where the global service locator is not yet initialized.
 * 
 * @author elifoster
 */
class LoadExtensionSchemaUpdatesHookHandler implements LoadExtensionSchemaUpdatesHook {
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
		return true;
	}
}