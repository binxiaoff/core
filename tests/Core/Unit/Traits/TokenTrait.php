<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Traits;

use KLS\Core\Entity\Staff;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

trait TokenTrait
{
    private function createToken(Staff $staff, array $attributes = []): TokenInterface
    {
        $user = $staff->getUser();
        $user->setCurrentStaff($staff);
        $token = new JWTUserToken($user->getRoles(), $user);

        foreach ($attributes as $key => $value) {
            $token->setAttribute($key, $value);
        }

        return $token;
    }
}
