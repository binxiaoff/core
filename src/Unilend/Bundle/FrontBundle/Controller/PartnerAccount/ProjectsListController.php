<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Clients, ProjectAbandonReason, ProjectCgv, Projects, ProjectsComments, ProjectsStatus, ProjectsStatusHistory};
use Unilend\Bundle\CoreBusinessBundle\Repository\ProjectsRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\TermsOfSaleManager;

class ProjectsListController extends Controller
{
    /**
     * @Route("partenaire/emprunteurs", name="partner_projects_list", methods={"GET"})
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param UserInterface|Clients|null $partnerUser
     *
     * @return Response
     */
    public function projectsListAction(?UserInterface $partnerUser): Response
    {
        $entityManager  = $this->get('doctrine.orm.entity_manager');
        $partnerManager = $this->get('unilend.service.partner_manager');

        $companies = $partnerManager->getUserCompanies($partnerUser);
        $submitter = null;

        if (false === in_array(Clients::ROLE_PARTNER_ADMIN, $partnerUser->getRoles())) {
            $submitter = $partnerUser->getIdClient();
        }

        /** @var ProjectsRepository $projectRepository */
        $projectRepository = $entityManager->getRepository(Projects::class);
        $pending           = $projectRepository->getPartnerProjects($companies, [ProjectsStatus::STATUS_REQUEST], $submitter);
        $ready             = $projectRepository->getPartnerProjects($companies, [ProjectsStatus::STATUS_REVIEW], $submitter);
        $online            = $projectRepository->getPartnerProjects($companies, [ProjectsStatus::STATUS_ONLINE], $submitter);
        $funded            = $projectRepository->getPartnerProjects($companies, [ProjectsStatus::STATUS_FUNDED, ProjectsStatus::STATUS_REPAYMENT, ProjectsStatus::STATUS_REPAID, ProjectsStatus::STATUS_LOSS], $submitter);
        $cancelled         = $projectRepository->getPartnerProjects($companies, [ProjectsStatus::STATUS_CANCELLED], $submitter);

        return $this->render('/partner_account/projects_list.html.twig', [
            'pending'        => $this->formatProject($partnerUser, $pending, false),
            'ready'          => $this->formatProject($partnerUser, $ready, true),
            'online'         => $this->formatProject($partnerUser, $online, true, true),
            'funded'         => $this->formatProject($partnerUser, $funded, true),
            'cancelled'      => $this->formatProject($partnerUser, $cancelled, false),
            'abandonReasons' => $entityManager
                ->getRepository(ProjectAbandonReason::class)
                ->findBy(['status' => ProjectAbandonReason::STATUS_ONLINE], ['reason' => 'ASC'])
        ]);
    }

