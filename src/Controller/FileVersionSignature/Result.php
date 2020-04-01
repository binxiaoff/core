<?php

declare(strict_types=1);

namespace Unilend\Controller\FileVersionSignature;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Result
{
    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Treat the callback from PSN
    }
}
