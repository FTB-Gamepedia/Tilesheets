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
	function __construct() {
		parent::__construct('SheetList');
	}

	/**
	 * Build special page
	 *
	 * @param null|string $par Subpage name
	 */
	function execute($par) {
		global $wgQueryPageDefaultLimit, $wgUser;
		$out = $this->getOutput();

		$this->setHeaders();
		$this->outputHeader();
		$out->addModuleStyles('ext.tilesheets.special');

		$opts = new FormOptions();

		$opts->add( 'limit', $wgQueryPageDefaultLimit );
		$opts->add( 'start', '' );
		$opts->add( 'page', 0 );

		$opts->fetchValuesFromRequest( $this->getRequest() );
		$opts->validateIntBounds( 'limit', 0, 5000 );

		// Init variables
		$start = $opts->getValue('start');
		$limit = intval($opts->getValue('limit'));
		$page = intval($opts->getValue('page'));

		// Load data
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->select(
			'ext_tilesheet_images',
			'COUNT(`mod`) AS row_count',
			array("`mod` BETWEEN {$dbr->addQuotes($start)} AND 'zzzzzzzz'")
		);
		foreach ($result as $row) {
			$maxRows = $row->row_count;
		}

		if (!isset($maxRows)) return;

		$results = $dbr->select(
			'ext_tilesheet_images',
			'*',
			array("`mod` BETWEEN {$dbr->addQuotes($start)} AND 'zzzzzzzz'"),
			__METHOD__,
			array(
				'ORDER BY' => '`mod` ASC',
				'LIMIT' => $limit,
				'OFFSET' => $page * $limit
			)
		);

		if ($maxRows == 0) {
			$out->addHTML($this->buildForm($opts));
			$out->addWikiText("Query returned an empty set (i.e. zero rows).");
			return;
		}

		// Load table
		$table = "{| class=\"mw-datatable\" style=\"width:100%\"\n";
		$msgModName = wfMessage('tilesheet-mod-name');
		$msgSizesName = wfMessage('tilesheet-sizes');
		$canEdit = in_array("edittilesheets", $wgUser->getRights());
		$table .= "! !! $msgModName !! $msgSizesName\n";
		foreach ($results as $result) {
			$lMod = $result->mod;
			$lSizes = $result->sizes;

			if ($canEdit) {
				$editLink = "[[Special:SheetManager/$lMod|Edit]]";
			} else {
				$editLink = "";
			}

			$table .= "|-\n";
			$table .= "| style=\"width:23px; padding-left:5px; padding-right:5px; text-align:center; font-weight:bold;\" | $editLink || $lMod || $lSizes\n";
		}
		$table .= "|}\n";

		// Page nav stuff
		// TODO replace with our pagination stuff
		$page = $opts->getValue('page');
		$pPage = $page-1;
		$nPage = $page+1;
		$lPage = floor($maxRows / $limit);
		if ($page == 0) {
			$prevPage = "'''First Page'''";
		} else {
			if ($page == 1) {
				$prevPage = "[{{fullurl:{{FULLPAGENAME}}|start=".$opts->getValue('start')."&limit=".$opts->getValue('limit')."}} &laquo; First Page]";
			} else {
				$prevPage = "[{{fullurl:{{FULLPAGENAME}}|start=".$opts->getValue('start')."&limit=".$opts->getValue('limit')."}} &laquo; First Page] [{{fullurl:{{FULLPAGENAME}}|page={$pPage}&start=".$opts->getValue('start')."&limit=".$opts->getValue('limit')."}} &lsaquo; Previous Page]";
			}
		}
		if ($lPage == $page) {
			$nextPage = "'''Last Page'''";
		} else {
			if ($lPage == $page + 1) {
				$nextPage = "[{{fullurl:{{FULLPAGENAME}}|page={$nPage}&start=".$opts->getValue('start')."&limit=".$opts->getValue('limit')."}} Last Page &raquo;]";
			} else {
				$nextPage = "[{{fullurl:{{FULLPAGENAME}}|page={$nPage}&start=".$opts->getValue('start')."&limit=".$opts->getValue('limit')."}} Next Page &rsaquo;] [{{fullurl:{{FULLPAGENAME}}|page={$lPage}&start=".$opts->getValue('start')."&limit=".$opts->getValue('limit')."}} Last Page &raquo;]";
			}
		}
		$pageSelection = "<div style=\"text-align:center;\" class=\"plainlinks\">$prevPage | $nextPage</div>";

		// Output page
		$out->addHTML($this->buildForm($opts));
		$out->addWikiText($pageSelection);
		$out->addWikiText($table);
	}

	/**
	 * Build filter form
	 *
	 * @param FormOptions $opts Input parameters
	 * @return string
	 */
	private function buildForm(FormOptions $opts) {
		global $wgScript;
		$optionTags = "";
		foreach ([20,50,100,250,500,5000] as $lim) {
			if ($opts->getValue('limit') == $lim) {
				$optionTags .= "<option selected=\"\" value=\"$lim\">$lim</option>";
			} else {
				$optionTags .= "<option value=\"$lim\">$lim</option>";
			}
		}

		$form = "<table>";
		$form .= TilesheetsForm::createFormRow('sheet-list', 'start', $opts->getValue('start'));
		$form .= '<tr><td style="text-align:right"><label for="limit">'.$this->msg('tilesheet-sheet-list-limit').'</td><td><select name="limit">'.$optionTags.'</select></td></tr>';
		$form .= TilesheetsForm::createSubmitButton('sheet-list');
		$form .= "</table>";

		$out = Xml::openElement('form', array('method' => 'get', 'action' => $wgScript, 'id' => 'ext-tilesheet-sheet-list-filter', 'class' => 'prefsection')) .
			Xml::fieldset($this->msg('tilesheet-sheet-list-legend')->text()) .
			Html::hidden('title', $this->getTitle()->getPrefixedText()) .
			$form .
			Xml::closeElement( 'fieldset' ) . Xml::closeElement( 'form' ) . "\n";

		return $out;
	}
}
