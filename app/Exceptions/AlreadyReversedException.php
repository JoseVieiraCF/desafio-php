<?php

namespace App\Exceptions;

use Exception;

class AlreadyReversedException extends Exception
{
    protected $message = 'Transação já revertida!';
}
