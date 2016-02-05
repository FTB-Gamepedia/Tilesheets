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
		$item = $opts->getValue('item');
		$x = $opts->getValue('x');
		$y = $opts->getValue('y');

		// Output filter
		$out->addHTML($this->buildSearchForm());

		if ($id == 0) return;

		// Process and save POST data
		if ($opts->getValue('update') == 1) self::updateTable($id, $item, $mod, $x, $y);
		if ($opts->getValue('delete') == 1) self::deleteEntry($id);

		// Output update form
		$out->addHTML($this->buildUpdateForm($id));
	}

	public static function createTile($mod, $item, $x, $y, $comment = "") {
		global $wgUser;

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
			$logEntry->setPerformer($wgUser);
			$logEntry->setTarget(Title::newFromText("Tile/$target", NS_SPECIAL));
			$logEntry->setComment($comment);
			$logEntry->setParameters(array("4::mod" => $mod, "5::item" => $item, "6::x" => $x, "7::y" => $y));
			$logId = $logEntry->insert();
			$logEntry->publish($logId);
			// End log
		} else return false;

		return true;
	}

	public static function deleteEntry($id, $comment = "") {
		global $wgUser;

		$dbw = wfGetDB(DB_MASTER);
		$stuff = $dbw->select('ext_tilesheet_items', '*', array('entry_id' => $id));
		$dbw->delete('ext_tilesheet_items', array('entry_id' => $id));

		if ($stuff->numRows() == 0) return false;

		foreach ($stuff as $item) {
			$target = empty($item->mod_name) || $item->mod_name == "undefined" ? $item->item_name : "$item->item_name ($item->mod_name)";

			// Start log
			$logEntry = new ManualLogEntry('tilesheet', 'deletetile');
			$logEntry->setPerformer($wgUser);
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
	 * @param string $comment
	 * @return bool
	 */
	public static function updateTable($id, $item, $mod, $x, $y, $comment = "") {
		global $wgUser;

		$dbw = wfGetDB(DB_MASTER);
		$stuff = $dbw->select('ext_tilesheet_items', '*', array('entry_id' => $id));
		$dbw->update('ext_tilesheet_items', array('mod_name' => $mod, 'item_name' => $item, 'x' => $x, 'y' => $y), array('entry_id' => $id));if ($stuff->numRows() == 0) return;

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
			$logEntry->setPerformer($wgUser);
			$logEntry->setTarget(Title::newFromText("Tile/$target", NS_SPECIAL));
			$logEntry->getComment($comment);
			$logEntry->setParameters(array("6::id" => $item->entry_id, "7::item" => $item->item_name, "8::mod" => $item->mod_name, "9::x" => $item->x, "10::y" => $item->y, "11::to_item" => $fItem, "12::to_mod" => $mod, "13::to_x" => $x, "14::to_y" => $y, "4::diff" => $diffString, "5::diff_json" => json_encode($diff)));
			$logId = $logEntry->insert();
			$logEntry->publish($logId);
			// End log
			return 0;
		}
	}

	/**
	 * Builds the filter form.
	 *
	 * @return string
	 */
	private function buildSearchForm() {
		global $wgScript;
		$form = "<table>";
		$form .= TilesheetsForm::createFormRow('tile-manager-filter', 'id', '', 'number', 'min="1" id="form-entry-id"');
		$form .= TilesheetsForm::createSubmitButton('tile-manager-filter');
		$form .= "</table>";

		$out = Xml::openElement('form', array('method' => 'get', 'action' => $wgScript, 'id' => 'ext-tilesheet-tile-manager-filter')) .
			Xml::fieldset($this->msg('tilesheet-tile-manager-filter-legend')->text()) .
			Html::hidden('title', $this->getTitle()->getPrefixedText()) .
			$form .
			Xml::closeElement( 'fieldset' ) . Xml::closeElement( 'form' ) . "\n";

		return $out;
	}

	/**
	 * Builds the update form, preloaded with the provided entry.
	 *
	 * @param $id
	 * @return string
	 */
	private function buildUpdateForm($id) {
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->select('ext_tilesheet_items', '*', array('entry_id' => $id));
		if ($result->numRows() == 0) {
			return "Query returned an empty set (i.e. zero rows).";
		} else {
			$item = $result->current()->item_name;
			$mod = $result->current()->mod_name;
			$x = $result->current()->x;
			$y = $result->current()->y;
		}

		global $wgScript;
		$form = "<table>";
		$form .= TilesheetsForm::createFormRow('tile-manager', 'id', $id, "text", 'readonly="readonly"');
		$form .= TilesheetsForm::createFormRow('tile-manager', 'item', $item);
		$form .= TilesheetsForm::createFormRow('tile-manager', 'mod', $mod);
		$form .= TilesheetsForm::createInputHint('tile-manager', 'mod');
		$form .= TilesheetsForm::createFormRow('tile-manager', 'x', $x);
		$form .= TilesheetsForm::createFormRow('tile-manager', 'y', $y);
		$form .= TilesheetsForm::createFormRow('tile-manager', 'delete', 1, "checkbox");
		$form .= TilesheetsForm::createSubmitButton('tile-manager');
		$form .= "</table>";

		$out = Xml::openElement('form', array('method' => 'get', 'action' => $wgScript, 'id' => 'ext-tilesheet-tile-manager-form', 'class' => 'prefsection')) .
			Xml::fieldset($this->msg('tilesheet-tile-manager-legend')->text()) .
			Html::hidden('title', $this->getTitle()->getPrefixedText()) .
			Html::hidden('token', $this->getUser()->getEditToken()) .
			Html::hidden('update', 1) .
			$form .
			Xml::closeElement( 'fieldset' ) . Xml::closeElement( 'form' ) . "\n";

		return $out;
	}
}
