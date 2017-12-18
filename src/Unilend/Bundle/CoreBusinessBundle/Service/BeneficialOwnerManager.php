<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Knp\Snappy\GeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;
use Twig_Environment;
use Unilend\Bundle\CoreBusinessBundle\Entity\Attachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\BeneficialOwner;
use Unilend\Bundle\CoreBusinessBundle\Entity\BeneficialOwnerType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBeneficialOwnerDeclaration;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectBeneficialOwnerUniversign;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface;
use Unilend\Bundle\FrontBundle\Service\UniversignManager;

class BeneficialOwnerManager
{
    const MAX_NUMBER_BENEFICIAL_OWNERS_TYPE_SHAREHOLDER   = 4;
    const MAX_NUMBER_BENEFICIAL_OWNERS_TYPE_LEGAL_MANAGER = 1;

    const BENEFICIAL_OWNER_DECLARATION_EXEMPTED_LEGAL_FORM_CODES = [1100, 1200, 1300, 1500, 1600, 1700, 1900];

    /** @var EntityManager */
    private $entityManager;
    /** @var GeneratorInterface $snappy */
    private $snappy;
    /** @var Twig_Environment */
    private $twig;
    /** @var string */
    private $protectedPath;
    /** @var RouterInterface */
    private $router;
    /** @var UniversignManager */
    private $universignManager;
    /** @var AttachmentManager */
    private $attachmentManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManager      $entityManager
     * @param GeneratorInterface $snappy
     * @param Twig_Environment   $twig
     * @param RouterInterface    $router
     * @param UniversignManager  $universignManager
     * @param AttachmentManager  $attachmentManager
     * @param string             $protectedPath
     */
    public function __construct(
        EntityManager $entityManager,
        GeneratorInterface $snappy,
        Twig_Environment $twig,
        RouterInterface $router,
        UniversignManager $universignManager,
        AttachmentManager $attachmentManager,
        LoggerInterface $logger,
        $protectedPath
    )
    {
        $this->entityManager     = $entityManager;
        $this->snappy            = $snappy;
        $this->twig              = $twig;
        $this->router            = $router;
        $this->universignManager = $universignManager;
        $this->attachmentManager = $attachmentManager;
        $this->protectedPath     = $protectedPath;
        $this->logger            = $logger;
    }

    /**
     * @param CompanyBeneficialOwnerDeclaration $declaration
     * @param Projects                          $project
     *
     * @return ProjectBeneficialOwnerUniversign
     * @throws \Exception
     */
    public function addProjectBeneficialOwnerDeclaration(CompanyBeneficialOwnerDeclaration $declaration, Projects $project)
    {
        if ($declaration->getIdCompany() !== $project->getIdCompany()) {
            throw new \Exception('Project Company and declaration company must be the same entity');
        }

        $universign = new ProjectBeneficialOwnerUniversign();
        $universign
            ->setIdDeclaration($declaration)
            ->setIdProject($project)
            ->setStatus(UniversignEntityInterface::STATUS_PENDING);

        $universign->setName($this->getBeneficialOwnerDeclarationFileName($universign));

        $this->entityManager->persist($universign);
        $this->entityManager->flush($universign);

        return $universign;
    }

    /**
     * @return string
     */
    public function getBeneficialOwnerDeclarationPdfRoot()
    {
        return $this->protectedPath . 'pdf/beneficial_owner';
    }

    /**
     * @param ProjectBeneficialOwnerUniversign $projectDeclaration
     *
     * @return string
     */
    public function getBeneficialOwnerDeclarationFileName(ProjectBeneficialOwnerUniversign $projectDeclaration)
    {
        $project = $projectDeclaration->getIdProject();

        return $project->getIdCompany()->getIdClientOwner()->getHash() . '-' . ProjectBeneficialOwnerUniversign::DOCUMENT_NAME . '-' . $project->getIdProject() . '.pdf';
    }

