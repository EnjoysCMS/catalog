<?php

use Build\Classes\AnnotationClassLoader;
use DI\ContainerBuilder;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Enjoys\AssetsCollector;
use Enjoys\AssetsCollector\Assets;
use Enjoys\AssetsCollector\Content\Minify\Adapters\CssMinify;
use Enjoys\AssetsCollector\Content\Minify\Adapters\JsMinify;
use Enjoys\AssetsCollector\Extensions\Twig\AssetsExtension;
use Enjoys\Config\Config;
use Enjoys\Cookie\Cookie;
use Enjoys\Cookie\Options;
use Enjoys\Forms\Renderer\Bootstrap4\Bootstrap4;
use Enjoys\Forms\Renderer\RendererInterface;
use Enjoys\Http\ServerRequest;
use Enjoys\Http\ServerRequestInterface;
use Enjoys\Session\Session;
use EnjoysCMS\Core\Components\Breadcrumbs\BreadcrumbsInterface;
use EnjoysCMS\Core\Components\Composer\Utils;
use EnjoysCMS\Core\Components\Doctrine\Hydrators\Column;
use EnjoysCMS\Core\Components\Doctrine\Hydrators\KeyPair;
use EnjoysCMS\Core\Components\Doctrine\Logger as DoctrineLogger;
use EnjoysCMS\Core\Components\Modules\Module;
use EnjoysCMS\Core\Components\TwigExtension\ExtendedFunctions;
use EnjoysCMS\Core\Components\TwigExtension\PickingCssJs;
use EnjoysCMS\Core\Components\TwigExtension\Setting;
use EnjoysCMS\Core\Components\TwigExtension\TwigLoader;
use EnjoysCMS\Core\Error\ErrorInterface;
use EnjoysCMS\Module\Catalog\Controller\Index;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Tree\TreeListener;
use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\Response;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Doctrine\UuidType;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Symfony\Component\Routing\Loader\AnnotationFileLoader;
use Symfony\Component\Routing\Loader\ClosureLoader;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;
use Twig\DeferredExtension\DeferredExtension;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\DebugExtension;
use Twig\TwigFunction;

use function DI\create;
use function DI\factory;
use function DI\get;

$builder = new ContainerBuilder();
//$builder->enableCompilation($_ENV['TEMP_DIR'].'/di');
//$builder->writeProxiesToFile(true, $_ENV['TEMP_DIR'].'/di/proxies');
$builder->useAutowiring(true);
$builder->useAnnotations(false);

