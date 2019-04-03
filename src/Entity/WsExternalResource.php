<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WsExternalResource
 *
 * @ORM\Table(name="ws_external_resource", uniqueConstraints={@ORM\UniqueConstraint(name="provider_resource_method", columns={"provider_name", "resource_name", "label", "method"})})
 * @ORM\Entity
 */
class WsExternalResource
{
    const STATUS_AVAILABLE   = true;
    const STATUS_UNAVAILABLE = false;

    /**
     * @var string
     *
     * @ORM\Column(name="provider_name", type="string", length=128)
     */
    private $providerName;

    /**
     * @var string
     *
     * @ORM\Column(name="resource_name", type="string", length=128)
     */
    private $resourceName;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=128, unique=true)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="method", type="string", length=10, nullable=true)
     */
    private $method;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_available", type="boolean")
     */
    private $isAvailable;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="validity_days", type="smallint", nullable=false, options={"default" : -1})
     */
    private $validityDays = -1;

    /**
     * @var int
     *
     * @ORM\Column(name="id_resource", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idResource;

    /**
     * @return string
     */
    public function getProviderName()
    {
        return $this->providerName;
    }

    /**
     * @param string $providerName
     *
     * @return WsExternalResource
     */
    public function setProviderName($providerName)
    {
        $this->providerName = $providerName;

        return $this;
    }

    /**
     * @return string
     */
    public function getResourceName()
    {
        return $this->resourceName;
    }

    /**
     * @param string $resourceName
     *
     * @return WsExternalResource
     */
    public function setResourceName($resourceName)
    {
        $this->resourceName = $resourceName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return WsExternalResource
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return WsExternalResource
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->isAvailable;
    }

    /**
     * @param bool $isAvailable
     *
     * @return WsExternalResource
     */
    public function setIsAvailable($isAvailable)
    {
        $this->isAvailable = $isAvailable;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     *
     * @return WsExternalResource
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return int
     */
    public function getValidityDays()
    {
        return $this->validityDays;
    }

    /**
     * @param int $validityDays
     *
     * @return WsExternalResource
     */
    public function setValidityDays($validityDays)
    {
        $this->validityDays = $validityDays;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdResource()
    {
        return $this->idResource;
    }

    /**
     * @param int $idResource
     *
     * @return WsExternalResource
     */
    public function setIdResource($idResource)
    {
        $this->idResource = $idResource;

        return $this;
    }
}
