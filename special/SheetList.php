<?php
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
	public function __construct() {
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
		$dbr = wfGetDB(DB_SLAVE);
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
			$out->addHTML($this->buildForm($opts));
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
		$out->addHTML($this->buildForm($opts));
		$out->addWikiText($pageSelection);
		$out->addWikiText($table);
	}

	const SIZES = [
		['data' => 20],
		['data' => 50],
		['data' => 100],
		['data' => 250],
		['data' => 500],
		['data' => 5000]
	];

	/**
	 * Build filter form
	 *
	 * @param FormOptions $opts Input parameters
	 * @return string
	 */
	private function buildForm(FormOptions $opts) {
		global $wgScript;
		$fieldset = new OOUI\FieldsetLayout([
			'label' => $this->msg('tilesheet-sheet-list-legend')->text(),
			'items' => [
				new OOUI\FieldLayout(
					new OOUI\DropdownInputWidget([
						'options' => self::SIZES,
						'value' => $opts->getValue('limit'),
						'name' => 'limit'
					]),
					['label' => $this->msg('tilesheet-sheet-list-limit')->text()]
				),
				new OOUI\ButtonInputWidget([
					'type' => 'submit',
					'label' => $this->msg('tilesheet-sheet-list-submit')->text(),
					'flags' => ['primary', 'progressive']
				])
			]
		]);
		$form = new OOUI\FormLayout([
			'method' => 'GET',
			'action' => $wgScript,
			'id' => 'ext-tilesheet-sheet-list-filter'
		]);
		$form->appendContent(
			$fieldset,
			new OOUI\HtmlSnippet(Html::hidden('title', $this->getPageTitle()->getPrefixedText()))
		);
		return new OOUI\PanelLayout([
			'classes' => ['tilesheet-sheet-list-filter-wrapper'],
			'framed' => true,
			'expanded' => false,
			'padded' => true,
			'content' => $form
		]);
	}
}
