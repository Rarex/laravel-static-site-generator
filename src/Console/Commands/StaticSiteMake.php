<?php

namespace Rarex\LaravelStaticSiteGenerator\Console\Commands;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

/**
 * Class StaticSiteMake
 *
 * @package App\Console\Commands
 */
class StaticSiteMake extends StaticSite
{
    const GET_CONTENT_METHOD_APP = 'app';
    const GET_CONTENT_METHOD_CURL = 'curl';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'static-site:make
        {--configFileName= : Config file name within app config directory}
        {--storageDirectoryName= : Directory name within storage directory}
        {--urlList=* : Urls to be converted into static files}
        {--skipUrlList=* : Urls to be skipped on auto generation}
        {--auto= : Automatically discover routes
        {--autoRequestMethodList=* : Only routes with specified method will be automatically converted to static files}
        {--autoSkipParametrized= : Parametrized routes will not be automatically converted to static files}
        {--autoSkipCSRFInput= : Pages with csrf forms will be skipped on auto generation}
        {--autoSkipCSRFMeta= : Pages with csrf meta tag will be skipped on auto generation}
        {--httpStatusCodeList=* : Http status codes to be converted to static files}
        {--addGitignoreToStaticDirectory= : Add .gitignore file static files directory}
        {--staticFileExtension= : Extension will be added to static file name}
        {--prependEchoContent= : "Echo" output will be prepended to route content}
        {--defaultGetContentMethod= : Get content method (app/curl)}
        {--rootUrlFileName= : File name for root url like \'/\'}
        {--createdDirectoryPermission= : Chmod permissions for created directory}
        {--createdFilePermission= : Chmod permissions for newly created files}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generating Static Site for specified urls';

    /**
     * Urls to be converted into static files
     *
     * @var string[]
     */
    protected $urlList = [];

    /**
     * Urls to be skipped on auto generation
     *
     * @var array
     */
    protected $skipUrlList = [];

    /**
     * Automatically discover routes
     *
     * @var bool
     */
    protected $auto = true;

    /**
     * Only routes with specified method will be automatically converted to static files
     *
     * @var bool
     */
    protected $autoRequestMethodList = ['GET'];

    /**
     * Parametrized routes will not be automatically converted to static files (not much sense to cache this routes)
     * You may pass urlList with specified parameters or overwrite getUrlList method for complex logic
     *
     *
     * @var bool
     */
    protected $autoSkipParametrized = true;

    /**
     * Pages with csrf forms will be skipped on auto generation
     *
     * @var bool
     */
    protected $autoSkipCSRFInput = true;

    /**
     * Pages with csrf meta tag will be skipped on auto generation
     *
     * @var bool
     */
    protected $autoSkipCSRFMeta = true;

    /**
     * Http status codes to be converted to static files
     *
     * @var array
     */
    protected $httpStatusCodeList = [200];

    /**
     * File name for root url like '/'
     *
     * @var string
     */
    protected $rootUrlFileName = '_';

    /**
     * Add .gitignore file static files directory
     *
     * @var bool
     */
    protected $addGitignoreToStaticDirectory = true;

    /**
     * Extension will be added to static file name
     *
     * Set false
     *
     * @var string|boolean|null
     */
    protected $staticFileExtension = 'html';

    /**
     * "Echo" output will be prepended to route content
     *
     * @var bool
     */
    protected $prependEchoContent = true;

    /**
     * Get content method
     * 'app' - through app()->handle method
     * 'curl' - through curl request
     *
     * @var 'app'|'curl'
     */
    protected $defaultGetContentMethod = self::GET_CONTENT_METHOD_APP;

    /**
     * @var Router;
     */
    private $router;

    /**
     * @var Request
     */
    private $request;

    /**
     * Create a new command instance.
     *
     * @param Router $router
     * @param Request $request
     */
    public function __construct(Router $router, Request $request)
    {
        parent::__construct();

        $this->router = $router;
        $this->request = $request;

        $this->autoRequestMethodList = array_map(function ($requestMethod) {
            return strtolower($requestMethod);
        }, $this->autoRequestMethodList);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $result = $this->generateStaticFiles();
        $this->createIncludeFile($result);
        if ($this->addGitignoreToStaticDirectory) {
            $this->createGitignoreFile();
        }
        $this->logGeneratedFiles($result);
    }

    /**
     * Obtain route content and write it to static files
     *
     * @return array
     */
    protected function generateStaticFiles()
    {
        $result = [];
        $urlList = $this->getUrlList();
        foreach ($urlList as $urlData) {
            $url = $urlData['url'];
            $getContentMethod = $urlData['method'];
            if (in_array($url, $this->skipUrlList)) {
                continue;
            }
            $fileName = $this->convertUrlToFileName($url);
            $filePath = $this->getStoragePath($fileName);
            $contentData = $this->getUrlContent($url, $getContentMethod);
            $resultItem = [
                'url' => $url,
                'statusCode' => $contentData['statusCode'],
                'message' => $contentData['message'],
                'staticFilePath' => $filePath,
                'fileName' => $fileName,
                'isCached' => null,
                'method' => $getContentMethod,
            ];
            $content = $contentData['content'];
            if (in_array($contentData['statusCode'], $this->httpStatusCodeList)) {
                $csrfInputRegExp = '/<input([^>])*name=["\']_token["\']([^>])*>/i';
                $csrfMetaRegExp = '/<meta([^>])*name=["\']csrf-token["\']([^>])*>/i';
                $isCsrfInput = preg_match($csrfInputRegExp, $content) == 1;
                $isCsrfMeta = preg_match($csrfMetaRegExp, $content) == 1;
                if ($this->auto) {
                    if ($this->autoSkipCSRFInput && $isCsrfInput) {
                        $resultItem['isCached'] = false;
                        $resultItem['message'] = 'Skipped by CSRF Input';
                    } elseif ($this->autoSkipCSRFMeta && $isCsrfMeta) {
                        $resultItem['isCached'] = false;
                        $resultItem['message'] = 'Skipped by CSRF Meta';
                    } else {
                        $isCached = $this->writeCachedContent($filePath, $content);
                        $resultItem['isCached'] = $isCached ? true : false;
                    }
                } else {
                    if ($isCsrfInput) {
                        $this->logWarning('CSRF token input found in page content at ' . $url);
                    } else if ($isCsrfMeta) {
                        $this->logWarning('CSRF meta tag found in page content at ' . $url);
                    }
                    $isCached = $this->writeCachedContent($filePath, $content);
                    $resultItem['isCached'] = $isCached ? true : false;
                }
            } else {
                $resultItem['isCached'] = false;
                $resultItem['message'] = 'Skipped by Status Code';
            }

            $result [] = $resultItem;
        }

        return $result;
    }

    /**
     * Url List to converted to static files
     *
     * @return array
     */
    protected function getUrlList()
    {
        $urlList = $this->urlList;
        if ($this->auto) {
            $urlList = array_merge($this->getRouteUrlList(), $urlList);
        }
        $result = [];
        foreach ($urlList as $url) {
            $item = [
                'url' => $url,
                'method' => $this->defaultGetContentMethod
            ];
            if (is_array($url)) {
                $item['url'] = $url[0];
                $item['method'] = isset($url[1]) ? $url[1] : $this->defaultGetContentMethod;
            }
            $item['url'] = $this->prepareUrl($item['url']);
            $result[$item['url']] = $item;
        }

        usort($result, function ($a, $b) {
            return strcmp($a['url'], $b['url']);
        });

        return $result;
    }

    /**
     * Prepend url with /
     *
     * @param $url
     * @return string
     */
    protected function prepareUrl($url)
    {
        return '/' . ltrim($url, '/');
    }

    /**
     * List of urls from filtered routes
     *
     * @return array
     */
    protected function getRouteUrlList()
    {
        return array_values(collect($this->router->getRoutes())->filter(function (Route $route) {
            return $this->filterRoute($route);
        })->map(function (Route $route) {
            return $route->uri();
        })->all());
    }

    /**
     * Filter route from being converted to static files
     *
     * @param Route $route
     * @return bool
     */
    protected function filterRoute(Route $route)
    {
        $isInAutoRequestMethodList = false;
        foreach ($route->methods as $requestMethod) {
            if (in_array(strtolower($requestMethod), $this->autoRequestMethodList)) {
                $isInAutoRequestMethodList = true;
                break;
            }
        }
        if (!$isInAutoRequestMethodList) {
            return false;
        }
        if ($this->autoSkipParametrized && count($route->parameterNames()) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Obtain url content through app()->handle or curl(for 301, 302 statuses)
     *
     * @param $url
     * @param $getContentMethod 'app' or 'curl'
     * @return array|mixed
     */
    protected function getUrlContent($url, $getContentMethod)
    {
        if ($getContentMethod == self::GET_CONTENT_METHOD_APP) {
            return $this->getAppHandleContent($url);
        } else {
            return $this->getHttpRequestContent($url);
        }
    }

    /**
     * Get route content through app->handle method
     *
     * @param $url
     * @return array
     */
    protected function getAppHandleContent($url)
    {
        $data = [
            'content' => '',
            'statusCode' => '',
            'message' => ''
        ];
        $request = Request::create($url);
        $request->headers->set('HOST',
            $this->request->headers->get('HOST')
        );
        try {
            ob_start();
            /** @var Response $response */
            $response = app()->handle($request);
            $echoContent = ob_get_contents();
            ob_end_clean();
            if ($this->prependEchoContent) {
                $data['content'] = $echoContent;
            }
            $data['content'] .= $response->getContent();
            $data['statusCode'] = $response->getStatusCode();
            $data['message'] = 'Ok';
        } catch (\Exception $exception) {
            $data['statusCode'] = $exception->getCode();
            $data['message'] = $exception->getMessage();
        }

        return $data;
    }

    /**
     * Add parameter to skip including of static files
     *
     * @param $url
     * @return string
     */
    protected function getHttpRequestUrl($url)
    {
        $url = url($url);
        return $url . (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'skipStaticFileInclude';
    }

    /**
     * Get route content through curl request
     *
     * @param $url
     * @return mixed
     */
    protected function getHttpRequestContent($url)
    {
        $url = url($url);

        $data = [
            'content' => '',
            'statusCode' => '',
            'message' => 'Ok'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $data['content'] = curl_exec($ch);
        $data['statusCode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($data['content'] === false) {
            $data['content'] = '';
            $data['message'] = curl_error($ch);
        }

        return $data;
    }

    /**
     * Write content to static files directory
     *
     * @param $filePath
     * @param $content
     * @return int
     */
    protected function writeCachedContent($filePath, $content)
    {
        return $this->createFile($filePath, $content);
    }

    /**
     * Convert url to escaped filename with configured extension
     *
     * @param $url
     * @return string
     */
    protected function convertUrlToFileName($url)
    {
        $url = ltrim($url, '/');
        $url = preg_replace('/[^A-Za-z0-9_\/\-]/', '_', $url);
        $url = $url === '' ? $this->rootUrlFileName : $url;
        $extension = $this->staticFileExtension;

        return is_string($extension) ? $url . '.' . $extension : $url;
    }

    /**
     * Create file for inclusion into index.php
     *
     * @param $contentGenerationResult
     * @return int
     */
    protected function createIncludeFile($contentGenerationResult)
    {
        return $this->createFile($this->getStoragePath('static.php'), $this->generateIncludeFileContent($contentGenerationResult));
    }

    /**
     * Generate content for static.php
     * Should be include into beginning of index.php
     *
     * @param $result
     * @return string
     */
    protected function generateIncludeFileContent($result)
    {
        $staticFileList = [];
        foreach ($result as $item) {
            $staticFileList[$item['url']] = $item['fileName'];
        }
        $staticFileContent = '<?php
$staticStoragePath = __DIR__;
$staticFileList = ' . var_export($staticFileList, true) . ';
if (isset($staticFileList[$_SERVER["REQUEST_URI"]])) {
    $filePath = $staticStoragePath . DIRECTORY_SEPARATOR . $staticFileList[$_SERVER["REQUEST_URI"]];
    if (file_exists($filePath)) {
        readfile($filePath);
        die;
    }
}';
        return $staticFileContent;
    }

    /**
     * Create .gitignore file within static files directory
     *
     * @return int
     */
    protected function createGitignoreFile()
    {
        return $this->createFile($this->getStoragePath('.gitignore'), $this->generateGitignoreFileContent());
    }

    /**
     * Generate content for .gitignore
     * (if addGitignoreToStaticDirectory is true)
     *
     * @return string
     */
    protected function generateGitignoreFileContent()
    {
        return "*\n!.gitignore\n";
    }

    /**
     * Log generated files information into console
     *
     * @param $files
     */
    protected function logGeneratedFiles($files)
    {
        $cachedFiles = array_filter($files, function ($item) {
            return $item['isCached'];
        });
        $notCachedFiles = array_filter($files, function ($item) {
            return !$item['isCached'];
        });
        $this->logInfo("Successfully cached:");
        $this->logTable(['Url', 'Status', 'File', 'Method'], array_map(function ($item) {
            return [
                $item['url'],
                $item['statusCode'],
                $item['staticFilePath'],
                $item['method'],
            ];
        }, $cachedFiles));

        $this->logInfo("Not cached:");
        $this->logTable(['Url', 'Status', 'Message', 'Method'], array_map(function ($item) {
            return [
                $item['url'],
                $item['statusCode'],
                $item['message'],
                $item['method'],
            ];
        }, $notCachedFiles));
    }
}
