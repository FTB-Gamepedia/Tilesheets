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
	public function __construct() {
		parent::__construct('CreateTileSheet', 'importtilesheets');
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
		// Restrict access from unauthorized users
		$this->checkPermissions();

		$out = $this->getOutput();
		$out->enableOOUI();
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
			if ( !$this->getUser()->matchEditToken( $this->getRequest()->getVal( 'token' ) ) ) {
				return;
			}

			$out->addHtml('<tt>');
			$mod = $opts->getValue('mod');
			$sizes = $opts->getValue('sizes');
			// If update mode
			if ($opts->getValue('update_table') == 1) {
				// Delete sheet
				$out->addHtml($this->returnMessage(SheetManager::deleteEntry($mod, $this->getUser(), $this->msg('tilesheet-create-summary-deletesheet')->text()), $this->msg('tilesheet-create-response-msg-deletesheet')->text()));
				// Truncate table
				$out->addHtml($this->returnMessage(SheetManager::truncateTable($mod, $this->getUser(), $this->msg('tilesheet-create-summary-deletesheet')->text()), $this->msg('tilesheet-create-response-msg-truncate')->text()));
			}

			// Create sheet
			$out->addHtml($this->returnMessage(SheetManager::createSheet($mod, $sizes, $this->getUser()), $this->msg('tilesheet-create-response-msg-newsheet')->text()));

			$input = explode("\n", trim($opts->getValue('input')));
			foreach ($input as $line) {
				if (trim($line) == "") continue;
				list($x, $y, $item) = explode(" ", $line, 3);
				$item = trim($item);

				// Create tile
				$out->addHtml($this->returnMessage(TileManager::createTile($mod, $item, $x, $y, $this->getUser(), $this->msg('tilesheet-create-summary-newtile')->text()), $this->msg('tilesheet-create-response-msg-newtile')->params($item, $mod, $x, $y)->text()));
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
		global $wgArticlePath;
		$fieldset = new OOUI\FieldsetLayout([
			'label' => $this->msg('tilesheet-create-legend')->text(),
			'items' => [
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'name' => 'mod',
						'id' => 'mod'
					]),
					['label' => $this->msg('tilesheet-create-mod')->text()]
				),
				new OOUI\LabelWidget([
					'label' => $this->msg('tilesheet-create-mod-hint')->text()
				]),
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'name' => 'sizes',
						'id' => 'sizes'
					]),
					['label' => $this->msg('tilesheet-create-sizes')->text()]
				),
				new OOUI\LabelWidget([
					'label' => new OOUI\HtmlSnippet($this->msg('tilesheet-create-sizes-hint')->parse())
				]),
				new OOUI\HorizontalLayout([
					'items' => [
						new OOUI\LabelWidget([
							'label' => $this->msg('tilesheet-create-input')->text()
						])
					]
				]),
				new OOUI\TextInputWidget([
					'classes' => ['tilesheet-importer-textarea'],
					'multiline' => true,
					'rows' => 40,
					'name' => 'input'
				]),
				new OOUI\HorizontalLayout([
					'items' => [
						new OOUI\ButtonInputWidget([
							'type' => 'submit',
							'label' => $this->msg('tilesheet-create-submit')->text(),
							'flags' => ['primary', 'progressive']
						]),
						new OOUI\CheckboxInputWidget([
							'value' => '1',
							'name' => 'update_table',
							'inputId' => 'update_table'
						]),
						new OOUI\LabelWidget([
							'label' => $this->msg('tilesheet-create-update')->text()
						]),
						new OOUI\LabelWidget([
							'classes' => ['tilesheet-create-update-hint'],
							'label' => new OOUI\HtmlSnippet($this->msg('tilesheet-create-update-hint')->parse())
						])
					]
				])
			]
		]);
		$form = new OOUI\FormLayout([
			'method' => 'POST',
			'action' => str_replace('$1', 'Special:CreateTileSheet', $wgArticlePath),
			'id' => 'ext-tilesheet-create-form'
		]);
		$form->appendContent(
			$fieldset,
			new OOUI\HtmlSnippet(
				Html::hidden('title', $this->getPageTitle()->getPrefixedText()) .
				Html::hidden('token', $this->getUser()->getEditToken())
			)
		);
		return new OOUI\PanelLayout([
			'classes' => ['tilesheet-importer-wrapper'],
			'framed' => true,
			'expanded' => false,
			'padded' => true,
			'content' => $form
		]);
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
			$out = '<span style="background-color:green; font-weight:bold; color:white;">' . $this->msg('tilesheet-create-response-success')->text() . '</span> '.$message."<br>";
		} else {
			$out = '<span style="background-color:red; font-weight:bold; color:white;">' . $this->msg('tilesheet-create-response-fail')->text() . '</span> '.$message."<br>";
		}
		return $out;
	}
}
