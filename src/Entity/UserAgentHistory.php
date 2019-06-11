<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Table(indexes={
 *     @ORM\Index(name="idx_user_agent_browser_device_model_brand_type", columns={"browser_name", "device_model", "device_brand", "device_type"})
 * })
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class UserAgentHistory
{
    use TimestampableAddedOnlyTrait;

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
     * @return string|null
     */
    public function getBrowserName(): ?string
    {
        return $this->browserName;
    }

    /**
     * @param string|null $browserName
     *
     * @return UserAgentHistory
     */
    public function setBrowserName(?string $browserName): UserAgentHistory
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
     * @return UserAgentHistory
     */
    public function setBrowserVersion(?string $browserVersion): UserAgentHistory
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
     * @return UserAgentHistory
     */
    public function setDeviceModel(?string $deviceModel): UserAgentHistory
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
     * @return UserAgentHistory
     */
    public function setDeviceBrand(?string $deviceBrand): UserAgentHistory
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
     * @return UserAgentHistory
     */
    public function setDeviceType(?string $deviceType): UserAgentHistory
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
     * @return UserAgentHistory
     */
    public function setUserAgentString(string $userAgentString): UserAgentHistory
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
