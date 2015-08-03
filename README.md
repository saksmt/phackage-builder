Phackage-builder
================

Collects php files and builds them into phar-package.

Usage
=====

As library
----------

    use Smt\Packer\Phar;

    $phar = new Phar('PATH/TO/DIRECTORY/TO/PACK', 'TARGET_PACKAGE_NAME', 'INDEX_FILE');
    $phar
        ->addBootstrapData('const YOUR_PREDEFINED_CONSTANT = 5;')
        ->addFilter('!/vendor')
        ->addCustomFilter(function (\SplFileInfo $file) { return rand(0, 1)); }) // Heh
        ->prepare()
        ->pack()
    ;

    // Also you can use some other cool features

    // $phar = ...
    use Smt\Generator\ClassBuilder;
    $phar
        ->addBootstrapData(
            (new ClassBuilder('CoolClass'))
                ->setNamespace('Vendor\Code') // You'll be able to use it in phar!
                ->addProperty('prop')
                    ->makePublic()
                    ->makeStatic()
                    ->setValue('\'SOME_SECRET_TOKEN_FOR_EXAMPLE\'')
                    ->end()
                ->addMethod('get awesomeness') // yeah you can write exactly right this! this would be converted to "getAwesomeness"
                    ->setReturnValue('\Grab it!\'')
                    ->end()
                ->getCode()
        )
        ->prepare()
        ->pack()
    ;

    // And the last cool feature
    // $phar = ...
    use Smt\Generator\DefineGenerator;
    $phar
        ->addBootstrapData(new DefineGenerator([
            'SOME_CONST' => 'value',
            'SomeClass.SOME_CONST' => 'value',
            'SomeClass::someVar' => '\'value\'', // Warning here you MUST to write quotes if you use strings
            'Some\Name\Space\SomeClass.CONSTANT' => 'value',
        ]))
        ->prepare()
        ->pack()
    ;

As package
----------

`php bootstrap.php pack PATH/TO/DIR -dSomeClass.SOME_CONST='value'`, for more see "last cool feature" from "As library" section.
