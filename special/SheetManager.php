<?php
/**
 * SheetManager special page file
 *
 * @file
 * @ingroup Extensions
 * @version 1.1.1
 * @author Jinbobo <paullee05149745@gmail.com>
 * @license
 */

class SheetManager extends SpecialPage {
	/**
	 * Calls parent constructor and sets special page title
	 */
	public function __construct() {
		parent::__construct('SheetManager', 'edittilesheets');
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

		$opts->add( 'mod', '' );
		$opts->add( 'sizes', '' );
		$opts->add( 'delete', 0 );
		$opts->add( 'truncate', 0 );
		$opts->add( 'update', 0 );

		$opts->fetchValuesFromRequest( $this->getRequest() );

		// Give precedence to subpage syntax
		if ( isset($par)) {
			$opts->setValue( 'mod', $par );
		}

		$mod = $opts->getValue('mod');
		$sizes = $opts->getValue('sizes');

		// Output filter
		$out->addHTML($this->buildSearchForm());

		if ($mod == '') return;

		// Update stuff
		if ($opts->getValue('update') == 1) Tilesheets::updateSheetRow($mod, $mod, $sizes, $this->getUser());
		if ($opts->getValue('delete') == 1 && in_array('sysop', $this->getUser()->getGroups())) self::deleteEntry($mod, $this->getUser());
		if ($opts->getValue('truncate') == 1 || $opts->getValue('delete') == 1 && in_array('sysop', $this->getUser()->getGroups())) self::truncateTable($mod, $this->getUser());

		// Output update table
		$this->displayUpdateForm($mod);
	}

	/**
	 * Delete the entry provided
	 *
	 * @param string $mod
	 * @param $comment
	 * @param User $user
	 * @return  bool
	 */
	public static function deleteEntry($mod, $user, $comment = "") {
		$dbw = wfGetDB(DB_PRIMARY);
		$stuff = $dbw->select('ext_tilesheet_images', '*', array('`mod`' => $mod));
		$result = $dbw->delete('ext_tilesheet_images', array('`mod`' => $mod));

		if ($stuff->numRows() == 0 || $result == false) {
			return false;
		}

		// Start log
		$logEntry = new ManualLogEntry('tilesheet', 'deletesheet');
		$logEntry->setPerformer($user);
		$logEntry->setTarget(Title::newFromText("Sheet/$mod", NS_SPECIAL));
		$logEntry->setComment($comment);
		$logEntry->setParameters(array("4::mod" => $mod, "5::sizes" => $stuff->current()->sizes));
		$logId = $logEntry->insert();
		$logEntry->publish($logId);
		// End log

		return true;
	}

	/**
	 * Deletes entries belonging to a sheet
	 *
	 * @param string $mod
	 * @param $comment
	 * @param User $user
	 * @return bool
	 */
	public static function truncateTable($mod, $user, $comment = "") {
		$dbw = wfGetDB(DB_PRIMARY);
		$stuff = $dbw->select('ext_tilesheet_items', '*', array('mod_name' => $mod));
		$result = $dbw->delete('ext_tilesheet_items', array('mod_name' => $mod));

		if ($stuff->numRows() == 0) return false;
		if ($result == false) return false;

		foreach ($stuff as $item) {
			$target = empty($item->mod_name) || $item->mod_name == "undefined" ? $item->item_name : "$item->item_name ($item->mod_name)";

			// Start log
			$logEntry = new ManualLogEntry('tilesheet', 'deletetile');
			$logEntry->setPerformer($user);
			$logEntry->setTarget(Title::newFromText("Tile/$target", NS_SPECIAL));
			$logEntry->setComment($comment);
			$logEntry->setParameters(array("4::id" => $item->entry_id, "5::item" => $item->item_name, "6::mod" => $item->mod_name, "7::x" => $item->x, "8::y" => $item->y));
			$logId = $logEntry->insert();
			$logEntry->publish($logId);
			// End log
		}

		return true;
	}

