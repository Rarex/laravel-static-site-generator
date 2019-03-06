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
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'static-site
        {--configFileName= : Config file name within app config directory}
        {--storageDirectoryName= : Directory name within storage Directory}
        {--createdDirectoryPermission= : Chmod permissions for created Directory}
        {--createdFilePermission= : Chmod permissions for newly created files}
        {--createdDirectoryPermission= : Chmod permissions for created directory}
        {--createdFilePermission= : Chmod permissions for newly created files}
    ';

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
     * Parameters to be converted as octal
     *
     * @var array
     */
    protected $octalParamList = [
        'createdFilePermission',
        'createdDirectoryPermission',
    ];

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

    /**
     * Overwrite properties with input options
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->options() as $key => $value) {
            if (is_null($value) || $value === [] || !property_exists($this, $key)) {
                continue;
            }
            if (in_array($value, ['true', 'false'])) {
                $value = $value === 'true';
            }
            if (in_array($key, $this->octalParamList)) {
                $value = intval(base_convert($value, 8, 10));
            }
            $this->$key = $value;
        }

        return parent::execute($input, $output);
    }

    /**
     * Execute the console command.
     */
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
     * Verbose based info log
     *
     * @param $text
     */
    protected function logInfo($text)
    {
        $this->info($text, OutputInterface::VERBOSITY_VERBOSE);
    }

    /**
     * Verbose based warning log
     *
     * @param $text
     */
    protected function logWarning($text)
    {
        $this->warn($text, OutputInterface::VERBOSITY_VERBOSE);
    }

    /**
     * Verbose based table log
     *
     * @param $columns
     * @param $data
     */
    protected function logTable($columns, $data)
    {
        if ($this->option('verbose')) {
            $this->table($columns, $data);
        }
    }

    /**
     * Root directory for static files
     *
     * @param string|null $toFile
     * @return string
     */
    protected function getStoragePath($toFile = null)
    {
        $storagePath = storage_path($this->storageDirectoryName);
        return $toFile ? $storagePath . DIRECTORY_SEPARATOR . $toFile : $storagePath;
    }

    /**
     * Create file and directory if required
     * @param $filePath
     * @param $content
     * @return int
     */
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
