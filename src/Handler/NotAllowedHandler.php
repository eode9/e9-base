<?php
namespace E9\Core\Handler;

use Slim\Handlers\NotAllowed;
use Slim\Views\Twig;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class NotAllowedHandler
 * @package App\Core\Action
 */
class NotAllowedHandler extends NotAllowed {

    /**
     * @var Twig
     */
    private $view;

    /**
     * @var string
     */
    private $templateFile;

    /**
     * NotAllowedHandler constructor.
     * @param Twig $view
     * @param $templateFile
     */
    public function __construct(Twig $view, $templateFile) {
        $this->view = $view;
        $this->templateFile = $templateFile;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $methods
     * @return \Psr\Http\Message\MessageInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $methods) {

        parent::__invoke($request, $response, $methods);

        $this->view->render($response, $this->templateFile, array(
            'methods' => $methods
        ));

        return $response->withStatus(405)
            ->withHeader('Allow', implode(', ', $methods))
            ->withHeader('Content-type', 'text/html');
    }

}