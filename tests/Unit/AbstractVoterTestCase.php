<?php

namespace Unilend\Test\Unit;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractVoterTestCase extends TestCase
{

    protected VoterInterface $voter;

    /**
     * @param string        $attribute
     * @param UserInterface $user
     * @param null|object   $subject
     */
    protected function expectAccessDenied(string $attribute, UserInterface $user, $subject = null): void
    {
        $vote = $this->vote($attribute, $user, $subject);
        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $vote,
            "Expected access denied for ${attribute}, got {$vote}"
        );
    }

    /**
     * @param string        $attribute
     * @param UserInterface $user
     * @param null|object   $subject
     */
    protected function expectAccessGranted(string $attribute, UserInterface $user, $subject = null): void
    {
        $vote = $this->vote($attribute, $user, $subject);
        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $vote,
            "Expected access granted for ${attribute}, got {$vote}"
        );
    }

    /**
     * @param string        $attribute
     * @param UserInterface $user
     * @param null|object   $subject
     */
    protected function expectAccessAbstained(string $attribute, UserInterface $user, $subject = null): void
    {
        $vote = $this->vote($attribute, $user, $subject);
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $vote,
            "Expected access abstained for ${attribute}, got {$vote}"
        );
    }

    /**
     * @param string        $attribute
     * @param UserInterface $user
     * @param null|object   $subject
     *
     * @return int
     */
    protected function vote(string $attribute, UserInterface $user, $subject = null): int
    {
        return $this->voter->vote(
            new JWTUserToken($user->getRoles(), $user),
            $subject,
            [$attribute]
        );
    }
}
