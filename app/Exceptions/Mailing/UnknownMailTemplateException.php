<?php

namespace App\Exceptions\Mailing;

use RuntimeException;

class UnknownMailTemplateException extends RuntimeException
{
    public static function forTemplate(string $template): self
    {
        return new self("Unknown mail template [{$template}].");
    }
}
