<?php

namespace Smt\Command;

use Smt\Generator\DefineGenerator;
use Smt\Packer\Phar;
use Smt\Parser\ArgumentParser;
use Smt\Component\Console\Style\GentooStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PackCommand extends Command
{
    public function configure()
    {
        $this
            ->setName('pack')
            ->setDescription('Pack package in phar from specified directory.')
            ->addArgument('path-to-package', InputArgument::REQUIRED, 'Path to directory of package')
            ->addOption('name', 'N', InputOption::VALUE_REQUIRED, 'Name of package', 'package.phar')
            ->addOption('define', 'd', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Provide define for package', [])
        ;
    }

    public function execute(InputInterface $in, OutputInterface $out)
    {
        $out = new GentooStyle($out, $in);
        $defineBag = new ArgumentParser($in->getOption('define'));
        $package = new Phar($in->getArgument('path-to-package'), $in->getOption('name'));
        if ($out->isVeryVerbose()) {
            $out->info('Defined variables:');
            $vars = [];
            foreach ($defineBag->all() as $key => $value) {
                $vars[] = sprintf('<info>%s</info> = %s', $key, $value);
            }
            $out
                ->listing($vars)
                ->newLine()
            ;
        }
        $package
            ->setOutput(new GentooStyle($out, $in))
            ->addBootstrapData((new DefineGenerator($defineBag->all()))->getCode())
            ->setCompression(Phar::NONE)
            ->prepare()
            ->pack()
        ;

    }
}