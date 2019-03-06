<?php

namespace Rarex\LaravelStaticSiteGenerator\Console\Commands;

use Illuminate\Support\Facades\File;

/**
 * Class StaticSiteClean
 *
 * @package App\Console\Commands
 */
class StaticSiteClean extends StaticSite
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'static-site:clean
            {--configFileName= : Config file name within app config directory}
            {--storageDirectoryName= : Directory name within Laravel storage Directory}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean static files directory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->cleanStaticFilesDirectory();
    }

    /**
     * Clean static files directory
     */
    protected function cleanStaticFilesDirectory()
    {
        $dirPath = $this->getStoragePath();
        if (File::isDirectory($dirPath)) {
            if ($this->confirm($this->getStoragePath() . ' directory will be cleaned. Do you wish to continue?', true)) {
                $this->logInfo('Clean: ' . $dirPath);
                File::cleanDirectory($dirPath);
            }
        }
    }
}
