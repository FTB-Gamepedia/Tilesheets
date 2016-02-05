<?php

class TilesheetsQueryTilesApi extends ApiQueryBase {
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
            'mod' => array(
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_DFLT => '',
            ),
        );
    }

    public function getParamDescription() {
        return array(
            'limit' => 'The maximum number of tiles to list',
            'start' => 'The tile name to start listing at',
            'mod' => 'The mod to filter by',
        );
    }

    public function getDescription() {
        return 'Get all of the tiles filtered by mod.';
    }

    public function getExamples() {
        return array(
            'api.php?action=query&list=tiles&tslimit=50&tsmod=W',
            'api.php?action=query&list=tiles&tsstart=Cobblestone&tsmod=V',
        );
    }

    public function execute() {
        $limit = $this->getParameter('limit');
        $start = $this->getParameter('start');
        $mod = $this->getParameter('mod');
        $dbr = wfGetDB(DB_SLAVE);

        $results = $dbr->select(
            'ext_tilesheet_items',
            '*',
            array(
                "mod_name = {$dbr->addQuotes($mod)} or {$dbr->addQuotes($mod)} = ''",
                "item_name BETWEEN {$dbr->addQuotes($start)} AND 'zzzzzzzz'"
            ),
            __METHOD__,
            array('LIMIT' => $limit,)
        );

        $ret = array();

        foreach ($results as $res) {
            $ret[$res->entry_id] = array(
                'mod' => $res->mod_name,
                'name' => $res->item_name,
                'x' => intval($res->x),
                'y' => intval($res->y),
            );
        }

        $this->getResult()->addValue('query', 'tiles', $ret);
    }
}