<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Knp\Snappy\GeneratorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;
use Twig_Environment;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\BeneficialOwner;
use Unilend\Bundle\CoreBusinessBundle\Entity\BeneficialOwnerType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBeneficialOwnerDeclaration;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectBeneficialOwnerUniversign;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface;
use Unilend\Bundle\CoreBusinessBundle\Repository\BeneficialOwnerRepository;
use Unilend\Bundle\FrontBundle\Service\UniversignManager;

class BeneficialOwnerManager
{
    const EXCEPTION_CODE_BENEFICIAL_OWNER_MANAGER = 3;
    const MAX_NUMBER_BENEFICIAL_OWNERS            = 4;

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

    /**
     * @param EntityManager      $entityManager
     * @param GeneratorInterface $snappy
     * @param Twig_Environment   $twig
     * @param RouterInterface    $router
     * @param UniversignManager  $universignManager
     * @param AttachmentManager  $attachmentManager
     * @param                    $protectedPath
     */
    public function __construct(
        EntityManager $entityManager,
        GeneratorInterface $snappy,
        Twig_Environment $twig,
        RouterInterface $router,
        UniversignManager $universignManager,
        AttachmentManager $attachmentManager,
        $protectedPath
    ) {
        $this->entityManager     = $entityManager;
        $this->snappy            = $snappy;
        $this->twig              = $twig;
        $this->router            = $router;
        $this->universignManager = $universignManager;
        $this->attachmentManager = $attachmentManager;
        $this->protectedPath     = $protectedPath;
    }

    /**
     * @return string
     */
    public function getBeneficialOwnerDeclarationPdfRoot()
    {
        return $this->protectedPath . 'pdf/beneficial_owner';
    }

    /**
     * @param ProjectBeneficialOwnerUniversign $universignDeclaration
     *
     * @return string
     */
    public function getBeneficialOwnerDeclarationFileName(ProjectBeneficialOwnerUniversign $universignDeclaration)
    {
        $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($universignDeclaration->getIdProject()->getIdCompany()->getIdClientOwner());

        return $client->getHash(). '-' . ProjectBeneficialOwnerUniversign::DOCUMENT_NAME . '-' . $universignDeclaration->getIdProject()->getIdProject() . '.pdf';
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

        $beneficialOwnerDeclaration = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectBeneficialOwnerUniversign')->findOneBy(['idProject' => $project], ['added' => 'DESC']);

        if (
            null === $beneficialOwnerDeclaration
            || null === $client
            || $beneficialOwnerDeclaration->getIdProject()->getIdCompany()->getIdClientOwner() != $client->getIdClient()
            || UniversignEntityInterface::STATUS_ARCHIVED === $beneficialOwnerDeclaration->getStatus()
        ){
            return [
                'action' => 'redirect',
                'url'    => $this->router->generate('home')
            ];
        }

        $beneficialOwnerDeclarationPdfRoot  = $this->getBeneficialOwnerDeclarationPdfRoot();
        $beneficialOwnerDeclarationFileName = $this->getBeneficialOwnerDeclarationFileName($beneficialOwnerDeclaration);

        if (
            UniversignEntityInterface::STATUS_SIGNED === $beneficialOwnerDeclaration->getStatus()
            && file_exists($beneficialOwnerDeclarationPdfRoot . DIRECTORY_SEPARATOR . $beneficialOwnerDeclarationFileName)
        ) {
            return [
                'action' => 'read',
                'path'   => $beneficialOwnerDeclarationPdfRoot . DIRECTORY_SEPARATOR . $beneficialOwnerDeclarationFileName,
                'name'   => $beneficialOwnerDeclarationFileName
            ];
        }

        if (false === file_exists($beneficialOwnerDeclarationPdfRoot . DIRECTORY_SEPARATOR . $beneficialOwnerDeclarationFileName)) {
            $this->generatePdfFile($beneficialOwnerDeclaration);
        }

        if (UniversignEntityInterface::STATUS_PENDING === $beneficialOwnerDeclaration->getStatus()) {
            if (null !== $beneficialOwnerDeclaration->getUrlUniversign()) {
                return [
                    'action' => 'sign',
                    'url'    => $beneficialOwnerDeclaration->getUrlUniversign()
                ];
            }

            if ($this->universignManager->createBeneficialOwnerDeclaration($beneficialOwnerDeclaration)) {
                return [
                    'action' => 'sign',
                    'url'    => $beneficialOwnerDeclaration->getUrlUniversign()
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
    public function generatePdfFile(ProjectBeneficialOwnerUniversign $universignDeclaration)
    {
        //TODO further variables to be defined when doing the PDF.
        $beneficialOwners   = $universignDeclaration->getIdDeclaration()->getBeneficialOwner();
        $pdfContent         = $this->twig->render('/pdf/beneficial_owner_declaration.html.twig', ['owners' => $beneficialOwners]);
        $outputFile         = $this->getBeneficialOwnerDeclarationPdfRoot() . DIRECTORY_SEPARATOR . $this->getBeneficialOwnerDeclarationFileName($universignDeclaration);
        $options            = [
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
     * @param string                            $lastName
     * @param string                            $firstName
     * @param \DateTime                         $birthday
     * @param string                            $birthPlace
     * @param int                               $idBirthCountry
     * @param int                               $countryOfResidence
     * @param UploadedFile                      $passport
     * @param BeneficialOwnerType|null          $type
     * @param string|null                       $percentage
     *
     * @return BeneficialOwner
     */
    public function createBeneficialOwner(
        CompanyBeneficialOwnerDeclaration $declaration,
        $lastName,
        $firstName,
        \DateTime $birthday,
        $birthPlace,
        $idBirthCountry,
        $countryOfResidence,
        UploadedFile $passport,
        BeneficialOwnerType $type = null,
        $percentage = null
    ) {
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

        $beneficialOwner = new BeneficialOwner();
        $beneficialOwner->setIdClient($owner)
            ->setIdDeclaration($declaration)
            ->setPercentageDetained($percentage)
            ->setIdType($type);

        $this->entityManager->persist($beneficialOwner);

        $attachmentType    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->find(AttachmentType::CNI_PASSPORTE);
        if ($attachmentType) {
            $this->attachmentManager->upload($owner, $attachmentType, $passport);
        }

        $this->entityManager->flush([$declaration, $owner, $ownerAddress, $beneficialOwner]);

        return $beneficialOwner;
    }
}
