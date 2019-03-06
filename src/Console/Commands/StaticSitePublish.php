<?php

namespace Rarex\LaravelStaticSiteGenerator\Console\Commands;

use Illuminate\Support\Facades\File;

/**
 * Class StaticSiteClean
 *
 * @package App\Console\Commands
 */
class StaticSitePublish extends StaticSite
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'static-site:publish
        {--configFileName= : Config file name within app config directory}
        {--new : New config file will be generated, otherwise current config will be merged with default values}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create config file with default params';

    /**
     * Parameters to be skipped on config generation
     *
     * @var array
     */
    protected $skipParamList = [
        'signature',
        'description',
        'configFileName',
        'octalParamList',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $config = $this->generateDefaultConfig();
        if (!$this->option('new')) {
            $config = $this->mergeWithCurrentConfig($config);
        }
        $this->writeConfigFile($config);
    }

    /**
     * Write config array to config file
     *
     * @param $config
     * @return int
     */
    protected function writeConfigFile($config)
    {
        return $this->createFile($this->getCurrentConfigFilePath(), $this->generateConfigFileContent($config));
    }

    /**
     * Return config file path
     */
    protected function getCurrentConfigFilePath()
    {
        return config_path($this->configFileName) . '.php';
    }

    /**
     * Generate default config
     *
     * @return array
     */
    protected function generateDefaultConfig()
    {
        $classList = [
            StaticSite::class,
            StaticSiteClean::class,
            StaticSiteMake::class,
        ];
        $config = [];
        foreach ($classList as $class) {
            try {
                $reflect = new \ReflectionClass($class);
            } catch (\Exception $e) {
                $this->logWarning($e->getMessage());
                continue;
            }
            $properties = $reflect->getProperties(\ReflectionProperty::IS_PROTECTED);
            $defaultValues = $reflect->getDefaultProperties();
            foreach ($properties as $property) {
                if ($property->getDeclaringClass()->getName() !== $class) {
                    continue;
                }
                $propertyName = $property->getName();
                if (in_array($propertyName, $this->skipParamList)) {
                    continue;
                }
                if (!isset($defaultValues[$propertyName])) {
                    continue;
                }

                $config[$propertyName] = [
                    'value' => $defaultValues[$propertyName],
                    'comments' => $property->getDocComment(),
                ];
            }
        }

        return $config;
    }

    /**
     * Merge default config with existing config file
     *
     * @param $config
     * @return mixed
     */
    protected function mergeWithCurrentConfig($config)
    {
        $currentConfigFilePath = $this->getCurrentConfigFilePath();
        if (File::isFile($currentConfigFilePath)) {
            $currentConfig = include $currentConfigFilePath;
            if (is_array($currentConfig)) {
                foreach ($currentConfig as $key => $value) {
                    if (isset($config[$key])) {
                        $config[$key]['value'] = $value;
                    }
                }
            }
        };

        return $config;
    }

    /**
     * Generate config file content
     *
     * @param $config
     * @return array|string
     */
    protected function generateConfigFileContent($config)
    {
        $content = ["<?php\n\n return [\n"];
        $space = '    ';
        foreach ($config as $key => $property) {
            $content [] = $space . trim($property['comments']);
            if (in_array($key, $this->octalParamList)) {
                $value = '0' . decoct($property['value']);
            } else {
                $value = $this->export_variable($property['value'], $space);
            }
            $content [] = $space . '"' . $key . '" => ' . $value . ',';
            $content [] = '';
        }
        $content [] = "];\n";
        $content = implode("\n", $content);
        return $content;
    }

    /**
     * Custom var_export realization
     *
     * @param $var
     * @param string $indent
     * @return mixed|string
     */
    private function export_variable($var, $indent = "")
    {
        switch (gettype($var)) {
            case "string":
                return '"' . addcslashes($var, "\\\$\"\r\n\t\v\f") . '"';
            case "array":
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = "$indent    "
                        . ($indexed ? "" : $this->export_variable($key) . " => ")
                        . $this->export_variable($value, "$indent    ");
                }
                return "[\n" . implode(",\n", $r) . "\n" . $indent . "]";
            case "boolean":
                return $var ? "TRUE" : "FALSE";
            default:
                return var_export($var, TRUE);
        }
    }
}
