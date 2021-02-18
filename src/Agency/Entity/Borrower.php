<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Core\Entity\Constant\LegalForm;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Traits\BlamableAddedTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Validator\Constraints\{Rcs as AssertRcs, Siren as AssertSiren};

/**
 * @ApiResource(
 *     normalizationContext={
 *         "groups": {
 *             "agency:borrower:read",
 *             "money:read"
 *         }
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "agency:borrower:write",
 *             "money:write"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *              "denormalization_context": {
 *                  "groups": {"agency:borrower:create", "money:write"}
 *              },
 *             "security_post_denormalize": "is_granted('create', object)",
 *         }
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     }
 * )
 *
 * @ORM\Table(name="agency_borrower")
 * @ORM\Entity
 */
class Borrower
{
    use PublicizeIdentityTrait;
    use BlamableAddedTrait;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Project", inversedBy="borrowers")
     *
     * @Assert\NotBlank
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create"})
     */
    private Project $project;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="100")
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:write"})
     */
    private string $corporateName;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback={LegalForm::class, "getConstList"})
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:write"})
     */
    private string $legalForm;

    /**
     * @var Money
     *
     * @Assert\Valid
     * @Assert\NotBlank
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:write"})
     */
    private Money $capital;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     *
     * @Assert\Length(max="100")
     * @Assert\NotBlank
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:write"})
     */
    private string $headquarterAddress;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="100")
     *
     * @AssertRcs
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:write"})
     */
    private string $matriculationNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:write"})
     */
    private string $matriculationCity;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=9)
     *
     * @Assert\NotBlank
     *
     * @AssertSiren
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:write"})
     */
    private string $siren;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:write"})
     */
    private string $signatoryFirstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:write"})
     */
    private string $signatoryLastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     * @Assert\Email
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:write"})
     */
    private string $signatoryEmail;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:write"})
     */
    private string $referentFirstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:write"})
     */
    private string $referentLastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="50")
     * @Assert\Email
     *
     * @Groups({"agency:borrower:read", "agency:borrower:create", "agency:borrower:write"})
     */
    private string $referentEmail;

    /**
     * @var Collection|BorrowerTrancheShare[]
     *
     * @ORM\OneToMany(targetEntity="BorrowerTrancheShare", mappedBy="borrower")
     */
    private Collection $trancheShares;

    /**
     * @param Project $project
     * @param Staff   $addedBy
     * @param string  $corporateName
     * @param string  $legalForm
     * @param Money   $capital
     * @param string  $headquarterAddress
     * @param string  $matriculationNumber
     * @param string  $matriculationCity
     * @param string  $siren
     * @param string  $signatoryFirstName
     * @param string  $signatoryLastName
     * @param string  $signatoryEmail
     * @param string  $referentFirstName
     * @param string  $referentLastName
     * @param string  $referentEmail
     */
    public function __construct(
        Project $project,
        Staff $addedBy,
        string $corporateName,
        string $legalForm,
        Money $capital,
        string $headquarterAddress,
        string $matriculationNumber,
        string $matriculationCity,
        string $siren,
        string $signatoryFirstName,
        string $signatoryLastName,
        string $signatoryEmail,
        string $referentFirstName,
        string $referentLastName,
        string $referentEmail
    ) {
        $this->project = $project;
        $this->addedBy = $addedBy;
        $this->corporateName = $corporateName;
        $this->legalForm = $legalForm;
        $this->capital = $capital;
        $this->headquarterAddress = $headquarterAddress;
        $this->matriculationNumber = $matriculationNumber;
        $this->matriculationCity = $matriculationCity;
        $this->siren = $siren;
        $this->signatoryFirstName = $signatoryFirstName;
        $this->signatoryLastName = $signatoryLastName;
        $this->signatoryEmail = $signatoryEmail;
        $this->referentFirstName = $referentFirstName;
        $this->referentLastName = $referentLastName;
        $this->referentEmail = $referentEmail;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return string
     */
    public function getCorporateName(): string
    {
        return $this->corporateName;
    }

    /**
     * @param string $corporateName
     *
     * @return Borrower
     */
    public function setCorporateName(string $corporateName): Borrower
    {
        $this->corporateName = $corporateName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLegalForm(): string
    {
        return $this->legalForm;
    }

    /**
     * @param string $legalForm
     *
     * @return Borrower
     */
    public function setLegalForm(string $legalForm): Borrower
    {
        $this->legalForm = $legalForm;

        return $this;
    }

    /**
     * @return Money
     */
    public function getCapital(): Money
    {
        return $this->capital;
    }

    /**
     * @param Money $capital
     *
     * @return Borrower
     */
    public function setCapital(Money $capital): Borrower
    {
        $this->capital = $capital;

        return $this;
    }

    /**
     * @return string
     */
    public function getHeadquarterAddress(): string
    {
        return $this->headquarterAddress;
    }

    /**
     * @param string $headquarterAddress
     *
     * @return Borrower
     */
    public function setHeadquarterAddress(string $headquarterAddress): Borrower
    {
        $this->headquarterAddress = $headquarterAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getMatriculationNumber(): string
    {
        return $this->matriculationNumber;
    }

    /**
     * @param string $matriculationNumber
     *
     * @return Borrower
     */
    public function setMatriculationNumber(string $matriculationNumber): Borrower
    {
        $this->matriculationNumber = $matriculationNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getMatriculationCity(): string
    {
        return $this->matriculationCity;
    }

    /**
     * @param string $matriculationCity
     *
     * @return Borrower
     */
    public function setMatriculationCity(string $matriculationCity): Borrower
    {
        $this->matriculationCity = $matriculationCity;

        return $this;
    }

    /**
     * @return string
     */
    public function getSiren(): string
    {
        return $this->siren;
    }

    /**
     * @param string $siren
     *
     * @return Borrower
     */
    public function setSiren(string $siren): Borrower
    {
        $this->siren = $siren;

        return $this;
    }

    /**
     * @return string
     */
    public function getSignatoryFirstName(): string
    {
        return $this->signatoryFirstName;
    }

    /**
     * @param string $signatoryFirstName
     *
     * @return Borrower
     */
    public function setSignatoryFirstName(string $signatoryFirstName): Borrower
    {
        $this->signatoryFirstName = $signatoryFirstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getSignatoryLastName(): string
    {
        return $this->signatoryLastName;
    }

    /**
     * @param string $signatoryLastName
     *
     * @return Borrower
     */
    public function setSignatoryLastName(string $signatoryLastName): Borrower
    {
        $this->signatoryLastName = $signatoryLastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getSignatoryEmail(): string
    {
        return $this->signatoryEmail;
    }

    /**
     * @param string $signatoryEmail
     *
     * @return Borrower
     */
    public function setSignatoryEmail(string $signatoryEmail): Borrower
    {
        $this->signatoryEmail = $signatoryEmail;

        return $this;
    }

    /**
     * @return string
     */
    public function getReferentFirstName(): string
    {
        return $this->referentFirstName;
    }

    /**
     * @param string $referentFirstName
     *
     * @return Borrower
     */
    public function setReferentFirstName(string $referentFirstName): Borrower
    {
        $this->referentFirstName = $referentFirstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getReferentLastName(): string
    {
        return $this->referentLastName;
    }

    /**
     * @param string $referentLastName
     *
     * @return Borrower
     */
    public function setReferentLastName(string $referentLastName): Borrower
    {
        $this->referentLastName = $referentLastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getReferentEmail(): string
    {
        return $this->referentEmail;
    }

    /**
     * @param string $referentEmail
     *
     * @return Borrower
     */
    public function setReferentEmail(string $referentEmail): Borrower
    {
        $this->referentEmail = $referentEmail;

        return $this;
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    public function validateMatriculationNumberConsistency(ExecutionContextInterface $context)
    {
        $tokens = explode(' ', $this->matriculationNumber);

        $city = array_filter($tokens, static fn ($token) => false === preg_match("/^(RCS|A|B|\d+)$/", $token));
        $city = implode(' ', $city);

        if ($city !== $this->getMatriculationCity()) {
            $context->buildViolation('Agency.Borrower.matriculationNumber.inconsistentCity')
                ->setInvalidValue($this->matriculationCity)
                ->setParameter('city', $city ?? '')
                ->atPath('matriculationNumber')
                ->addViolation();
        }

        $siren = end($tokens);

        if ($siren !== $this->getSiren()) {
            $context->buildViolation('Agency.Borrower.matriculationNumber.inconsistentSiren')
                ->setInvalidValue($this->matriculationNumber)
                ->setParameter('siren', $siren ?? '')
                ->atPath('matriculationNumber')
                ->addViolation();
        }
    }
}
