<?php

namespace App\Exceptions;

use Exception;

class InsufficientStaminaException extends Exception
{
    // Você pode personalizar mensagens padrão, ou métodos extras se quiser
    public function __construct($message = "Stamina insuficiente para realizar a ação.", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
