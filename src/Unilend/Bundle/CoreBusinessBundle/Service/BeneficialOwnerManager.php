<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\BeneficialOwnerDeclaration;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBeneficialOwner;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;

class BeneficialOwnerManager
{
    const EXCEPTION_CODE_BENEFICIAL_OWNER_MANAGER = 3;

    const BENEFICIAL_OWNER_ATTACHMENT_TYPES = [
        AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_1,
        AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_2,
        AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_3,
        AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_VERSO_1,
        AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_VERSO_2,
        AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_VERSO_3
    ];

    /** @var EntityManager */
    private $entityManager;


    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    public function getDeclarationForCompany(Companies $company)
    {



    }

    public function getDeclarationForProject(Projects $project)
    {

    }


    public function saveBeneficialOwner(Clients $client, ClientsAdresses $address, Companies $company, $percentage, $type)
    {

    }

    public function checkBeneficialOwner(Clients $beneficialOwner, ClientsAdresses $address)
    {
        if (null === $beneficialOwner->getNom() || null === $beneficialOwner->getPrenom()) {
            throw new \Exception('Beneficial owner must have a first and last name', self::EXCEPTION_CODE_BENEFICIAL_OWNER_MANAGER);
        }

        if (
            null === $beneficialOwner->getNaissance()
            || null === $beneficialOwner->getIdPaysNaissance()
            || null === $this->entityManager->getRepository('UnilendCoreBusinessBundle:PaysV2')->find($beneficialOwner->getIdPaysNaissance())
        ) {
            throw new \Exception('Beneficial owner must have a birthdate and a valid birth country', self::EXCEPTION_CODE_BENEFICIAL_OWNER_MANAGER);
        }

        if (null === $address->getIdPaysFiscal() || null === $this->entityManager->getRepository('UnilendCoreBusinessBundle:PaysV2')->find($address->getIdPaysFiscal())) {
            throw new \Exception('Beneficial owner must have a valid country of residence', self::EXCEPTION_CODE_BENEFICIAL_OWNER_MANAGER);
        }

        $beneficialOwner->getAttachments(); //TODO check attachment types

        return true;
    }
}