	public static function createSheet($mod, $sizes, $user, $comment = "") {
		$dbw = wfGetDB(DB_PRIMARY);
		// Check if already exists
		$result = $dbw->select('ext_tilesheet_images', 'COUNT(`mod`) AS count', array('`mod`' => $mod));

		// Insert into tilesheet list
		if ($result->current()->count == 0)
			$dbw->insert('ext_tilesheet_images', array('`mod`' => $mod, '`sizes`' => $sizes));
		else
			return false;

		// Start log
		$logEntry = new ManualLogEntry('tilesheet', 'createsheet');
		$logEntry->setPerformer($user);
		$logEntry->setTarget(Title::newFromText("Sheet/$mod", NS_SPECIAL));
		$logEntry->setComment($comment);
		$logEntry->setParameters(array("4::mod" => $mod, "5::sizes" => $sizes));
		$logId = $logEntry->insert();
		$logEntry->publish($logId);
		// End log

		return true;
	}

	/**
	 * Builds the filter form.
	 *
	 * @return string
	 */
	private function buildSearchForm() {
		global $wgScript;
		$fieldset = new OOUI\FieldsetLayout([
			'label' => $this->msg('tilesheet-sheet-manager-filter-legend')->text(),
			'items' => [
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'name' => 'mod',
						'value' => '',
						'id' => 'form-entry-mod',
						'icon' => 'search'
					]),
					['label' => $this->msg('tilesheet-sheet-manager-filter-mod')->text()]
				),
				new OOUI\ButtonInputWidget([
					'label' => $this->msg('tilesheet-sheet-manager-filter-submit')->text(),
					'type' => 'submit'
				])
			]
		]);
		$form = new OOUI\FormLayout([
			'method' => 'GET',
			'action' => $wgScript,
			'id' => 'ext-tilesheet-sheet-manager-filter'
		]);
		$form->appendContent(
			$fieldset,
			new OOUI\HtmlSnippet(Html::hidden('title', $this->getPageTitle()->getPrefixedText()))
		);
		return new OOUI\PanelLayout([
			'classes' => ['sheet-manager-filter-wrapper'],
			'framed' => true,
			'expanded' => false,
			'padded' => true,
			'content' => $form
		]);
	}

	private function displayUpdateForm($mod) {
		$dbr = wfGetDB(DB_REPLICA);
		$result = $dbr->select('ext_tilesheet_images', '*', array('`mod`' => $mod));
		if ($result->numRows() == 0) {
			return $this->msg('tilesheet-fail-norows')->text();
		} else {
			$mod = $result->current()->mod;
			$sizes = $result->current()->sizes;
		}

		$formDescriptor = [
		    'mod' => [
		        'type' => 'text',
		        'name' => 'mod',
                'default' => $mod,
                'readonly' => true,
                'label-message' => 'tilesheet-sheet-manager-mod',
                'help-message' => 'tilesheet-sheet-manager-mod-hint'
            ],
            'sizes' => [
                'type' => 'text',
                'name' => 'sizes',
                'default' => $sizes,
                'label-message' => 'tilesheet-sheet-manager-sizes',
                'help-message' => 'tilesheet-sheet-manager-sizes-hint'
            ]
        ];

        $htmlForm = HTMLForm::factory('ooui', $formDescriptor, $this->getContext());
        $htmlForm
            ->addButton([
                'name' => 'delete',
                'value' => 1,
                'label-message' => 'tilesheet-sheet-manager-delete',
                'id' => 'delete',
                'flags' => ['destructive']
            ])
            ->addButton([
                'name' => 'truncate',
                'value' => 1,
                'label-message' => 'tilesheet-sheet-manager-truncate',
                'id' => 'truncate',
                'flags' => ['destructive']
            ])
            ->addHiddenField('update', 1)
            ->setMethod('get')
            ->setWrapperLegendMsg('tilesheet-sheet-manager-legend')
            ->setId('ext-tilesheet-sheet-manager')
            ->setSubmitTextMsg('tilesheet-sheet-manager-submit')
            ->prepareForm()
            ->displayForm(false);
	}
}
