<?php

declare(strict_types=1);

namespace KLS\Core\Service\Jwt;

use KLS\Core\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface PayloadManagerInterface
{
    public static function getScope(): string;

    public function getPayloads(User $user): iterable;

    public function updateSecurityToken(TokenInterface $token, array $payload);

    public function isPayloadValid(array $payload);
}
