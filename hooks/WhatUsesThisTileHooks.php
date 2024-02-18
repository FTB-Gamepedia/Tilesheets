<?php

use MediaWiki\Hook\PageMoveCompleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use Wikimedia\Rdbms\ILoadBalancer;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;

/**
 * Handles all hooks relating to the WhatUsesThisTile feature.
 * 
 * Adds and removes entries to the ext_tilesheet_tilelinks table when pages are deleted, moved, or saved.
 */
class WhatUsesThisTileHooks implements PageDeleteCompleteHook, PageMoveCompleteHook, PageSaveCompleteHook {
	/**
	 * Specified in extension.json
	 * @param ILoadBalancer $dbLoadBalancer
	 */
	public function __construct(private ILoadBalancer $dbLoadBalancer) {}
	
	public function onPageDeleteComplete(ProperPageIdentity $page, Authority $deleter, string $reason, int $pageID, RevisionRecord $deletedRev, ManualLogEntry $logEntry, int $archivedRevisionCount) {
		$this->clearTileLinksForPage($page->getId(), $page->getNamespace());
	}
	
	public function onPageMoveComplete($old, $new, $userIdentity, $pageID, $redirID, $reason, $revision) {
		// It's worth noting that the ID doesn't change when pages are moved, according to Manual:Page table
		// However, you can move pages across namespaces, so we still need to update the table.
		$dbw = $this->dbLoadBalancer->getConnection(DB_PRIMARY);
		$dbw->newUpdateQueryBuilder()
			->update('ext_tilesheet_tilelinks')
			->set(array('tl_from_namespace' => $new->getNamespace()))
			->where(array(
				'tl_from' => $pageID,
				'tl_from_namespace' => $old->getNamespace()
			))
			->caller(__METHOD__)
			->execute();
	}
	
	public function onPageSaveComplete($wikiPage, $user, $summary, $flags, $revisionRecord, $editResult) {
		$pageID = $wikiPage->getId();
		$namespace = $wikiPage->getNamespace();
		$pageName = $wikiPage->getTitle()->getText();
		
		$this->clearTileLinksForPage($pageID, $namespace);
		if (Tilesheets::$tileLinks[$namespace][$pageName] != null) {
			array_unique(Tilesheets::$tileLinks[$namespace][$pageName]);
			foreach (Tilesheets::$tileLinks[$namespace][$pageName] as $entryID) {
				$this->addToTileLinks($pageID, $namespace, $entryID);
			}
		}
		Tilesheets::$tileLinks[$namespace][$pageName] = array();
		
		return true;
	}
	
	private function clearTileLinksForPage(int $pageID, int $namespaceID) {
		$dbw = $this->dbLoadBalancer->getConnection(DB_PRIMARY);
		$dbw->newDeleteQueryBuilder()
			->deleteFrom('ext_tilesheet_tilelinks')
			->where(array(
				'tl_from' => $pageID, 
				'tl_from_namespace' => $namespaceID
			))
			->execute();
	}
	
	private function addToTileLinks(int $pageID, int $namespaceID, int $tileID) {
		$dbw = $this->dbLoadBalancer->getConnection(DB_PRIMARY);
		
		$result = $dbw->newSelectQueryBuilder()
			->select('COUNT(`tl_to`)')
			->from('ext_tilesheet_tilelinks')
			->where(array(
				'tl_from' => $pageID,
				'tl_from_namespace' => $namespaceID,
				'tl_to' => $tileID
			))
			->fetchField();
		
		if ($result == 0) {
			$dbw->newInsertQueryBuilder()
				->insertInto('ext_tilesheet_tilelinks')
				->row(array(
					'tl_from' => $pageID,
					'tl_from_namespace' => $namespaceID,
					'tl_to' => $tileID
				))
				->caller(__METHOD__)
				->execute();
		}
	}
}

