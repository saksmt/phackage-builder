<?php

namespace Smt\PhackageBuilder\Application;

use Smt\PhackageBuilder\Command\PackCommand;
use Symfony\Component\Console\Application as BaseApp;

/**
 * Phackage builder application
 * @author Kirill Saksin <kirillsaksin@yandex.ru>
 * @package Smt\PhackageBuilder\Application
 */
class PhackageBuilderApp extends BaseApp
{
    /**
     * @const string Application version
     */
    const VERSION = '0.0.0';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('Phackage-Builder', self::VERSION);
        $this->configureCommands();
    }

    /**
     * Adds application commands
     */
    private function configureCommands()
    {
        $this->add(new PackCommand());
    }
}