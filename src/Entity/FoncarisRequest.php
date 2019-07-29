<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Interfaces\FileStorageInterface;
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class FoncarisRequest implements FileStorageInterface
{
    use TimestampableTrait;
    use ConstantsAwareTrait;

    public const FONCARIS_GUARANTEE_NO_NEED            = 0;
    public const FONCARIS_GUARANTEE_NEED               = 1;
    public const FONCARIS_GUARANTEE_ALREADY_GUARANTEED = 2;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Project
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\Project")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_project", nullable=false)
     * })
     */
    private $project;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $choice;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=191, nullable=true)
     */
    private $relativeFilePath;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @param Project $project
     *
     * @return FoncarisRequest
     */
    public function setProject(Project $project): FoncarisRequest
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getChoice(): ?int
    {
        return $this->choice;
    }

    /**
     * @param int|null $choice
     *
     * @return FoncarisRequest
     */
    public function setChoice(?int $choice): FoncarisRequest
    {
        if (in_array($choice, self::getFoncarisGuaranteeOptions())) {
            $this->choice = $choice;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function needFoncarisGuarantee(): bool
    {
        return self::FONCARIS_GUARANTEE_NEED === $this->getChoice();
    }

    /**
     * @return array
     */
    public static function getFoncarisGuaranteeOptions(): array
    {
        return self::getConstants('FONCARIS_GUARANTEE_');
    }

    /**
     * @param string|null $relativeFilePath
     *
     * @return self
     */
    public function setRelativeFilePath(?string $relativeFilePath): FoncarisRequest
    {
        $this->relativeFilePath = $relativeFilePath;

        return $this;
    }

    /**
     * @return string
     */
    public function getRelativeFilePath(): ?string
    {
        return $this->relativeFilePath;
    }
}
