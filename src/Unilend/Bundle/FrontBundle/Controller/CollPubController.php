<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, Partner, ProjectAbandonReason, Projects, ProjectsStatus, Users, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Service\{PartnerManager, ProjectManager, ProjectStatusManager};

/**
 * @Security("has_role('ROLE_BORROWER')")
 * jane@doe.com Unilend2019
 */
class CollPubController extends Controller
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
    }

    /**
     * @Route("/collpub", name="collpub_loans")
     *
     * @param UserInterface|Clients|null $user
     *
     * @return Response
     */
    public function loans(?UserInterface $user): Response
    {
        $template          = [];
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $companyRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
        $company           = $companyRepository->findOneBy(['idClientOwner' => $user]);
        $projects          = $projectRepository->findBy(['idCompany' => $company], ['status' => 'ASC', 'updated' => 'DESC']);

        foreach ($projects as $project) {
            $template['projects'][$project->getStatus()][] = $project;
        }

        return $this->render('/collpub/loans.html.twig', $template);
    }

    /**
     * @Route("/collpub/depot", name="collpub_project_request", methods={"GET"})
     *
     * @param ProjectManager $projectManager
     * @param PartnerManager $partnerManager
     *
     * @return Response
     */
    public function projectRequest(ProjectManager $projectManager, PartnerManager $partnerManager): Response
    {
        $partner  = $partnerManager->getDefaultPartner();
        $template = [
            'loanPeriods' => $projectManager->getPossibleProjectPeriods(),
            'products'    => $this->entityManager->getRepository('UnilendCoreBusinessBundle:PartnerProduct')->findBy(['idPartner' => $partner])
        ];

        return $this->render('/collpub/project_request.html.twig', $template);
    }

    /**
     * @Route("/collpub/depot", name="collpub_project_request_form", methods={"POST"})
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $user
     * @param ProjectStatusManager       $projectStatusManager
     *
     * @return RedirectResponse
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function projectRequestFormAction(Request $request, ?UserInterface $user, ProjectStatusManager $projectStatusManager): RedirectResponse
    {
        $formData    = $request->request->get('simulator');
        $title       = $formData['title'] ?? null;
        $amount      = $formData['amount'] ?? null;
        $duration    = $formData['duration'] ?? null;
        $product     = $formData['product'] ?? null;
        $end         = $formData['end'] ?? '00/00/0000';
        $description = $formData['description'] ?? null;

        $partner = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->findOneBy(['label' => Partner::PARTNER_CACIB_LABEL]);
        $company = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $user]);

        try {
            $this->entityManager->beginTransaction();

            $project = new Projects();
            $project
                ->setIdPartner($partner)
                ->setIdCompany($company)
                ->setTitle($title)
                ->setSlug($this->entityManager->getConnection()->generateSlug($title))
                ->setAmount($amount)
                ->setPeriod($duration)
                ->setIdProduct($product)
                ->setDateFin(\DateTime::createFromFormat('d/m/Y H:i:s', $end . ' 00:00:00'))
                ->setComments($description)
                ->setCreateBo(false)
                ->setRisk(['A', 'B', 'C', 'D', 'E'][rand(0, 4)])
                ->setStatus(ProjectsStatus::STATUS_REQUEST);

            $this->entityManager->persist($project);
            $this->entityManager->flush($project);

            $projectStatusManager->addProjectStatus(Users::USER_ID_FRONT, $project->getStatus(), $project);

            $this->entityManager->commit();

            return $this->redirectToRoute('collpub_project_request_summary', ['hash' => $project->getHash()]);
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();

            $this->logger->error('An error occurred while creating project. Message: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);
        }

        return $this->redirectToRoute('collpub_project_request');
    }

    /**
     * @Route("/collpub/depot/{hash}", name="collpub_project_request_summary", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param string $hash
     *
     * @return Response
     */
    public function projectRequestSummary(string $hash): Response
    {
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (false === $project instanceof Projects) {
            return $this->redirectToRoute('collpub_loans');
        }

        $template = [
            'project' => $project,
            'product' => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Product')->find($project->getIdProduct())
        ];

        return $this->render('/collpub/project_request_summary.html.twig', $template);
    }

    /**
     * @Route("/collpub/projet/{hash}", name="collpub_project_request_details", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param string $hash
     *
     * @return Response
     */
    public function projectRequestDetails(string $hash): Response
    {
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (false === $project instanceof Projects) {
            return $this->redirectToRoute('collpub_loans');
        }

        $template = [
            'project'        => $project,
            'product'        => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Product')->find($project->getIdProduct()),
            'abandonReasons' => $this->entityManager
                ->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason')
                ->findBy(['status' => ProjectAbandonReason::STATUS_ONLINE], ['reason' => 'ASC'])
        ];

        return $this->render('/collpub/project_request_details.html.twig', $template);
    }

    /**
     * @Route("/depot/submit/{hash}", name="collpub_project_request_submit", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param string               $hash
     * @param ProjectStatusManager $projectStatusManager
     *
     * @return RedirectResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function projectRequestSubmit(string $hash, ProjectStatusManager $projectStatusManager): RedirectResponse
    {
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (false === $project instanceof Projects || $project->getStatus() >= ProjectsStatus::STATUS_CANCELLED) {
            return $this->redirectToRoute('collpub_loans');
        }

        $projectStatusManager->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::STATUS_REVIEW, $project);

        return $this->redirectToRoute('collpub_loans');
    }

    /**
     * @Route("/depot/abandon/{hash}", name="collpub_project_request_cancel", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param string               $hash
     * @param ProjectStatusManager $projectStatusManager
     *
     * @return RedirectResponse
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function projectRequestAbandon(string $hash, ProjectStatusManager $projectStatusManager): RedirectResponse
    {
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (false === $project instanceof Projects || $project->getStatus() >= ProjectsStatus::STATUS_CANCELLED) {
            return $this->redirect('collpub_loans');
        }

        $projectStatusManager->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::STATUS_CANCELLED, $project);

        return $this->redirectToRoute('collpub_loans');
    }
}
