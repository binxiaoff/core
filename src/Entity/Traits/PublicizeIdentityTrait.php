<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use ApiPlatform\Core\Annotation\ApiProperty;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

trait PublicizeIdentityTrait
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @ApiProperty(identifier=false)
     */
    private $id;

    /**
     * @see https://github.com/ramsey/uuid-doctrine/issues/13
     *
     * @var string
     *
     * @ORM\Column(length=36, unique=true)
     *
     * @ApiProperty(identifier=true)
     *
     * @Groups({"publicId:read"})
     */
    private $publicId;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPublicId(): string
    {
        return $this->publicId;
    }

    /**
     * @return $this
     */
    public function setPublicId(): self
    {
        if (null === $this->publicId) {
            try {
                $this->publicId = (string) (Uuid::uuid4());
            } catch (Throwable $e) {
                $this->publicId = md5(uniqid('', false));
            }
        }

        return $this;
    }
}
