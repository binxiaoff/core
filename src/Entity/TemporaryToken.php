<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Table(name="temporary_token", indexes={@ORM\Index(name="fk_temporary_token_id_client", columns={"id_client"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\TemporaryTokenRepository")
 * @ORM\HasLifecycleCallbacks
 */
class TemporaryToken
{
    use TimestampableTrait;

    private const LIFETIME_SHORT  = '1 hour';
    private const LIFETIME_MEDIUM = '1 day';
    private const LIFETIME_LONG   = '1 week';

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=150)
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
     */
    private $id;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $client;

    /**
     * TemporaryToken constructor.
     *
     * @param Clients $clients
     * @param string  $expirationDelay
     *
     * @throws Exception
     */
    private function __construct(
        Clients $clients,
        string $expirationDelay = self::LIFETIME_SHORT
    ) {
        $this->token   = bin2hex(random_bytes(16));
        $this->client  = $clients;
        $this->added   = new DateTimeImmutable();
        $this->expires = (new DateTimeImmutable())->add(DateInterval::createFromDateString($expirationDelay));
    }

    /**
     * @param Clients $client
     *
     * @throws Exception
     *
     * @return TemporaryToken
     */
    public static function generateShortToken(Clients $client): TemporaryToken
    {
        return new TemporaryToken($client, static::LIFETIME_SHORT);
    }

    /**
     * @param Clients $client
     *
     * @throws Exception
     *
     * @return TemporaryToken
     */
    public static function generateLongToken(Clients $client): TemporaryToken
    {
        return new TemporaryToken($client, static::LIFETIME_SHORT);
    }

    /**
     * @return Clients
     */
    public function getClient(): Clients
    {
        return $this->client;
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
        $this->accessed = new DateTimeImmutable();

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

    /**
     * @throws Exception
     * @throws DomainException
     *
     * @return TemporaryToken
     */
    public function extendLong(): TemporaryToken
    {
        if (false === $this->isValid()) {
            throw new DomainException('Invalid token');
        }

        $this->expires = (new DateTimeImmutable())->add(DateInterval::createFromDateString(self::LIFETIME_LONG));

        return $this;
    }
}
