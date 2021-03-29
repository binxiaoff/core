<?php

declare(strict_types=1);

namespace Unilend\Core\EventSubscriber\JWT;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Unilend\Core\Entity\User;

interface JwtPayloadManagerInterface
{
    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @param array $payload
     *
     * @return bool
     */
    public function isTokenPayloadValid(array $payload): bool;

    /**
     * @param User $user
     *
     * @return iterable|array[]
     */
    public function generatePayloads(User $user): iterable;

    /**
     * @param TokenInterface $token
     * @param array          $payload
     *
     * @return void
     */
    public function updateSecurityToken(TokenInterface $token, array $payload): void;
}
