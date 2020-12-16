<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Clients;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Table(
 *     indexes={
 *      @ORM\Index(columns={"id_user", "browser_name", "device_model", "device_brand", "device_type"})
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
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\User")
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
     * UserAgent constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->added = new DateTimeImmutable();
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return UserAgent
     */
    public function setUser(User $user): UserAgent
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBrowserName(): ?string
    {
        return $this->browserName;
    }

    /**
     * @param string|null $browserName
     *
     * @return UserAgent
     */
    public function setBrowserName(?string $browserName): UserAgent
    {
        $this->browserName = $browserName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBrowserVersion(): ?string
    {
        return $this->browserVersion;
    }

    /**
     * @param string|null $browserVersion
     *
     * @return UserAgent
     */
    public function setBrowserVersion(?string $browserVersion): UserAgent
    {
        $this->browserVersion = $browserVersion;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeviceModel(): ?string
    {
        return $this->deviceModel;
    }

    /**
     * @param string|null $deviceModel
     *
     * @return UserAgent
     */
    public function setDeviceModel(?string $deviceModel): UserAgent
    {
        $this->deviceModel = $deviceModel;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeviceBrand(): ?string
    {
        return $this->deviceBrand;
    }

    /**
     * @param string|null $deviceBrand
     *
     * @return UserAgent
     */
    public function setDeviceBrand(?string $deviceBrand): UserAgent
    {
        $this->deviceBrand = $deviceBrand;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeviceType(): ?string
    {
        return $this->deviceType;
    }

    /**
     * @param string|null $deviceType
     *
     * @return UserAgent
     */
    public function setDeviceType(?string $deviceType): UserAgent
    {
        $this->deviceType = $deviceType;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserAgentString(): string
    {
        return $this->userAgentString;
    }

    /**
     * @param string $userAgentString
     *
     * @return UserAgent
     */
    public function setUserAgentString(string $userAgentString): UserAgent
    {
        $this->userAgentString = $userAgentString;

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
