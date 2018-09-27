<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, ProjectAbandonReason, Projects, ProjectsComments, ProjectsStatus};
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
        $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $prospects         = $projectRepository->getPartnerProspects($companies, $submitter);
        $borrowers         = $projectRepository->getPartnerProjects($companies, $submitter);
        $abandoned         = $projectRepository->getPartnerAbandoned($companies, $submitter);
        $rejected          = $projectRepository->getPartnerRejected($companies, $submitter);

        $incompleteProjects = [];
        $completeProjects   = [];

        foreach ($borrowers as $project) {
            if (ProjectsStatus::INCOMPLETE_REQUEST === $project->getStatus()) {
                $incompleteProjects[] = $project;
            } else {
                $completeProjects[] = $project;
            }
        }
        unset($borrowers);

        return $this->render('/partner_account/projects_list.html.twig', [
            'prospects'          => $this->formatProject($partnerUser, $prospects, false),
            'incompleteProjects' => $this->formatProject($partnerUser, $incompleteProjects, false),
            'completeProjects'   => $this->formatProject($partnerUser, $completeProjects, true),
            'abandoned'          => $this->formatProject($partnerUser, $abandoned, true, true),
            'rejected'           => $this->formatProject($partnerUser, $rejected, true),
            'abandonReasons'     => $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason')
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
        $hash = $request->request->getAlnum('hash');

        if (1 !== preg_match('/^[0-9a-f]{32}$/', $hash)) {
            return $this->redirect($request->headers->get('referer'));
        }

        $translator        = $this->get('translator');
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $partnerManager    = $this->get('unilend.service.partner_manager');

        $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);
        $userCompanies     = $partnerManager->getUserCompanies($partnerUser);

        if (false === ($project instanceof Projects) || false === in_array($project->getIdCompanySubmitter(), $userCompanies)) {
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
    private function formatProject(Clients $client, array $projects, $loadNotes, $abandoned = false): array
    {
        $display    = [];
        $translator = $this->get('translator');

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

            if ($termsOfSale = $project->getTermsOfSale()) {
                $dateFormatter = new \IntlDateFormatter($this->getParameter('locale'), \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT);
                $display[$project->getIdProject()]['tos'][] = $dateFormatter->format($termsOfSale->getAdded());
            }

            if ($abandoned) {
                $entityManager        = $this->get('doctrine.orm.entity_manager');
                $historyRepository    = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory');
                $abandonProjectStatus = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->findOneBy(['status' => ProjectsStatus::ABANDONED]);
                $history = $historyRepository->findOneBy([
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
        $projectStatusRepositoryHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory');
        $lastLoginDate                  = $client->getLastlogin();
        $notes                          = $project->getPublicMemos();

        return (
            null === $lastLoginDate
            || count($notes) && $lastLoginDate < $notes[0]->getAdded()
            || $lastLoginDate < $projectStatusRepositoryHistory->findOneBy(['idProject' => $project->getIdProject()], ['added' => 'DESC', 'idProjectStatusHistory' => 'DESC'])->getAdded()
        );
    }
}
