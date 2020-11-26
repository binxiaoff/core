<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\FileVersionSignature;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Result
{
    /**
     * @param Request $request
     *
     * @return void
     */
    public function __invoke(Request $request): void
    {
        // Treat the callback from PSN
    }
}
