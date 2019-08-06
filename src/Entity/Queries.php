<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="queries")
 * @ORM\Entity
 */
class Queries
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="sql", type="text", length=16777215)
     */
    private $sql;

    /**
     * @var int
     *
     * @ORM\Column(name="paging", type="integer")
     */
    private $paging;

    /**
     * @var int
     *
     * @ORM\Column(name="executions", type="integer")
     */
    private $executions;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="executed", type="datetime")
     */
    private $executed;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_query", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idQuery;

    /**
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
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
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @param int $paging
     *
     * @return Queries
     */
    public function setPaging($paging)
    {
        $this->paging = $paging;

        return $this;
    }

    /**
     * @return int
     */
    public function getPaging()
    {
        return $this->paging;
    }

    /**
     * @param int $executions
     *
     * @return Queries
     */
    public function setExecutions($executions)
    {
        $this->executions = $executions;

        return $this;
    }

    /**
     * @return int
     */
    public function getExecutions()
    {
        return $this->executions;
    }

    /**
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
     * @return \DateTime
     */
    public function getExecuted()
    {
        return $this->executed;
    }

    /**
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
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
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
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @return int
     */
    public function getIdQuery()
    {
        return $this->idQuery;
    }
}
