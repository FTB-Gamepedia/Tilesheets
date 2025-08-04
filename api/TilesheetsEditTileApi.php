<?php

use Wikimedia\Rdbms\ILoadBalancer;
use MediaWiki\Permissions\PermissionManager;
use Wikimedia\ParamValidator\ParamValidator;

class TilesheetsEditTileApi extends ApiBase {
    public function __construct($query, $moduleName, private ILoadBalancer $dbLoadBalancer, private PermissionManager $permissionManager) {
        parent::__construct($query, $moduleName, 'ts');
    }

    public function getAllowedParams() {
        return array(
            'token' => null,
            'summary' => array(
            	ParamValidator::PARAM_TYPE => 'string',
            	ParamValidator::PARAM_DEFAULT => ''
            ),
            'id' => array(
                ParamValidator::PARAM_TYPE => 'integer',
                ParamValidator::PARAM_REQUIRED => true,
            ),
            'toname' => array(
                ParamValidator::PARAM_TYPE => 'string',
            ),
            'tomod' => array(
                ParamValidator::PARAM_TYPE => 'string',
            ),
            'tox' => array(
                ParamValidator::PARAM_TYPE => 'integer',
            ),
            'toy' => array(
                ParamValidator::PARAM_TYPE => 'integer',
            ),
            'toz' => array(
                ParamValidator::PARAM_TYPE => 'integer',
            )
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
            'api.php?action=edittile&id=1&tomod=V&toname=Log&tox=1&toy=1',
        );
    }

    public function execute() {
        if (!$this->permissionManager->userHasRight($this->getUser(), 'edittilesheets')) {
            $this->dieWithError('You do not have permission to edit tiles', 'permissiondenied');
        }

        $toMod = $this->getParameter('tomod');
        $toName = $this->getParameter('toname');
        $toX = $this->getParameter('tox');
        $toY = $this->getParameter('toy');
        $toZ = $this->getParameter('toz');
        $id = $this->getParameter('id');
        $summary = $this->getParameter('summary');

        if (empty($toMod) && empty($toName) && empty($toX) && empty($toY) && empty($toZ)) {
            $this->dieWithError('You have to specify one of tomod, toname, tox, toy, or toz', 'nochangeparams');
        }

        $dbr = $this->dbLoadBalancer->getConnection(DB_REPLICA);
        $entry = $dbr->select('ext_tilesheet_items', '*', array('entry_id' => $id));

        if ($entry->numRows() == 0) {
            $this->dieWithError('That entry does not exist', 'noentry');
        }

        $row = $entry->current();

        $toMod = empty($toMod) ? $row->mod_name : $toMod;
        $toName = empty($toName) ? $row->item_name : $toName;
        $toX = empty($toX) ? $row->x : $toX;
        $toY = empty($toY) ? $row->y : $toY;
        $toZ = empty($toZ) ? $row->z : $toZ;

        $result = TileManager::updateTable($id, $toName, $toMod, $toX, $toY, $toZ, $this->getUser(), $this->dbLoadBalancer, $summary);
        if ($result != 0) {
        	if (is_string($result))
        		$error = $result;
        	else
            	$error = $result == 1 ? 'That entry does not exist' : 'There was no change';
            $this->dieWithError($error, 'updatefail');
        } else {
            $this->getResult()->addValue('edit', 'edittile', array($id => true));
        }
    }
}
