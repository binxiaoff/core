<?php

declare(strict_types=1);

namespace Unilend\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class HealthCheck
{
    /**
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['status' => 'available'], JsonResponse::HTTP_OK);
    }
}
