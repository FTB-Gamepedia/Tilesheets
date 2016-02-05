<?php

class TilesheetsAddSheetApi extends ApiBase {
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
            'sizes' => array(
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_DFLT => '16|32',
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

    public function getParamDescription() {
        return array(
            'token' => 'The edit token',
            'summary' => 'An optional edit summary',
            'mod' => 'The mod abbreviation',
            'sizes' => 'Pipe separated list of tile sizes',
        );
    }

    public function getDescription() {
        return 'Creates a new tilesheet with the given mod abbreviation and sizes';
    }

    public function getExamples() {
        return array(
            'api.php?action=createsheet&tssummary=This mod rocks&tsmod=MOD&tssizes=16|32|64',
        );
    }

    public function execute() {
        if (!in_array('edittilesheets', $this->getUser()->getRights())) {
            $this->dieUsage('You do not have permission to create tilesheets', 'permissiondenied');
        }

        $mod = $this->getParameter('mod');
        $sizes = $this->getParameter('sizes');
        $sizes = implode(',', $sizes);
        $summary = $this->getParameter('summary');

        $result = SheetManager::createSheet($mod, $sizes, $summary);
        $this->getResult()->addValue('edit', 'createsheet', array($mod => $result));
    }
}