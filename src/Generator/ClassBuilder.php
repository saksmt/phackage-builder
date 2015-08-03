<?php

namespace Smt\Generator;

use Smt\Generator\Exception\NonScalarConstantException;
use Smt\Generator\Util\NameValidator;

class ClassBuilder
{
    const ACCESS_PRIVATE = 'private';
    const ACCESS_PROTECTED = 'protected';
    const ACCESS_PUBLIC = 'public';

    /**
     * @var string
     */
    private $name;

    /**
     * @var MethodBuilder
     */
    private $methods = [];

    /**
     * @var PropertyBuilder
     */
    private $properties = [];

    /**
     * @var string[]
     */
    private $constants = [];

    /**
     * @var bool
     */
    private $abstract = false;

    /**
     * @var string|null
     */
    private $namespace;

    /**
     * @param string $className
     */
    public function __construct($className)
    {
        $this->name = ucfirst(NameValidator::validateCamelCase($className));
    }

    /**
     * @param string $name
     * @return $this
     */
    public static function createEnumeration($name)
    {
        return (new self($name))->forceAbstract();
    }

    /**
     * @param string $name
     * @return MethodBuilder
     */
    public function addMethod($name)
    {
        $method = new MethodBuilder($name);
        $method->setClass($this);
        $this->methods[] = $method;
        return $method;
    }

    /**
     * @return MethodBuilder
     */
    public function addConstructor()
    {
        return $this->addMethod('__construct');
    }

    /**
     * @param string $namespace
     * @return $this
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @param string $name
     * @return PropertyBuilder
     */
    public function addProperty($name)
    {
        $property = new PropertyBuilder($name);
        $property->setClass($this);
        $this->properties[] = $property;
        return $property;
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     * @throws NonScalarConstantException
     */
    public function addConstant($name, $value)
    {
        if (!is_scalar($value)) {
            throw new NonScalarConstantException();
        }
        if (is_string($value)) {
            $value = sprintf('\'%s\'', $value);
        }
        $this->constants[strtoupper(NameValidator::validateUnderscore($name))] = $value;
        return $this;
    }

    /**
     * @return $this
     */
    public function forceAbstract()
    {
        $this->abstract = true;
        return $this;
    }

    /**
     * @return string
     * @throws Exception\BadNameException
     */
    public function getCode()
    {
        $code = sprintf('class %s',
            $this->name
        ) . PHP_EOL . $this->getIndentation() . '{' . PHP_EOL;
        foreach ($this->constants as $name => $value) {
            $code .= $this->getIndentation(1) . sprintf('const %s = %s;',
                $name,
                $value
            ) . PHP_EOL;
        }
        $code .= PHP_EOL;
        foreach ($this->properties as $property) {
            $code .= $property->build();
        }
        $code .= PHP_EOL;
        foreach ($this->methods as $method) {
            if ($method->isAbstract()) {
                $this->abstract = true;
            }
            $code .= $method->build();
        }
        $code .= PHP_EOL . $this->getIndentation() . '}';
        if ($this->abstract) {
            $code = 'abstract ' . $code;
        }
        $code = $this->getIndentation() . $code;
        $namespace = $this->namespace;
        if (!isset($namespace)) {
            $namespace = '';
        }
        return 'namespace ' . $namespace . PHP_EOL . '{' . PHP_EOL . $code . PHP_EOL . '}' . PHP_EOL;
    }

    public function hasNamespace()
    {
        return isset($this->namespace);
    }

    private function getIndentation($level = 0)
    {
        return str_repeat(' ', 4 * ($level + $this->hasNamespace()));
    }
}