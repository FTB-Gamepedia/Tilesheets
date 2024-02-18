<?php
use Wikimedia\Rdbms\ILoadBalancer;
use MediaWiki\Permissions\PermissionManager;

/**
 * TileList special page file
 *
 * @file
 * @ingroup Extensions
 * @version 1.1.1
 * @author Jinbobo <paullee05149745@gmail.com>
 * @license
 */

class TileList extends SpecialPage {
	/**
	 * Calls parent constructor and sets special page title
	 */
	public function __construct(private ILoadBalancer $dbLoadBalancer, private PermissionManager $permissionManager) {
		parent::__construct('TileList');
	}

	/**
	 * Return the group name for this special page.
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function getGroupName() {
		return 'tilesheet';
	}

	/**
	 * Build special page
	 *
	 * @param null|string $par Subpage name
	 */
	public function execute($par) {
		global $wgQueryPageDefaultLimit;
		$out = $this->getOutput();
		$out->enableOOUI();

		$this->setHeaders();
		$this->outputHeader();
		$out->addModuleStyles('ext.tilesheets.special');

		$opts = new FormOptions();

		$opts->add('limit', $wgQueryPageDefaultLimit);
		$opts->add('mod', '');
		$opts->add('regex', '');
		$opts->add('langs', '');
		$opts->add('invertlang', 0);
		$opts->add('page', 0);
		$opts->add('from', 1);

		$opts->fetchValuesFromRequest($this->getRequest());
		$opts->validateIntBounds('limit', 0, 5000);

		// Init variables
		$mod = $opts->getValue('mod');
		$regex = $opts->getValue('regex');
		$limit = intval($opts->getValue('limit'));
		$page = intval($opts->getValue('page'));
		$opts->setValue('langs', str_replace(' ', '', $opts->getValue('langs')));
		$langs = explode(',', $opts->getValue('langs'));
		$from = intval($opts->getValue('from'));

		// Load data
		$dbr = $this->dbLoadBalancer->getConnection(DB_REPLICA);
		$formattedEntryIDs = '';

		if (!empty($langs)) {
			$langResult = $dbr->newSelectQueryBuilder()
				->select('entry_id')
				->from('ext_tilesheet_languages')
				->where(array('lang' => $langs))
				->fetchResultSet();

			$filteredEntryIDs = array();
			foreach ($langResult as $result) {
				$filteredEntryIDs[] = $result->entry_id;
			}

			$formattedEntryIDs = implode(', ', $filteredEntryIDs);
		}

		$conditions = array("entry_id >= $from");
		if ($formattedEntryIDs != '') {
			$conditions[] = 'entry_id ' . ($opts->getValue('invertlang') == 1 ? 'NOT' : '') . ' IN (' . $formattedEntryIDs . ')';
		}
		if ($mod != '') {
			$conditions[] = "mod_name = {$dbr->addQuotes($mod)}";
		}

		$searchNames = $regex != '';

		try {
			if ($searchNames) {
				$conditions[] = "item_name REGEXP {$dbr->addQuotes($regex)}";
			}
			$maxRows = $dbr->newSelectQueryBuilder()
				->select('COUNT(entry_id)')
				->from('ext_tilesheet_items')
				->where($conditions)
				->fetchField();
		} catch (Exception $exception) {
			// Fallback to the following query when the regex is invalid.
			if ($searchNames) {
				$conditions = array_replace($conditions, array(count($conditions) - 1 => "item_name = {$dbr->addQuotes($regex)}"));
			}
			$maxRows = $dbr->newSelectQueryBuilder()
				->select('COUNT(entry_id)')
				->from('ext_tilesheet_items')
				->where($conditions)
				->fetchField();
		}

        // TODO: Specify between: `entry_id ASC`; `item_name ASC`; `entry_id DESC`; `item_name DESC`
		$order = 'entry_id ASC';
		$results = $dbr->newSelectQueryBuilder()
			->select('*')
			->from('ext_tilesheet_items')
			->where($conditions)
			->caller(__METHOD__)
			->orderBy($order)
			->limit($limit)
			->offset($page * $limit)
			->fetchResultSet();

		if ($maxRows == 0) {
		    $this->displayFilterForm($opts);
			$out->addWikiTextAsInterface($this->msg('tilesheet-fail-norows')->text());
			return;
		}

		// Load table
		$table = "{| class=\"mw-datatable\" style=\"width:100%\"\n";
		$msgItemName = wfMessage('tilesheet-item-name');
		$msgModName = wfMessage('tilesheet-mod-name');
		$msgSizesName = wfMessage('tilesheet-sizes');
		$msgXName = wfMessage('tilesheet-x');
		$msgYName = wfMessage('tilesheet-y');
		$msgZName = wfMessage('tilesheet-z');
		$canEdit = $this->permissionManager->userHasRight($this->getUser(), 'edittilesheets');
		$canTranslate = $this->permissionManager->userHasRight($this->getUser(), 'translatetiles');
		$table .= "!";
		if ($canEdit) {
			$table .= " !!";
		}
		if ($canTranslate) {
			$table .= " !!";
		}
		$table .= " !! # !!  $msgItemName !! $msgModName !! $msgXName !! $msgYName !! $msgZName !! $msgSizesName\n";
		$linkStyle = "style=\"width:23px; padding-left:5px; padding-right: 5px; text-align:center; font-weight:bold;\"";
		foreach ($results as $result) {
			$lId = $result->entry_id;
			$lItem = $result->item_name;
			$lMod = $result->mod_name;
			$lX = $result->x;
			$lY = $result->y;
			$lZ = $result->z;
			$lSizes = Tilesheets::getModTileSizes($lMod);
			if ($lSizes == null);
			else {
				foreach ($lSizes as $key => $size) {
					$lSizes[$key] = "[[:File:Tilesheet $lMod $size $lZ.png|{$size}px]]";
				}
				$lSizes = implode(",", $lSizes);
			}

			if ($canEdit) {
				$editLink = "[[Special:TileManager/$lId|" . $this->msg('tilesheet-edit')->text() . "]]";
				$sEditLink = "[[Special:SheetManager/$lMod|$lMod]]";
			} else {
				$editLink = "";
				$sEditLink = "$lMod";
			}

			$translateLink = $canTranslate ? "[[Special:TileTranslator/$lId|" . $this->msg('tilesheet-tile-list-translate')->text() . "]]" : '';

			$viewLink = "[[Special:ViewTile/$lId|" . $this->msg('tilesheet-tile-list-view') . "]]";

			$table .= "|-\n| ";
			if ($canEdit) {
				$table .= "$linkStyle | $editLink || ";
			}
			if ($canTranslate) {
				$table .= "$linkStyle | $translateLink || ";
			}
			$table .= "$linkStyle | $viewLink || $lId ||  $lItem || $sEditLink || $lX || $lY || $lZ || $lSizes\n";
		}
		$table .= "|}\n";

		// Page nav stuff
		// TODO replace with our pagination stuff
		$page = $opts->getValue('page');
		$pPage = $page-1;
		$nPage = $page+1;
		$lPage = floor($maxRows / $limit);
		if ($page == 0) {
			$prevPage = "'''" . $this->msg('tilesheet-pagination-first')->text() .  "'''";
		} else {
			if ($page == 1) {
				$prevPage = "[{{fullurl:{{FULLPAGENAME}}|regex=".$opts->getValue('regex')."&mod=".$opts->getValue('mod')."&langs=".$opts->getValue('langs')."&invertlang=".$opts->getValue('invertlang')."&from=".$opts->getValue('from')."&limit=".$opts->getValue('limit')."}} " . $this->msg('tilesheet-pagination-first-arrow')->text() . "]";
			} else {
				$prevPage = "[{{fullurl:{{FULLPAGENAME}}|regex=".$opts->getValue('regex')."&mod=".$opts->getValue('mod')."&langs=".$opts->getValue('langs')."&invertlang=".$opts->getValue('invertlang')."&from=".$opts->getValue('from')."&limit=".$opts->getValue('limit')."}} " . $this->msg('tilesheet-pagination-first-arrow')->text() . "] [{{fullurl:{{FULLPAGENAME}}|page={$pPage}&mod=".$opts->getValue('mod')."&langs=".$opts->getValue('langs')."&invertlang=".$opts->getValue('invertlang')."&limit=".$opts->getValue('limit')."}} " . $this->msg('tilesheet-pagination-prev')->text() . "]";
			}
		}
		if ($lPage == $page) {
			$nextPage = "'''" . $this->msg('tilesheet-pagination-last') . "'''";
		} else {
			if ($lPage == $page + 1) {
				$nextPage = "[{{fullurl:{{FULLPAGENAME}}|page={$nPage}&regex=".$opts->getValue('regex')."&mod=".$opts->getValue('mod')."&langs=".$opts->getValue('langs')."&invertlang=".$opts->getValue('invertlang')."&from=".$opts->getValue('from')."&limit=".$opts->getValue('limit')."}} " . $this->msg('tilesheet-pagination-last-arrow')->text() . "]";
			} else {
				$nextPage = "[{{fullurl:{{FULLPAGENAME}}|page={$nPage}&regex=".$opts->getValue('regex')."&mod=".$opts->getValue('mod')."&langs=".$opts->getValue('langs')."&invertlang=".$opts->getValue('invertlang')."&from=".$opts->getValue('from')."&limit=".$opts->getValue('limit')."}} " . $this->msg('tilesheet-pagination-next')->text() . "] [{{fullurl:{{FULLPAGENAME}}|page={$lPage}&regex=".$opts->getValue('regex')."&mod=".$opts->getValue('mod')."&langs=".$opts->getValue('langs')."&invertlang=".$opts->getValue('invertlang')."&limit=".$opts->getValue('limit')."}} " . $this->msg('tilesheet-pagination-last-arrow')->text() . "]";
			}
		}
		$pageSelection = "<div style=\"text-align:center;\" class=\"plainlinks\">$prevPage | $nextPage</div>";

        // Output page
        $this->displayFilterForm($opts);
		$out->addWikiTextAsInterface($pageSelection);
		$out->addWikiTextAsInterface($table);
	}

