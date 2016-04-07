<?php

class TilesheetsQueryTranslationsApi extends ApiQueryBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'ts');
    }

    public function getAllowedParams() {
        return array(
            'id' => array(
                ApiBase::PARAM_TYPE => 'integer',
                ApiBase::PARAM_REQUIRED => true,
                ApiBase::PARAM_MIN => 1,
            ),
            'lang' => array(
                ApiBase::PARAM_TYPE => 'string',
            ),
        );
    }

    public function getParamDescription() {
        return array(
            'id' => 'The entry ID to get translations for',
            'lang' => 'The language to get translations for',
        );
    }

    public function getDescription() {
        return 'Get all of the translations for the given entry ID and/or language.';
    }

    public function getExamples() {
        return array(
            'api.php?action=query&list=tiletranslations&tsid=6',
            'api.php?action=query&list=tiletranslations&tslang=es',
            'api.php?action=query&list=tiletranslations&tsid=6&tslang=es',
        );
    }

    public function execute() {
        $id = $this->getParameter('id');
        $lang = $this->getParameter('lang');

        $dbr = wfGetDB(DB_SLAVE);

        $results = $dbr->select(
            'ext_tilesheet_languages',
            array('display_name', 'description'),
            array(
                'entry_id' => $id,
                'lang' => $lang,
            )
        );

        $ret = array();

        foreach ($results as $res) {
            $ret[] = array(
                'entry_id' => $res->entry_id,
                'description' => $res->description,
                'display_name' => $res->display_name,
                'language' => $res->lang,
            );
        }

        $this->getResult()->addValue('query', 'tiles', $ret);
    }
}