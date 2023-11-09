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
		$dbw->update(
			'ext_tilesheet_tilelinks',
			array('tl_from_namespace' => $new->getNamespace()),
			array(
				'tl_from' => $pageID,
				'tl_from_namespace' => $old->getNamespace()
			),
			__METHOD__
		);
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
		$dbw->delete(
			'ext_tilesheet_tilelinks', 
			array(
				'tl_from' => $pageID, 
				'tl_from_namespace' => $namespaceID
			)
		);
	}
	
	private function addToTileLinks(int $pageID, int $namespaceID, int $tileID) {
		$dbw = $this->dbLoadBalancer->getConnection(DB_PRIMARY);
		
		$result = $dbw->select(
			'ext_tilesheet_tilelinks', 
			'COUNT(`tl_to`) AS count', 
			array(
				'tl_from' => $pageID,
				'tl_from_namespace' => $namespaceID,
				'tl_to' => $tileID
			)
		);
		
		if ($result->current()->count == 0) {
			$dbw->insert(
				'ext_tilesheet_tilelinks', 
				array(
					'tl_from' => $pageID,
					'tl_from_namespace' => $namespaceID,
					'tl_to' => $tileID
				),
				__METHOD__
			);
		}
	}
}

