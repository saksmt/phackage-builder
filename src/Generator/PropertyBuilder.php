<?php

namespace Smt\Generator;

class PropertyBuilder extends ClassPartBuilder
{
    /**
     * @var string
     */
    private $value;

    /**
     * @param string $value
     * @return $this
     */
    public function setValue($value)
    {
        if (preg_match('(new|[\$\(\)])', $value)) {
            throw new \InvalidArgumentException(sprintf('"%s" can`t be initial value!', $value));
        }
        $this->value = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function build()
    {
        $code = sprintf('%s%s%s$%s',
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