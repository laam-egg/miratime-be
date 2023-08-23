<?php

namespace App\Exceptions;

use Exception;

class HttpException extends Exception
{
    protected $status;

    public function getStatus() {
        return $this->status;
    }

    public function __construct($status, $message) {
        $this->status = $status;
        $this->message = $message;
    }
}
