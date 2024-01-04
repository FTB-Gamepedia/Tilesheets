<?php

use Wikimedia\Rdbms\ILoadBalancer;
use MediaWiki\Permissions\PermissionManager;
use Wikimedia\ParamValidator\ParamValidator;

class TilesheetsEditSheetApi extends ApiBase {
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
            'mod' => array(
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ),
            'tomod' => array(
                ParamValidator::PARAM_TYPE => 'string',
            ),
            'tosizes' => array(
                ParamValidator::PARAM_TYPE => 'integer',
                ParamValidator::PARAM_ISMULTI => true,
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
        if (!$this->permissionManager->userHasRight($this->getUser(), 'edittilesheets')) {
            $this->dieWithError('You do not have permission to edit sheets', 'permissiondenied');
        }

        $curMod = $this->getParameter('mod');
        $toMod = $this->getParameter('tomod');
        $toSizes = implode(',', $this->getParameter('tosizes'));
        $summary = $this->getParameter('summary');

        if (empty($toMod) && empty($toSizes)) {
            $this->dieWithError('You have to specify one of tomod or tosizes', 'nochangeparams');
        }

        $dbr = $this->dbLoadBalancer->getConnection(DB_REPLICA);
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

        $result = Tilesheets::updateSheetRow($curMod, $toMod, $toSizes, $this->getUser(), $this->dbLoadBalancer, $summary);
        if ($result) {
            $this->getResult()->addValue('edit', 'editsheet', array($curMod => $toMod, $row->sizes => $toSizes));
        } else {
            $this->dieWithError('The update errored. This does not necessarily mean that it failed, please see your logs.', 'updateerror');
        }
    }
}
