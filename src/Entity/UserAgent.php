<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="user_agent", indexes={@ORM\Index(name="idx_user_agent_client_browser_device_model_brand_type", columns={"id_client", "browser_name", "device_model", "device_brand", "device_type"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class UserAgent
{
    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClient;

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
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


    /**
     * @return Clients
     */
    public function getIdClient(): Clients
    {
        return $this->idClient;
    }

    /**
     * @param Clients $idClient
     *
     * @return UserAgent
     */
    public function setIdClient(Clients $idClient): UserAgent
    {
        $this->idClient = $idClient;

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
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * @param \DateTime $added
     *
     * @return UserAgent
     */
    public function setAdded(\DateTime $added): UserAgent
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (! $this->added instanceof \DateTime || -1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }
}
