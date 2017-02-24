<?php
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
	public function __construct() {
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

		$this->setHeaders();
		$this->outputHeader();
		$out->addModuleStyles('ext.tilesheets.special');

		$opts = new FormOptions();

		$opts->add('limit', $wgQueryPageDefaultLimit);
		$opts->add('mod', '');
		$opts->add('regex', '');
		$opts->add('page', 0);

		$opts->fetchValuesFromRequest($this->getRequest());
		$opts->validateIntBounds('limit', 0, 5000);

		// Init variables
		$mod = $opts->getValue('mod');
		$regex = $opts->getValue('regex');
		$limit = intval($opts->getValue('limit'));
		$page = intval($opts->getValue('page'));

		// Load data
		$dbr = wfGetDB(DB_SLAVE);
		try {
			$searchValue = "item_name REGEXP {$dbr->addQuotes($regex)}";
			$result = $dbr->select(
				'ext_tilesheet_items',
				'COUNT(entry_id) AS row_count',
				array(
					"mod_name = {$dbr->addQuotes($mod)} OR {$dbr->addQuotes($mod)} = ''",
					$searchValue,
				)
			);
		} catch (Exception $exception) {
			// Fallback to the following query when the regex is invalid.
			$searchValue = "item_name = {$dbr->addQuotes($regex)}";
			$result = $dbr->select(
				'ext_tilesheet_items',
				'COUNT(entry_id) AS row_count',
				array(
					"mod_name = {$dbr->addQuotes($mod)} OR {$dbr->addQuotes($mod)} = ''",
					$searchValue,
				)
			);
		}
		foreach ($result as $row) {
			$maxRows = $row->row_count;
		}

		if (!isset($maxRows)) return;

        // TODO: Specify between: `entry_id ASC`; `item_name ASC`; `entry_id DESC`; `item_name DESC`
        $order = 'entry_id ASC';
		$results = $dbr->select(
			'ext_tilesheet_items',
			'*',
			array(
				"mod_name = {$dbr->addQuotes($mod)} OR {$dbr->addQuotes($mod)} = ''",
				$searchValue,
			),
			__METHOD__,
			array(
				'ORDER BY' => $order,
				'LIMIT' => $limit,
				'OFFSET' => $page * $limit,
			)
		);

		if ($maxRows == 0) {
			$out->addHTML($this->buildForm($opts));
            // TODO: Localization
			$out->addWikiText("Query returned an empty set (i.e. zero rows).");
			return;
		}

		// Load table
		$table = "{| class=\"mw-datatable\" style=\"width:100%\"\n";
		$msgItemName = wfMessage('tilesheet-item-name');
		$msgModName = wfMessage('tilesheet-mod-name');
		$msgSizesName = wfMessage('tilesheet-sizes');
		$msgXName = wfMessage('tilesheet-x');
		$msgYName = wfMessage('tilesheet-y');
		$canEdit = in_array("edittilesheets", $this->getUser()->getRights());
		$canTranslate = in_array('translatetiles', $this->getUser()->getRights());
		$table .= "! !! !! # !!  $msgItemName !! $msgModName !! $msgXName !! $msgYName !! $msgSizesName\n";
		$linkStyle = "style=\"width:23px; padding-left:5px; padding-right: 5px; text-align:center; font-weight:bold;\"";
		foreach ($results as $result) {
			$lId = $result->entry_id;
			$lItem = $result->item_name;
			$lMod = $result->mod_name;
			$lX = $result->x;
			$lY = $result->y;
			$lSizes = Tilesheets::getModTileSizes($lMod);
			if ($lSizes == null);
			else {
				foreach ($lSizes as $key => $size) {
					$lSizes[$key] = "[[:File:Tilesheet $lMod $size.png|{$size}px]]";
				}
				$lSizes = implode(",", $lSizes);
			}

			if ($canEdit) {
			    // TODO: Localization
				$editLink = "[[Special:TileManager/$lId|Edit]]";
				$sEditLink = "[[Special:SheetManager/$lMod|$lMod]]";
			} else {
				$editLink = "";
				$sEditLink = "$lMod";
			}

			// TODO: Localization
			$translateLink = $canTranslate ? "[[Special:TileTranslator/$lId|Translate]]" : '';

			$table .= "|-\n";
			$table .= "| $linkStyle | $editLink || $linkStyle | $translateLink || $lId ||  $lItem || $sEditLink || $lX || $lY || $lSizes\n";
		}
		$table .= "|}\n";

		// Page nav stuff
		// TODO replace with our pagination stuff
		$page = $opts->getValue('page');
		$pPage = $page-1;
		$nPage = $page+1;
		$lPage = floor($maxRows / $limit);
        // TODO: Localization for all of this "<Thing> Page" stuff.
		if ($page == 0) {
			$prevPage = "'''First Page'''";
		} else {
			if ($page == 1) {
				$prevPage = "[{{fullurl:{{FULLPAGENAME}}|regex=".$opts->getValue('regex')."&mod=".$opts->getValue('mod')."&limit=".$opts->getValue('limit')."}} &laquo; First Page]";
			} else {
				$prevPage = "[{{fullurl:{{FULLPAGENAME}}|regex=".$opts->getValue('regex')."&mod=".$opts->getValue('mod')."&limit=".$opts->getValue('limit')."}} &laquo; First Page] [{{fullurl:{{FULLPAGENAME}}|page={$pPage}&mod=".$opts->getValue('mod')."&limit=".$opts->getValue('limit')."}} &lsaquo; Previous Page]";
			}
		}
		if ($lPage == $page) {
			$nextPage = "'''Last Page'''";
		} else {
			if ($lPage == $page + 1) {
				$nextPage = "[{{fullurl:{{FULLPAGENAME}}|page={$nPage}&regex=".$opts->getValue('regex')."&mod=".$opts->getValue('mod')."&limit=".$opts->getValue('limit')."}} Last Page &raquo;]";
			} else {
				$nextPage = "[{{fullurl:{{FULLPAGENAME}}|page={$nPage}&regex=".$opts->getValue('regex')."&mod=".$opts->getValue('mod')."&limit=".$opts->getValue('limit')."}} Next Page &rsaquo;] [{{fullurl:{{FULLPAGENAME}}|page={$lPage}&regex=".$opts->getValue('regex')."&mod=".$opts->getValue('mod')."&limit=".$opts->getValue('limit')."}} Last Page &raquo;]";
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
		$form .= TilesheetsForm::createFormRow('tile-list', 'regex', $opts->getValue('regex'));
		$form .= TilesheetsForm::createFormRow('tile-list', 'mod', $opts->getValue('mod'));
		$form .= '<tr><td style="text-align:right"><label for="limit">'.$this->msg('tilesheet-tile-list-limit').'</td><td><select name="limit">'.$optionTags.'</select></td></tr>';
		$form .= TilesheetsForm::createSubmitButton('tile-list');
		$form .= "</table>";

		$out = Xml::openElement('form', array('method' => 'get', 'action' => $wgScript, 'id' => 'ext-tilesheet-tile-list-filter')) .
			Xml::fieldset($this->msg('tilesheet-tile-list-legend')->text()) .
			Html::hidden('title', $this->getPageTitle()->getPrefixedText()) .
			$form .
			Xml::closeElement('fieldset') . Xml::closeElement('form') . "\n";

		return $out;
	}
}
