<?php

use MediaWiki\Html\FormOptions;
use MediaWiki\Html\Html;
use MediaWiki\Title\Title;
use Wikimedia\Rdbms\ILoadBalancer;
use MediaWiki\User\UserGroupManager;

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
	public function __construct(private ILoadBalancer $dbLoadBalancer, private UserGroupManager $groupManager) {
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

		$userGroups = $this->groupManager->getUserGroups($this->getUser());

		// Update stuff
		if ($opts->getValue('update') == 1) Tilesheets::updateSheetRow($mod, $mod, $sizes, $this->getUser(), $this->dbLoadBalancer);
		if ($opts->getValue('delete') == 1 && in_array('sysop', $userGroups)) self::deleteEntry($mod, $this->getUser(), $this->dbLoadBalancer);
		if ($opts->getValue('truncate') == 1 || $opts->getValue('delete') == 1 && in_array('sysop', $userGroups)) self::truncateTable($mod, $this->getUser(), $this->dbLoadBalancer);

		// Output update table
		$this->displayUpdateForm($mod);
	}

	/**
	 * Delete the entry provided
	 *
	 * @param string $mod
	 * @param User $user
	 * @param ILoadBalancer $dbLoadBalancer
	 * @param $comment
	 * @return  bool
	 */
	public static function deleteEntry($mod, $user, ILoadBalancer $dbLoadBalancer, $comment = "") {
		$dbw = $dbLoadBalancer->getConnection(DB_PRIMARY);
		$row = $dbw->newSelectQueryBuilder()
			->select('*')
			->from('ext_tilesheet_images')
			->where(array('`mod`' => $mod))
			->fetchRow();

		if (!$row) {
			return false;
		}

		try {
			$dbw->newDeleteQueryBuilder()
				->deleteFrom('ext_tilesheet_images')
				->where(array('`mod`' => $mod))
				->execute();
		} catch (Exception $e) {
			return false;
		}

		// Start log
		$logEntry = new ManualLogEntry('tilesheet', 'deletesheet');
		$logEntry->setPerformer($user);
		$logEntry->setTarget(Title::newFromText("Sheet/$mod", NS_SPECIAL));
		$logEntry->setComment($comment);
		$logEntry->setParameters(array("4::mod" => $mod, "5::sizes" => $row->sizes));
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
	public static function truncateTable($mod, $user, ILoadBalancer $dbLoadBalancer, $comment = "") {
		$dbw = $dbLoadBalancer->getConnection(DB_PRIMARY);
		$stuff = $dbw->newSelectQueryBuilder()
			->select('*')
			->from('ext_tilesheet_items')
			->where(array('`mod_name`' => $mod))
			->fetchResultSet();

		if ($stuff->numRows() == 0) return false;

		try {
			$dbw->newDeleteQueryBuilder()
				->deleteFrom('ext_tilesheet_items')
				->where(array('`mod_name`' => $mod))
				->execute();
		} catch (Exception $e) {
			return false;
		}

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

	public static function createSheet($mod, $sizes, $user, ILoadBalancer $dbLoadBalancer, $comment = "") {
		$dbw = $dbLoadBalancer->getConnection(DB_PRIMARY);
		// Check if already exists
		$result = $dbw->newSelectQueryBuilder()
			->select('COUNT(`mod`)')
			->from('ext_tilesheet_images')
			->where(array('`mod`' => $mod))
			->fetchField();

		// Insert into tilesheet list
		if ($result == 0) {
			try {
				$dbw->newInsertQueryBuilder()
					->insertInto('ext_tilesheet_images')
					->row(array('`mod`' => $mod, '`sizes`' => $sizes))
					->execute();
			} catch (Exception $e) {
				return false;
			}
		} else {
			return false;
		}

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
		$dbr = $this->dbLoadBalancer->getConnection(DB_REPLICA);
		$result = $dbr->newSelectQueryBuilder()
			->select('*')
			->from('ext_tilesheet_images')
			->where(array('`mod`' => $mod))
			->fetchRow();
		if (!$result) {
			return $this->msg('tilesheet-fail-norows')->text();
		} else {
			$mod = $result->mod;
			$sizes = $result->sizes;
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
