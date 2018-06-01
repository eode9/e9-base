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
     * @param $message
     * @param $data
     * @param $code
     * @return Response
     */
    public function prepareError(Response $response, $message, $data, $code): Response
    {
        try {
            return $response->withJson(JSendResponse::error($message, $code, $data), $code);
        } catch (InvalidJSendException $e) {
            return $response->withJson(['status' => 'error', 'message' => 'Server error'], 500);
        }
    }

    /**
     * @param Response $response
     * @param $data
     * @param $code
     * @return Response
     */
    public function prepareSuccess(Response $response, $data, $code): Response
    {
        try {
            return $response->withJson(JSendResponse::success($data), $code);
        } catch (InvalidJSendException $e) {
            return $response->withJson(['status' => 'error', 'message' => 'Server error'], 500);
        }
    }
}
