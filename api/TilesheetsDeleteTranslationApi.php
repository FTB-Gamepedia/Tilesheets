<?php

use Wikimedia\Rdbms\ILoadBalancer;
use MediaWiki\Permissions\PermissionManager;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;

class TilesheetsDeleteTranslationApi extends ApiBase {
	public function __construct($query, $moduleName, private ILoadBalancer $dbLoadBalancer, private PermissionManager $permissionManager) {
        parent::__construct($query, $moduleName, 'ts');
    }

    public function getAllowedParams() {
        return array(
            'token' => null,
            'id' => array(
                ParamValidator::PARAM_TYPE => 'integer',
                ParamValidator::PARAM_REQUIRED => true,
                IntegerDef::PARAM_MIN => 1,
            ),
            'lang' => array(
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
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
            'api.php?action=deletetranslation&tsid=6&tslang=es',
        );
    }

    public function execute() {
    	if (!$this->permissionManager->userHasRight($this->getUser(), 'edittilesheets')) {
            $this->dieWithError('You do not have permission to delete tile translations', 'permissiondenied');
        }

        $id = $this->getParameter('id');
        $lang = $this->getParameter('lang');

        $response = TileTranslator::deleteEntry($id, $lang, $this->getUser(), $this->dbLoadBalancer);
        if ($response == true) {
            $this->getResult()->addValue('edit', 'deletetranslation', array('id' => $id, 'language' => $lang));
        } else {
            $this->dieWithError('That entry does not exist', 'entrynotexist');
        }
    }
}
