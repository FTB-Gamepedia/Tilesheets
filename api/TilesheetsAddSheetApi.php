<?php

use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Rdbms\ILoadBalancer;
use MediaWiki\Permissions\PermissionManager;

class TilesheetsAddSheetApi extends ApiBase {
    public function __construct($query, $moduleName, private ILoadBalancer $dbLoadBalancer, private PermissionManager $permissionmManager) {
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
            'sizes' => array(
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_DFLT => '16|32',
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
            'api.php?action=createsheet&tssummary=This mod rocks&tsmod=MOD&tssizes=16|32|64',
        );
    }

    public function execute() {
    	if (!$this->permissionmManager->userHasRight($this->getUser(), 'edittilesheets')) {
            $this->dieWithError('You do not have permission to create tilesheets', 'permissiondenied');
        }

        $mod = $this->getParameter('mod');
        $sizes = $this->getParameter('sizes');
        $sizes = implode(',', $sizes);
        $summary = $this->getParameter('summary');

        $result = SheetManager::createSheet($mod, $sizes, $this->getUser(), $this->dbLoadBalancer, $summary);
        $this->getResult()->addValue('edit', 'createsheet', array($mod => $result));
    }
}
