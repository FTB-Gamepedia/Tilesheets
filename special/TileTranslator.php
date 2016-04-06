<?php

/**
 * TileTranslator special page
 */
class TileTranslator extends SpecialPage {
    /**
     * Calls parent constructor and sets special page title
     */
    public function __construct() {
        parent::__construct('TileTranslator', 'edittilesheets');
    }

    /**
     * Return the group name for this special page.
     *
     * @access	protected
     * @return	string
     */
    protected function getGroupName() {
        return 'tilesheet';
    }

    /**
     * Build special page
     *
     * @param null|string $par Subpage name
     */
    public function execute($par) {
        $this->checkPermissions();

        $out = $this->getOutput();
        $out->addModuleStyles('ext.tilesheets.special');

        $this->setHeaders();
        $this->outputHeader();

        $opts = new FormOptions();

        $opts->add('id', 0);
        $opts->add('description', '');
        $opts->add('display_name', '');
        $opts->add('language', 'en');
        $opts->add('update', 0);
        $opts->add('delete', 0);

        $opts->fetchValuesFromRequest($this->getRequest());

        // Give precedence to subpage syntax
        if (isset($par)) {
            $opts->setValue('id', $par);
        }

        $id = $opts->getValue('id');
        $description = $opts->getValue('description');
        $displayName = $opts->getValue('display_name');
        $language = $opts->getValue('language');
        $update = $opts->getValue('update');
        $delete = $opts->getValue('delete');

        $out->addHTML($this->buildSearchForm($id, $language));

        if ($id == 0) {
            return;
        }

        // Process and save POST data
        if ($delete == 1) {
            if ($language == 'en') {
                $out->addHTML(wfMessage('tilesheet-translatedelete-warning'));
            } else {
                $this->deleteEntry($id, $language, $this->getUser());
            }
        } else if ($update == 1) {
            self::updateTable($id, $displayName, $description, $language, $this->getUser());
        }

        // Output update form
        $out->addHTML($this->buildUpdateForm($id, $language));
    }

    public static function updateTable($id, $displayName, $description, $language, $user, $comment = '') {
        $dbw = wfGetDB(DB_MASTER);
        $byID = $dbw->select('ext_tilesheet_languages', '*', array('entry_id'));
        if ($byID->numRows() == 0 || empty($language)) {
            return 1;
        }
        $stuff = $dbw->select('ext_tilesheet_languages', '*', array('entry_id' => $id, 'lang' => $language));
        if ($stuff->numRows() == 0) {
            $dbw->insert(
                'ext_tilesheet_languages',
                array(
                    'entry_id' => $id,
                    'display_name' => $displayName,
                    'description' => $description,
                    'lang' => $language
                )
            );
        } else {
            $dbw->update(
                'ext_tilesheet_languages',
                array(
                    "display_name" => $displayName,
                    "description" => $description,
                ),
                array(
                    'entry_id' => $id,
                    'lang' => $language,
                )
            );
        }

        $logEntry = new ManualLogEntry('tilesheet', 'translatetile');
        $logEntry->setPerformer($user);
        $logEntry->setComment($comment);
        $logEntry->setTarget(Title::newFromText("Tile/$id", NS_SPECIAL));
        $logEntry->setParameters(array(
            '4::id' => $id,
            '5::lang' => $language,
            '6::name' => $displayName,
            '7::desc' => $description
        ));
        $logID = $logEntry->insert();
        $logEntry->publish($logID);
        return 0;
    }

    public static function deleteEntry($id, $language, $user, $comment = "") {
        $dbw = wfGetDB(DB_MASTER);
        $stuff = $dbw->select('ext_tilesheet_languages', '*', array('entry_id' => $id, 'lang' => $language));
        if ($stuff->numRows() == 0) {
            return false;
        }
        $dbw->delete('ext_tilesheet_languages', array('entry_id' => $id, 'lang' => $language));
        $logEntry = new ManualLogEntry('tilesheet', 'deletetranslation');
        $logEntry->setPerformer($user);
        $logEntry->setComment($comment);
        $logEntry->setTarget(Title::newFromText("Tile/$id", NS_SPECIAL));
        $logEntry->setParameters(array('4::id' => $id, '5::lang' => $language));
        $logID = $logEntry->insert();
        $logEntry->publish($logID);
        return true;
    }

    /**
     * Builds the filter form.
     *
     * @param string $id The default entry ID
     * @param string $language The default language code. Defaults to 'en'.
     * @return string
     */
    private function buildSearchForm($id = '', $language = 'en') {
        global $wgScript;
        $form = "<table>";
        $form .= TilesheetsForm::createFormRow('tile-translator-filter', 'id', $id, 'number', 'min="1" id="form-entry-id"');
        $form .= TilesheetsForm::createFormRow('tile-translator-filter', 'language', $language);
        $form .= TilesheetsForm::createSubmitButton('tile-translator-filter');
        $form .= "</table>";

        $out = Xml::openElement('form', array('method' => 'get', 'action' => $wgScript, 'id' => 'ext-tilesheet-tile-translator-filter')) .
            Xml::fieldset($this->msg('tilesheet-tile-translator-filter-legend')->text()) .
            Html::hidden('title', $this->getPageTitle()->getPrefixedText()) .
            $form .
            Xml::closeElement( 'fieldset' ) . Xml::closeElement( 'form' ) . "\n";

        return $out;
    }

    private function buildUpdateForm($id, $language) {
        // TODO: Dropdown to list all available languages.
        $dbr = wfGetDB(DB_SLAVE);
        $result = $dbr->select('ext_tilesheet_languages', '*', array('entry_id' => $id, 'lang' => $language));
        if ($result->numRows() == 0) {
            return "Query returned an empty set (i.e. zero rows).";
        } else {
            $displayName = $result->current()->display_name;
            $description = $result->current()->description;
        }

        global $wgScript;
        $form = "<table>";
        $form .= TilesheetsForm::createFormRow('tile-translator', 'id', $id, "text", 'readonly="readonly"');
        $form .= TilesheetsForm::createFormRow('tile-translator', 'display_name', $displayName);
        $form .= TilesheetsForm::createFormRow('tile-translator', 'description', $description);
        $form .= TilesheetsForm::createFormRow('tile-translator', 'language', $language);
        $form .= TilesheetsForm::createInputHint('tile-translator', 'language');
        $form .= TilesheetsForm::createFormRow('tile-translator', 'delete', 1, "checkbox");
        $form .= TilesheetsForm::createSubmitButton('tile-translator');
        $form .= "</table>";

        $out = Xml::openElement(
            'form',
            array(
                'method' => 'get',
                'action' => $wgScript,
                'id' => 'ext-tilesheet-tile-translator-form',
                'class' => 'prefsection')
            ) .
            Xml::fieldset($this->msg('tilesheet-tile-translator-legend')->text()) .
            Html::hidden('title', $this->getPageTitle()->getPrefixedText()) .
            Html::hidden('token', $this->getUser()->getEditToken()) .
            Html::hidden('update', 1) .
            $form .
            Xml::closeElement('fieldset') . Xml::closeElement('form') . "\n";

        return $out;
    }
}