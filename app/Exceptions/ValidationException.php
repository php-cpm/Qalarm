<?php
namespace App\Exceptions;

use Illuminate\Validation\Validator;

class ValidationException extends ApiException
{
    public function __construct($validator, $code = 1)
    {
        if ($validator instanceof Validator) {
            $message = $validator->messages()->first();
        } else {
            $message = (string) $validator;
        }

        parent::__construct($message, self::DEFAULT_CODE+$code, null);
    }
}
