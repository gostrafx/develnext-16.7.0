<?php
namespace ide\formats\form\event;

use ide\editors\AbstractEditor;
use php\gui\event\UXMouseEvent;

class MouseParamEventKind extends MouseEventKind
{
    public function getParamVariants(AbstractEditor $contextEditor = null)
    {
        return [
            'Any button' => '',
            '-',
            'Left button' => 'Left',
            'Right button' => 'Right',
            'Middle button' => 'Middle',
            '-',
            'Double tap' => '2x',
            'Triple tap' => '3x',
        ];
    }
}