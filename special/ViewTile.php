<?php

use Wikimedia\Rdbms\ILoadBalancer;

class ViewTile extends SpecialPage {
	public function __construct(private ILoadBalancer $dbLoadBalancer) {
		parent::__construct('ViewTile');
	}

	protected function getGroupName() {
		return 'tilesheet';
	}

	public function execute($subPage) {
		$out = $this->getOutput();
		$this->setHeaders();
		$this->outputHeader();
		$out->addModuleStyles('ext.tilesheets.special');
		$out->addModules('ext.tilesheets.viewtile');

		$dbr = $this->dbLoadBalancer->getConnection(DB_REPLICA);
		$result = $dbr->select('ext_tilesheet_items', '*', array('entry_id' => $subPage));
		if ($result->numRows() == 0) {
			$out->addWikiText($this->msg('tilesheet-fail-norows')->text());
		} else {
			$item = $result->current()->item_name;
			$mod = $result->current()->mod_name;

			$itemFromMod = wfMessage('tilesheet-tile-viewer-header', $item, $mod);
			$out->addWikiText("== $itemFromMod ==\n\n");

			$sizeText = wfMessage('tilesheet-tile-viewer-size');
			$tileText = wfMessage('tilesheet-tile-viewer-tile');

			$outText = "{| class=\"mw-datatable\"\n";
			$outText .= "! $sizeText !! $tileText\n";

			foreach (Tilesheets::getModTileSizes($mod) as $size) {
				$outText .= "|-\n| $size || ";
				$outText .= "{{#icon:item=$item|mod=$mod|size=$size}}";
				$outText .= "\n";
			}
			$outText .= "|}";

			$out->addWikiText($outText);
		}
	}
}