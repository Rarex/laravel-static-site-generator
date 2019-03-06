# Laravel static site generator
Artisan commands to generate static site from Laravel application

##Installation

```
composer require rarex/laravel-static-site-generator
```

##Usage
Run console command:
```php
php artisan static-site -v
```

Include generated file at the beginning of `public/index.php` file:
```php
$staticSiteFile = __DIR__ . '/../storage/static-site/static.php';
if (file_exists($staticSiteFile)) {
    include_once $staticSiteFile;
}
```


####TODO
 * TODO: Add move static files to document root option
 * TODO: Test with older versions of laravel
 * TODO: Write readme
 * TODO: Add tests