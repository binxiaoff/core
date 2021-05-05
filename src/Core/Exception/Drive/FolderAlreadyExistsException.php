<?php

declare(strict_types=1);

namespace Unilend\Core\Exception\Drive;

use Throwable;

class FolderAlreadyExistsException extends \Exception
{
    public function __construct($message = 'Path already exist', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
