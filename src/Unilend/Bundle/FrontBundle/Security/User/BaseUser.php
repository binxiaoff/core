<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Symfony\Component\Security\Core\Encoder\EncoderAwareInterface;
use Symfony\Component\Security\Core\User\{
    AdvancedUserInterface, EquatableInterface, UserInterface
};

class BaseUser implements AdvancedUserInterface, EquatableInterface, EncoderAwareInterface
{
    /** @var string */
    private $username;
    /** @var string */
    private $password;
    /** @var string */
    private $email;
    /** @var string */
    private $salt;
    /** @var array */
    private $roles;
    /** @var bool */
    private $isActive;
    /** @var int */
    private $clientId;
    /** @var string */
    private $hash;
    /** @var int */
    private $clientStatus;
    /** @var \DateTime */
    private $lastLoginDate;
    /** @var string */
    private $encoderName;

    /**
     * @param string         $username
     * @param string|null    $password
     * @param string         $email
     * @param string|null    $salt
     * @param string[]       $roles
     * @param bool           $isActive
     * @param int            $clientId
     * @param string         $hash
     * @param int            $clientStatus
     * @param \DateTime|null $lastLoginDate
     */
    public function __construct(
        string $username,
        ?string $password,
        string $email,
        ?string $salt,
        array $roles,
        bool $isActive,
        int $clientId,
        string $hash,
        int $clientStatus,
        ?\DateTime $lastLoginDate = null
    )
    {
        $this->username      = $username;
        $this->password      = $password;
        $this->email         = $email;
        $this->salt          = $salt;
        $this->roles         = $roles;
        $this->isActive      = $isActive;
        $this->clientId      = $clientId;
        $this->hash          = $hash;
        $this->clientStatus  = $clientStatus;
        $this->lastLoginDate = $lastLoginDate;
    }

    /**
     * @inheritDoc
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @inheritDoc
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @inheritDoc
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    /**
     * @inheritDoc
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @inheritDoc
     */
    public function getEncoderName(): ?string
    {
        if ('default' === $this->encoderName) {
            return null;
        }

        if (1 === preg_match('/^[0-9a-f]{32}$/', $this->password)) {
            return 'md5';
        }

        return null; // use the default encoder
    }

    public function useDefaultEncoder(): void
    {
        $this->encoderName = 'default';
    }

    /**
     * @return bool
     */
    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @return int
     */
    public function getClientId(): int
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return int
     */
    public function getClientStatus(): int
    {
        return $this->clientStatus;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastLoginDate(): ?\DateTime
    {
        return $this->lastLoginDate;
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    /**
     * @param UserInterface $user
     *
     * @return bool
     */
    public function isEqualTo(UserInterface $user): bool
    {
        if (false === $user instanceof BaseUser) {
            return false;
        }

        if ($this->hash !== $user->getHash()) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            if ($this->username !== $user->getUsername()) {
                return false;
            }
        }

        if ($this->username !== $user->getUsername()) {
            if ($this->password !== $user->getPassword()) {
                return false;
            }
        }

        return true;
    }
    /**
     * @inheritDoc
     */
    public function isAccountNonExpired(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isCredentialsNonExpired(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isAccountNonLocked(): bool
    {
        return $this->isActive;
    }
}
