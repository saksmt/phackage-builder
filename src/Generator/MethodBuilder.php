<?php

namespace Smt\PhackageBuilder\Generator;

use Smt\PhackageBuilder\Generator\Util\NameValidator;

/**
 * Method builder
 * @package Smt\PhackageBuilder\Generator
 * @author Kirill Saksin <kirillsaksin@yandex.ru>
 * @api
 */
class MethodBuilder extends AbstractClassPartBuilder
{
    /**
     * @var array Arguments of method
     */
    private $arguments = [];

    /**
     * @var string Return valude of method
     */
    private $returnValue = null;

    /**
     * @var bool
     */
    private $abstract = false;

    /**
     * {@inheritdoc}
     * @api
     */
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
     * Add method argument
     * @param string $name Argument name
     * @param string|null $default Argument default value
     * @return MethodBuilder This instance
     * @api
     */
    public function addArgument($name, $default = null)
    {
        $this->arguments[] = (object) [
            'name' => $name,
            'default' => $default,
        ];
        return $this;
    }

    /**
     * Set return value for method
     * @param string $value Return value
     * @return MethodBuilder This instance
     * @api
     */
    public function setReturnValue($value)
    {
        $this->returnValue = $value;
        return $this;
    }

    /**
     * Make method abstract
     * @return MethodBuilder This instance
     * @api
     */
    public function makeAbstract()
    {
        $this->abstract = true;
        return $this;
    }

    /** {@inheritdoc} */
    public function build()
    {
        $prefix = sprintf(
            '%s%s%s%sfunction %s(%s)',
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
                $this->getReturnString() . PHP_EOL .
                $this->getIndentation() . '}' . PHP_EOL . PHP_EOL;
        }
    }

    /**
     * Check if method is abstract
     * @return bool True if method is abstract, false otherwise
     */
    public function isAbstract()
    {
        return $this->abstract;
    }

    /**
     * Get modifier for abstract
     * @return string String with abstract modifier
     */
    private function getAbstractString()
    {
        return $this->abstract ? 'abstract ' : '';
    }

    /**
     * Get string with arguments definition
     * @return string Method arguments definition
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
     * Get string with return definition
     * @return string Return definition
     */
    private function getReturnString()
    {
        if (isset($this->returnValue)) {
            return sprintf('return %s;', $this->returnValue);
        }
        return '';
    }
}