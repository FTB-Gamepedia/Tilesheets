<?php
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * SheetsList special page file
 *
 * @file
 * @ingroup Extensions
 * @version 1.1.1
 * @author Jinbobo <paullee05149745@gmail.com>
 * @license
 */

class SheetList extends SpecialPage {
	/**
	 * Calls parent constructor and sets special page title
	 */
	public function __construct(private ILoadBalancer $dbLoadBalancer) {
		parent::__construct('SheetList');
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
		$opts->add('page', 0);

		$opts->fetchValuesFromRequest($this->getRequest());
		$opts->validateIntBounds('limit', 0, 5000);

		// Init variables
		$limit = intval($opts->getValue('limit'));
		$page = intval($opts->getValue('page'));

		// Load data
		$dbr = $this->dbLoadBalancer->getConnection(DB_REPLICA);
		$result = $dbr->select(
			'ext_tilesheet_images',
			'COUNT(`mod`) AS row_count'
		);
		foreach ($result as $row) {
			$maxRows = $row->row_count;
		}

		if (!isset($maxRows)) return;

		$results = $dbr->select(
			'ext_tilesheet_images',
			'*',
			array(),
			__METHOD__,
			array(
				'ORDER BY' => '`mod` ASC',
				'LIMIT' => $limit,
				'OFFSET' => $page * $limit
			)
		);

		if ($maxRows == 0) {
			$this->displayFilterForm($opts);
			$out->addWikiText($this->msg('tilesheet-fail-norows')->text());
			return;
		}

		// Load table
		$table = "{| class=\"mw-datatable\" style=\"width:100%\"\n";
		$msgModName = wfMessage('tilesheet-mod-name');
		$msgSizesName = wfMessage('tilesheet-sizes');
		$canEdit = in_array("edittilesheets", $this->getUser()->getRights());
		$table .= "!";
		if ($canEdit) {
			$table .= " !!";
		}
		$table .= " $msgModName !! $msgSizesName\n";
		foreach ($results as $result) {
			$lMod = $result->mod;
			$lSizes = $result->sizes;

			if ($canEdit) {
				$editLink = " style=\"width:23px; padding-left:5px; padding-right:5px; text-align:center; font-weight:bold;\" | [[Special:SheetManager/$lMod|" . $this->msg('tilesheet-edit')->text() . "]] ||";
			} else {
				$editLink = "";
			}

			$table .= "|-\n";
			$table .= "|$editLink $lMod || $lSizes\n";
		}
		$table .= "|}\n";

		// Page nav stuff
		// TODO replace with our pagination stuff
		$page = $opts->getValue('page');
		$pPage = $page-1;
		$nPage = $page+1;
		$lPage = floor($maxRows / $limit);
		if ($page == 0) {
			$prevPage = "'''" . $this->msg('tilesheet-pagination-first')->text() . "'''";
		} else {
			if ($page == 1) {
				$prevPage = "[{{fullurl:{{FULLPAGENAME}}|limit=".$opts->getValue('limit')."}} " . $this->msg('tilesheet-pagination-first-arrow')->text() . "]";
			} else {
				$prevPage = "[{{fullurl:{{FULLPAGENAME}}|limit=".$opts->getValue('limit')."}} " . $this->msg('tilesheet-pagination-first-arrow')->text() . "] [{{fullurl:{{FULLPAGENAME}}|page={$pPage}&limit=".$opts->getValue('limit')."}} " . $this->msg('tilesheet-pagination-prev')->text() ."]";
			}
		}
		if ($lPage == $page) {
			$nextPage = "'''" . $this->msg('tilesheet-pagination-last')->text() . "'''";
		} else {
			if ($lPage == $page + 1) {
				$nextPage = "[{{fullurl:{{FULLPAGENAME}}|page={$nPage}&limit=".$opts->getValue('limit')."}} " . $this->msg('tilesheet-pagination-last-arrow')->text() . "]";
			} else {
				$nextPage = "[{{fullurl:{{FULLPAGENAME}}|page={$nPage}&limit=".$opts->getValue('limit')."}} " . $this->msg('tilesheet-pagination-next')->text() . "] [{{fullurl:{{FULLPAGENAME}}|page={$lPage}&limit=".$opts->getValue('limit')."}} " . $this->msg('tilesheet-pagination-last-arrow')->text() . "]";
			}
		}
		$pageSelection = "<div style=\"text-align:center;\" class=\"plainlinks\">$prevPage | $nextPage</div>";

		// Output page
		$this->displayFilterForm($opts);
		$out->addWikiText($pageSelection);
		$out->addWikiText($table);
	}

	private function displayFilterForm(FormOptions $opts) {
	    $lang = $this->getLanguage();
	    $formDescriptor = [
            'limit' => [
                'type' => 'limitselect',
                'name' => 'limit',
                'label-message' => 'tilesheet-sheet-list-limit',
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
            ->setWrapperLegendMsg('tilesheet-sheet-list-legend')
            ->setId('ext-tilesheet-sheet-list-filter')
            ->setSubmitTextMsg('tilesheet-sheet-list-submit')
            ->setSubmitProgressive()
            ->prepareForm()
            ->displayForm(false);
	}
}
