<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, Loans, ProjectAbandonReason, Projects, ProjectsStatus, Users, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Service\{PartnerManager, ProjectManager, ProjectStatusManager};

/**
 * @Security("has_role('ROLE_USER')")
 */
class DemoController extends Controller
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
     * @Route("/accueil", name="demo_loans")
     *
     * @param UserInterface|Clients|null $user
     * @param PartnerManager             $partnerManager
     *
     * @return Response
     */
    public function loans(?UserInterface $user, PartnerManager $partnerManager): Response
    {
        $template          = ['projects' => []];
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

        if ($user->isLender()) {
            $loanRepository                 = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans');
            $loans                          = $loanRepository->findBy(['idLender' => $user->getWalletByType(WalletType::LENDER)]);
            $template['projects']['lender'] = array_map(function (Loans $loan) {
                return $loan->getProject();
            }, $loans);
        }

        if ($user->isBorrower()) {
            $companyRepository                = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
            $borrowerCompany                  = $companyRepository->findOneBy(['idClientOwner' => $user]);
            $template['projects']['borrower'] = $projectRepository->findBy(['idCompany' => $borrowerCompany]);
        }

        if ($user->isPartner()) {
            $companies                      = $partnerManager->getUserCompanies($user);
            $submitter                      = in_array(Clients::ROLE_PARTNER, $user->getRoles()) ? $user->getIdClient() : null;
            $template['projects']['broker'] = $projectRepository->getPartnerProjects($companies, null, $submitter);
        }

        return $this->render('/demo/loans.html.twig', $template);
    }

    /**
     * @Route("/depot", name="demo_project_request", methods={"GET"})
     *
     * @param UserInterface|Clients|null $user
     * @param ProjectManager             $projectManager
     * @param PartnerManager             $partnerManager
     *
     * @return Response
     */
    public function projectRequest(?UserInterface $user, ProjectManager $projectManager, PartnerManager $partnerManager): Response
    {
        $borrowers = [];
        $partner   = $partnerManager->getPartner($user) ?? $partnerManager->getDefaultPartner();

        // @todo check test and add requester if user is a partner
        if ([Clients::ROLE_BORROWER] === array_intersect($user->getRoles(), [Clients::ROLE_BORROWER, Clients::ROLE_PARTNER])) {
            $borrowers[] = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $user]);
        }

        $template = [
            'loanPeriods' => $projectManager->getPossibleProjectPeriods(),
            'borrowers'   => $borrowers,
            'products'    => $this->entityManager->getRepository('UnilendCoreBusinessBundle:PartnerProduct')->findBy(['idPartner' => $partner])
        ];

        return $this->render('/demo/project_request.html.twig', $template);
    }

    /**
     * @Route("/depot", name="demo_project_request_form", methods={"POST"})
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $user
     * @param PartnerManager             $partnerManager
     * @param ProjectStatusManager       $projectStatusManager
     *
     * @return RedirectResponse
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function projectRequestFormAction(Request $request, ?UserInterface $user, PartnerManager $partnerManager, ProjectStatusManager $projectStatusManager): RedirectResponse
    {
        $formData    = $request->request->get('simulator');
        $company     = $formData['company'] ?? null;
        $product     = $formData['product'] ?? null;
        $title       = $formData['title'] ?? null;
        $amount      = $formData['amount'] ?? null;
        $duration    = $formData['duration'] ?? null;
        $description = $formData['description'] ?? null;

        $partner = $partnerManager->getPartner($user) ?? $partnerManager->getDefaultPartner();

        if ($company) {
            $company = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($company);
        }

        if (null === $company && [Clients::ROLE_BORROWER] === array_intersect($user->getRoles(), [Clients::ROLE_BORROWER, Clients::ROLE_PARTNER])) {
            $company = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $user]);
        }

        if (null === $company) {
            $company = $partner->getIdCompany();
        }

        try {
            $this->entityManager->beginTransaction();

            $project = new Projects();
            $project
                ->setIdCompany($company)
                ->setTitle($title)
                ->setSlug($this->entityManager->getConnection()->generateSlug($title))
                ->setAmount($amount)
                ->setPeriod($duration)
                ->setComments($description)
                ->setCreateBo(false)
                ->setRisk(['A', 'B', 'C', 'D', 'E'][rand(0,4)])
                ->setStatus(ProjectsStatus::STATUS_REQUEST)
                ->setIdPartner($partner)
                ->setIdProduct($product)
                ->setIdCompanySubmitter($user->getCompanyClient() ? $user->getCompanyClient()->getIdCompany() : null)
                ->setIdClientSubmitter($user);

            $this->entityManager->persist($project);
            $this->entityManager->flush($project);

            $projectStatusManager->addProjectStatus(Users::USER_ID_FRONT, $project->getStatus(), $project);

            $this->entityManager->commit();

            return $this->redirectToRoute('demo_project_request_summary', ['hash' => $project->getHash()]);
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();

            $this->logger->error('An error occurred while creating project. Message: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);
        }

        return $this->redirectToRoute('demo_project_request');
    }

    /**
     * @Route("/depot/{hash}", name="demo_project_request_summary", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
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
            return $this->redirectToRoute('demo_loans');
        }

        $template = [
            'project'        => $project,
            'product'        => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Product')->find($project->getIdProduct()),
            'abandonReasons' => $this->entityManager
                ->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason')
                ->findBy(['status' => ProjectAbandonReason::STATUS_ONLINE], ['reason' => 'ASC'])
        ];

        return $this->render('/demo/project_request_summary.html.twig', $template);
    }

    /**
     * @Route("/depot/submit", name="demo_project_request_submit")
     *
     * @param Request              $request
     * @param ProjectStatusManager $projectStatusManager
     *
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function projectRequestSubmit(Request $request, ProjectStatusManager $projectStatusManager): Response
    {
        $hash = $request->request->get('hash');

        if (1 !== preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $hash)) {
            return $this->redirect($request->headers->get('referer'));
        }

        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (false === $project instanceof Projects) {
            return $this->redirect($request->headers->get('referer'));
        }

        $projectStatusManager->addProjectStatus(Users::USER_ID_FRONT, ProjectsStatus::STATUS_REVIEW, $project);

        return $this->redirectToRoute('demo_loans');
    }

    /**
     * @Route("/depot/abandon", name="demo_project_request_abandon")
     *
     * @param Request              $request
     * @param ProjectStatusManager $projectStatusManager
     *
     * @return Response
     */
    public function projectRequestAbandon(Request $request, ProjectStatusManager $projectStatusManager): Response
    {
        $hash = $request->request->get('hash');

        if (1 !== preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $hash)) {
            return $this->redirect($request->headers->get('referer'));
        }

        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (false === $project instanceof Projects) {
            return $this->redirect($request->headers->get('referer'));
        }

        $abandonReasonRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason');

        if (
            $project->getStatus() !== ProjectsStatus::STATUS_CANCELLED
            && $request->request->get('reason')
            && ($abandonReason = $abandonReasonRepository->findBy(['idAbandon' => $request->request->get('reason')]))
        ) {
            $projectStatusManager->abandonProject($project, $abandonReason, Users::USER_ID_FRONT);
        }

        return $this->redirectToRoute('demo_loans');
    }
}
