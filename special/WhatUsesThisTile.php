<?php

class WhatUsesThisTile extends SpecialPage {
	private $target;

	public function __construct() {
		parent::__construct('WhatUsesThisTile');
	}

	protected function getGroupName() {
		return 'tilesheet';
	}

	public function execute($entryID) {
		$out = $this->getOutput();
		$this->setHeaders();
		$this->outputHeader();
		$opts = new FormOptions();

		// Require subpage syntax. Nobody memorizes entry IDs.
		if (empty($entryID)) {
			$out->setPageTitle($this->msg('tilesheet-whatusesthistile-title-noid'));
			$out->addWikiText($this->msg('tilesheet-whatusesthistile-noid'));
			return;
		}

		$this->target = $entryID;

		$opts->add('limit', $this->getConfig()->get('QueryPageDefaultLimit'));
		$opts->add('page', 0);
		$opts->add('from', 0);
		$opts->add('back', 0);

		$opts->fetchValuesFromRequest($this->getRequest());
		$opts->validateIntBounds('limit', 0, 5000);

		$limit = intval($opts->getValue('limit'));
		$page = intval($opts->getValue('page'));
		$from = intval($opts->getValue('from'));

		$dbr = wfGetDB(DB_REPLICA);
		$tileData = $dbr->select('ext_tilesheet_items', '*', array('entry_id' => $entryID));
		$out->addBacklinkSubtitle(Title::newFromText("ViewTile/$entryID", NS_SPECIAL));
		if ($tileData->numRows() == 0) {
			$out->setPageTitle($this->msg('tilesheet-whatusesthistile-title', $entryID));
			$out->addWikiText($this->msg('tilesheet-whatusesthistile-notile', $entryID));
			return;
		} else {
			$name = $tileData->current()->item_name;
			$out->setPageTitle($this->msg('tilesheet-whatusesthistile-title-full', $name, $tileData->current()->mod_name));

			$conditions = ['tl_to' => $entryID];
			if ($from) {
				$conditions[] = "tl_from >= $from";
			}
			$results = $dbr->select(
				'ext_tilesheet_tilelinks',
				'*',
				$conditions,
				__METHOD__,
				array(
					// Get an extra row so we can determine pagination things
					'LIMIT' => $limit + 1,
					'OFFSET' => $page * $limit,
					'ORDER BY' => 'tl_from ASC'
				)
			);

			if ($results->numRows() == 0) {
				$out->addWikiText($this->msg('tilesheet-whatusesthistile-none', $name));
				return;
			} else {
				$out->addWikiText($this->msg('tilesheet-whatusesthistile-some', $name));

				$rows = [];
				foreach ($results as $row) {
					$rows[] = $row;
				}

				// Get the next value from our extra entry if possible.
				if (count($rows) > $limit) {
					$next = $rows[$limit]->tl_from;
					$rows = array_slice($rows, 0, $limit);
				} else {
					$next = false;
				}

				$prevNext = $this->getPrevNext($from, $next, $opts);
				$out->addHtml($prevNext);

				$out->addHtml(Xml::openElement('ul'));
				foreach ($rows as $row) {
					$pageName = $dbr->select(
						'page',
						'page_title',
						array(
							'page_id' => $row->tl_from,
							'page_namespace' => $row->tl_from_namespace
						),
						__METHOD__
					)->current()->page_title;
					$title = Title::newFromText($pageName, $row->tl_from_namespace);
					$out->addHtml(Xml::openElement('li') . $this->getLinkRenderer()->makeKnownLink($title) . Xml::closeElement('li'));
				}
				$out->addHtml(Xml::closeElement('ul'));

				$out->addHtml($prevNext);
			}
		}
	}

	/**
	 * Taken from SpecialWhatlinkshere and adapted for our standards and codebase
	 * @param $prevID integer
	 * @param $nextID integer|bool
	 * @param $opts FormOptions
	 * @return string
	 */
	private function getPrevNext($prevID, $nextID, $opts) {
		$limit = intval($opts->getValue('limit'));
		$prev = $this->msg('whatlinkshere-prev')->numParams($limit)->escaped();
		$next = $this->msg('whatlinkshere-next')->numParams($limit)->escaped();

		$changed = $opts->getChangedValues();

		if ($prevID != 0) {
			$overrides = [
				'from' => $opts->getValue('back')
			];
			$prev = $this->makeSelfLink($prev, array_merge($changed, $overrides));
		}
		if ($nextID != 0) {
			$overrides = [
				'from' => $nextID,
				'back' => $prevID
			];
			$next = $this->makeSelfLink($next, array_merge($changed, $overrides));
		}

		$limitLinks = [];
		$lang = $this->getLanguage();
		foreach ([ 20, 50, 100, 250, 500 ] as $limit) {
			$prettyLimit = htmlspecialchars($lang->formatNum($limit));
			$overrides = [ 'limit' => $limit ];
			$limitLinks[] = $this->makeSelfLink($prettyLimit, array_merge($changed, $overrides));
		}

		$nums = $lang->pipeList($limitLinks);

		return $this->msg('viewprevnext')->rawParams($prev, $next, $nums)->escaped();
	}

	/**
	 * Taken from SpecialWhatlinkshere and adapted for our standards and codebase
	 * @param $text string
	 * @param $query array
	 * @return string
	 */
	private function makeSelfLink($text, $query) {
		$text = new HtmlArmor($text);

		return $this->getLinkRenderer()->makeKnownLink($this->getPageTitle($this->target), $text, [], $query);
	}
}