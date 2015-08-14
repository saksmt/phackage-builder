<?php

namespace Smt\PhackageBuilder\Generator;

/**
 * Creates define code for specified parameters
 * @package Smt\PhackageBuilder\Generator
 * @author Kirill Saksin <kirill.saksin@yandex.ru>
 * @api
 */
class DefineGenerator
{
    /**
     * @const string Constant
     */
    const CONSTANT = 'const';

    /**
     * @const string Static property
     */
    const STATIC_PROPERTY = 'static';

    /**
     * @const string Class member property
     */
    const MEMBER_PROPERTY = 'member';

    /**
     * @var string[] Separator map
     */
    private static $separatorMap = [
        self::CONSTANT => '.',
        self::STATIC_PROPERTY => '::',
        self::MEMBER_PROPERTY => '->',
    ];

    /**
     * @var string Generated code
     */
    private $code = '';

    /**
     * Constructor
     * @param array $parameters Parameters
     * @api
     */
    public function __construct(array $parameters)
    {
        $classData = [];
        foreach ($parameters as $key => $value) {
            if (!preg_match('/([a-z0-9_]\\\\?)+(\.|::|->)[a-z0-9_]/i', $key)) {
                $this->generateDefine($key, $value);
            } else {
                $dataType = '';
                foreach (self::$separatorMap as $type => $symbol) {
                    if (strpos($key, $symbol) !== false) {
                        $dataType = $type;
                        break;
                    }
                }
                $className = strstr($key, self::$separatorMap[$dataType], true);
                $data = [
                    $dataType => [
                        substr(strstr($key, self::$separatorMap[$dataType]), strlen(self::$separatorMap[$dataType])) => $value,
                    ],
                ];
                if (isset($classData[$className])) {
                    $data = array_merge_recursive($classData[$className], $data);
                }
                $classData[$className] = $data;
            }
        }
        if (!empty($this->code)) {
            $this->code = 'namespace {' . PHP_EOL . $this->code . '}' . PHP_EOL;
        }
        $this->buildClasses($classData);
    }

    /**
     * Get code
     * @return string Code
     * @api
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Generate define expression
     * @param string $key Define name
     * @param string $value Define value
     */
    private function generateDefine($key, $value)
    {
        $this->code .= sprintf('const %s = \'%s\';', $key, $value) . PHP_EOL;
    }

    /**
     * Build classes code based on preparsed data
     * @param array $classData Class data
     */
    private function buildClasses(array $classData)
    {
        foreach ($classData as $classPath => $classDefinition) {
            $className = $classPath;
            $namespace = '';
            if (strpos($className, '\\')) {
                $className = substr($classPath, strrpos($classPath, '\\') + 1);
                $namespace = substr($classPath, 0, strrpos($classPath, '\\'));
            }
            $this->buildClass($className, $namespace, $classDefinition);
        }
    }

    /**
     * Build class code
     * @param string $className Class name
     * @param string $namespace Class namespace name
     * @param array $classDefinition Definition of class
     * @throws Exception\NonScalarConstantException
     */
    private function buildClass($className, $namespace, array $classDefinition)
    {
        $class = (new ClassBuilder($className))->setNamespace($namespace);
        if (isset($classDefinition[self::CONSTANT])) {
            foreach ($classDefinition[self::CONSTANT] as $name => $value) {
                $class->addConstant($name, $value);
            }
        }
        if (isset($classDefinition[self::MEMBER_PROPERTY])) {
            foreach ($classDefinition[self::MEMBER_PROPERTY] as $name => $value) {
                $class
                    ->addProperty($name)
                    ->setValue($value)
                    ->makePublic()
                    ->end()
                ;
            }

        }
        if (isset($classDefinition[self::STATIC_PROPERTY])) {
            foreach ($classDefinition[self::STATIC_PROPERTY] as $name => $value) {
                $class
                    ->addProperty($name)
                    ->setValue($value)
                    ->makeStatic()
                    ->makePublic()
                    ->end()
                ;
            }

        }
        $this->code .= $class->getCode() . PHP_EOL;
    }
}