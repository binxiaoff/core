<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 */
class Download
{
    use TimestampableAddedOnlyTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Attachment
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Attachment", inversedBy="downloads")
     * @ORM\JoinColumn(name="id_attachment", nullable=false)
     */
    private $attachment;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumn(referencedColumnName="id_client", name="id_client", nullable=false)
     */
    private $client;

    /**
     * @param Attachment $attachment
     * @param Clients    $client
     *
     * @throws Exception
     */
    public function __construct(Attachment $attachment, Clients $client)
    {
        $this->attachment = $attachment;
        $this->client     = $client;
        $this->added      = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Attachment
     */
    public function getAttachment(): Attachment
    {
        return $this->attachment;
    }

    /**
     * @return Clients
     */
    public function getClient(): Clients
    {
        return $this->client;
    }
}
