<?php

class TilesheetsDeleteSheetApi extends ApiBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'ts');
    }

    public function getAllowedParams() {
        return array(
            'mods' => array(
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_REQUIRED => true,
                ApiBase::PARAM_ALLOW_DUPLICATES => false,
                ApiBase::PARAM_ISMULTI => true,
            ),
            'summary' => null,
            'token' => null,
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
            'api.php?action=deletesheet&tsmods=A|B|C&tssummary=Because I don\'t know my ABCs.',
        );
    }

    public function execute() {
        if (!in_array('edittilesheets', $this->getUser()->getRights())) {
            $this->dieWithError('You do not have permission to edit tilesheets', 'permissiondenied');
        }

        $mods = $this->getParameter('mods');
        $summary = $this->getParameter('summary');
        $ret = array();

        foreach ($mods as $mod) {
            $ret[$mod] = SheetManager::deleteEntry($mod, $this->getUser(), $summary);
        }

        $this->getResult()->addValue('edit', 'deletesheet', $ret);
    }
}
