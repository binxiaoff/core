<?php

declare(strict_types=1);

namespace Unilend\Core\Service\Jwt;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Unilend\Core\Entity\User;

interface PayloadManagerInterface
{
    public function getScope(): string;

    public function getPayloads(User $user): iterable;

    public function updateSecurityToken(TokenInterface $token, array $payload);
}
