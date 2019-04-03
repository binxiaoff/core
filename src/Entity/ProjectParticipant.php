<?php
declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\Roleable;
use Unilend\Entity\Traits\Timestampable;

/**
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"id_project", "id_company"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ProjectParticipant
{
    use Roleable;
    use Timestampable;

    // Use COMPANY_ prefix to distinguish it from Symfony user's roles
    const COMPANY_ROLE_ARRANGER = 'COMPANY_ROLE_ARRANGER'; // The company who arranges a loan syndication.
    const COMPANY_ROLE_AGENT    = 'COMPANY_ROLE_AGENT'; // The company who ensures the smooth running of repayment process after the project is funded.
    const COMPANY_ROLE_RUN      = 'COMPANY_ROLE_RUN'; // The abbreviation of Responsable Unique de Notation, who gives a note on the borrower.
    const COMPANY_ROLE_LENDER   = 'COMPANY_ROLE_LENDER';

    const ROLE_DEFAULT = self::COMPANY_ROLE_LENDER;

    const ALL_ROLES = [self::COMPANY_ROLE_ARRANGER, self::COMPANY_ROLE_AGENT, self::COMPANY_ROLE_RUN, self::COMPANY_ROLE_LENDER];

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Projects", inversedBy="projectParticipants")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
     */
    private $project;

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies", inversedBy="projectParticipants")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company", referencedColumnName="id_company", nullable=false)
     * })
     */
    private $company;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Projects
     */
    public function getProject(): Projects
    {
        return $this->project;
    }

    /**
     * @param Projects $project
     *
     * @return ProjectParticipant
     */
    public function setProject(Projects $project): ProjectParticipant
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return Companies
     */
    public function getCompany(): Companies
    {
        return $this->company;
    }

    /**
     * @param Companies $company
     *
     * @return ProjectParticipant
     */
    public function setCompany(Companies $company): ProjectParticipant
    {
        $this->company = $company;

        return $this;
    }

    public function isArranger(): bool
    {
        return in_array(self::COMPANY_ROLE_ARRANGER, $this->getRoles());
    }
}
