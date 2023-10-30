<?php

use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Rdbms\ILoadBalancer;
use MediaWiki\Permissions\PermissionManager;

class TilesheetsAddTilesApi extends ApiBase {
    public function __construct($query, $moduleName, private ILoadBalancer $dbLoadBalancer, private PermissionManager $permissionManager) {
        parent::__construct($query, $moduleName, 'ts');
    }

    public function getAllowedParams() {
        return array(
            'token' => null,
            'summary' => null,
            'mod' => array(
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ),
            'import' => array(
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
                ParamValidator::PARAM_ISMULTI => true,
                ParamValidator::PARAM_ALLOW_DUPLICATES => false,
            ),
        );
    }

    public function needsToken() {
        return 'csrf';
    }

    public function getTokenSalt() {
        return '';
    }

    public function mustBePosted() {
        return true;
    }

    public function isWriteMode() {
        return true;
    }

    public function getExamples() {
        return array(
            'api.php?action=addtiles&tssummary=Adding many tiles&tsmod=V&tsimport=0 0 0 Item|0 1 0 Other Item'
        );
    }

    public function execute() {
    	if (!$this->permissionManager->userHasRight($this->getUser(), 'edittilesheets')) {
            $this->dieWithError('You do not have permission to add tiles', 'permissiondenied');
        }

        $mod = $this->getParameter('mod');
        $summary = $this->getParameter('summary');
        $import = $this->getParameter('import');

        $dbr = $this->dbLoadBalancer->getConnection(DB_REPLICA);
        $result = $dbr->select(
            'ext_tilesheet_images',
            '`mod`',
            array('`mod`' => $mod),
            __METHOD__
        );

        if ($result->numRows() == 0) {
            $this->dieWithError("No sheet found for mod $mod", 'nosheetfound');
        }

        $return = [];
        foreach ($import as $entry) {
            list($x, $y, $z, $item) = explode(' ', $entry, 4);
            $res = TileManager::createTile($mod, $item, $x, $y, $z, $this->getUser(), $this->dbLoadBalancer, $summary);
            // Get the new tile's ID.
            if ($res) {
                $selectResult = $dbr->select(
                    'ext_tilesheet_items',
                    '`entry_id`',
                    array('item_name' => $item, 'mod_name' => $mod, 'x' => $x, 'y' => $y, 'z' => $z),
                    __METHOD__
                );
                $id = $selectResult->current()->entry_id;
                $return[$item] = $id;
            }
        }
        $this->getResult()->addValue('edit', 'addtiles', $return);
    }
}