    /**
     * @param Projects|\projects $project
     * @param Clients|\clients   $client
     *
     * @return array
     */
    public function createProjectBeneficialOwnerDeclaration($project, $client)
    {
        if ($project instanceof \projects) {
            $project = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project);
        }

        if ($client instanceof \clients) {
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        if (
            null === $project
            || null === $client
            || $project->getIdCompany()->getIdClientOwner() != $client
        ) {
            return [
                'action' => 'redirect',
                'url'    => $this->router->generate('home')
            ];
        }

        $projectDeclaration = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectBeneficialOwnerUniversign')->findOneBy(['idProject' => $project, 'status' => [UniversignEntityInterface::STATUS_PENDING, UniversignEntityInterface::STATUS_SIGNED]], ['added' => 'DESC']);
        if (null === $projectDeclaration) {
            $companyDeclaration = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyBeneficialOwnerDeclaration')->findCurrentDeclarationByCompany($project->getIdCompany());
            $projectDeclaration = $this->addProjectBeneficialOwnerDeclaration($companyDeclaration, $project);
        }

        $beneficialOwnerDeclarationPdfRoot  = $this->getBeneficialOwnerDeclarationPdfRoot();
        $beneficialOwnerDeclarationFileName = $this->getBeneficialOwnerDeclarationFileName($projectDeclaration);

        if (
            UniversignEntityInterface::STATUS_SIGNED === $projectDeclaration->getStatus()
            && file_exists($beneficialOwnerDeclarationPdfRoot . DIRECTORY_SEPARATOR . $beneficialOwnerDeclarationFileName)
        ) {
            return [
                'action' => 'read',
                'path'   => $beneficialOwnerDeclarationPdfRoot . DIRECTORY_SEPARATOR . $beneficialOwnerDeclarationFileName,
                'name'   => $beneficialOwnerDeclarationFileName
            ];
        }

        if (false === file_exists($beneficialOwnerDeclarationPdfRoot . DIRECTORY_SEPARATOR . $beneficialOwnerDeclarationFileName)) {
            $this->generateProjectPdfFile($projectDeclaration);
        }

        if (UniversignEntityInterface::STATUS_PENDING === $projectDeclaration->getStatus()) {
            if (null !== $projectDeclaration->getUrlUniversign()) {
                return [
                    'action' => 'sign',
                    'url'    => $projectDeclaration->getUrlUniversign()
                ];
            }

            if ($this->universignManager->createBeneficialOwnerDeclaration($projectDeclaration)) {
                return [
                    'action' => 'sign',
                    'url'    => $projectDeclaration->getUrlUniversign()
                ];
            }
        }

        return [
            'action' => 'redirect',
            'url'    => $this->router->generate('home')
        ];
    }

    /**
     * @param ProjectBeneficialOwnerUniversign $universignDeclaration
     */
    public function generateProjectPdfFile(ProjectBeneficialOwnerUniversign $projectDeclaration)
    {
        $template   = [
            'owners'  => $this->getOwnerDataFromDeclaration($projectDeclaration->getIdDeclaration()),
            'company' => $projectDeclaration->getIdProject()->getIdCompany()
        ];
        $pdfContent = $this->twig->render('/pdf/beneficial_owner_declaration.html.twig', $template);
        $outputFile = $this->getBeneficialOwnerDeclarationPdfRoot() . DIRECTORY_SEPARATOR . $this->getBeneficialOwnerDeclarationFileName($projectDeclaration);
        $options    = [
            'footer-html'   => '',
            'header-html'   => '',
            'margin-top'    => 20,
            'margin-right'  => 15,
            'margin-bottom' => 10,
            'margin-left'   => 15
        ];
        $this->snappy->generateFromHtml($pdfContent, $outputFile, $options, true);
    }

