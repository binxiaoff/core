<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Table(name="queries")
 * @ORM\Entity(repositoryClass="Unilend\Repository\QueriesRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Queries
{
    use TimestampableTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="query", type="text", length=16777215)
     */
    private $query;

    /**
     * @var int
     *
     * @ORM\Column(name="paging", type="integer", options={"default": 100})
     */
    private $paging = 100;

    /**
     * @var int
     *
     * @ORM\Column(name="executions", type="integer", options={"default": 0})
     */
    private $executions = 0;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="executed", type="datetime", nullable=true)
     */
    private $executed;

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
    public function setName(string $name): Queries
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $query
     *
     * @return Queries
     */
    public function setQuery(string $query): Queries
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @param int $paging
     *
     * @return Queries
     */
    public function setPaging(int $paging): Queries
    {
        $this->paging = $paging;

        return $this;
    }

    /**
     * @return int
     */
    public function getPaging(): int
    {
        return $this->paging;
    }

    /**
     * @param int|null $executions
     *
     * @return Queries
     */
    public function setExecutions(?int $executions): Queries
    {
        $this->executions = $executions;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getExecutions(): ?int
    {
        return $this->executions;
    }

    /**
     * @param DateTime|null $executed
     *
     * @return Queries
     */
    public function setExecuted(?DateTime $executed): Queries
    {
        $this->executed = $executed;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getExecuted(): ?DateTime
    {
        return $this->executed;
    }

    /**
     * @return int
     */
    public function getIdQuery(): int
    {
        return $this->idQuery;
    }
}
