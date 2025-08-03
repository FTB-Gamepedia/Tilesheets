<?php

use MediaWiki\Html\FormOptions;
use MediaWiki\Html\Html;
use MediaWiki\Title\Title;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * TileManager special page file
 *
 * @file
 * @ingroup Extensions
 * @version 1.1.1
 * @author Jinbobo <paullee05149745@gmail.com>
 * @license
 */

class TileManager extends SpecialPage {
	/**
	 * Calls parent constructor and sets special page title
	 */
	public function __construct(private ILoadBalancer $dbLoadBalancer) {
		parent::__construct('TileManager', 'edittilesheets');
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

		$opts->add( 'id', 0 );
		$opts->add( 'mod', '' );
		$opts->add( 'item', '' );
		$opts->add( 'x', 0 );
		$opts->add( 'y', 0 );
		$opts->add( 'z', 0 );
		$opts->add( 'update', 0 );
		$opts->add( 'delete', 0 );

		$opts->fetchValuesFromRequest( $this->getRequest() );

		// Give precedence to subpage syntax
		if ( isset($par)) {
			$opts->setValue( 'id', $par );
		}

		$id = $opts->getValue('id');
		$mod = $opts->getValue('mod');
		$item = htmlspecialchars_decode($opts->getValue('item'));
		$x = $opts->getValue('x');
		$y = $opts->getValue('y');
		$z = $opts->getValue('z');

		// Output filter
		$out->addHTML($this->buildSearchForm());

		if ($id == 0) return;

		// Process and save POST data
		if ($opts->getValue('delete') == 1) {
			self::deleteEntry($id, $this->getUser(), $this->dbLoadBalancer);
		} else if ($opts->getValue('update') == 1) {
			self::updateTable($id, $item, $mod, $x, $y, $z, $this->getUser(), $this->dbLoadBalancer);
		}

		// Output update form
		$this->displayUpdateForm($id);
	}

	public static function createTile($mod, $item, $x, $y, $z, $user, ILoadBalancer $dbLoadBalancer, $comment = "") {
		$dbw = $dbLoadBalancer->getConnection(DB_PRIMARY);
		// Check if position on tilesheet is already occupied
		$result = $dbw->newSelectQueryBuilder()
			->select('COUNT(`entry_id`)')
			->from('ext_tilesheet_items')
			->where(array('mod_name' => $mod, 'x' => intval($x), 'y' => intval($y), 'z' => intval($z)))
			->fetchField();
		if ($result != 0) return false;

		// Check if item is already defined
		$result = $dbw->newSelectQueryBuilder()
			->select('COUNT(`entry_id`)')
			->from('ext_tilesheet_items')
			->where(array('mod_name' => $mod, 'item_name' => $item))
			->fetchField();
		if ($result != 0) return false;

		// Insert to tilesheet list
		try {
			$dbw->newInsertQueryBuilder()
				->insertInto('ext_tilesheet_items')
				->row(array(
					'`item_name`' => $item,
					'`mod_name`' => $mod,
					'`x`' => $x,
					'`y`' => $y,
					'`z`' => $z))
				->execute();
		} catch (Exception $e) {
			return false;
		}

		$target = empty($mod) || $mod == "undefined" ? $item : "$item ($mod)";

		// Start log
		$logEntry = new ManualLogEntry('tilesheet', 'createtile');
		$logEntry->setPerformer($user);
		$logEntry->setTarget(Title::newFromText("Tile/$target", NS_SPECIAL));
		$logEntry->setComment($comment);
		$logEntry->setParameters(array("4::mod" => $mod, "5::item" => $item, "6::x" => $x, "7::y" => $y, "8::z" => $z));
		$logId = $logEntry->insert();
		$logEntry->publish($logId);
		// End log

		return true;
	}

	public static function deleteEntry($id, $user, ILoadBalancer $dbLoadBalancer, $comment = "") {
		$dbw = $dbLoadBalancer->getConnection(DB_PRIMARY);
		$stuff = $dbw->newSelectQueryBuilder()
			->select('*')
			->from('ext_tilesheet_items')
			->where(array('entry_id' => $id))
			->fetchResultSet();
		if ($stuff->numRows() == 0) return false;

		try {
			$dbw->newDeleteQueryBuilder()
				->deleteFrom('ext_tilesheet_items')
				->where(array('entry_id' => $id))
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
			$logEntry->setParameters(array("4::id" => $item->entry_id, "5::item" => $item->item_name, "6::mod" => $item->mod_name, "7::x" => $item->x, "8::y" => $item->y, "9::z" => $item->z));
			$logEntry->setComment($comment);
			$logId = $logEntry->insert();
			$logEntry->publish($logId);
			// End log
		}
		return true;
	}

