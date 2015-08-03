<?php

namespace Smt\Generator;

use Smt\Generator\Util\NameValidator;

abstract class ClassPartBuilder
{
    private $static = false;
    private $access = ClassBuilder::ACCESS_PRIVATE;
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
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = NameValidator::validateCamelCase($name);
    }

    /**
     * @param $prefix
     * @return $this
     */
    protected function prependName($prefix)
    {
        $this->name = $prefix . $this->name;
        return $this;
    }

    /**
     * @return $this
     */
    public function makePublic()
    {
        $this->access = ClassBuilder::ACCESS_PUBLIC;
        return $this;
    }

    /**
     * @return $this
     */
    public function makeProtected()
    {
        $this->access = ClassBuilder::ACCESS_PROTECTED;
        return $this;
    }

    /**
     * @return $this
     */
    public function makePrivate()
    {
        $this->access = ClassBuilder::ACCESS_PRIVATE;
        return $this;
    }

    /**
     * @return $this
     */
    public function makeStatic()
    {
        $this->static = true;
        return $this;
    }

    /**
     * @return ClassBuilder
     */
    public function end()
    {
        return $this->class;
    }

    /**
     * @param ClassBuilder $classBuilder
     * @return $this
     */
    public function setClass(ClassBuilder $classBuilder)
    {
        $this->class = $classBuilder;
        return $this;
    }

    protected function getIndentation($level = 1)
    {
        return str_repeat(' ', 4 * ($level + $this->class->hasNamespace()));
    }

    /**
     * @param string $name
     * @param array|null $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->class, $name], $arguments);
    }

    /**
     * @return ClassBuilder
     */
    protected function getClass()
    {
        return $this->class;
    }

    /**
     * @return string
     */
    protected function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    protected function getAccess()
    {
        return $this->access . ' ';
    }

    /**
     * @return string
     */
    protected function getStaticString()
    {
        return ($this->static) ? 'static ' : '';
    }

    /**
     * @return string
     */
    abstract public function build();
}