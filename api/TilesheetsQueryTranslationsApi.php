<?php

use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\ParamValidator\ParamValidator;

class TilesheetsQueryTranslationsApi extends ApiQueryBase {
    public function __construct($query, $moduleName, private ILoadBalancer $dbLoadBalancer) {
        parent::__construct($query, $moduleName, 'ts');
    }

    public function getAllowedParams() {
        return array(
            'id' => array(
                ParamValidator::PARAM_TYPE => 'integer',
                ParamValidator::PARAM_REQUIRED => true,
                ParamValidator::PARAM_MIN => 1,
            ),
            'lang' => array(
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_DFLT => '',
            )
        );
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

        $dbr = $this->dbLoadBalancer->getConnection(DB_REPLICA);

        $results = $dbr->select(
            'ext_tilesheet_languages',
            '*',
            array(
                'entry_id' => $id,
                "lang = {$dbr->addQuotes($lang)} OR {$dbr->addQuotes($lang)} = ''",
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