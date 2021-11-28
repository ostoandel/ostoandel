<?php

if (!function_exists('__')) {
    function __($singular, $args = null) {
        if (!$singular) {
            return null;
        }

        App::uses('I18n', 'I18n');
        $translated = I18n::translate($singular);
        $arguments = func_get_args();
        return I18n::insertArgs($translated, array_slice($arguments, 1));
    }
}