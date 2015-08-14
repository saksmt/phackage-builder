<?php

namespace Smt\PhackageBuilder\Generator;

/**
 * Property builder
 * @package Smt\PhackageBuilder\Generator
 * @author Kirill Saksin <kirill.saksin@yandex.ru>
 * @api
 */
class PropertyBuilder extends AbstractClassPartBuilder
{
    /**
     * @var string Property value
     */
    private $value;

    /**
     * Set initial property value
     * @param string $value Property value
     * @return PropertyBuilder This instance
     * @api
     */
    public function setValue($value)
    {
        if (preg_match('(new|[\$\(\)])', $value)) {
            throw new \InvalidArgumentException(sprintf('"%s" can`t be initial value!', $value));
        }
        $this->value = $value;
        return $this;
    }

    /** {@inheritdoc} */
    public function build()
    {
        $code = sprintf(
            '%s%s%s$%s',
            $this->getIndentation(),
            $this->getAccess(),
            $this->getStaticString(),
            $this->getName()
        );
        if (isset($this->value)) {
            $code .= ' = ' . $this->value;
        }
        return $code . ';' . PHP_EOL;
    }
}