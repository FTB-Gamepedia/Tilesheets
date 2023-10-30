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
			if ( !$this->getUser()->matchEditToken( $this->getRequest()->getVal( 'wpEditToken' ) ) ) {
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
				list($x, $y, $z, $item) = explode(" ", $line, 4);
				$item = trim($item);

				// Create tile
				$out->addHtml($this->returnMessage(TileManager::createTile($mod, $item, $x, $y, $z, $this->getUser(), $this->msg('tilesheet-create-summary-newtile')->text()), $this->msg('tilesheet-create-response-msg-newtile')->params($item, $mod, $x, $y, $z)->text()));
			}
		$out->addHtml('</tt>');
		} else {
			$this->displayForm();
		}

	}

	private function displayForm() {
	    global $wgArticlePath;

        $formDescriptor = [
            'mod' => [
                'type' => 'text',
                'name' => 'mod',
                'label-message' => 'tilesheet-create-mod',
                'id' => 'mod',
                'help-message' => 'tilesheet-create-mod-hint'
            ],
            'sizes' => [
                'type' => 'text',
                'name' => 'sizes',
                'label-message' => 'tilesheet-create-sizes',
                'id' => 'sizes',
                'help-message' => 'tilesheet-create-sizes-hint'
            ],
            'input' => [
                'type' => 'textarea',
                'name' => 'input',
                'rows' => 40,
                'label-message' => 'tilesheet-create-input',
                'cssclass' => 'tilesheet-importer-textarea',
                'help-message' => 'tilesheet-create-input-hint'
            ],
            'update' => [
                'type' => 'check',
                'name' => 'update_table',
                'default' => 0,
                'label-message' => 'tilesheet-create-update',
                'id' => 'update_table',
                'help-message' => 'tilesheet-create-update-hint',
                'csshelpclass' => 'tilesheet-create-update-hint'
            ]
        ];

        $htmlForm = HTMLForm::factory('ooui', $formDescriptor, $this->getContext());
        $htmlForm
            ->setMethod('post')
            ->setAction(str_replace('$1', 'Special:CreateTileSheet', $wgArticlePath))
            ->setWrapperLegendMsg('tilesheet-create-legend')
            ->setId('ext-tilesheet-create-form')
            ->setSubmitTextMsg('tilesheet-create-submit')
            ->prepareForm()
            ->displayForm(false);
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
