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
                ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2,
            ),
            'from' => array(
                ApiBase::PARAM_TYPE => 'integer',
                ApiBase::PARAM_DFLT => 0,
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
            'from' => 'The tile ID to start listing at',
            'mod' => 'The mod to filter by',
        );
    }

    public function getDescription() {
        return 'Get all of the tiles filtered by mod.';
    }

    public function getExamples() {
        return array(
            'api.php?action=query&list=tiles&tslimit=50&tsmod=W',
            'api.php?action=query&list=tiles&tsfrom=15&tsmod=V',
        );
    }

    public function execute() {
        $limit = $this->getParameter('limit');
        $from = $this->getParameter('from');
        $mod = $this->getParameter('mod');
        $dbr = wfGetDB(DB_SLAVE);

        $results = $dbr->select(
            'ext_tilesheet_items',
            '*',
            array(
                "mod_name = {$dbr->addQuotes($mod)} or {$dbr->addQuotes($mod)} = ''",
                "entry_id >= $from"
            ),
            __METHOD__,
            array('LIMIT' => $limit + 1,)
        );

        $ret = array();
        $count = 0;
        foreach ($results as $res) {
            $count++;
            if ($count > $limit) {
                $this->setContinueEnumParameter('from', $res->entry_id);
                break;
            }
            $ret[] = array(
                'id' => intval($res->entry_id),
                'mod' => $res->mod_name,
                'name' => $res->item_name,
                'x' => intval($res->x),
                'y' => intval($res->y),
            );
        }

        $this->getResult()->addValue('query', 'tiles', $ret);
    }
}