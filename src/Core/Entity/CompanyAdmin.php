<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="core_company_admin",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uniq_companyAdmin_companu_user", columns={"id_company", "id_user"})
 *     }
 * )
 */
class CompanyAdmin
{
    use PublicizeIdentityTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Company", inversedBy="admins")
     * @ORM\JoinColumn(name="id_company")
     */
    private Company $company;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\User")
     * @ORM\JoinColumn(name="id_user")
     */
    private User $user;

    public function __construct(User $user, Company $company)
    {
        $this->company = $company;
        $this->user    = $user;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
