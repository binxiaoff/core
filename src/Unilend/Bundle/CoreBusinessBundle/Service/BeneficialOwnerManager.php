<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Knp\Snappy\GeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig_Environment;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectBeneficialOwnerUniversign;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface;
use Unilend\Bundle\FrontBundle\Service\UniversignManager;

class BeneficialOwnerManager
{
    const EXCEPTION_CODE_BENEFICIAL_OWNER_MANAGER = 3;

    const BENEFICIAL_OWNER_DECLARATION_EXEMPTED_LEGAL_FORM_CODES = [1100, 1200, 1300, 1500, 1600, 1700, 1900];

    const VALIDATION_TYPE_UNIVERSIGN   = 'Universign';

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

    /**
     * @param EntityManager      $entityManager
     * @param GeneratorInterface $snappy
     * @param Twig_Environment   $twig
     * @param RouterInterface    $router
     * @param UniversignManager  $universignManager
     * @param                    $protectedPath
     */
    public function __construct(
        EntityManager $entityManager,
        GeneratorInterface $snappy,
        Twig_Environment $twig,
        RouterInterface $router,
        UniversignManager $universignManager,
        $protectedPath
    )
    {
        $this->entityManager     = $entityManager;
        $this->snappy            = $snappy;
        $this->twig              = $twig;
        $this->router            = $router;
        $this->universignManager = $universignManager;
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

        return false === in_array($project->getIdCompany()->getLegalFormCode(), self::BENEFICIAL_OWNER_DECLARATION_EXEMPTED_LEGAL_FORM_CODES);
    }
}
