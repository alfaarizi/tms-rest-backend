<?php

namespace app\commands;

use yii\console\Controller;
use yii\helpers\Console;

class BaseController extends Controller
{
    protected function promptBoolean($text, $default = true)
    {
        $value = Console::prompt($text, [
            'required' => true,
            'default' => $default ? 'Y' : 'N',
            'validator' => function ($input, &$error) {
                $input = strtoupper($input);
                if (
                    $input === 'Y' || $input === 'YES' ||
                    $input === 'N' || $input === 'NO'
                ) {
                    return true;
                } else {
                    $error = 'Type Y/N!';
                }
                return false;
            }
        ]);

        switch (strtoupper($value)) {
            case 'Y':
            case 'YES':
                return true;
            case 'N':
            case 'NO':
                return false;
        }
        return false;
    }
}
