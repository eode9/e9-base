<?php
/**
 * Created by PhpStorm.
 * User: Eode9
 * Date: 17/05/2018
 * Time: 17:58
 */

/**
 * CLI
 */
$app->add(new \pavlakis\cli\CliRequest());

/**
 * CORS
 */
$app->add(function (\Slim\Http\Request $request, \Slim\Http\Response $response, $next) {
    $newResponse = $response->withHeader('Access-Control-Allow-Origin', getenv('FRONT_BASE_URL'))
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Allow-Headers', array('Content-Type', 'X-Requested-With', 'Authorization'))
        ->withHeader('Access-Control-Allow-Methods', array('GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'));

    if ($request->isOptions()) {
        return $newResponse;
    }

    return $next($request, $newResponse);
});

/**
 * Session
 */
$app->add(new \RKA\SessionMiddleware(['name' => getenv('APP_NAME')]));

/**
 * CSRF
 */
//$guard = new \Slim\Csrf\Guard('csrf', $_SESSION);
//$guard->setFailureCallable(function ($request, $response, $next) {
//    $request = $request->withAttribute('csrf_result', false);
//    return $next($request, $response);
//});
//$app->add($guard);

/**
 * Not found handler
 */
$app->add(function ($request, $response, $next) {
    try {
        $response = $next($request, $response);
    } catch (Slim\Exception\NotFoundException $e) {
        $notFoundHandler = $this->get('notFoundHandler');
        return $notFoundHandler($request->withAttribute('message', $e->getMessage()), $response);
    }
    return $response;
});

/**
 * ACL
 */
//$app->add(function (\Slim\Http\Request $request, $response, $next) use ($app) {
//
//    $route = $request->getAttribute('route');
//
//    if ($route === null) {
//        throw new Slim\Exception\NotFoundException($request, $response, array());
//    }
//
//    $args = $route->getArguments();
//
//    if (array_key_exists('_resource', $args) && array_key_exists('_privilege', $args)) {
//        $session = $app->getContainer()->get('session');
//        $user = $app->getContainer()->get('user');
//
//        if (!$user) {
//            $session->delete('user');
//            $app->getContainer()->get('flash')->addMessage('errors', 'Invalid user. Please login.');
//            return $response->withStatus(302)->withHeader('Location', '/login');
//        }
//
//        $acl = $app->getContainer()->get('acl');
//        try {
//            if (!$user->isSuperAdmin() && !$acl->isAllowed($user->getUuid(), $args['_resource'], $args['_privilege'])) {
//                throw new \Slim\Exception\MethodNotAllowedException($request, $response, array('Unauthorized access'));
//            }
//        } catch (Slim\Exception\MethodNotAllowedException $e) {
//            $notAllowedHandler = $this->get('notAllowedHandler');
//            return $notAllowedHandler(
//                $request->withAttribute('message', $e->getMessage()),
//                $response,
//                array('Unauthorized access')
//            );
//        }
//    }
//
//    return $next($request, $response);
//});

$app->add('JwtAuthentication');
$app->add('Negotiation');