<?php
/**
 * CreateTileSheet special page file
 *
 * @file
 * @ingroup Extensions
 * @version 1.1.1
 * @author Jinbobo <paullee05149745@gmail.com>
 * @license
 */

class CreateTileSheet extends SpecialPage {
	/**
	 * Calls parent constructor and sets special page title
	 */
	function __construct() {
		parent::__construct('CreateTileSheet', 'importtilesheets');
	}

	/**
	 * Build special page
	 *
	 * @param null|string $par Subpage name
	 */
	function execute($par) {
		global $wgUser;
		// Restrict access from unauthorized users
		if (!$this->userCanExecute($this->getUser())) {
			$this->displayRestrictionError();
			return;
		}

		$out = $this->getOutput();
		$out->addModuleStyles('ext.tilesheets.special');

		$this->setHeaders();
		$this->outputHeader();

		$opts = new FormOptions();

		$opts->add( 'mod', 0 );
		$opts->add( 'sizes', 0 );
		$opts->add( 'input', 0 );
		$opts->add( 'update_table', 0 );

		$opts->fetchValuesFromRequest( $this->getRequest() );

		$opts->setValue('mod', $this->getRequest()->getText('mod'));
		$opts->setValue('sizes', $this->getRequest()->getText('sizes'));
		$opts->setValue('input', $this->getRequest()->getText('input'));
		$opts->setValue('update_table', intval($this->getRequest()->getText('update_table')));

		// Process and save POST data
		if ($_POST) {
			// XSRF prevention
			if ( !$wgUser->matchEditToken( $this->getRequest()->getVal( 'token' ) ) ) {
				return;
			}

			$out->addHtml('<tt>');
			$dbw = wfGetDB(DB_MASTER);

			$mod = $opts->getValue('mod');
			$sizes = $opts->getValue('sizes');
			// If update mode
			if ($opts->getValue('update_table') == 1) {
				// Delete sheet
				$out->addHtml($this->returnMessage(SheetManager::deleteEntry($mod, "Updating tilesheet through import tool."), "Removing existing tilesheet..."));
				// Truncate table
				$out->addHtml($this->returnMessage(SheetManager::truncateTable($mod, "Updating tilesheet through import tool."), "Removing existing tilesheet entries..."));
			}

			// Create sheet
			$out->addHtml($this->returnMessage(SheetManager::createSheet($mod, $sizes), "Adding new tilesheet..."));

			$input = explode("\n", trim($opts->getValue('input')));
			foreach ($input as $line) {
				if (trim($line) == "") continue;
				list($x, $y, $item) = explode(" ", $line, 3);
				$item = trim($item);

				// Create tile
				$out->addHtml($this->returnMessage(TileManager::createTile($mod, $item, $x, $y, "Importing tilesheet."), "Adding $item from $mod on [Tilesheet $mod %.png] ($x,$y) to entry list..."));
			}
		$out->addHtml('</tt>');
		} else {
			$out->addHtml($this->buildForm());
		}

	}

	/**
	 * Build the tilesheet creation form
	 *
	 * @return string
	 */
	private function buildForm() {
		global $wgArticlePath, $wgUser;
		$form = "<table>";
		$form .= TilesheetsForm::createFormRow('create', 'mod');
		$form .= TilesheetsForm::createInputHint('create', 'mod');
		$form .= TilesheetsForm::createFormRow('create', 'sizes');
		$form .= TilesheetsForm::createInputHint('create', 'sizes');
		$form .= "<tr><td class='mw-label'>".$this->msg('tilesheet-create-input')->text()."</td><td></td></td>";
		$form .= TilesheetsForm::createInputHint('create', 'input');
		$form .= "<tr><td colspan=\"2\"><textarea name=\"input\" style=\"width:100%; height: 600px;\"></textarea></td></td>";
		$form .= "<tr><td colspan=\"2\"><input type=\"submit\" value=\"".$this->msg("tilesheet-create-submit")->text()."\"><input type=\"checkbox\" value=\"1\" name=\"update_table\" id=\"update_table\"><label for=\"update_table\">".$this->msg("tilesheet-create-update")->text()."</label><span style=\"font-size: x-small;padding: .2em .5em;color: #666;\">".$this->msg("tilesheet-create-update-hint")->parse()."</span></td></tr>";
		$form .= "</table>";

		$out = Xml::openElement('form', array('method' => 'post', 'action' => str_replace('$1', 'Special:CreateTileSheet', $wgArticlePath), 'id' => 'ext-tilesheet-create-form', 'class' => 'prefsection')) .
			Xml::fieldset($this->msg('tilesheet-create-legend')->text()) .
			Html::hidden('title', $this->getTitle()->getPrefixedText()) .
			Html::hidden( 'token', $wgUser->getEditToken() ) .
			$form .
			Xml::closeElement( 'fieldset' ) . Xml::closeElement( 'form' ) . "\n";

		return $out;
	}

	/**
	 * Helper function for displaying results
	 *
	 * @param bool $state Return value an action
	 * @param string $message Message to display
	 * @return string
	 */
	private function returnMessage($state, $message) {
		if ($state) {
			$out = '<span style="background-color:green; font-weight:bold; color:white;">SUCCESS</span> '.$message."<br>";
		} else {
			$out = '<span style="background-color:red; font-weight:bold; color:white;">FAIL</span> '.$message."<br>";
		}
		return $out;
	}
}
