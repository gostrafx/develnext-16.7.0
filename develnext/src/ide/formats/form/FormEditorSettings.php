<?php
namespace ide\formats\form;

use ide\settings\AbstractSettings;

class FormEditorSettings extends AbstractSettings
{
    /**
     * @return string
     */
    public function getTitle()
    {
        return "Form and scene editor";
    }

    public function getMenuTitle()
    {
        return 'Settings for "Form Editor"';
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return "formEditor";
    }
}