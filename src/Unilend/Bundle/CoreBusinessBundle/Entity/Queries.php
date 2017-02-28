<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Queries
 *
 * @ORM\Table(name="queries")
 * @ORM\Entity
 */
class Queries
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="sql", type="text", length=16777215, nullable=false)
     */
    private $sql;

    /**
     * @var integer
     *
     * @ORM\Column(name="paging", type="integer", nullable=false)
     */
    private $paging;

    /**
     * @var integer
     *
     * @ORM\Column(name="executions", type="integer", nullable=false)
     */
    private $executions;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="executed", type="datetime", nullable=false)
     */
    private $executed;

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
     * @ORM\Column(name="id_query", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idQuery;



    /**
     * Set name
     *
     * @param string $name
     *
     * @return Queries
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set sql
     *
     * @param string $sql
     *
     * @return Queries
     */
    public function setSql($sql)
    {
        $this->sql = $sql;

        return $this;
    }

    /**
     * Get sql
     *
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * Set paging
     *
     * @param integer $paging
     *
     * @return Queries
     */
    public function setPaging($paging)
    {
        $this->paging = $paging;

        return $this;
    }

    /**
     * Get paging
     *
     * @return integer
     */
    public function getPaging()
    {
        return $this->paging;
    }

    /**
     * Set executions
     *
     * @param integer $executions
     *
     * @return Queries
     */
    public function setExecutions($executions)
    {
        $this->executions = $executions;

        return $this;
    }

    /**
     * Get executions
     *
     * @return integer
     */
    public function getExecutions()
    {
        return $this->executions;
    }

    /**
     * Set executed
     *
     * @param \DateTime $executed
     *
     * @return Queries
     */
    public function setExecuted($executed)
    {
        $this->executed = $executed;

        return $this;
    }

    /**
     * Get executed
     *
     * @return \DateTime
     */
    public function getExecuted()
    {
        return $this->executed;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Queries
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
     * @return Queries
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
     * Get idQuery
     *
     * @return integer
     */
    public function getIdQuery()
    {
        return $this->idQuery;
    }
}
