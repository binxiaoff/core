<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Functional\Api;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Service\Jwt\StaffPayloadManager;

abstract class AbstractApiTest extends ApiTestCase
{
    protected function createAuthClient(Staff $staff): Client
    {
        $token = $this->getStaffToken($staff);

        return static::createClient([], ['auth_bearer' => $token]);
    }

    private function getStaffToken(Staff $staff): string
    {
        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = static::$container->get(JWTTokenManagerInterface::class);
        /** @var IriConverterInterface $iriConverter */
        $iriConverter = static::$container->get(IriConverterInterface::class);

        $payload['@scope'] = StaffPayloadManager::getScope();
        $payload['staff']  = $iriConverter->getIriFromItem($staff);

        return $jwtManager->createFromPayload($staff->getUser(), $payload);
    }
}
