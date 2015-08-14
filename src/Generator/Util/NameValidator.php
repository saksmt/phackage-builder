<?php

namespace Smt\PhackageBuilder\Generator\Util;

use Smt\PhackageBuilder\Generator\Exception\BadNameException;

/**
 * Utility class for generation valid names
 * @author Kirill Saksin <kirillsaksin@yandex.ru>
 * @package Smt\PhackageBuilder\Generator\Util
 */
class NameValidator
{

    /**
     * Validate|Generate names in camelCase
     * @param string $name Original name
     * @return string Valid camelCase name
     * @throws BadNameException
     */
    public static function validateCamelCase($name)
    {
        return self::baseValidator($name, function ($name, $namePart) {
            return $name . ucfirst($namePart);
        });
    }

    /**
     * Validate|Generate names in underscore
     * @param string $name Original name
     * @return string Valid underscore name
     * @throws BadNameException
     */
    public static function validateUnderscore($name)
    {
        return self::baseValidator($name, function ($name, $namePart) {
            return $name . '_' . $namePart;
        });
    }

    /**
     * Base validator|generator
     * @param string $name
     * @return string
     * @throws BadNameException
     */
    private static function baseValidator($name, callable $generationStrategy)
    {
        if (!is_string($name) || preg_match('/[0-9]/', substr($name, 0, 1))) {
            throw new BadNameException($name);
        }
        $nameParts = preg_split('/[\s\-\_]/', $name);
        $name = array_shift($nameParts);
        foreach ($nameParts as $namePart) {
            $name = $generationStrategy($name, $namePart);
        }
        return $name;
    }
}