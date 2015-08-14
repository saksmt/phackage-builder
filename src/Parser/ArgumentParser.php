<?php

namespace Smt\PhackageBuilder\Parser;

/**
 * Parses map-like commandline arguments
 * @package Smt\PhackageBuilder\Parser
 * @author Kirill Saksin <kirillsaksin@yandex.ru>
 * @api
 */
class ArgumentParser
{
    /**
     * @var array Argument storage
     */
    private $storage = [];

    /**
     * Constructor
     * @param array $arguments Commandline arguments
     * @api
     */
    public function __construct(array $arguments)
    {
        foreach ($arguments as $argument) {
            $complexArg = $this->parseArgument($argument);
            $this->add($complexArg->key, $complexArg->value);
        }
    }

    /**
     * Get argument
     * @param string $key Argument name
     * @param mixed $default Default value to return
     * @return mixed
     * @api
     */
    public function get($key, $default = null)
    {
        if (isset($this->storage[$key])) {
            return $this->storage[$key];
        }
        return $default;
    }

    /**
     * Get arguments as array
     * @return array Arguments
     * @api
     */
    public function all()
    {
        return $this->storage;
    }

    /**
     * Parse single argument
     * @param string $argument Argument
     * @return object Argument object
     */
    private function parseArgument($argument)
    {
        $complex = explode('=', $argument);
        $complex = (object) [
            'key' => array_shift($complex),
            'value' => isset($complex[0]) ? $complex[0] : true,
        ];
        return $complex;
    }

    /**
     * Add argument to storage
     * @param string $key Name
     * @param string $value Value
     * @return ArgumentParser This instance
     */
    private function add($key, $value)
    {
        $this->storage[$key] = $value;
        return $this;
    }
}