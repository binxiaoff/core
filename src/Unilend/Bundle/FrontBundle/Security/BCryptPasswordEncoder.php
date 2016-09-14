<?php

namespace Unilend\Bundle\FrontBundle\Security;

use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder as BaseEncoder;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class BCryptPasswordEncoder extends BaseEncoder
{

    public function encodePassword($raw, $salt)
    {
        if (false === $this->hasCapitalLetter($raw) || false == $this->hasLowerCaseLetter($raw) || false === $this->hasMinLength($raw)){
            throw new BadCredentialsException('Invalid password.');
        }

        return parent::encodePassword($raw, $salt);
    }

    public function hasCapitalLetter($password)
    {
        return (bool) preg_match('/[A-Z]/', $password);
    }

    public function hasLowerCaseLetter($password)
    {
        return (bool) preg_match('/[a-z]/', $password);
    }

    public function hasMinLength($password)
    {
        return strlen($password) >= 6;
    }

}