	/**
	 * Updates the tilesheet table with the provided stuff.
	 *
	 * @param string|int $id
	 * @param string $item
	 * @param string $mod
	 * @param string|int $x
	 * @param string|int $y
     * @param string|int $z
	 * @param User $user
	 * @param ILoadBalancer $dbLoadBalancer
	 * @param string $comment
	 * @return int|string 0 if successful, 1 if the entry does not exist, 2 if there was no change, or string of the error message if the UPDATE query failed
	 */
	public static function updateTable($id, $item, $mod, $x, $y, $z, $user, ILoadBalancer $dbLoadBalancer, $comment = "") {
		$dbw = $dbLoadBalancer->getConnection(DB_PRIMARY);
		$stuff = $dbw->newSelectQueryBuilder()
			->select('*')
			->from('ext_tilesheet_items')
			->where(array('entry_id' => $id))
			->fetchResultSet();
		if ($stuff->numRows() == 0) return 1;

		try {
			$dbw->newUpdateQueryBuilder()
				->update('ext_tilesheet_items')
				->set(array(
					'mod_name' => $mod,
					'item_name' => $item,
					'x' => $x,
					'y' => $y,
					'z' => $z
				))
				->where(array('entry_id' => $id))
				->execute();
		} catch (Exception $e) {
			return $e->getMessage();
		}

		$fItem = $item;
		foreach ($stuff as $item) {
			$target = empty($item->mod_name) || $item->mod_name == "undefined" ? $item->item_name : "$item->item_name ($item->mod_name)";

			$diff = array();
			if ($item->item_name != $fItem) {
				$diff['item'][] = $item->item_name;
				$diff['item'][] = $fItem;
			}
			if ($item->mod_name != $mod) {
				$diff['mod'][] = $item->mod_name;
				$diff['mod'][] = $mod;
			}
			if ($item->x != $x) {
				$diff['x'][] = $item->x;
				$diff['x'][] = $x;
			}
			if ($item->y != $y) {
				$diff['y'][] = $item->y;
				$diff['y'][] = $y;
			}
			if ($item->z != $z) {
				$diff['z'][] = $item->z;
				$diff['z'][] = $z;
			}
			$diffString = "";
			foreach ($diff as $field => $change) {
				$diffString .= "$field [$change[0] -> $change[1]] ";
			}
			if ($diffString == "" || count($diff) == 0) return 2; // No change

			// Start log
			$logEntry = new ManualLogEntry('tilesheet', 'edittile');
			$logEntry->setPerformer($user);
			$logEntry->setTarget(Title::newFromText("Tile/$target", NS_SPECIAL));
			$logEntry->setComment($comment);
			$logEntry->setParameters(array("6::id" => $item->entry_id, "7::item" => $item->item_name, "8::mod" => $item->mod_name, "9::x" => $item->x, "10::y" => $item->y, "15::z" => $item->z, "11::to_item" => $fItem, "12::to_mod" => $mod, "13::to_x" => $x, "14::to_y" => $y, "16::to_z" => $z, "4::diff" => $diffString, "5::diff_json" => json_encode($diff)));
			$logId = $logEntry->insert();
			$logEntry->publish($logId);
			// End log
			return 0;
		}
		return 1;
	}

	/**
	 * Builds the filter form.
	 *
	 * @return string
	 */
	private function buildSearchForm() {
		global $wgScript;
		$fieldset = new OOUI\FieldsetLayout([
			'label' => $this->msg('tilesheet-tile-manager-filter-legend')->text(),
			'items' => [
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'type' => 'number',
						'name' => 'id',
						'value' => '',
						'min' => '1',
						'id' => 'form-entry-id',
						'icon' => 'search'
					]),
					['label' => $this->msg('tilesheet-tile-manager-filter-id')->text()]
				),
				new OOUI\ButtonInputWidget([
					'label' => $this->msg('tilesheet-tile-manager-filter-submit')->text(),
					'type' => 'submit'
				])
			]
		]);
		$form = new OOUI\FormLayout([
			'method' => 'GET',
			'action' => $wgScript,
			'id' => 'ext-tilesheet-tile-manager-filter'
		]);
		$form->appendContent(
			$fieldset,
			new OOUI\HtmlSnippet(Html::hidden('title', $this->getPageTitle()->getPrefixedText()))
		);
		return new OOUI\PanelLayout([
			'classes' => ['tile-manager-filter-wrapper'],
			'framed' => true,
			'expanded' => false,
			'padded' => true,
			'content' => $form
		]);
	}

	private function displayUpdateForm($id) {
		$dbr = $this->dbLoadBalancer->getConnection(DB_REPLICA);
		$result = $dbr->select('ext_tilesheet_items', '*', array('entry_id' => $id));
		if ($result->numRows() == 0) {
			return $this->msg('tilesheet-fail-norows')->text();
		} else {
			$item = $result->current()->item_name;
			$mod = $result->current()->mod_name;
			$x = $result->current()->x;
			$y = $result->current()->y;
			$z = $result->current()->z;
		}

		$formDescriptor = [
		    'id' => [
		        'type' => 'int',
                'name' => 'id',
                'default' => $id,
                'readonly' => true,
                'label-message' => 'tilesheet-tile-manager-id'
            ],
            'item' => [
                'type' => 'text',
                'name' => 'item',
                'default' => htmlspecialchars($item),
                'label-message' => 'tilesheet-tile-manager-item'
            ],
            'mod' => [
                'type' => 'text',
                'name' => 'mod',
                'default' => $mod,
                'label-message' => 'tilesheet-tile-manager-mod'
            ],
            'x' => [
                'type' => 'int',
                'name' => 'x',
                'default' => $x,
                'label-message' => 'tilesheet-tile-manager-x'
            ],
            'y' => [
                'type' => 'int',
                'name' => 'y',
                'default' => $y,
                'label-message' => 'tilesheet-tile-manager-y'
            ],
            'z' => [
                'type' => 'int',
                'name' => 'z',
                'default' => $z,
                'label-message' => 'tilesheet-tile-manager-z'
            ]
        ];

        $htmlForm = HTMLForm::factory('ooui', $formDescriptor, $this->getContext());
        $htmlForm
            ->addButton([
                'name' => 'delete',
                'value' => 1,
                'label-message' => 'tilesheet-tile-manager-delete',
                'flags' => ['destructive']
            ])
            ->setMethod('get')
            ->addHiddenField('update', 1)
            ->setWrapperLegendMsg('tilesheet-tile-manager-legend')
            ->setId('ext-tilesheet-tile-manager-form')
            ->setSubmitTextMsg('tilesheet-tile-manager-submit')
            ->prepareForm()
            ->displayForm(false);
	}
}