$builder->addDefinitions(
    [
        'RequestSymfony' => function () {
            return Request::createFromGlobals();
        },
        ServerRequestInterface::class => create(ServerRequest::class),
        'Modules' => factory(
            function () {
                $catalog = new Module(Utils::parseComposerJson(__DIR__ . '/../composer.json'));
                $catalog->path = __DIR__ . '/..';
                return [
                    $catalog,
//                    new Module(
//                        Utils::parseComposerJson($_ENV['PROJECT_DIR'] . '/vendor/enjoyscms/elfinder/composer.json')
//                    )
                ];
            }
        ),
        RendererInterface::class => function () {
            return new Bootstrap4();
        },
        'Setting' => factory(
            function () {
                return [];
            }
        ),
        BreadcrumbsInterface::class => factory(
            function () {
                return new EnjoysCMS\Core\Components\Breadcrumbs\Breadcrumbs();
            }
        ),
        'Config' => factory(
            function (ContainerInterface $c) {
                //$config->addConfig($configFilename, ['flags' => Yaml::PARSE_CONSTANT], Config::YAML);
                return new Config($c->get(LoggerInterface::class)->withName('Config'));
            }
        ),
        LoggerInterface::class => factory(
            function (ContainerInterface $container) {
                $logger = new Logger('Logger');
                $logger->pushHandler($container->get(BrowserConsoleHandler::class));
                return $logger;
            }
        ),
        BrowserConsoleHandler::class => factory(
            function () {
                $console = new BrowserConsoleHandler();
                $output = "[[%channel%]]{font-weight: bold} [[%level_name%]]{macro: autolabel}  %message% ";
                $formatter = new LineFormatter($output);
                $console->setFormatter($formatter);
                return $console;
            }
        ),
        EntityManager::class => factory(
            function (ContainerInterface $c) {
                $cache = new FilesystemAdapter(directory: __DIR__ . '/.temp/doctrine');
                // $doctrineCacheImpl = new DoctrineProvider($cache);
                $config = new Configuration();
                $config->setMetadataCache($cache);
                $config->setQueryCache($cache);
                // $config->setResultCacheImpl($doctrineCacheImpl);
                $config->setProxyDir(__DIR__ . '/.temp/doctrine_proxy');
                $config->setProxyNamespace('DoctrineProxies');
                $config->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_ALWAYS);
                $config->setMetadataDriverImpl(
                    $config->newDefaultAnnotationDriver(
                        [
                            __DIR__ . "/../src",
                        ],
                        false
                    )
                );
                $config->setSQLLogger(
                    new DoctrineLogger($c->get(LoggerInterface::class)->withName('ORM'))
                );
                // database configuration parameters
                $conn = [
                    'url' => $_ENV['DB_URL']
                ];
                $evm = new EventManager();
                $treeListener = new TreeListener();
                $evm->addEventSubscriber($treeListener);
                $evm->addEventSubscriber(new TimestampableListener());
                // obtaining the entity manager
                $em = EntityManager::create($conn, $config, $evm);
                $em->getConfiguration()->addCustomHydrationMode(
                    'Column',
                    Column::class
                );
                $em->getConfiguration()->addCustomHydrationMode(
                    'KeyPair',
                    KeyPair::class
                );
                Type::addType('uuid', UuidType::class);

                return $em;
            }
        ),
        Session::class => create(Session::class),
        RequestContext::class => create()->method('fromRequest', get('RequestSymfony')),

        UrlGeneratorInterface::class => factory(
            function (ContainerInterface $c) {
                return new UrlGenerator($c->get('Router')->getRouteCollection(), $c->get(RequestContext::class));
            }
        ),
        'Router' => factory(
            function (ContainerInterface $c) {
                $context = $c->get(RequestContext::class);

                $loader = new ClosureLoader();
                return new Router(
                    $loader,
                    function () use ($c) {
                        $routeCollection = new RouteCollection();
                        $fileLocator = new FileLocator([__DIR__ . '/../src']);

                        $loader = new YamlFileLoader($fileLocator);

                        $reader = new AnnotationReader();
                        $annotationClassLoader = new AnnotationClassLoader($reader);

                        $loaderResolver = new LoaderResolver(
                            [

                                new AnnotationFileLoader($fileLocator, $annotationClassLoader),
                                new AnnotationDirectoryLoader($fileLocator, $annotationClassLoader),

                            ]
                        );

                        $loader = new DelegatingLoader($loaderResolver);

                        $routeCollection->addCollection(
                            (new Router(
                                $loader, __DIR__ . '/../src'
                            ))->getRouteCollection()
                        );
                        $routeCollection->addCollection(
                            (new Router(
                                $loader, __DIR__ . '/../vendor/enjoyscms/admin/src'
                            ))->getRouteCollection()
                        );
//                        $routeCollection->addCollection(
//                            (new Router(
//                                $loader, __DIR__ . '/../vendor/enjoyscms/elfinder/src'
//                            ))->getRouteCollection()
//                        );

                        $routeCollection->add('system/index', new Route('/', ['_controller' => [Index::class, 'view']]));

                        return $routeCollection;
                    },
                    [
                        'cache_dir' => __DIR__ . '/.temp',
                        'debug' => true
                    ],
                    $context
                );
            }
        ),
        Assets::class => create(Assets::class)->constructor(
            factory(
                function (ContainerInterface $c) {
                    $environment = new AssetsCollector\Environment('build/web/assets', __DIR__ . '/..');
                    $environment->setBaseUrl('/assets');

                    $environment->setStrategy(Assets::STRATEGY_MANY_FILES);
                    $environment->setLogger($c->get(LoggerInterface::class)->withName('AssetsCollector'));
                    $environment->setMinifyCSS(new CssMinify([]));
                    $environment->setMinifyJS(new JsMinify([]));

                    return $environment;
                }
            )
        ),
        Environment::class => factory(
            function (ContainerInterface $c) {
                $loader = new TwigLoader('/', __DIR__ . '/..');
                $twig = new Environment(
                    $loader,
                    [
                        'debug' => true,
                        'cache' => __DIR__ . '/.temp/twig',
                        'auto_reload' => true,
                        'strict_variables' => false,
                        'auto_escape' => 'html',
                        'optimizations' => 0,
                        'charset' => 'utf8',
                    ]
                );

                $twig->addExtension(new RoutingExtension($c->get(UrlGeneratorInterface::class)));
                $twig->addExtension(new AssetsExtension($c->get(Assets::class)));


                $twig->addExtension(new DebugExtension());


                $twig->addExtension(
                    new class($c) extends AbstractExtension {

                        public function __construct(private $c)
                        {
                        }

                        public function getFunctions(): array
                        {
                            return [
                                new TwigFunction('access', function () {
                                    return true;
                                }, ['is_safe' => ['html']]),
                                new TwigFunction('access2route', function () {
                                    return true;
                                }, ['is_safe' => ['html']]),
                                new TwigFunction('accessInRoutes', function () {
                                    return true;
                                }, ['is_safe' => ['html']]),
                                new TwigFunction(
                                    'getApplicationAdminLinks', function () {
                                    return [];
                                }, ['is_safe' => ['html']]
                                ),
                                new TwigFunction('getModules', function () {
                                    return $this->c->get('Modules');
                                }, ['is_safe' => ['html']]),
                            ];
                        }

                        public function stub($return)
                        {
                            return $return;
                        }
                    }
                );

                $twig->addExtension(new Setting());
                $twig->addExtension(new PickingCssJs());
                $twig->addExtension(new DeferredExtension());
                $twig->addExtension(new ExtendedFunctions());

                $twig->addGlobal('_ENV', $_ENV);
                $twig->addGlobal('_config', $c->get('Config'));
                $twig->addGlobal('_module', $c->get('Modules'));
                return $twig;
            }
        ),
        Cookie::class => factory(
            function () {
                $options = new Options();
                $options->setPath('/'); //default: '' (string empty)
                //$options->setSecure(true); //default: false
                //$options->setHttponly(true); //default: false
                //$options->setSameSite('Strict'); //default: Lax
                return new Cookie($options);
            }
        ),
        'summernote' => factory(
            function () {
                return;
            }
        ),
        ErrorInterface::class =>  factory(
            function(){
                return new class {
                    public function http(int $code, string $message = null): void
                    {
                        $response = new Response($code);
                        $response->getBody()->write(sprintf('%s. %s',$response->getReasonPhrase(), $message));

                        (new SapiEmitter())->emit($response);
                        exit;
                    }
                };
            }
        ),
//        EventDispatcher::class => factory(
//            function (ContainerInterface $container){
//                $dispatcher = new Symfony\Component\EventDispatcher\EventDispatcher();
//                //$dispatcher->addSubscriber(...);
//                return $dispatcher;
//            }
//        )
    ],
);

return $builder->build();
