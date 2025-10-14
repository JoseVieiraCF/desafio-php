<?php

namespace App\Exceptions;

use Exception;

class RecipientUserNotFound extends Exception
{
    protected $message = 'Usuário destinatário não encontrado!'; 
}
