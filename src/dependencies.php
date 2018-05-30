<?php

$container = $app->getContainer();

/**
 * Anonymous function that return the view component
 * @param $c
 * @return \Slim\Views\Twig
 */
$container['view'] = function ($c) {

//    $cacheProvider = new DoctrineCacheAdapter(new ArrayCache());
//    $cacheStrategy = new LifetimeCacheStrategy($cacheProvider);
//    $cacheExtension = new CacheExtension($cacheStrategy);

    $settings = $c->get('settings');
    $view = new \Slim\Views\Twig($settings['view']['template_path'], $settings['view']['twig']);

// Add extensions
    $view->addExtension(new Slim\Views\TwigExtension($c->get('router'), $c->get('request')->getUri()));
    $view->addExtension(new Twig_Extension_Debug());
    $view->addExtension(new Twig_Extensions_Extension_Text());
    $view->addExtension(new Twig_Extensions_Extension_I18n());
    $view->addExtension(new Twig_Extensions_Extension_Intl());

    $view->addExtension(new E9\Core\View\TwigExtension($c->get('acl'), $c->get('router'), $c->get('request')->getUri()));


// Zend Form
//    $renderer = new \Zend\View\Renderer\PhpRenderer();
//    $renderer->setHelperPluginManager($renderer);
//
//    $view->getInstance()->registerUndefinedFunctionCallback(
//        function ($name) use ($view, $renderer) {
//            if (!$view->has($name)) {
//                return false;
//            }
//            $callable = [$renderer->plugin($name), '__invoke'];
//            $options  = ['is_safe' => ['html']];
//            return new \Twig_SimpleFunction(null, $callable, $options);
//        }
//    );

// Locale
    $current_lang = $c['session']->get('current_language', 'fr_FR');
    putenv(sprintf('LC_ALL=%s', $current_lang));
    setlocale(LC_ALL, $current_lang);
    $domain = 'messages';
    $pathToDomain = BASE_PATH . '/data/locales';

    if ($pathToDomain !== bindtextdomain($domain, $pathToDomain)) {
        die($pathToDomain);
    }
    bind_textdomain_codeset($domain, 'UTF-8');
    textdomain($domain);

    $view->offsetSet('ALLOW_FEEDBACKS', getenv('ALLOW_FEEDBACKS'));
    $view->offsetSet('user', $c['user']);

    $view->offsetSet('siteName', E9_SITE_NAME);

    $view->offsetSet('current_language', $c['dm']->getRepository(\E9\Core\Document\Lang::class)->findOneBy([
        'iso' => $current_lang
    ]));

    $view->offsetSet('langs', $c['dm']->getRepository(\E9\Core\Document\Lang::class)->findAll());
    $view->offsetSet('settings', $settings);

    return $view;
};

/**
 * Return the flash messages component
 * @param $c
 * @return \Slim\Flash\Messages
 */
$container['flash'] = function ($c) {
    return new \Slim\Flash\Messages;
};

/**
 * Return the Error Handler
 * @param $c
 * @return Closure
 */
$container['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        $data = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => explode("\n", $exception->getTraceAsString()),
        ];

        /** @var \Monolog\Logger $logger */
        $logger = $c['logger'];
        $logger->crit($exception->getMessage(), $data);

        return $c->get('response')->withStatus(500)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($data));
    };
};

$container['phpErrorHandler'] = function ($c) {
    return function ($request, $response, $error) use ($c) {
// retrieve logger from $container here and log the error
        $response->getBody()->rewind();

        $data = [
            'code' => $error->getCode(),
            'message' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => explode("\n", $error->getTraceAsString()),
        ];

        /** @var \Monolog\Logger $logger */
        $logger = $c['logger'];
        $logger->crit($error->getMessage(), $data);

        return $c->get('response')->withStatus(500)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($data));
    };
};

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
// This error code is not included in error_reporting, so ignore it
        return;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});


/**
 * Return the logger component
 * @param $c
 * @return \Monolog\Logger
 */
$container['logger'] = function ($c) {
    $settings = $c->get('settings');
    $logger = new \Monolog\Logger($settings['logger']['name']);
    $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
    $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['logger']['path'], \Monolog\Logger::DEBUG));

    if (getenv('APP_SENTRY_KEY')) {
        $client = new Raven_Client(getenv('APP_SENTRY_KEY'));
        $handler = new Monolog\Handler\RavenHandler($client, \Monolog\Logger::INFO);
        $handler->setFormatter(new Monolog\Formatter\LineFormatter("%message% %context% %extra%\n"));
        $logger->pushHandler($handler);
    }

    return $logger;
};

