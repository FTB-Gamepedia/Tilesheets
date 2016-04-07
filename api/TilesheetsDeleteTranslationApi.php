<?php

class TilesheetsDeleteTranslationApi extends ApiBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'ts');
    }

    public function getAllowedParams() {
        return array(
            'token' => null,
            'id' => array(
                ApiBase::PARAM_TYPE => 'integer',
                ApiBase::PARAM_REQUIRED => true,
                ApiBase::PARAM_MIN => 1,
            ),
            'lang' => array(
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_REQUIRED => true,
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
            'id' => 'The entry ID to delete',
            'lang' => 'The language of the entry to delete'
        );
    }

    public function getDescription() {
        return 'Deletes a translation for an entry ID.';
    }

    public function getExamples() {
        return array(
            'api.php?action=deletetranslation&tsid=6&tslang=es-ni',
        );
    }

    public function execute() {
        if (!in_array('edittilesheets', $this->getUser()->getRights())) {
            $this->dieUsage('You do not have permission to delete tile translations', 'permissiondenied');
        }

        $id = $this->getParameter('id');
        $lang = $this->getParameter('lang');

        $response = TileTranslator::deleteEntry($id, $lang, $this->getUser());
        if ($response == true) {
            $this->getResult()->addValue('edit', 'deletetranslation', array('id' => $id, 'language' => $lang));
        } else {
            $this->dieUsage('That entry does not exist', 'entrynotexist');
        }
    }
}