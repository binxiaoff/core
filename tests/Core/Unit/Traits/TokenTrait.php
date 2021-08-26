<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Traits;

use KLS\Core\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

trait TokenTrait
{
    private function createToken(User $user, array $attributes = []): TokenInterface
    {
        $token = new JWTUserToken($user->getRoles(), $user);

        foreach ($attributes as $key => $value) {
            $token->setAttribute($key, $value);
        }

        return $token;
    }
}