/**
 * Return the Doctrine ORM component
 * @param $c
 * @return \Doctrine\ODM\MongoDB\DocumentManager
 * @throws \Doctrine\ORM\ORMException
 */
$container['dm'] = function ($c) use ($loader) {

    $settings = $c->get('settings');

    $config = new Doctrine\ODM\MongoDB\Configuration();
    $config->setHydratorDir($settings['doctrine-odm']['meta']['hydrator_dir']);
    $config->setHydratorNamespace($settings['doctrine-odm']['meta']['hydrator_namespace']);
    $config->setProxyDir($settings['doctrine-odm']['meta']['proxy_dir']);
    $config->setProxyNamespace($settings['doctrine-odm']['meta']['proxy_namespace']);
    $config->setMetadataDriverImpl(\Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver::create(
        $settings['doctrine-odm']['meta']['directory_mapping']));
    $config->setRetryConnect(true);
    $config->setDefaultDB($settings['doctrine-odm']['connection']['dbname']);

//    $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ApcuCache());

    \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver::registerAnnotationClasses();

    $dsn = sprintf('mongodb://%s:%s@%s:%s/',
        $settings['doctrine-odm']['connection']['user'],
        $settings['doctrine-odm']['connection']['password'],
        $settings['doctrine-odm']['connection']['server'],
        $settings['doctrine-odm']['connection']['port']
    );

    $connection = new \Doctrine\MongoDB\Connection($dsn, [], $config);

    define('MONGODB_NAME', $settings['doctrine-odm']['connection']['dbname']);

    return \Doctrine\ODM\MongoDB\DocumentManager::create($connection, $config);
};

/**
 * Return the default mailer component
 * @param $c
 * @return Swift_Mailer
 */
$container['mailer'] = function ($c) {
    $settings = $c->get('settings');

    $transport = Swift_SmtpTransport::newInstance($settings['mail']['default']['host'], $settings['mail']['default']['port'])
        ->setUsername($settings['mail']['default']['login'])
        ->setPassword($settings['mail']['default']['password']);

    return Swift_Mailer::newInstance($transport);
};

/**
 * Return the session manager component
 * @param $c
 * @return \RKA\Session
 */
$container['session'] = function ($c) {
    $session = new \RKA\Session();
//    $session::regenerate();
    return $session;
};

$container['JwtAuthentication'] = function ($container) {

    return new \Slim\Middleware\JwtAuthentication([
        'path' => '/api',
        'passthrough' => [
            '/api/v1/auth',
        ],
        'relaxed' => [
            'localhost',
            getenv('APP_DOMAIN')
        ],
        'secret' => getenv('JWT_SECRET'),
        'logger' => $container->get('logger'),
        'secure' => false, // HTTPS ?
//        "algorithm" => ['HS256'],
        'error' => function ($request, $response, $arguments) {
            $data['status'] = 'error';
            $data['message'] = $arguments['message'];
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        },
        'callback' => function ($request, $response, $arguments) use ($container) {
            $container['token']->hydrate($arguments['decoded']);
        }
    ]);
};

$container['Negotiation'] = function ($container) {
    return new \Gofabian\Negotiation\NegotiationMiddleware([
        'accept' => ['application/json']
    ]);
};

$container['cache'] = function ($container) {
    return new \Micheh\Cache\CacheUtil();
};

/**
 * Return the cookie manager component
 * @param $c
 * @return \Slim\Http\Cookies
 */
$container['cookie'] = function ($c) {
    $request = $c->get('request');
    return new \Slim\Http\Cookies($request->getCookieParams());
};

$container['validator'] = function ($c) {
    return Symfony\Component\Validator\Validation::createValidatorBuilder()
        ->enableAnnotationMapping()
        ->getValidator();
};

/**
 * Action factory for "notFoundHandler" Controller
 * @param $c
 * @return \E9\Core\Handler\NotFoundHandler
 */
$container['notFoundHandler'] = function ($c) {
    return new E9\Core\Handler\NotFoundHandler($c->get('view'), 'views/404.twig');
};

/**
 * Action factory for "notAllowedHandler" Controller
 * @param $c
 * @return \E9\Core\Handler\NotAllowedHandler
 */
$container['notAllowedHandler'] = function ($c) {
    return new E9\Core\Handler\NotAllowedHandler($c->get('view'), 'views/405.twig');
};

//$container['App\Core\Action\UploadedDocumentAction'] = function ($c) {
//    return new App\Core\Action\UploadedDocumentAction($c->get('view'), $c->get('logger'), $c->get('datadb'));
//};

