<?php
namespace App\Exceptions;

use RuntimeException;

class ApiException extends RuntimeException
{
    /**
     * @see App\Components\ClientResponse
     */
    const DEFAULT_CODE = 20000;

    public function __construct($message = '', $code = 0, $previous = null)
    {
        /**
         * 该code会在返回的json中直接使用，所以请勿使用0
         */
        if ($code == 0) {
            $code = self::DEFAULT_CODE;
        }
        parent::__construct($message, $code, $previous);
    }
}
