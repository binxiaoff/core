<?php

namespace Unilend\Bundle\FrontBundle\Security;

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
    public function encodePassword($raw, $salt)
    {
        if (false === $this->isPasswordSafe($raw)) {
            throw new BadCredentialsException('Invalid password.');
        }

        return parent::encodePassword($raw, $salt);
    }

    /**
     * Check if the password has at least PASSWORD_LENGTH_MIN chars of which contains at least one uppercase and at least one lowercase.
     * Todo: change it back to private non static function after "TECH-108 Replace the BaseUser by clients"
     *
     * @param $raw
     *
     * @return bool
     */
    public static function isPasswordSafe($raw)
    {
        $regex = '/^(?=.*[a-z])(?=.*[A-Z]).{' . self::PASSWORD_LENGTH_MIN . ',}$/';
        if (1 === preg_match($regex, $raw)) {
            return true;
        }

        return false;
    }

}
