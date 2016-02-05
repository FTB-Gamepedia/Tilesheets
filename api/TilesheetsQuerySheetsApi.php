<?php

class TilesheetsQuerySheetsApi extends ApiQueryBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'ts');
    }

    public function getAllowedParams() {
        return array(
            'limit' => array(
                ApiBase::PARAM_DFLT => 10,
                ApiBase::PARAM_TYPE => 'limit',
                ApiBase::PARAM_MIN => 1,
                ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
                Apibase::PARAM_MAX2 => ApiBase::LIMIT_BIG2,
            ),
            'start' => array(
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_DFLT => '',
            ),
        );
    }

    public function getParamDescription() {
        return array(
            'limit' => 'The maximum number of sheets to list',
            'start' => 'The abbreviation to start listing at'
        );
    }

    public function getDescription() {
        return 'Get all of the sheets.';
    }

    public function getExamples() {
        return array(
            'api.php?action=query&list=tilesheets&tslimit=50',
            'api.php?action=query&list=tilesheets&tsstart=Q',
        );
    }

    public function execute() {
        $limit = $this->getParameter('limit');
        $start = $this->getParameter('start');
        $dbr = wfGetDB(DB_SLAVE);

        $results = $dbr->select(
            'ext_tilesheet_images',
            '*',
            array("`mod` BETWEEN {$dbr->addQuotes($start)} AND 'zzzzzzzz'"),
            __METHOD__,
            array(
                'ORDER BY' => '`mod` ASC',
                'LIMIT' => $limit,
            )
        );

        $ret = array();

        foreach ($results as $res) {
            $sizes = array_map('intval', explode(',', $res->sizes));
            array_push($ret, array('mod' => $res->mod, 'sizes' => $sizes));
        }

        $this->getResult()->addValue('query', 'tilesheets', $ret);
    }
}