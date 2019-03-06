<?php

namespace Rarex\LaravelStaticSiteGenerator\Providers;

use Rarex\LaravelStaticSiteGenerator\Console\Commands\StaticSite;
use Rarex\LaravelStaticSiteGenerator\Console\Commands\StaticSiteClean;
use Rarex\LaravelStaticSiteGenerator\Console\Commands\StaticSiteMake;
use Rarex\LaravelStaticSiteGenerator\Console\Commands\StaticSitePublish;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                StaticSite::class,
                StaticSiteMake::class,
                StaticSiteClean::class,
                StaticSitePublish::class,
            ]);
        }
    }
}
