<?php
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
	public function __construct() {
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

		// Output filter
		$out->addHTML($this->buildSearchForm());

		if ($id == 0) return;

		// Process and save POST data
		if ($opts->getValue('delete') == 1) {
			self::deleteEntry($id, $this->getUser());
		} else if ($opts->getValue('update') == 1) {
			self::updateTable($id, $item, $mod, $x, $y, $this->getUser());
		}

		// Output update form
		$out->addHTML($this->buildUpdateForm($id));
	}

	public static function createTile($mod, $item, $x, $y, $user, $comment = "") {
		$dbw = wfGetDB(DB_MASTER);
		// Check if position on tilesheet is already occupied
		$result = $dbw->select('ext_tilesheet_items', 'COUNT(`entry_id`) AS count', array('`mod_name`' => $mod, '`x`' => intval($x), '`y`' => intval($y)));
		if ($result->current()->count != 0) return false;

		// Check if item is already defined
		$result = $dbw->select('ext_tilesheet_items', 'COUNT(`entry_id`) AS count', array('`mod_name`' => $mod, '`item_name`' => $item));
		if ($result->current()->count != 0) return false;

		// Insert to tilesheet list
		$result = $dbw->insert('ext_tilesheet_items', array(
			'`item_name`' => $item,
			'`mod_name`' => $mod,
			'`x`' => $x,
			'`y`' => $y));

		if ($result != false) {
			$target = empty($mod) || $mod == "undefined" ? $item : "$item ($mod)";

			// Start log
			$logEntry = new ManualLogEntry('tilesheet', 'createtile');
			$logEntry->setPerformer($user);
			$logEntry->setTarget(Title::newFromText("Tile/$target", NS_SPECIAL));
			$logEntry->setComment($comment);
			$logEntry->setParameters(array("4::mod" => $mod, "5::item" => $item, "6::x" => $x, "7::y" => $y));
			$logId = $logEntry->insert();
			$logEntry->publish($logId);
			// End log
		} else return false;

		return true;
	}

	public static function deleteEntry($id, $user, $comment = "") {
		$dbw = wfGetDB(DB_MASTER);
		$stuff = $dbw->select('ext_tilesheet_items', '*', array('entry_id' => $id));
		$dbw->delete('ext_tilesheet_items', array('entry_id' => $id));

		if ($stuff->numRows() == 0) return false;

		foreach ($stuff as $item) {
			$target = empty($item->mod_name) || $item->mod_name == "undefined" ? $item->item_name : "$item->item_name ($item->mod_name)";

			// Start log
			$logEntry = new ManualLogEntry('tilesheet', 'deletetile');
			$logEntry->setPerformer($user);
			$logEntry->setTarget(Title::newFromText("Tile/$target", NS_SPECIAL));
			$logEntry->setParameters(array("4::id" => $item->entry_id, "5::item" => $item->item_name, "6::mod" => $item->mod_name, "7::x" => $item->x, "8::y" => $item->y));
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
	 * @param User $user
	 * @param string $comment
	 * @return bool
	 */
	public static function updateTable($id, $item, $mod, $x, $y, $user, $comment = "") {
		$dbw = wfGetDB(DB_MASTER);
		$stuff = $dbw->select('ext_tilesheet_items', '*', array('entry_id' => $id));
		$dbw->update('ext_tilesheet_items', array('mod_name' => $mod, 'item_name' => $item, 'x' => $x, 'y' => $y), array('entry_id' => $id));

		if ($stuff->numRows() == 0) return 1;

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
			$logEntry->setParameters(array("6::id" => $item->entry_id, "7::item" => $item->item_name, "8::mod" => $item->mod_name, "9::x" => $item->x, "10::y" => $item->y, "11::to_item" => $fItem, "12::to_mod" => $mod, "13::to_x" => $x, "14::to_y" => $y, "4::diff" => $diffString, "5::diff_json" => json_encode($diff)));
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

	/**
	 * Builds the update form, preloaded with the provided entry.
	 *
	 * @param $id
	 * @return string
	 */
	private function buildUpdateForm($id) {
		global $wgScript;
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->select('ext_tilesheet_items', '*', array('entry_id' => $id));
		if ($result->numRows() == 0) {
			return $this->msg('tilesheet-fail-norows')->text();
		} else {
			$item = $result->current()->item_name;
			$mod = $result->current()->mod_name;
			$x = $result->current()->x;
			$y = $result->current()->y;
		}

		$fieldset = new OOUI\FieldsetLayout([
			'label' => $this->msg('tilesheet-tile-manager-legend')->text(),
			'items' => [
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'type' => 'number',
						'name' => 'id',
						'value' => $id,
						'readOnly' => true
					]),
					['label' => $this->msg('tilesheet-tile-manager-id')->text()]
				),
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'name' => 'item',
						'value' => htmlspecialchars($item)
					]),
					['label' => $this->msg('tilesheet-tile-manager-item')->text()]
				),
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'name' => 'mod',
						'value' => $mod
					]),
					['label' => $this->msg('tilesheet-tile-manager-mod')->text()]
				),
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'name' => 'x',
						'value' => $x,
						'type' => 'number'
					]),
					['label' => $this->msg('tilesheet-tile-manager-x')->text()]
				),
				new OOUI\FieldLayout(
					new OOUI\TextInputWidget([
						'name' => 'y',
						'value' => $y,
						'type' => 'number'
					]),
					['label' => $this->msg('tilesheet-tile-manager-y')->text()]
				),
				new OOUI\HorizontalLayout([
					'items' => [
						new OOUI\ButtonInputWidget([
							'type' => 'submit',
							'label' => $this->msg('tilesheet-tile-manager-submit')->text(),
							'flags' => ['primary', 'progressive'],
							'name' => 'update',
							'value' => 1
						]),
						new OOUI\ButtonInputWidget([
							'type' => 'submit',
							'label' => $this->msg('tilesheet-tile-manager-delete')->text(),
							'flags' => ['destructive'],
							'icon' => 'remove',
							'name' => 'delete',
							'value' => 1
						])
					]
				])
			]
		]);
		$form = new OOUI\FormLayout([
			'method' => 'GET',
			'action' => $wgScript,
			'id' => 'ext-tilesheet-tile-manager-form'
		]);
		$form->appendContent(
			$fieldset,
			new OOUI\HtmlSnippet(
				Html::hidden('title', $this->getPageTitle()->getPrefixedText()) .
				Html::hidden('token', $this->getUser()->getEditToken())
			)
		);

		return new OOUI\PanelLayout([
			'classes' => ['tile-manager-wrapper'],
			'framed' => true,
			'expanded' => false,
			'padded' => true,
			'content' => $form
		]);
	}
}
