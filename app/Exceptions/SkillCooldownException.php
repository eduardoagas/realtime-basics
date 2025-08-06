<?php

namespace App\Exceptions;

use Exception;

class SkillCooldownException extends Exception
{
    public function __construct($message = "Skill em cooldown, aguarde para usar novamente.", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
