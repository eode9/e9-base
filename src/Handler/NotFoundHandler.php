<?php
namespace E9\Core\Handler;

use Slim\Handlers\NotFound;
use Slim\Views\Twig;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class NotFoundHandler extends NotFound
{
    /**
     * @var Twig
     */
    private $view;

    /**
     * @var string
     */
    private $templateFile;

    /**
     * NotFoundHandler constructor.
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
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response) {
        parent::__invoke($request, $response);

        $this->view->render($response, $this->templateFile);

        return $response->withStatus(404);
    }

}