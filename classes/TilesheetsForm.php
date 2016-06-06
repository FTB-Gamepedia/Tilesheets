<?php
/**
 * TilesheetsForm class file
 *
 * @file
 * @ingroup Extensions
 * @version 1.1.2
 * @author Jinbobo <paullee05149745@gmail.com>, Telshin <timmrysk@gmail.com>
 * @license
 */
class TilesheetsForm {
	/**
	 * Helper function for creating form rows
	 *
	 * @param string $ext Submodule name
	 * @param string $name Field name
	 * @param string $value Default value
	 * @param string $type Input type
	 * @param string $attr Input attributes
	 * @param string $lattr Label attributes
	 * @return string
	 */
	static public function createFormRow($ext, $name, $value = "", $type = "text", $attr = "", $lattr = "") {
		$msgName = wfMessage("tilesheet-$ext-$name")->text();
		$html = "<tr><td class=\"mw-label\"><label for=\"$name\" $lattr>$msgName</td><td class=\"mw-input\"><input type=\"$type\" name=\"$name\" id=\"$name\" value=\"$value\" $attr></td></tr>";
		return $html;
	}

	/**
	 * Helper function for creating deletion checkboxes for the various managers (SheetManager, TileManager, etc).
	 * 
	 * See createFormRow for parameters descriptions.
	 * @param $ext
	 * @param $name
	 * @param int $value
	 * @param string $attr
	 * @param string $lattr
	 * @return string
	 */
	static public function createDeleteCheckboxRow($ext, $name = 'delete', $value = 1, $attr = '', $lattr = '') {
		$msg = wfMessage("tilesheet-$ext-$name")->text();
		$html = "<tr>";
		$html .= "<td class=\"mw-label\"><label for=\"$name\" $lattr><span style=\"color: red; font-weight: bold;\">$msg</span></td>";
		$html .= "<td class=\"mw-input\"><input type=\"checkbox\" name=\"$name\" id=\"$name\" value=\"$value\" $attr></td>";
		$html .= "</tr>";
		return $html;
	}

	/**
	 * Helper function for creating submit buttons
	 *
	 * @param string $ext Submodule name
	 * @param string $msg System message name
	 * @return string
	 */
	static public function createSubmitButton($ext, $msg = "submit") {
		return "<tr><td colspan=\"2\"><input type=\"submit\" value=\"".wfMessage("tilesheet-$ext-$msg")->text()."\"></td></tr>";
	}

	/**
	 * Helper function for displaying input hints
	 *
	 * @param string $ext Submodule name
	 * @param string $name Field name
	 * @param string $hint The format hint, e.g. <code>$x $y $item_name</code>.
	 * @return string
	 */
	static public function createInputHint($ext, $name, $hint = '') {
		return "<tr><td colspan=\"2\" class=\"htmlform-tip\">".wfMessage("tilesheet-$ext-$name-hint")->parse()."</td></tr>";
	}
}
