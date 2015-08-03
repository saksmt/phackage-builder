<?php

namespace Smt\Parser;

class ArgumentParser
{
    private $storage = [];

    public function __construct(array $arguments)
    {
        foreach ($arguments as $argument) {
            $complexArg = $this->parseArgument($argument);
            $this->add($complexArg->key, $complexArg->value);
        }
    }

    private function parseArgument($argument)
    {
        $complex = explode('=', $argument);
        $complex = (object)[
            'key' => array_shift($complex),
            'value' => isset($complex[0]) ? $complex[0] : true,
        ];
        return $complex;
    }

    private function add($key, $value)
    {
        $this->storage[$key] = $value;
        return $this;
    }

    public function get($key, $default = null)
    {
        if (isset($this->storage[$key])) {
            return $this->storage[$key];
        }
        return $default;
    }

    public function all()
    {
        return $this->storage;
    }
}