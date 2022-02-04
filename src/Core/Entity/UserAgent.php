<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * Represents an user agent. [@link https://fr.wikipedia.org/wiki/User_agent]
 * Created to reduce duplication in the @see UserFailedLogin
 *  and @see UserSuccessfulLogin.
 *
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(columns={"id_user", "browser_name", "device_model", "device_brand", "device_type"})
 *     },
 *     name="core_user_agent"
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class UserAgent
{
    use TimestampableAddedOnlyTrait;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\User")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_user", referencedColumnName="id", nullable=false)
     * })
     */
    private $user;

    /**
     * @var string|null
     *
     * @ORM\Column(name="browser_name", type="string", length=48, nullable=true)
     */
    private $browserName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="browser_version", type="string", length=32, nullable=true)
     */
    private $browserVersion;

    /**
     * @var string|null
     *
     * @ORM\Column(name="device_model", type="string", length=48, nullable=true)
     */
    private $deviceModel;

    /**
     * @var string|null
     *
     * @ORM\Column(name="device_brand", type="string", length=48, nullable=true)
     */
    private $deviceBrand;

    /**
     * @var string|null
     *
     * @ORM\Column(name="device_type", type="string", length=32, nullable=true)
     */
    private $deviceType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="user_agent_string", type="string", length=256)
     */
    private $userAgentString;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->added = new DateTimeImmutable();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): UserAgent
    {
        $this->user = $user;

        return $this;
    }

    public function getBrowserName(): ?string
    {
        return $this->browserName;
    }

    public function setBrowserName(?string $browserName): UserAgent
    {
        $this->browserName = $browserName;

        return $this;
    }

    public function getBrowserVersion(): ?string
    {
        return $this->browserVersion;
    }

    public function setBrowserVersion(?string $browserVersion): UserAgent
    {
        $this->browserVersion = $browserVersion;

        return $this;
    }

    public function getDeviceModel(): ?string
    {
        return $this->deviceModel;
    }

    public function setDeviceModel(?string $deviceModel): UserAgent
    {
        $this->deviceModel = $deviceModel;

        return $this;
    }

    public function getDeviceBrand(): ?string
    {
        return $this->deviceBrand;
    }

    public function setDeviceBrand(?string $deviceBrand): UserAgent
    {
        $this->deviceBrand = $deviceBrand;

        return $this;
    }

    public function getDeviceType(): ?string
    {
        return $this->deviceType;
    }

    public function setDeviceType(?string $deviceType): UserAgent
    {
        $this->deviceType = $deviceType;

        return $this;
    }

    public function getUserAgentString(): string
    {
        return $this->userAgentString;
    }

    public function setUserAgentString(string $userAgentString): UserAgent
    {
        $this->userAgentString = $userAgentString;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
