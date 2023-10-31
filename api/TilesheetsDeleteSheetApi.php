<?php

use Wikimedia\Rdbms\ILoadBalancer;
use MediaWiki\Permissions\PermissionManager;
use Wikimedia\ParamValidator\ParamValidator;

class TilesheetsDeleteSheetApi extends ApiBase {
	public function __construct($query, $moduleName, private ILoadBalancer $dbLoadBalancer, private PermissionManager $permissionManager) {
        parent::__construct($query, $moduleName, 'ts');
    }

    public function getAllowedParams() {
        return array(
            'mods' => array(
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
                ParamValidator::PARAM_ALLOW_DUPLICATES => false,
                ParamValidator::PARAM_ISMULTI => true,
            ),
            'summary' => array(
            	ParamValidator::PARAM_TYPE => 'string',
            	ParamValidator::PARAM_DEFAULT => ''
            ),
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
    	if (!$this->permissionManager->userHasRight($this->getUser(), 'edittilesheets')) {
            $this->dieWithError('You do not have permission to edit tilesheets', 'permissiondenied');
        }

        $mods = $this->getParameter('mods');
        $summary = $this->getParameter('summary');
        $ret = array();

        foreach ($mods as $mod) {
        	$ret[$mod] = SheetManager::deleteEntry($mod, $this->getUser(), $this->dbLoadBalancer, $summary);
        }

        $this->getResult()->addValue('edit', 'deletesheet', $ret);
    }
}
