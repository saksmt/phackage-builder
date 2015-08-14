<?php

namespace Smt\PhackageBuilder\Generator;

use Smt\PhackageBuilder\Generator\Util\NameValidator;

/**
 * Base builder
 * @package Smt\PhackageBuilder\Generator
 * @author Kirill Saksin <kirillsaksin@yandex.ru>
 */
abstract class AbstractClassPartBuilder
{
    /**
     * @var bool Is part is static
     */
    private $static = false;

    /**
     * @var string Part access modifier
     */
    private $access = ClassBuilder::ACCESS_PRIVATE;

    /**
     * @var string Indentation
     */
    protected static $indentation = '    ';

    /**
     * @var string
     */
    private $name;

    /**
     * @var ClassBuilder
     */
    private $class;

    /**
     * Generate code
     * @return string Part code
     */
    abstract public function build();

    /**
     * Constructor
     * @param string $name Part name
     */
    public function __construct($name)
    {
        $this->name = NameValidator::validateCamelCase($name);
    }

    /**
     * Make part public
     * @return AbstractClassPartBuilder This instance
     */
    public function makePublic()
    {
        $this->access = ClassBuilder::ACCESS_PUBLIC;
        return $this;
    }

    /**
     * Make part protected
     * @return AbstractClassPartBuilder This instance
     */
    public function makeProtected()
    {
        $this->access = ClassBuilder::ACCESS_PROTECTED;
        return $this;
    }

    /**
     * Make part private
     * @return AbstractClassPartBuilder This instance
     */
    public function makePrivate()
    {
        $this->access = ClassBuilder::ACCESS_PRIVATE;
        return $this;
    }

    /**
     * Make part static
     * @return AbstractClassPartBuilder This instance
     */
    public function makeStatic()
    {
        $this->static = true;
        return $this;
    }

    /**
     * End build of part
     * @return ClassBuilder Part holder
     */
    public function end()
    {
        return $this->class;
    }

    /**
     * Set part holder
     * @param ClassBuilder $classBuilder Holder
     * @return AbstractClassPartBuilder This instance
     */
    public function setClass(ClassBuilder $classBuilder)
    {
        $this->class = $classBuilder;
        return $this;
    }

    /**
     * Magic method to delegate unknown method calls to holder
     * @param string $name Method name
     * @param array|null $arguments Method arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->class, $name], $arguments);
    }

    /**
     * Get indentation string
     * @param int $level Nesting level
     * @return string Indentation string
     */
    protected function getIndentation($level = 1)
    {
        return str_repeat(' ', 4 * ($level + $this->class->hasNamespace()));
    }

    /**
     * Get part holder
     * @return ClassBuilder Holder
     */
    protected function getClass()
    {
        return $this->class;
    }

    /**
     * Get part name
     * @return string Part name
     */
    protected function getName()
    {
        return $this->name;
    }

    /**
     * Get access modifier
     * @return string String with modifier
     */
    protected function getAccess()
    {
        return $this->access . ' ';
    }

    /**
     * Get static modifier
     * @return string String with modifier
     */
    protected function getStaticString()
    {
        return ($this->static) ? 'static ' : '';
    }

    /**
     * Set prefix for part
     * @param string $prefix Prefix
     * @return AbstractClassPartBuilder This instance
     */
    protected function prependName($prefix)
    {
        $this->name = $prefix . $this->name;
        return $this;
    }
}