<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * @ORM\Table(name="temporary_links_login", indexes={@ORM\Index(name="fk_temporary_links_login_id_client", columns={"id_client"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\TemporaryLinksLoginRepository")
 * @ORM\HasLifecycleCallbacks
 */
class TemporaryLinksLogin
{
    public const PASSWORD_TOKEN_LIFETIME_SHORT  = 'T1H';
    public const PASSWORD_TOKEN_LIFETIME_MEDIUM = '1D';
    public const PASSWORD_TOKEN_LIFETIME_LONG   = '1W';

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=150)
     */
    private $token;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="expires", type="datetime")
     */
    private $expires;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="accessed", type="datetime", nullable=true)
     */
    private $accessed;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_link", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLink;

    /**
     * @var \Unilend\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClient;

    /**
     * @param Clients $idClient
     *
     * @return TemporaryLinksLogin
     */
    public function setIdClient(Clients $idClient): TemporaryLinksLogin
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * @return Clients
     */
    public function getIdClient(): Clients
    {
        return $this->idClient;
    }

    /**
     * @param string $token
     *
     * @return TemporaryLinksLogin
     */
    public function setToken(string $token): TemporaryLinksLogin
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param DateTimeImmutable $expires
     *
     * @return TemporaryLinksLogin
     */
    public function setExpires(DateTimeImmutable $expires): TemporaryLinksLogin
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getExpires(): DateTime
    {
        return $this->expires;
    }

    /**
     * @throws Exception
     *
     * @return bool
     */
    public function isExpires(): bool
    {
        return $this->getExpires() < new DateTime();
    }

    /**
     * @param DateTime|null $accessed
     *
     * @return TemporaryLinksLogin
     */
    public function setAccessed(?DateTime $accessed): TemporaryLinksLogin
    {
        $this->accessed = $accessed;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getAccessed(): ?DateTime
    {
        return $this->accessed;
    }

    /**
     * @param DateTime $added
     *
     * @return TemporaryLinksLogin
     */
    public function setAdded(DateTime $added): TemporaryLinksLogin
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getAdded(): DateTime
    {
        return $this->added;
    }

    /**
     * @param DateTime|null $updated
     *
     * @return TemporaryLinksLogin
     */
    public function setUpdated(?DateTime $updated): TemporaryLinksLogin
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getUpdated(): ?DateTime
    {
        return $this->updated;
    }

    /**
     * @return int
     */
    public function getIdLink(): int
    {
        return $this->idLink;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (!$this->added instanceof DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue(): void
    {
        $this->updated = new DateTime();
    }

    /**
     * @throws Exception
     *
     * @return TemporaryLinksLogin
     */
    public function extendLongExpiration(): TemporaryLinksLogin
    {
        $expiryDate = $this->generateExpiration(self::PASSWORD_TOKEN_LIFETIME_LONG);
        $this->setExpires($expiryDate);

        return $this;
    }

    /**
     * @param mixed $life
     *
     * @throws Exception
     *
     * @return DateTimeImmutable
     */
    public function generateExpiration($life): DateTimeImmutable
    {
        return (new DateTimeImmutable('NOW'))->add(new DateInterval('P' . $life));
    }
}
