<?php

namespace Smt\Generator\Util;

use Smt\Generator\Exception\BadNameException;

class NameValidator
{
    /**
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

    public static function validateCamelCase($name)
    {
        return self::baseValidator($name, function ($name, $namePart) {
            return $name . ucfirst($namePart);
        });
    }

    public static function validateUnderscore($name)
    {
        return self::baseValidator($name, function ($name, $namePart) {
            return $name . '_' . $namePart;
        });
    }
}