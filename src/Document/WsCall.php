<?php

namespace Unilend\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
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
     * @MongoDB\Field(name="id_ws_call_history", type="integer")
     */
    protected $idWsCallHistory;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $response;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $added;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $parameter;

    /**
     * @return string $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $provider
     *
     * @return $this
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param string $resource
     *
     * @return $this
     */
    public function setResource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param string $response
     *
     * @return $this
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $siren
     *
     * @return $this
     */
    public function setSiren($siren)
    {
        $this->siren = $siren;

        return $this;
    }

    /**
     * @return string
     */
    public function getSiren()
    {
        return $this->siren;
    }

    /**
     * @param \DateTime $added
     *
     * @return $this
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @param int $idWsCallHistory
     *
     * @return $this
     */
    public function setIdWsCallHistory($idWsCallHistory)
    {
        $this->idWsCallHistory = $idWsCallHistory;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdWsCallHistory()
    {
        return $this->idWsCallHistory;
    }

    /**
     * @param string $parameter
     *
     * @return $this
     */
    public function setParameter($parameter)
    {
        $this->parameter = $parameter;

        return $this;
    }

    /**
     * @return string
     */
    public function getParameter()
    {
        return $this->parameter;
    }
}
