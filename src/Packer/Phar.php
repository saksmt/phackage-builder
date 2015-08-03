<?php

namespace Smt\Packer;

use Smt\Component\Console\Style\ImprovedStyleInterface;
use Smt\Component\Console\Style\StubStyle;

class Phar extends \Phar
{

    /**
     * @var string
     */
    private $file;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string[]
     */
    private $filterMap = [
        '!/vendor',
        '!/.*\.phar',
    ];

    /**
     * @var string
     */
    private $bootstrapCode = '';

    /**
     * @var string[][]
     */
    private $filterChain;

    /**
     * @var string
     */
    private $sourcesDir;

    /**
     * @var callable[]
     */
    private $customFilters = [];

    /**
     * @var string
     */
    private $bootstrapFile;

    /**
     * @var int
     */
    private $compression = self::GZ;

    /**
     * @var ImprovedStyleInterface
     */
    private $output;

    /**
     * @param string $directory Files to pack
     * @param string $name Target phar name
     * @param string $bootstrapFile Base file
     */
    public function __construct($directory, $name, $bootstrapFile = 'bootstrap.php')
    {
        @mkdir('/tmp/phar');
        $this->file = '/tmp/phar/' . uniqid('PHAR') . '.phar';
        $directory = realpath($directory);
        $this->sourcesDir = $directory;
        $this->path = $directory . '/' . $name;
        $this->bootstrapFile = $bootstrapFile;
        $this->addBootstrapData('<?php')
        ;
        $this->output = new StubStyle();
        $this->stripPath = strlen($directory);
        parent::__construct($this->file);
    }

    /**
     * @param ImprovedStyleInterface $out
     * @return $this
     */
    public function setOutput(ImprovedStyleInterface $out)
    {
        $this->output = $out;
        return $this;
    }

    /**
     * Set filter map
     * @param array $map
     * @return $this
     */
    public function setFilterMap(array $map)
    {
        $this->filterMap = $map;
        return $this;
    }

    /**
     * Add file name filter
     * @param string $filterString
     */
    public function addFilter($filterString)
    {
        $this->filterMap[] = $filterString;
    }

    /**
     * @param int $compression
     * @return $this
     */
    public function setCompression($compression)
    {
        $this->compression = $compression;
        return $this;
    }

    /**
     * Prepare for packing
     * @return $this
     */
    public function prepare()
    {
        $this->buildMap();
        return $this;
    }

    /**
     * Pack the phar
     * @param bool $useRelativePaths
     * @return $this
     */
    public function pack($useRelativePaths = true)
    {
        $this->output->info(sprintf('Creating package from "%s"', $this->sourcesDir));
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->sourcesDir));
        $log = function () {};
        if ($this->output->isVerbose()) {
            $log = function (\SplFileInfo $file) {
                $this->output->info(sprintf('Adding "%s/%s"...', $file->getPath(), $file->getFilename()));
            };
        }
        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isDir() && $this->filterPass($file)) {
                $log($file);
                if ($useRelativePaths) {
                    $this->addFile($file->getPath() . '/' . $file->getFilename(),
                        substr($file->getPath(), $this->stripPath) . $file->getFilename()
                    );
                } else {
                    $this->addFile($file->getPath() . '/' . $file->getFilename());
                }
            }
        }
        $this->createLoader();
        $this->output->info('Saving...');
        copy($this->file, $this->path);
        $this->output->success('Done!');
        return $this;
    }

    /**
     * Add custom callable filter
     * @param callable $filter
     * @return $this
     */
    public function addCustomFilter(callable $filter)
    {
        $this->customFilters[] = $filter;
        return $this;
    }

    /**
     * Add code to execute on startup
     * @param string $data
     * @return $this
     */
    public function addBootstrapData($data)
    {
        $this->bootstrapCode .= $data . PHP_EOL;
        return $this;
    }

    protected function buildMap()
    {
        $this->filterChain = [
            'accept' => [],
            'reject' => [],
        ];
        foreach ($this->filterMap as $rule) {
            if (substr($rule, 0, 1) == '!') {
                $this->filterChain['reject'][] = $this->prepareFilterRule(substr($rule, 1));
            } else {
                $this->filterChain['accept'][] = $this->prepareFilterRule($rule);
            }
        }
    }

    protected function prepareFilterRule($rule)
    {
        return '/' . str_replace('/', '\\/', $rule) . '/';
    }

    private function filterPass(\SplFileInfo $file)
    {
        $log = function () {};
        if ($this->output->isVeryVerbose()) {
            $log = function ($rule) use ($file) {
                $this->output->info(sprintf('"%s/%s" filtered out by "%s" rule', $file->getPath(), $file->getFilename(), $rule));
            };
        }
        foreach ($this->filterChain['accept'] as $rule) {
            if (!preg_match($rule, $file->getPath() . '/' . $file->getFilename())) {
                $log($rule);
                return false;
            }
        }
        foreach ($this->filterChain['reject'] as $rule) {
            if (preg_match($rule, $file->getPath() . '/' . $file->getFilename())) {
                $log($rule);
                return false;
            }
        }
        foreach ($this->customFilters as $filter) {
            if (!$filter($file)) {
                return false;
            }
        }
        return true;
    }

    public function __destruct()
    {
        @unlink($this->file);
        parent::__destruct();
    }

    private function createLoader()
    {
        $this->output->info('Writing bootstrap code...');
        $bootstrapCodeFile = uniqid('BOOTSTRAP') . '.php';
        $loaderFile = uniqid('LOADER') . '.php';
        ;
        $this->addFromString($bootstrapCodeFile, $this->bootstrapCode);
        $this->addFromString($loaderFile, sprintf(
            '<?php' . PHP_EOL .
            'require_once __DIR__ . \'/%s\';' . PHP_EOL .
            'require_once __DIR__ . \'/%s\';',
            $bootstrapCodeFile,
            $this->bootstrapFile
        ));
        $this->setStub(
            '#!/usr/bin/env php' . PHP_EOL .
            $this->createDefaultStub($loaderFile)
        );
        if ($this->compression !== self::NONE) {
            $this->output->info('Compressing...');
            $this->compressFiles($this->compression);
        }
    }
}