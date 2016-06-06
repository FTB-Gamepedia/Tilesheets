<?php

class TilesheetsAddTileApi extends ApiBase {
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
            'name' => array(
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_REQUIRED => true,
            ),
            'x' => array(
                ApiBase::PARAM_TYPE => 'integer',
                APiBase::PARAM_REQUIRED => true,
                ApiBase::PARAM_MIN => 0,
            ),
            'y' => array(
                ApiBase::PARAM_TYPE => 'integer',
                ApiBase::PARAM_REQUIRED => true,
                ApiBase::PARAM_MIN => 0,
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
            'name' => 'The tile name',
            'mod' => 'The mod abbreviation',
            'x' => 'The X coordinate of the tile',
            'y' => 'The Y coordinate of the tile',
        );
    }

    public function getDescription() {
        return 'Adds a single tile to a given tilesheet';
    }

    public function getExamples() {
        return array(
            'api.php?action=addtile&tssummary=Forgot to add a tile&tsname=Item&tsmod=V&tsx=0&tsy=0',
        );
    }

    public function execute() {
        if (!in_array('edittilesheets', $this->getUser()->getRights())) {
            $this->dieUsage('You do not have permission to add tiles', 'permissiondenied');
        }

        $mod = $this->getParameter('mod');
        $summary = $this->getParameter('summary');
        $name = $this->getParameter('name');
        $x = $this->getParameter('x');
        $y = $this->getParameter('y');

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

        $res = TileManager::createTile($mod, $name, $x, $y, $this->getUser(), $summary);
        $return = array($name => $res);

        // Get the new tile's ID.
        if ($res) {
            $selectResult = $dbr->select(
                'ext_tilesheet_items',
                '`entry_id`',
                array('item_name' => $name, 'mod_name' => $mod, 'x' => $x, 'y' => $y),
                __METHOD__
            );
            $id = $selectResult->current()->entry_id;
            $return[$name] = $id;
        }
        $this->getResult()->addValue('edit', 'addtile', $return);
    }
}