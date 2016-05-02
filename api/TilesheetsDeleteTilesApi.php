<?php

class TilesheetsDeleteTilesApi extends ApiBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'ts');
    }

    public function getAllowedParams() {
        return array(
            'token' => null,
            'summary' => null,
            'ids' => array(
                ApiBase::PARAM_TYPE => 'integer',
                ApiBase::PARAM_REQUIRED => true,
                ApiBase::PARAM_ISMULTI => true,
                ApiBase::PARAM_MIN => 1,
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
            'ids' => 'A list of entry IDs to delete',
        );
    }

    public function getDescription() {
        return 'Deletes individual tile entries from the database';
    }

    public function getExamples() {
        return array(
            'api.php?action=deletetiles&tsids=1|2|3',
        );
    }

    public function execute() {
        if (!in_array('edittilesheets', $this->getUser()->getRights())) {
            $this->dieUsage('You do not have permission to delete tiles', 'permissiondenied');
        }

        $ids = $this->getParameter('ids');
        $summary = $this->getParameter('summary');

        $ret = array();
        foreach ($ids as $id) {
            $result = TileManager::deleteEntry($id, $this->getUser(), $summary);
            $ret[$id] = $result;
        }

        $this->getResult()->addValue('edit', 'deletetiles', $ret);
    }
}