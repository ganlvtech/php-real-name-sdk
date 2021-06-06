<?php

namespace RealNameSdk;

use Exception;

class BadResponseException extends Exception
{
    /** @var \Psr\Http\Message\ResponseInterface */
    public $response;

    public function __construct($message = "", $response = null)
    {
        parent::__construct($message);
        $this->response = $response;
    }
}
