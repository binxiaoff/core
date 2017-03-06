<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Errors
 *
 * @ORM\Table(name="errors")
 * @ORM\Entity
 */
class Errors
{
    /**
     * @var string
     *
     * @ORM\Column(name="errid", type="string", length=191, nullable=false)
     */
    private $errid;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", length=16777215, nullable=false)
     */
    private $message;

    /**
     * @var string
     *
     * @ORM\Column(name="trace", type="text", length=16777215, nullable=false)
     */
    private $trace;

    /**
     * @var string
     *
     * @ORM\Column(name="session", type="text", length=16777215, nullable=false)
     */
    private $session;

    /**
     * @var string
     *
     * @ORM\Column(name="post", type="text", length=16777215, nullable=false)
     */
    private $post;

    /**
     * @var string
     *
     * @ORM\Column(name="server", type="text", length=16777215, nullable=false)
     */
    private $server;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_error", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idError;



    /**
     * Set errid
     *
     * @param string $errid
     *
     * @return Errors
     */
    public function setErrid($errid)
    {
        $this->errid = $errid;

        return $this;
    }

    /**
     * Get errid
     *
     * @return string
     */
    public function getErrid()
    {
        return $this->errid;
    }

    /**
     * Set message
     *
     * @param string $message
     *
     * @return Errors
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set trace
     *
     * @param string $trace
     *
     * @return Errors
     */
    public function setTrace($trace)
    {
        $this->trace = $trace;

        return $this;
    }

    /**
     * Get trace
     *
     * @return string
     */
    public function getTrace()
    {
        return $this->trace;
    }

    /**
     * Set session
     *
     * @param string $session
     *
     * @return Errors
     */
    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get session
     *
     * @return string
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set post
     *
     * @param string $post
     *
     * @return Errors
     */
    public function setPost($post)
    {
        $this->post = $post;

        return $this;
    }

    /**
     * Get post
     *
     * @return string
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * Set server
     *
     * @param string $server
     *
     * @return Errors
     */
    public function setServer($server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Get server
     *
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Errors
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return Errors
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get idError
     *
     * @return integer
     */
    public function getIdError()
    {
        return $this->idError;
    }
}