	private function displayFilterForm(FormOptions $opts) {
        // Build filter form
        $lang = $this->getLanguage();
        $formDescriptor = [
            'from' => [
                'type' => 'int',
                'name' => 'from',
                'default' => $opts->getValue('from'),
                'label-message' => 'tilesheet-tile-list-from',
                'min' => 1
            ],
            'regex' => [
                'type' => 'text',
                'name' => 'regex',
                'default' => $opts->getValue('regex'),
                'label-message' => 'tilesheet-tile-list-regex'
            ],
            'mod' => [
                'type' => 'text',
                'name' => 'mod',
                'default' => $opts->getValue('mod'),
                'label-message' => 'tilesheet-tile-list-mod'
            ],
            'langs' => [
                'type' => 'text',
                'name' => 'langs',
                'default' => $opts->getValue('langs'),
                'label-message' => 'tilesheet-tile-list-langs'
            ],
            'invertlang' => [
                'type' => 'check',
                'name' => 'invertlang',
                'default' => 0,
                'label-message' => 'tilesheet-tile-list-invertlang'
            ],
            'limit' => [
                'type' => 'limitselect',
                'name' => 'limit',
                'label-message' => 'tilesheet-tile-list-limit',
                'options' => [
                    $lang->formatNum(20) => 20,
                    $lang->formatNum(50) => 50,
                    $lang->formatNum(100) => 100,
                    $lang->formatNum(250) => 250,
                    $lang->formatNum(500) => 500,
                    $lang->formatNum(5000) => 5000
                ],
                'default' => $opts->getValue('limit')
            ]
        ];

        $htmlForm = HTMLForm::factory('ooui', $formDescriptor, $this->getContext());
        $htmlForm
            ->setMethod('get')
            ->setWrapperLegendMsg('tilesheet-tile-list-legend')
            ->setId('ext-tilesheet-tile-list-filter')
            ->setSubmitTextMsg('tilesheet-tile-list-submit')
            ->prepareForm()
            ->displayForm(false);
    }
}
