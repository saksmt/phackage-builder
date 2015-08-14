<?php

namespace Smt\PhackageBuilder\Packer;

use Smt\Component\Console\Style\ImprovedStyleInterface;
use Smt\Component\Console\Style\StubStyle;

/**
 * Represents phar package
 * @package Smt\PhackageBuilder\Packer
 * @author Kirill Saksin <kirillsaksin@yandex.ru>
 * @api
 */
class Phar extends \Phar
{

    /**
     * @var string Temp file path
     */
    private $file;

    /**
     * @var string Path to package
     */
    private $path;

    /**
     * @var string[] List of filters
     */
    private $filterMap = [
        '!/vendor',
        '!/.*\.phar',
    ];

    /**
     * @var string Bootstrap code
     */
    private $bootstrapCode = '';

    /**
     * @var string[][] Chain of parsed filters
     */
    private $filterChain;

    /**
     * @var string Path to directory with sources
     */
    private $sourcesDir;

    /**
     * @var callable[] List of user-defined filter callbacks
     */
    private $customFilters = [];

    /**
     * @var string Path to bootstrap file
     */
    private $bootstrapFile;

    /**
     * @var int Compression level
     */
    private $compression = self::GZ;

    /**
     * @var ImprovedStyleInterface Output
     */
    private $output;

    /**
     * Constructor
     * @param string $directory Files to pack
     * @param string $name Target phar name
     * @param string $bootstrapFile Base file
     * @api
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
     * Set output
     * @param ImprovedStyleInterface $out Output
     * @return Phar This instance
     * @api
     */
    public function setOutput(ImprovedStyleInterface $out)
    {
        $this->output = $out;
        return $this;
    }

    /**
     * Set filter map
     * @param array $map Filter map
     * @return Phar This instance
     * @api
     */
    public function setFilterMap(array $map)
    {
        $this->filterMap = $map;
        return $this;
    }

    /**
     * Add file name filter
     * @param string $filterString Filter
     * @return Phar This instance
     * @api
     */
    public function addFilter($filterString)
    {
        $this->filterMap[] = $filterString;
        return $this;
    }

    /**
     * Set compression
     * @param int $compression Compression
     * @return Phar This instance
     * @api
     */
    public function setCompression($compression)
    {
        $this->compression = $compression;
        return $this;
    }

    /**
     * Prepare for packing
     * @return Phar This instance
     * @api
     */
    public function prepare()
    {
        $this->buildMap();
        return $this;
    }

    /**
     * Pack the phar
     * @param bool $useRelativePaths Whether to use relative paths inside of package
     * @return Phar This instance
     * @api
     */
    public function pack($useRelativePaths = true)
    {
        $this->output->info(sprintf('Creating package from "%s"', $this->sourcesDir));
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->sourcesDir));
        $log = function () {
        };
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
                    $this->addFile(
                        $file->getPath() . '/' . $file->getFilename(),
                        substr($file->getPath(), $this->stripPath) . '/' . $file->getFilename()
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
     * @param callable $filter Callable filter
     * @return Phar This instance
     * @api
     */
    public function addCustomFilter(callable $filter)
    {
        $this->customFilters[] = $filter;
        return $this;
    }

    /**
     * Add code to execute on startup
     * @param string $data Code
     * @return Phar This instance
     * @api
     */
    public function addBootstrapData($data)
    {
        $this->bootstrapCode .= $data . PHP_EOL;
        return $this;
    }

    /** {@inheritdoc} */
    public function __destruct()
    {
        @unlink($this->file);
        parent::__destruct();
    }

    /**
     * Build filter map
     */
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

    /**
     * Converts filter rule to regex
     * @param string $rule Filter rule
     * @return string Regex
     */
    protected function prepareFilterRule($rule)
    {
        return '/' . str_replace('/', '\\/', $rule) . '/';
    }

    /**
     * Check if file passes over filters
     * @param \SplFileInfo $file File
     * @return bool True if file passed, false otherwise
     */
    private function filterPass(\SplFileInfo $file)
    {
        $log = function () {
        };
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

    /**
     * Builds loader files
     */
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