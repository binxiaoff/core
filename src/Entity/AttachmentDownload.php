<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Unilend\Entity\Traits\BlamableAddedTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 */
class AttachmentDownload
{
    use BlamableAddedTrait;
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
     * @var FileVersion
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\FileVersion", inversedBy="attachmentDownloads")
     * @ORM\JoinColumn(name="id_attachment", nullable=false)
     */
    private $attachment;

    /**
     * @param FileVersion $attachment
     * @param Staff       $addedBy
     *
     * @throws Exception
     */
    public function __construct(FileVersion $attachment, Staff $addedBy)
    {
        $this->attachment = $attachment;
        $this->addedBy    = $addedBy;
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
     * @return FileVersion
     */
    public function getAttachment(): FileVersion
    {
        return $this->attachment;
    }
}
