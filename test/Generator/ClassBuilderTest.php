<?php

namespace Smt\PhackageBuilder\Test\Generator;

use Smt\PhackageBuilder\Generator\ClassBuilder;

/**
 * Class builder test
 * @package Smt\PhackageBuilder\Test\Generator
 */
class ClassBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function test()
    {
        $class = (new ClassBuilder('test'))
            ->setNamespace('Test')
            ->addConstant('test constant', 5)
            ->addProperty('test property')
                ->setValue('24')
                ->makePublic()
                ->end()
            ->addProperty('nulled static test property')
                ->makePublic()
                ->makeStatic()
                ->end()
            ->addMethod('test getter')
                ->setReturnValue('self::TEST_CONSTANT')
                ->end()
            ->addMethod('test with arguments')
                ->addArgument('test argument')
                ->addArgument('test argument with default value', 24)
                ->setReturnValue('$this->testGetter() + $testArgumentWithDefaultValue')
                ->end()
            ->getCode()
        ;
        eval($class);
        $this->assertTrue(class_exists('\Test\Test'));
        $this->assertEquals(5, \Test\Test::TEST_CONSTANT);
        $this->assertNull(\Test\Test::$nulledStaticTestProperty);
        $object = new \Test\Test();
        $this->assertEquals(24, $object->testProperty);
        $this->assertEquals(5, $object->testGetter());
        $this->assertEquals(29, $object->testWithArguments(6));
    }
}