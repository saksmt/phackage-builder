<?php

namespace Smt\Generator;

use Smt\Generator\Util\NameValidator;

class MethodBuilder extends ClassPartBuilder
{
    /**
     * @var array
     */
    private $arguments = [];

    /**
     * @var string
     */
    private $returnValue = null;

    /**
     * @var bool
     */
    private $abstract = false;

    public function __construct($name)
    {
        $isMagic = substr($name, 0, 2) == '__';
        parent::__construct($name);
        if ($isMagic) {
            $this->prependName('__');
        }
        $this->makePublic();
    }

    /**
     * @param string $name
     * @param string|null $default
     * @return $this
     */
    public function addArgument($name, $default = null)
    {
        $this->arguments[] = (object)[
            'name' => $name,
            'default' => $default,
        ];
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setReturnValue($value)
    {
        $this->returnValue = $value;
        return $this;
    }

    /**
     * @return $this
     */
    public function makeAbstract()
    {
        $this->abstract = true;
        return $this;
    }

    /**
     * @return string
     */
    public function build()
    {
        $prefix = sprintf('%s%s%s%sfunction %s(%s)',
            $this->getIndentation(),
            $this->getAbstractString(),
            $this->getAccess(),
            $this->getStaticString(),
            $this->getName(),
            $this->getArgumentString()
        );
        if ($this->abstract) {
            return $prefix . ';' . PHP_EOL;
        } else {
            return $prefix . PHP_EOL .
                $this->getIndentation() . '{' . PHP_EOL . $this->getIndentation(2) .
                sprintf('return %s;', $this->returnValue) . PHP_EOL .
                $this->getIndentation() . '}' . PHP_EOL . PHP_EOL;
        }
    }

    /**
     * @return string
     */
    private function getAbstractString()
    {
        return $this->abstract ? 'abstract ' : '';
    }

    /**
     * @return string
     * @throws Exception\BadNameException
     */
    private function getArgumentString()
    {
        $arguments = [];
        foreach ($this->arguments as $argumentObject) {
            $argument = '$' . NameValidator::validateCamelCase($argumentObject->name);
            if (isset($argumentObject->default)) {
                if (is_string($argumentObject->default)) {
                    if (preg_match('(new|[\$\(\)])', $argumentObject->default)) {
                        throw new \InvalidArgumentException(sprintf('"%s" can`t be default value!', $argumentObject->default));
                    }
                }
                $argument .= ' = ' . $argumentObject->default;
            }
            $arguments[] = $argument;
        }
        return implode(', ', $arguments);
    }

    /**
     * @return bool
     */
    public function isAbstract()
    {
        return $this->abstract;
    }
}