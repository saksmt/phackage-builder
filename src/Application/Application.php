<?php

namespace Smt\Application;

use Smt\Command\PackCommand;
use Symfony\Component\Console\Application as BaseApp;

class Application extends BaseApp
{
    const VERSION = '0.0.0';

    public function __construct()
    {
        parent::__construct('Phackage-Builder', self::VERSION);
        $this->configureCommands();
    }

    private function configureCommands()
    {
        $this->add(new PackCommand());
    }
}