    /**
     * @param CompanyBeneficialOwnerDeclaration $declaration
     *
     * @return string
     */
    public function generateCompanyPdfFile(CompanyBeneficialOwnerDeclaration $declaration)
    {
        $template   = [
            'owners'     => $this->getOwnerDataFromDeclaration($declaration),
            'company'    => $declaration->getIdCompany(),
            'disclaimer' => 'Ce document a pour but le contrôle de contenu et n\'est en aucun cas representatif de la mise en forme du document final.'
        ];
        $pdfContent = $this->twig->render('/pdf/beneficial_owner_declaration.html.twig', $template);
        $options    = [
            'footer-html'   => '',
            'header-html'   => '',
            'margin-top'    => 20,
            'margin-right'  => 15,
            'margin-bottom' => 10,
            'margin-left'   => 15
        ];

        return $this->snappy->getOutputFromHtml($pdfContent, $options);
    }

    /**
     * @param CompanyBeneficialOwnerDeclaration $declaration
     * @param string                            $lastName
     * @param string                            $firstName
     * @param \DateTime                         $birthday
     * @param string                            $birthPlace
     * @param int                               $idBirthCountry
     * @param int                               $countryOfResidence
     * @param UploadedFile|Attachment           $passport
     * @param BeneficialOwnerType|null          $type
     * @param string|null                       $percentage
     * @param int|null                          $idClient
     *
     * @return BeneficialOwner
     *
     * @throws \Exception
     */
    public function createBeneficialOwner(
        CompanyBeneficialOwnerDeclaration $declaration,
        $lastName,
        $firstName,
        \DateTime $birthday,
        $birthPlace,
        $idBirthCountry,
        $countryOfResidence,
        $passport,
        BeneficialOwnerType $type = null,
        $percentage = null,
        $idClient = null
    )
    {
        if (empty($idClient)) {
            $owner = new Clients();
            $owner->setPrenom($firstName)
                ->setNom($lastName)
                ->setNaissance($birthday)
                ->setVilleNaissance($birthPlace)
                ->setIdPaysNaissance($idBirthCountry);

            $this->entityManager->persist($owner);

            $ownerAddress = new ClientsAdresses();
            $ownerAddress->setIdPaysFiscal($countryOfResidence)
                ->setIdClient($owner);

            $this->entityManager->persist($ownerAddress);
        } else {
            $owner = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($idClient);
            if (null === $owner) {
                throw new \Exception('The client with id ' . $idClient . ' does not exist and can not be used as beneficial Owner');
            }

            if (empty($owner->getPrenom())) {
                $owner->setPrenom($firstName);
            }

            if (empty($owner->getNom())) {
                $owner->setNom($lastName);
            }

            if (empty($owner->getNaissance())) {
                $owner->setNaissance($birthday);
            }

            if (empty($owner->getVilleNaissance())) {
                $owner->setVilleNaissance($birthPlace);
            }

            if (empty($owner->getIdPaysNaissance())) {
                $owner->setIdPaysNaissance($idBirthCountry);
            }

            $ownerAddress = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $owner]);
            if (null === $ownerAddress) {
                $ownerAddress = new ClientsAdresses();
                $ownerAddress->setIdPaysFiscal($countryOfResidence)
                    ->setIdClient($owner);

                $this->entityManager->persist($ownerAddress);
            } else {
                $ownerAddress->setIdPaysFiscal($countryOfResidence);
            }
        }

        $beneficialOwner = new BeneficialOwner();
        $beneficialOwner->setIdClient($owner)
            ->setIdDeclaration($declaration)
            ->setPercentageDetained($percentage)
            ->setIdType($type);

        $this->entityManager->persist($beneficialOwner);
        $this->entityManager->flush([$declaration, $owner, $ownerAddress, $beneficialOwner]);

        $attachmentType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->find(AttachmentType::CNI_PASSPORTE);
        if ($passport instanceof UploadedFile && $attachmentType) {
            $this->attachmentManager->upload($owner, $attachmentType, $passport);
        }

        if (CompanyBeneficialOwnerDeclaration::STATUS_PENDING === $declaration->getStatus()) {
            $this->modifyPendingCompanyDeclaration($declaration);
        }

        return $beneficialOwner;
    }

    /**
     * @param BeneficialOwner $owner
     * @param null|string     $type
     * @param null|string     $percentage
     *
     * @throws \Exception
     */
    public function modifyBeneficialOwner(BeneficialOwner $owner, $type = null, $percentage = null)
    {
        $ownerType = null;

        if (null !== $type) {
            $ownerType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:BeneficialOwnerType')->find($type);
            if (null === $ownerType) {
                throw new \Exception('BeneficialOwnerType ' . $type . ' does not exist.');
            }
        }

        switch ($owner->getIdDeclaration()->getStatus()) {
            case CompanyBeneficialOwnerDeclaration::STATUS_ARCHIVED:
                throw new \Exception('An archived declaration should not be edited. idDeclaration : ' . $owner->getIdDeclaration()->getId() . ' idOwner: ' . $owner->getId());
            case CompanyBeneficialOwnerDeclaration::STATUS_PENDING:
                $this->modifyOwnerInPendingDeclaration($owner, $ownerType, $percentage);
                break;
            case CompanyBeneficialOwnerDeclaration::STATUS_VALIDATED:
                $this->modifyOwnerInValidatedDeclaration($owner, $ownerType, $percentage);
                break;
            default:
                $this->logger->warning('CompanyBeneficialOwnerDeclaration status ' . $owner->getIdDeclaration()->getStatus() . ' is not supported.', ['idDeclaration' => $owner->getIdDeclaration()->getId(), 'class' => __CLASS__, 'function' => __FUNCTION__]);
                break;
        }
    }

    /**
     * @param BeneficialOwner          $owner
     * @param BeneficialOwnerType|null $type
     * @param string|null              $percentage
     *
     * @throws \Exception
     */
    private function modifyOwnerInPendingDeclaration(BeneficialOwner $owner, $type, $percentage)
    {
        $percentage = empty($percentage) ? null : $percentage;

        $owner->setIdType($type)
            ->setPercentageDetained($percentage);

        $this->entityManager->flush($owner);

        $this->modifyPendingCompanyDeclaration($owner->getIdDeclaration());
    }

    /**
     * @param CompanyBeneficialOwnerDeclaration $declaration
     *
     * @throws \Exception
     */
    public function modifyPendingCompanyDeclaration(CompanyBeneficialOwnerDeclaration $declaration)
    {
        $projectDeclarations = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectBeneficialOwnerUniversign')->findBy(['idDeclaration' => $declaration]);

        if (false === empty($projectDeclarations)) {
            foreach ($projectDeclarations as $universign) {
                $universign->setStatus(UniversignEntityInterface::STATUS_CANCELED);
                $this->entityManager->flush($universign);

                $beneficialOwnerDeclarationPdfRoot  = $this->getBeneficialOwnerDeclarationPdfRoot();
                $beneficialOwnerDeclarationFileName = $this->getBeneficialOwnerDeclarationFileName($universign);

                if (file_exists($beneficialOwnerDeclarationPdfRoot . DIRECTORY_SEPARATOR . $beneficialOwnerDeclarationFileName)) {
                    unlink($beneficialOwnerDeclarationPdfRoot . DIRECTORY_SEPARATOR . $beneficialOwnerDeclarationFileName);
                }

                $this->addProjectBeneficialOwnerDeclaration($universign->getIdDeclaration(), $universign->getIdProject());
            }
        }
    }

    /**
     * @param BeneficialOwner          $owner
     * @param BeneficialOwnerType|null $type
     * @param string|null              $percentage
     *
     * @throws \Exception
     */
    private function modifyOwnerInValidatedDeclaration($owner, $type, $percentage)
    {
        $owner->getIdDeclaration()->setStatus(CompanyBeneficialOwnerDeclaration::STATUS_ARCHIVED);

        $newDeclaration = clone $owner->getIdDeclaration();
        $newDeclaration->setStatus(CompanyBeneficialOwnerDeclaration::STATUS_PENDING);

        $this->entityManager->persist($newDeclaration);

        $newOwner = clone $owner;
        $newOwner->setIdDeclaration($newDeclaration)
            ->setIdType($type)
            ->setPercentageDetained($percentage);
        $this->entityManager->persist($newOwner);

        $this->entityManager->flush([$owner, $newOwner, $newDeclaration]);

        $projectDeclarations = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectBeneficialOwnerUniversign')->findBy(['idDeclaration' => $owner->getIdDeclaration()]);

        if (false === empty($projectDeclarations)) {
            foreach ($projectDeclarations as $universign) {
                switch ($universign->getStatus()) {
                    case UniversignEntityInterface::STATUS_PENDING:
                        $universign->setStatus(UniversignEntityInterface::STATUS_CANCELED);
                        $this->entityManager->flush($universign);
                        $this->createProjectBeneficialOwnerDeclaration($universign->getIdProject(), $universign->getIdProject()->getIdCompany()->getIdClientOwner());
                        break;
                    case UniversignEntityInterface::STATUS_SIGNED:
                        if (ProjectsStatus::REMBOURSEMENT > $universign->getIdProject()->getStatus()) {
                            $universign->setStatus(UniversignEntityInterface::STATUS_ARCHIVED);
                            $this->entityManager->flush($universign);
                            $this->createProjectBeneficialOwnerDeclaration($universign->getIdProject(), $universign->getIdProject()->getIdCompany()->getIdClientOwner());
                        }
                        break;
                    default:
                        //no impact
                        break;
                }
            }
        }
    }

    /**
     * @param string $type
     *
     * @return int
     * @throws \Exception
     */
    public function getMaxNumbersAccordingToType($type)
    {
        switch($type) {
            case BeneficialOwnerType::TYPE_LEGAL_MANAGER:
                return self::MAX_NUMBER_BENEFICIAL_OWNERS_TYPE_LEGAL_MANAGER;
            case BeneficialOwnerType::TYPE_SHAREHOLDER:
                return self::MAX_NUMBER_BENEFICIAL_OWNERS_TYPE_SHAREHOLDER;
            default:
                throw new \Exception('BeneficialOwnerType' . $type . ' does not exist');
        }
    }

    /**
     * @param \projects|Projects $project
     *
     * @return bool
     */
    public function projectNeedsBeneficialOwnerDeclaration($project)
    {
        if ($project instanceof \projects) {
            $project = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project);
        }

        return $this->companyNeedsBeneficialOwnerDeclaration($project->getIdCompany());
    }

    /**
     * @param Companies|int $company
     *
     * @return bool
     */
    public function companyNeedsBeneficialOwnerDeclaration($company)
    {
        if (false === $company instanceof Companies) {
            $company = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($company);
        }

        return false === in_array($company->getLegalFormCode(), self::BENEFICIAL_OWNER_DECLARATION_EXEMPTED_LEGAL_FORM_CODES);
    }

    /**
     * @param Companies|int $company
     *
     * @return bool
     */
    public function checkBeneficialOwnerDeclarationContainsAtLeastCompanyOwner($company)
    {
        if (false === $company instanceof Companies) {
            $company = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($company);
        }

        $currentDeclaration         = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyBeneficialOwnerDeclaration')
            ->findCurrentDeclarationByCompany($company);
        $companyOwnerBeneficialOwner = $this->entityManager->getRepository('UnilendCoreBusinessBundle:BeneficialOwner')
            ->findOneBy(['idDeclaration' => $currentDeclaration, 'idClient' => $company->getIdClientOwner()]);

        return null !== $companyOwnerBeneficialOwner;
    }

    /**
     * @param CompanyBeneficialOwnerDeclaration $declaration
     *
     * @return array
     */
    private function getOwnerDataFromDeclaration(CompanyBeneficialOwnerDeclaration $declaration)
    {
        $owners = [];
        foreach ($declaration->getBeneficialOwner() as $owner) {
            $clientAddress = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $owner->getIdClient()]);
            $owners[]        = [
                'owner'   => $owner,
                'country' => $clientAddress->getIdPaysFiscal()
            ];
        }

        return $owners;
    }
}
