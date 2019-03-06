<?php

namespace Rarex\LaravelStaticSiteGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StaticSiteBase
 *
 * @package App\Console\Commands
 */
class StaticSite extends Command
{
    protected $signature = 'static-site';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Base class for StaticSite commands';

    /**
     * Config file name within app config directory
     *
     * @var string
     */
    protected $configFileName = 'static-site';

    /**
     * Directory name within storage Directory
     *
     * @var string
     */
    protected $storageDirectoryName = 'static-site';

    /**
     * Chmod permissions for created Directory
     *
     * @var int
     */
    protected $createdDirectoryPermission = 0755;

    /**
     * Chmod permissions for created file
     *
     * @var int
     */
    protected $createdFilePermission = 0644;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $config = config($this->configFileName);
        if ($config && is_iterable($config)) {
            foreach ($config as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }

        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->options() as $key => $value) {
            if (is_null($value) || $value === [] || !property_exists($this, $key)) {
                continue;
            }
            if (in_array($value, ['true', 'false'])) {
                $value = $value === 'true';
            }
            $this->$key = $value;
        }

        return parent::execute($input, $output);
    }

    public function handle()
    {
        $this->logInfo('Start static-site:clean');
        $this->call('static-site:clean');
        $this->logInfo('End static-site:clean');

        $this->logInfo('------------');

        $this->logInfo('Start static-site:make');
        $this->call('static-site:make');
        $this->logInfo('End static-site:make');
    }

    /**
     * Verbose only log function
     *
     * @param $text
     */
    protected function logInfo($text)
    {
        $this->info($text, OutputInterface::VERBOSITY_VERBOSE);
    }

    protected function logWarning($text)
    {
        $this->warn($text, OutputInterface::VERBOSITY_VERBOSE);
    }

    protected function logTable($columns, $data)
    {
        if ($this->option('verbose')) {
            $this->table($columns, $data);
        }
    }

    /**
     * Root director for static files
     *
     * @return string
     */
    protected function getStoragePath($toFile = null)
    {
        $storagePath = storage_path($this->storageDirectoryName);
        return $toFile ? $storagePath . DIRECTORY_SEPARATOR . $toFile : $storagePath;
    }

    protected function createFile($filePath, $content)
    {
        $this->logInfo('Create: ' . $filePath);

        $dirPath = dirname($filePath);
        if (!File::isDirectory($dirPath)) {
            $this->logInfo('Create: ' . $dirPath);
            File::makeDirectory($dirPath, $this->createdDirectoryPermission, true);
        }

        $result = File::put($filePath, $content);
        File::chmod($filePath, $this->createdFilePermission);

        return $result;
    }
}
