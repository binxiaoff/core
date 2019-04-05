<?php

namespace Unilend\Security;

use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder as BaseEncoder;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class BCryptPasswordEncoder extends BaseEncoder
{
    const PASSWORD_LENGTH_MIN = 8;

    /**
     * @param string $raw
     * @param string $salt
     *
     * @return string
     */
    public function encodePassword($raw, $salt): string
    {
        if (false === $this->isPasswordStrongEnough($raw)) {
            throw new BadCredentialsException('Invalid password.');
        }

        return parent::encodePassword($raw, $salt);
    }

    /**
     * Check if the password has at least PASSWORD_LENGTH_MIN chars of which contains at least one uppercase and at least one lowercase.
     *
     * @param $raw
     *
     * @return bool
     */
    private function isPasswordStrongEnough($raw): bool
    {
        $regex = '/^(?=.*[a-z])(?=.*[A-Z]).{' . self::PASSWORD_LENGTH_MIN . ',}$/';
        if (1 === preg_match($regex, $raw)) {
            return true;
        }

        return false;
    }

}
