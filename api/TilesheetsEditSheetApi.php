<?php

class TilesheetsEditSheetApi extends ApiBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'ts');
    }

    public function getAllowedParams() {
        return array(
            'token' => null,
            'summary' => null,
            'mod' => array(
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_REQUIRED => true,
            ),
            'tomod' => array(
                ApiBase::PARAM_TYPE => 'string',
            ),
            'tosizes' => array(
                ApiBase::PARAM_TYPE => 'integer',
                ApiBase::PARAM_ISMULTI => true,
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
            'api.php?action=editsheet&mod=A&tomod=B&tosizes=32|64',
        );
    }

    public function execute() {
        if (!in_array('edittilesheets', $this->getUser()->getRights())) {
            $this->dieWithError('You do not have permission to edit sheets', 'permissiondenied');
        }

        $curMod = $this->getParameter('mod');
        $toMod = $this->getParameter('tomod');
        $toSizes = implode(',', $this->getParameter('tosizes'));
        $summary = $this->getParameter('summary');

        if (empty($toMod) && empty($toSizes)) {
            $this->dieWithError('You have to specify one of tomod or tosizes', 'nochangeparams');
        }

        $dbr = wfGetDB(DB_REPLICA);
        $entry = $dbr->select('ext_tilesheet_images', '*', array('`mod`' => $curMod));

        if ($entry->numRows() == 0) {
            $this->dieWithError('That entry does not exist', 'noentry');
        }

        $row = $entry->current();

        $toMod = empty($toMod) ? $row->mod : $toMod;
        $toSizes = empty($toSizes) ? $row->sizes : $toSizes;

        if ($toMod == $row->mod && $toSizes == $row->sizes) {
            $this->dieWithError('There was no change', 'nochange');
        }

        $result = Tilesheets::updateSheetRow($curMod, $toMod, $toSizes, $this->getUser(), $summary);
        if ($result) {
            $this->getResult()->addValue('edit', 'editsheet', array($curMod => $toMod, $row->sizes => $toSizes));
        } else {
            $this->dieWithError('The update errored. This does not necessarily mean that it failed, please see your logs.', 'updateerror');
        }
    }
}
