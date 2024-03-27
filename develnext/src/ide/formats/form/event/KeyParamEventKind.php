<?php
namespace ide\formats\form\event;

use ide\editors\AbstractEditor;
use php\gui\event\UXKeyEvent;

class KeyParamEventKind extends KeyEventKind
{
    public function getParamVariants(AbstractEditor $contextEditor = null)
    {
        $letters = [
            'A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E', 'F' => 'F', 'G' => 'G', 'H' => 'H', 'I' => 'I',
            'J' => 'J', 'K' => 'K', 'L' => 'L', 'M' => 'M', 'N' => 'N', 'O' => 'O', 'P' => 'P', 'Q' => 'Q', 'R' => 'R',
            'S' => 'S', 'T' => 'T', 'U' => 'U', 'V' => 'V', 'W' => 'W', 'X' => 'X', 'Y' => 'Y', 'Z' => 'Z',
            '-',
            'Any letter' => 'AnyLetter'
        ];

        $digits = [
            0, 1, 2, 3, 4, 5, 6, 7, 8, 9, '-', 'Any number' => 'AnyDigit'
        ];

        $funcKeys = [
            'F1' => 'F1', 'F2' => 'F2', 'F3' => 'F3', 'F4' => 'F4', 'F5' => 'F5',
            'F6' => 'F6', 'F7' => 'F7', 'F8' => 'F8', 'F9' => 'F9', 'F10' => 'F10', 'F11' => 'F11', 'F12' => 'F12',
            '-',
            'F1-F12' => 'AnyF'
        ];

        $multimedia = [
            'Play' => 'Play',
            'Record' => 'Record',
            'Rewind' => 'Rewind',
            'Previous Track' => 'PreviousTrack',
            'Next Track' => 'NextTrack',
            '-',
            'Volume Up' => 'VolumeUp',
            'Volume Down' => 'VolumeDown',
            'Mute' => 'Mute'
        ];

        $others = [
            'Space' => 'Space',
            'Enter' => 'Enter',
            'Delete' => 'Delete',
            'Tab' => 'Tab',
            'Print Screen' => 'PrintScreen',
        ];

        $directions = [
            'Left' => 'Left',
            'Right' => 'Right',
            'Up' => 'Up',
            'Down' => 'Down',
            '-',
            'Any direction' => 'AnyDirection'
        ];

        $variants = [
            'Direction' => $directions,
            'Letters' => $letters,
            'Numbers' => $digits,
            'Functional' => $funcKeys,
            'Other' => $others,
        ];

        $ctrLetters = [];
        $altLetters = [];
        $shiftLetters = [];

        foreach ($variants as $group => $codes) {
            foreach ($codes as $code => $name) {
                if ($name === '-') continue;

                $ctrLetters[$group]['Ctrl + ' . $code] = 'Ctrl+' . $name;
            }
        }

        foreach ($variants as $group => $codes) {
            foreach ($codes as $code => $name) {
                if ($name === '-') continue;

                $altLetters[$group]['Alt + ' . $code] = 'Alt+' . $name;
            }
        }

        foreach ($variants as $group => $codes) {
            foreach ($codes as $code => $name) {
                if ($name === '-') continue;

                $shiftLetters[$group]['Shift + ' . $code] = 'Shift+' . $name;
            }
        }

        return [
            'Any button' => '',
            '-',
            'Space' => 'Space',
            'Enter' => 'Enter',
            'Escape' => 'Esc',
            '-',
            'Direction' => $directions,
            'Numbers' => $digits,
            'Letters' => $letters,
            'Functional' => $funcKeys,
            //'Мультимедиа' => $multimedia,
            'Other' => [
                'Tab' => 'Tab',
                'Backspace' => 'Backspace',
                'Delete' => 'Delete',
                'Insert' => 'Insert',
                'Pause' => 'Pause',
                'Print Screen' => 'PrintScreen',
            ],
            '-',
            'Ctrl + ?' => $ctrLetters,
            'Alt + ?' => $altLetters,
            'Shift + ?' => $shiftLetters,
        ];
    }

}