<?php

class TilesheetsEditTileApi extends ApiBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'ts');
    }

    public function getAllowedParams() {
        return array(
            'token' => null,
            'summary' => null,
            'id' => array(
                ApiBase::PARAM_TYPE => 'integer',
                ApiBase::PARAM_REQUIRED => true,
            ),
            'toname' => array(
                ApiBase::PARAM_TYPE => 'string',
            ),
            'tomod' => array(
                ApiBase::PARAM_TYPE => 'string',
            ),
            'tox' => array(
                ApiBase::PARAM_TYPE => 'integer',
            ),
            'toy' => array(
                ApiBase::PARAM_TYPE => 'integer',
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

    public function getParamDescription() {
        return array(
            'token' => 'The edit token',
            'summary' => 'An optional edit summary',
            'id' => 'The entry ID for the tile',
            'tomod' => 'The new mod abbreviation',
            'toname' => 'The new item name',
            'tox' => 'The new X coordinate',
            'toy' => 'The new Y coordinate',
        );
    }

    public function getDescription() {
        return 'Edits a tile entry\'s data';
    }

    public function getExamples() {
        return array(
            'api.php?action=edittile&id=1&tomod=V&toname=Log&tox=1&toy=1',
        );
    }

    public function execute() {
        if (!in_array('edittilesheets', $this->getUser()->getRights())) {
            $this->dieUsage('You do not have permission to edit tiles', 'permissiondenied');
        }

        $toMod = $this->getParameter('tomod');
        $toName = $this->getParameter('toname');
        $toX = $this->getParameter('tox');
        $toY = $this->getParameter('toy');
        $id = $this->getParameter('id');
        $summary = $this->getParameter('summary');

        if (empty($toMod) && empty($toName) && empty($toX) && empty($toY)) {
            $this->dieUsage('You have to specify one of tomod, toname, tox, or toy', 'nochangeparams');
        }

        $dbr = wfGetDB(DB_SLAVE);
        $entry = $dbr->select('ext_tilesheet_items', '*', array('entry_id' => $id));

        if ($entry->numRows() == 0) {
            $this->dieUsage('That entry does not exist', 'noentry');
        }

        $row = $entry->current();

        $toMod = empty($toMod) ? $row->mod_name : $toMod;
        $toName = empty($toName) ? $row->item_name : $toName;
        $toX = empty($toX) ? $row->x : $toX;
        $toY = empty($toY) ? $row->y : $toY;

        $result = TileManager::updateTable($id, $toName, $toMod, $toX, $toY, $summary);
        if ($result != 0) {
            $error = $result == 1 ? 'That entry does not exist' : 'There was no change';
            $this->dieUsage($error, 'updatefail');
        } else {
            $this->getResult()->addValue('edit', 'edittile', array($id => true));
        }
    }
}