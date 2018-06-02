<?php

namespace E9\Core\Action;

use JSend\InvalidJSendException;
use JSend\JSendResponse;
use Slim\Http\Response;

/**
 * Class APIAbstractAction
 * @package App\Core\Action
 */
abstract class AbstractAPIAction extends AbstractAction
{
    /**
     * @param Response $response
     * @param array $error
     * @param integer $code
     * @return Response
     */
    public function prepareError(Response $response, array $error, int $code): Response
    {
        $errorMessage = $error['message'] ?? null;
        $errorCode = $error['code'] ?? null;
        $data = $error['data'] ?? null;

        try {
            return $response->withJson(JSendResponse::error($errorMessage, $errorCode, $data), $code);
        } catch (InvalidJSendException $e) {
            return $response->withJson(['status' => 'error', 'message' => 'System issue'], 500);
        }
    }

    /**
     * @param Response $response
     * @param array $data
     * @param int $code
     * @return Response
     */
    public function prepareFail(Response $response, array $data, int $code): Response
    {
        return $response->withJson(JSendResponse::fail($data), $code);
    }

    /**
     * @param Response $response
     * @param array $data
     * @param int $code
     * @return Response
     */
    public function prepareSuccess(Response $response, array $data, int $code): Response
    {
        return $response->withJson(JSendResponse::success($data), $code);
    }
}
