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

    $view->offsetSet('current_language', $c['em']->getRepository(\E9\Core\Entity\Lang::class)->findOneBy([
        'iso' => $current_lang
    ]));

    $view->offsetSet('langs', $c['em']->getRepository(\E9\Core\Entity\Lang::class)->findAll());
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
 * Return MongoDB database
 * @param $c
 * @return MongoDB\Database
 */
$container['datadb'] = function ($c) {
    $connection = new \MongoDB\Client();
    return $connection->selectDatabase('app');
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
 * @return \Doctrine\ORM\EntityManager
 * @throws \Doctrine\ORM\ORMException
 */
$container['em'] = function ($c) use ($loader) {

    $settings = $c->get('settings');

    $config = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
        $settings['doctrine']['meta']['entity_path'],
        $settings['doctrine']['meta']['auto_generate_proxies'],
        $settings['doctrine']['meta']['proxy_dir'],
        $settings['doctrine']['meta']['cache'],
        false
    );

    $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ApcuCache());

// Add Asserts annotations, etc.
    \Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

    /* Start Model refactoring */
//    $evm = new \Doctrine\Common\EventManager;
//    $rtel = new \Doctrine\ORM\Tools\ResolveTargetEntityListener;

// Adds a target-entity class
//    $rtel->addResolveTargetEntity(
//        'App\\Workspace\\Model\\WorkspaceUserInterface',
//        'App\\Core\\Entity\\User', array());

// Add the ResolveTargetEntityListener
//    $evm->addEventListener(Doctrine\ORM\Events::loadClassMetadata, $rtel);
    /* End Model refactoring */

// Return entity manager
//    return \Doctrine\ORM\EntityManager::create($settings['doctrine']['connection'], $config, $evm);
    return \Doctrine\ORM\EntityManager::create($settings['doctrine']['connection'], $config);
};

/**
 * Return the Doctrine ORM component
 * @param $c
 * @return \Doctrine\ODM\MongoDB\DocumentManager
 * @throws \Doctrine\ORM\ORMException
 */
$container['dm'] = function ($c) use ($loader) {

    $settings = $c->get('settings');

    $connection = new \Doctrine\MongoDB\Connection(
        new MongoClient()
    );

    $config = new Doctrine\ODM\MongoDB\Configuration();
    $config->setHydratorDir($settings['doctrine-odm']['meta']['hydrator_dir']);
    $config->setHydratorNamespace($settings['doctrine-odm']['meta']['hydrator_namespace']);
    $config->setProxyDir($settings['doctrine-odm']['meta']['proxy_dir']);
    $config->setProxyNamespace($settings['doctrine-odm']['meta']['proxy_namespace']);

    $evm = new \Doctrine\Common\EventManager;
//    $rtdl = new \Doctrine\ODM\MongoDB\Tools\ResolveTargetDocumentListener;

// Adds a target-document class
//    $rtdl->addResolveTargetDocument(
//        'Acme\\InvoiceModule\\Model\\InvoiceSubjectInterface',
//        'Acme\\CustomerModule\\Document\\Customer',
//        array()
//    );

// Add the ResolveTargetDocumentListener
//    $evm->addEventListener(\Doctrine\ODM\MongoDB\Events::loadClassMetadata, $rtdl);

// Create the document manager as you normally would
    return \Doctrine\ODM\MongoDB\DocumentManager::create($connection, $config, $evm);
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

//    if (APP_ENV == 'prod') {
//        $transport = Swift_SmtpTransport::newInstance($settings['mail']['prod']['host'], $settings['mail']['prod']['port'])
//            ->setUsername($settings['mail']['prod']['login'])
//            ->setPassword($settings['mail']['prod']['password']);
//    } else {
//        $transport = Swift_SmtpTransport::newInstance($settings['mail']['dev']['host'], $settings['mail']['dev']['port'])
//            ->setUsername($settings['mail']['dev']['login'])
//            ->setPassword($settings['mail']['dev']['password']);
//    }

    return Swift_Mailer::newInstance($transport);
};

/**
 * Return the session manager component
 * @param $c
 * @return \RKA\Session
 */
$container['session'] = function ($c) {
    $session = new \RKA\Session();
    $session::regenerate();
    return $session;
};


$container['JwtAuthentication'] = function ($container) {

    return new \Slim\Middleware\JwtAuthentication([
        'path' => '/api',
        'passthrough' => [
//            '/api/token',
            '/api/v1/auth',
            '/blog/auto-draft',
            '/api/v1/register',
            '/api/v1/user-exists',
            '/api/v1/forgot-password',
            '/api/v1/reset-password-info',
            '/api/v1/reset-password',
            '/api/v1/input-value',
            '/api/v1/consumption-value',
            '/api/v1/consumption',
            '/api/v1/input',
            '/api/v1/output',
            '/api/v1/resource',
        ],
        'secret' => 'secret',
        'relaxed' => ['localhost', 'app.dev'],
//        'secret' => getenv('JWT_SECRET'),
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


/**
 * Action factory for "notFoundHandler" Controller
 * @param $c
 * @return \App\Core\Handler\NotFoundHandler
 */
$container['notFoundHandler'] = function ($c) {
    return new E9\Core\Handler\NotFoundHandler($c->get('view'), 'views/404.twig', function ($request, $response) use ($c) {
        return $c['response']->withStatus(404);
    });
};

/**
 * Action factory for "notAllowedHandler" Controller
 * @param $c
 * @return \App\Core\Handler\NotAllowedHandler
 */
$container['notAllowedHandler'] = function ($c) {
    return new App\Core\Handler\NotAllowedHandler($c->get('view'), 'views/405.twig', function ($request, $response, $methods) use ($c) {
        return $c['response']->withStatus(405);
    });
};

//$container['App\Core\Action\UploadedDocumentAction'] = function ($c) {
//    return new App\Core\Action\UploadedDocumentAction($c->get('view'), $c->get('logger'), $c->get('datadb'));
//};

/**
 * Modules dependencies
 */
foreach (ENABLED_MODULES as $module) {
    $filename = sprintf('%s/modules/%s/dependencies.php', BASE_PATH, $module);
    if (is_file($filename)) {
        require_once $filename;
    } else {
        die('Config file missing : ' . $filename);
    }
}
