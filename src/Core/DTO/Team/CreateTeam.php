<?php

declare(strict_types=1);

namespace Unilend\Core\DTO\Team;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\CompanyGroupTag;
use Unilend\Core\Entity\Team;

class CreateTeam
{
    /**
     * @var string
     *
     * @Assert\NotBlank
     *
     * @Groups({"team:create"})
     */
    public string $name;

    /**
     * @var Team
     *
     * @Assert\NotBlank
     *
     * @Groups({"team:create"})
     */
    public Team $parent;

    /**
     * @var CompanyGroupTag[]|iterable
     *
     * @Groups({"team:create"})
     *
     * @Assert\All(
     *
     *    @\Unilend\Core\Validator\Constraints\CompanyGroupTag(teamPropertyPath="parent")
     * )
     */
    public iterable $companyGroupTags;

    /**
     * @param string   $name
     * @param Team     $parent
     * @param iterable $companyGroupTags
     */
    public function __construct(string $name, Team $parent, iterable $companyGroupTags = [])
    {
        $this->name = $name;
        $this->parent = $parent;
        $this->companyGroupTags = $companyGroupTags;
    }
}