    /**
     * @Route("partenaire/emprunteurs", name="partner_project_tos", condition="request.isXmlHttpRequest()", methods={"POST"})
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $partnerUser
     *
     * @return Response
     */
    public function projectRequestDetailsFormAction(Request $request, ?UserInterface $partnerUser): Response
    {
        $hash = $request->request->filter('hash', null, FILTER_SANITIZE_STRING);

        if (1 !== preg_match('/^[0-9a-f-]{32,36}$/', $hash)) {
            return $this->redirect($request->headers->get('referer'));
        }

        $translator        = $this->get('translator');
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $partnerManager    = $this->get('unilend.service.partner_manager');

        $projectRepository = $entityManager->getRepository(Projects::class);
        $project           = $projectRepository->findOneBy(['hash' => $hash]);
        $userCompanies     = $partnerManager->getUserCompanies($partnerUser);

        if (false === $project instanceof Projects || false === in_array($project->getIdCompanySubmitter(), $userCompanies)) {
            return new JsonResponse([
                'error'   => true,
                'message' => $translator->trans('partner-project-list_popup-project-tos-message-error')
            ]);
        }

        try {
            $termsOfSaleManager = $this->get('unilend.service.terms_of_sale_manager');
            $termsOfSaleManager->sendBorrowerEmail($project);
        } catch (\Exception $exception) {
            switch ($exception->getCode()) {
                case TermsOfSaleManager::EXCEPTION_CODE_INVALID_EMAIL:
                    return new JsonResponse([
                        'error'   => true,
                        'message' => $translator->trans('partner-project-list_popup-project-tos-message-error-email')
                    ]);
                case TermsOfSaleManager::EXCEPTION_CODE_INVALID_PHONE_NUMBER:
                    return new JsonResponse([
                        'error'   => true,
                        'message' => $translator->trans('partner-project-list_popup-project-tos-message-error-phone-number')
                    ]);
                case TermsOfSaleManager::EXCEPTION_CODE_PDF_FILE_NOT_FOUND:
                    return new JsonResponse([
                        'error'   => true,
                        'message' => $translator->trans('partner-project-list_popup-project-tos-message-error-file-not-found')
                    ]);
                default:
                    return new JsonResponse([
                        'error'   => true,
                        'message' => $translator->trans('partner-project-list_popup-project-tos-message-error')
                    ]);
            }
        }

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans('partner-project-list_popup-project-tos-message-success')
        ]);
    }

    /**
     * @param Clients    $client
     * @param Projects[] $projects
     * @param bool       $loadNotes
     * @param bool       $abandoned
     *
     * @return array
     */
    private function formatProject(Clients $client, array $projects, bool $loadNotes, bool $abandoned = false): array
    {
        $display                        = [];
        $translator                     = $this->get('translator');
        $entityManager                  = $this->get('doctrine.orm.entity_manager');
        $projectStatusHistoryRepository = $entityManager->getRepository(ProjectsStatusHistory::class);
        $termsOfSaleRepository          = $entityManager->getRepository(ProjectCgv::class);

        foreach ($projects as $project) {
            $display[$project->getIdProject()] = [
                'id'         => $project->getIdProject(),
                'hash'       => $project->getHash(),
                'name'       => empty($project->getTitle()) ? $project->getIdCompany()->getName() : $project->getTitle(),
                'amount'     => $project->getAmount(),
                'duration'   => $project->getPeriod(),
                'status'     => $project->getStatus(),
                'date'       => $project->getAdded(),
                'submitter'  => [
                    'firstName' => $project->getIdClientSubmitter() && $project->getIdClientSubmitter()->getIdClient() ? $project->getIdClientSubmitter()->getPrenom() : '',
                    'lastName'  => $project->getIdClientSubmitter() && $project->getIdClientSubmitter()->getIdClient() ? $project->getIdClientSubmitter()->getNom() : '',
                    'entity'    => $project->getIdCompanySubmitter()->getName()
                ],
                'motive'     => $project->getIdBorrowingMotive() ? $translator->trans('borrowing-motive_motive-' . $project->getIdBorrowingMotive()) : '',
                'memos'      => $loadNotes ? $this->formatNotes($project->getPublicMemos()) : [],
                'hasChanged' => $loadNotes ? $this->hasProjectChanged($project, $client) : false,
                'tos'        => []
            ];

            $termsOfSale = $termsOfSaleRepository->findOneBy(['idProject' => $project]);

            if ($termsOfSale) {
                $dateFormatter = new \IntlDateFormatter($this->getParameter('locale'), \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT);
                $display[$project->getIdProject()]['tos'][] = $dateFormatter->format($termsOfSale->getAdded());
            }

            if ($abandoned) {
                if (false === isset($abandonProjectStatus)) {
                    $abandonProjectStatus = $entityManager->getRepository(ProjectsStatus::class)->findOneBy(['status' => ProjectsStatus::STATUS_CANCELLED]);
                }

                $history = $projectStatusHistoryRepository->findOneBy([
                    'idProject'       => $project->getIdProject(),
                    'idProjectStatus' => $abandonProjectStatus
                ]);
                $display[$project->getIdProject()]['projectAbandonReasons'] = $history ? $history->getAbandonReasons() : [];
            }
        }

        return $display;
    }

    /**
     * @param ProjectsComments[] $notes
     *
     * @return array
     */
    private function formatNotes($notes)
    {
        $display = [];

        foreach ($notes as $note) {
            $display[] = [
                'author'  => $note->getIdUser()->getFirstname() . ' ' . $note->getIdUser()->getName(),
                'date'    => $note->getAdded(),
                'content' => $note->getContent()
            ];
        }

        return $display;
    }

    /**
     * @param Projects $project
     * @param Clients  $client
     *
     * @return bool
     */
    private function hasProjectChanged(Projects $project, Clients $client): bool
    {
        $entityManager                  = $this->get('doctrine.orm.entity_manager');
        $projectStatusRepositoryHistory = $entityManager->getRepository(ProjectsStatusHistory::class);
        $lastLoginDate                  = $client->getLastlogin();
        $notes                          = $project->getPublicMemos();

        return (
            null === $lastLoginDate
            || count($notes) && $lastLoginDate < $notes[0]->getAdded()
            || $lastLoginDate < $projectStatusRepositoryHistory->findOneBy(['idProject' => $project->getIdProject()], ['added' => 'DESC', 'idProjectStatusHistory' => 'DESC'])->getAdded()
        );
    }
}
