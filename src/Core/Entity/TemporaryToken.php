<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Core\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Table(name="core_temporary_token", indexes={@ORM\Index(name="fk_temporary_token_id_user", columns={"id_user"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class TemporaryToken
{
    use TimestampableTrait;

    protected const LIFETIME_MEDIUM     = '1 day';
    protected const LIFETIME_ULTRA_LONG = '1 month';

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(name="updated", type="datetime_immutable", nullable=true)
     */
    protected $updated;

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(name="added", type="datetime_immutable")
     */
    protected $added;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=150)
     *
     * @ApiProperty(identifier=true)
     */
    private $token;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="expires", type="datetime_immutable")
     */
    private $expires;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="accessed", type="datetime_immutable", nullable=true)
     */
    private $accessed;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @ApiProperty(identifier=false)
     */
    private $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\User")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_user", referencedColumnName="id", nullable=false)
     * })
     */
    private $user;

    /**
     * TemporaryToken constructor.
     *
     * @param User   $users
     * @param string $expirationDelay
     *
     * @throws Exception
     */
    private function __construct(User $users, string $expirationDelay)
    {
        $this->token   = bin2hex(random_bytes(16));
        $this->user  = $users;
        $this->added   = new DateTimeImmutable();
        $this->expires = (new DateTimeImmutable())->add(DateInterval::createFromDateString($expirationDelay));
    }

    /**
     * @param User $user
     *
     * @return TemporaryToken

     **@throws Exception
     *
     * @internal Use Unilend\Core\Service\TemporaryTokenGenerator::generateMediumToken
     *
     */
    public static function generateMediumToken(User $user): TemporaryToken
    {
        return new TemporaryToken($user, static::LIFETIME_MEDIUM);
    }

    /**
     * @param User $user
     *
     * @return TemporaryToken

     **@throws Exception
     *
     * @internal Use Unilend\Core\Service\TemporaryTokenGenerator::generateUltraLongToken
     *
     */
    public static function generateUltraLongToken(User $user): TemporaryToken
    {
        return new TemporaryToken($user, static::LIFETIME_ULTRA_LONG);
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getExpires(): DateTimeImmutable
    {
        return $this->expires;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getAccessed(): ?DateTimeImmutable
    {
        return $this->accessed;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @throws Exception
     *
     * @return TemporaryToken
     */
    public function setExpired(): TemporaryToken
    {
        $this->expires = new DateTimeImmutable();

        return $this;
    }

    /**
     * @throws Exception
     *
     * @return TemporaryToken
     */
    public function setAccessed(): TemporaryToken
    {
        $this->accessed = $this->accessed ?: new DateTimeImmutable();

        return $this;
    }

    /**
     * @throws Exception
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return (new DateTimeImmutable()) < $this->expires;
    }
}
