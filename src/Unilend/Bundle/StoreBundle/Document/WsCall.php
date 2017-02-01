<?php

namespace Unilend\Bundle\StoreBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 * @MongoDB\Indexes({
 *     @MongoDB\Index(keys={"provider", "resource"}, background="true"),
 *     @MongoDB\Index(keys={"siren"}, background="true"),
 *     @MongoDB\Index(keys={"added"}, background="true", order="desc")
 *     })
 */
class WsCall
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $provider;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $resource;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $siren;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $response;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $added;

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set service
     *
     * @param string $provider
     * @return $this
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * Get service
     *
     * @return string $service
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Set method
     *
     * @param string $resource
     * @return $this
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * Get method
     *
     * @return string $method
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set response
     *
     * @param string $response
     * @return $this
     */
    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Get response
     *
     * @return string $response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return mixed
     */
    public function getSiren()
    {
        return $this->siren;
    }

    /**
     * @param mixed $siren
     */
    public function setSiren($siren)
    {
        $this->siren = $siren;
    }

    /**
     * @return mixed
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @param mixed $added
     */
    public function setAdded($added)
    {
        $this->added = $added;
    }
}
