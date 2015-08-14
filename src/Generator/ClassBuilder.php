<?php

namespace Smt\PhackageBuilder\Generator;

use Generator\Exception\ClassGenerationException;
use Smt\PhackageBuilder\Generator\Exception\NonScalarConstantException;
use Smt\PhackageBuilder\Generator\Util\NameValidator;

/**
 * Creates code for PHP class
 * @package Smt\PhackageBuilder\Generator
 * @api
 */
class ClassBuilder
{
    /**
     * @const string Private access modifier
     */
    const ACCESS_PRIVATE = 'private';

    /**
     * @const string Protected access modifier
     */
    const ACCESS_PROTECTED = 'protected';

    /**
     * @const string Public access modifier
     */
    const ACCESS_PUBLIC = 'public';

    /**
     * @var string Class name
     */
    private $name;

    /**
     * @var MethodBuilder[] Class methods
     */
    private $methods = [];

    /**
     * @var PropertyBuilder[] Class properties
     */
    private $properties = [];

    /**
     * @var string[] Class constants
     */
    private $constants = [];

    /**
     * @var bool Is class abstract
     */
    private $abstract = false;

    /**
     * @var string|null Class namespace
     */
    private $namespace;

    /**
     * @var bool Is class final
     */
    private $final;

    /**
     * Constructor
     * @param string $className Class name
     * @api
     */
    public function __construct($className)
    {
        $this->name = ucfirst(NameValidator::validateCamelCase($className));
    }

    /**
     * Set class final or not
     * @param bool $final Is class final
     * @return ClassBuilder This instance
     * @api
     */
    public function setFinal($final = true)
    {
        $this->final = $final;
        return $this;
    }

    /**
     * Factory method to create enumeration
     * @param string $name Enumeration name
     * @return ClassBuilder
     * @api
     */
    public static function createEnumeration($name)
    {
        return (new self($name))
            ->setFinal()
            ->addConstructor()
                ->makePrivate()
            ->end()
        ;
    }

    /**
     * Add method to class
     * @param string $name Method name
     * @return MethodBuilder Method builder
     * @api
     */
    public function addMethod($name)
    {
        $method = new MethodBuilder($name);
        $method->setClass($this);
        $this->methods[] = $method;
        return $method;
    }

    /**
     * Add constructor
     * @return MethodBuilder Method builder
     * @api
     */
    public function addConstructor()
    {
        return $this->addMethod('__construct');
    }

    /**
     * Set namespace for class
     * @param string $namespace Namespace name
     * @return ClassBuilder This instance
     * @api
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Add property to class
     * @param string $name Property name
     * @return PropertyBuilder Property builder
     * @api
     */
    public function addProperty($name)
    {
        $property = new PropertyBuilder($name);
        $property->setClass($this);
        $this->properties[] = $property;
        return $property;
    }

    /**
     * Add constant to class
     * @param string $name Constant name
     * @param string $value Constant value
     * @return ClassBuilder This instance
     * @throws NonScalarConstantException
     * @api
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
     * Force class to be abstract
     * @return ClassBuilder This instance
     * @api
     */
    public function forceAbstract()
    {
        $this->abstract = true;
        return $this;
    }

    /**
     * Get built code
     * @return string Generated code
     * @throws ClassGenerationException
     * @api
     */
    public function getCode()
    {
        $code = sprintf(
            'class %s',
            $this->name
        ) . PHP_EOL . $this->getIndentation() . '{' . PHP_EOL;
        foreach ($this->constants as $name => $value) {
            $code .= $this->getIndentation(1) . sprintf(
                'const %s = %s;',
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
        $code = $this->setModifiers($code);
        $code = $this->getIndentation() . $code;
        $namespace = $this->namespace;
        if (!isset($namespace)) {
            $namespace = '';
        }
        return 'namespace ' . $namespace . PHP_EOL . '{' . PHP_EOL . $code . PHP_EOL . '}' . PHP_EOL;
    }

    /**
     * Check if class has namespace
     * @return bool True if namespace is set, false otherwise
     * @api
     */
    public function hasNamespace()
    {
        return isset($this->namespace);
    }

    /**
     * Generate indentation string
     * @param int $level Block nesting level
     * @return string Indentation
     */
    private function getIndentation($level = 0)
    {
        return str_repeat(' ', 4 * ($level + $this->hasNamespace()));
    }

    /**
     * @param string $code Already generated code
     * @return string Class with modifiers
     * @throws ClassGenerationException
     */
    private function setModifiers($code)
    {
        if ($this->abstract && $this->final) {
            throw new ClassGenerationException('Class can\'t be both abstract and final!');
        }
        if ($this->abstract) {
            $code = 'abstract ' . $code;
        }
        if ($this->final) {
            $code = 'final ' . $code;
            return $code;
        }
        return $code;
    }
}