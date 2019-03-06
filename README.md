# Laravel static site generator
Artisan commands to generate static site from Laravel application

## Installation

```
composer require rarex/laravel-static-site-generator
```

## Usage

> If you are using Laravel version lower than 5.5 add following code to your `config/app.php` `providers` array:
>```
>Rarex\LaravelStaticSiteGenerator\Providers\ServiceProvider::class,
>``` 


Run `static-site` artisan console command:
```
php artisan static-site -v
```

Include generated php file at the beginning of your `public/index.php` file:
```php
<?php

$staticSiteFile = __DIR__ . '/../storage/static-site/static.php';
if (file_exists($staticSiteFile)) {
    include_once $staticSiteFile;
}
```

## Configuration 
Run `static-site:publish` artisan console command:
```
php artisan static-site:publish -v
```
`static-site.php` file will be created at application config directory

Parameter | Default Value | Description
------------- | -------------- | --------------
`storageDirectoryName` | `'static-site'` | Directory name within storage directory
`urlList` | `[]` | Custom urls to be converted into static files
`auto` | `true` | Automatically discover routes and generate static files 
`autoRequestMethodList` | `['GET']` | Only routes with specified method will be automatically converted into static files
`autoSkipParametrized` | `true` | Parametrized routes will be skipped on auto generation
`autoSkipCSRFInput` | `true` | Pages with csrf form field will be skipped on auto generation
`autoSkipCSRFMeta` | `true` | Pages with csrf meta tag will be skipped on auto generation
`skipUrlList` | `[]` | Custom urls to be skipped on auto generation
`httpStatusCodeList` | `[200]` | Http status codes to be converted into static files
`rootUrlFileName` | `'_'` | File name for root url like '/'
`createdDirectoryPermission` | `0755` | Permissions for created directory
`createdFilePermission` | `0644` | Permissions for created file
`addGitignoreToStaticDirectory` | `true` | Add .gitignore file static files directory
`staticFileExtension` | `'html'` | Extension will be added to static file name
`prependEchoContent` | `true` | "Echo" output will be prepended to route content (on 'app' get content method)
`defaultGetContentMethod` | `app` | Get content method  `'app'` - use internal app()->handle method, `'curl'` - make curl request 

## Command List 

## `static-site`

Clean destination directory and create static files

```
php artisan static-site
```

Argument | Default Value | Description
------------- | -------------- | -------------- 
`--configFileName`  | `'static-site'` | Config file name within app config directory
`--storageDirectoryName` | `'static-site'` | Directory name within storage directory
`--createdDirectoryPermission` | `0755` | Chmod permissions for created directory
`--createdFilePermission` | `0644` | Chmod permissions for newly created files
`-v` | flag | Display console output
`-n` | flag | Do not ask any interactive question

## `static-site:make`

Create static files

```
php artisan static-site:make
```

Argument | Default Value | Description
------------- | -------------- | -------------- 
`--configFileName`  | `'static-site'` | Config file name within app config directory
`--storageDirectoryName` | `'static-site'` | Directory name within storage directory
`--urlList` | `[]` | Custom urls to be converted into static files
`--auto` | `true` | Automatically discover routes and generate static files 
`--autoRequestMethodList` | `['GET']` | Only routes with specified method will be automatically converted into static files
`--autoSkipParametrized` | `true` | Parametrized routes will be skipped on auto generation
`--autoSkipCSRFInput` | `true` | Pages with csrf form field will be skipped on auto generation
`--autoSkipCSRFMeta` | `true` | Pages with csrf meta tag will be skipped on auto generation
`--skipUrlList` | `[]` | Custom urls to be skipped on auto generation
`--httpStatusCodeList` | `[200]` | Http status codes to be converted to static files
`--rootUrlFileName` | `'_'` | File name for root url like '/'
`--createdDirectoryPermission` | `0755` | Permissions for created directory
`--createdFilePermission` | `0644` | Permissions for created file
`--addGitignoreToStaticDirectory` | `true` | Add .gitignore file static files directory
`--staticFileExtension` | `'html'` | Extension will be added to static file name
`--prependEchoContent` | `true` | "Echo" output will be prepended to route content (on 'app' get content method)
`--defaultGetContentMethod` | `app` | Get content method  `'app'` - use internal app()->handle method, `'curl'` - make curl request
`-v` | flag | Display console output
`-n` | flag | Do not ask any interactive question


## `static-site:clean`

Clean static files directory

```
php artisan static-site:clean
```

Argument | Default Value | Description
------------- | -------------- | --------------
`--configFileName`  | `'static-site'` | Config file name within app config directory
`--storageDirectoryName` | `'static-site'` | Directory name within storage directory
`-v` | flag | Display console output
`-n` | flag | Do not ask any interactive question

 
## `static-site:publish`

Create new config file with default parameters or merge with existing config file

```
php artisan static-site:publish
```

Argument | Default Value | Description
------------- | -------------- | --------------
`--configFileName`  | `'static-site'` | Config file name within app config directory
`-new` | flag | Force to overwrite existing config file 
`-v` | flag | Display console output


    
    
## TODO
 * TODO: Test with older versions of laravel
 * TODO: Add tests
