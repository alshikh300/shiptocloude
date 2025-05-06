<?php

namespace App\Exceptions;

use Exception;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

class CustomException extends Exception
{
    use ApiResponses;

    public function __construct($message, $code = 400)
    {
        parent::__construct($message, $code);
    }

    public function render($request): JsonResponse
    {
        return $this->error('Exception: '.$this->getMessage(), $this->getCode());
    }
}
