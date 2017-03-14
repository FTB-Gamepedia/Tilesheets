<?php

class TilesheetsAddTilesApi extends ApiBase {
    public function __construct($query, $moduleName) {
        parent::__construct($query, $moduleName, 'ts');
    }

    public function getAllowedParams() {
        return array(
            'token' => null,
            'summary' => null,
            'mod' => array(
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_REQUIRED => true,
            ),
            'import' => array(
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_REQUIRED => true,
                ApiBase::PARAM_ISMULTI => true,
                ApiBase::PARAM_ALLOW_DUPLICATES => false,
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
            'summary' => 'An optional edit summary',
            'mod' => 'The mod abbreviation',
            'import' => 'A pipe separated list of entries. Format each entry as `X Y Item Name`.',
        );
    }

    public function getDescription() {
        return 'Adds a single tile to a given tilesheet';
    }

    public function getExamples() {
        return array(
            'api.php?action=addtiles&tssummary=Adding many tiles&tsmod=V&tsimport=0 0 Item|0 1 Other Item'
        );
    }

    public function execute() {
        if (!in_array('edittilesheets', $this->getUser()->getRights())) {
            $this->dieUsage('You do not have permission to add tiles', 'permissiondenied');
        }

        $mod = $this->getParameter('mod');
        $summary = $this->getParameter('summary');
        $import = $this->getParameter('import');

        $dbr = wfGetDB(DB_SLAVE);
        $result = $dbr->select(
            'ext_tilesheet_images',
            '`mod`',
            array('`mod`' => $mod),
            __METHOD__
        );

        if ($result->numRows() == 0) {
            $this->dieUsage("No sheet found for mod $mod", 'nosheetfound');
        }

        $return = [];
        foreach ($import as $entry) {
            list($x, $y, $item) = explode(' ', $entry, 3);
            $res = TileManager::createTile($mod, $item, $x, $y, $this->getUser(), $summary);
            // Get the new tile's ID.
            if ($res) {
                $selectResult = $dbr->select(
                    'ext_tilesheet_items',
                    '`entry_id`',
                    array('item_name' => $item, 'mod_name' => $mod, 'x' => $x, 'y' => $y),
                    __METHOD__
                );
                $id = $selectResult->current()->entry_id;
                $return[$item] = $id;
            }
        }
        $this->getResult()->addValue('edit', 'addtiles', $return);
    }
}