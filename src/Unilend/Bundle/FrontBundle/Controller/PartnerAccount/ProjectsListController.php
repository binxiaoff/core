<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsComments;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Repository\ProjectsRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\FrontBundle\Security\User\UserPartner;

class ProjectsListController extends Controller
{
    /**
     * @Route("partenaire/emprunteurs", name="partner_projects_list")
     * @Method("GET")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectsListAction()
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $companies     = $this->getUserCompanies();

        /** @var ProjectsRepository $projectRepository */
        $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $prospects         = $projectRepository->getPartnerProspects($companies);
        $borrowers         = $projectRepository->getPartnerProjects($companies);
        $abandoned         = $projectRepository->getPartnerAbandoned($companies);
        $rejected          = $projectRepository->getPartnerRejected($companies);

        return $this->render('/partner_account/projects_list.html.twig', [
            'prospects'      => $this->formatProject($prospects, false),
            'borrowers'      => $this->formatProject($borrowers, true),
            'abandoned'      => $this->formatProject($abandoned, true, true),
            'rejected'       => $this->formatProject($rejected, true),
            'abandonReasons' => $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason')->findBy([], ['label' => 'ASC'])
        ]);
    }

    /**
     * @Route("partenaire/emprunteurs", name="partner_project_tos", condition="request.isXmlHttpRequest()")
     * @Method("POST")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function projectRequestDetailsFormAction(Request $request)
    {
        $hash = $request->request->getAlnum('hash');

        if (1 !== preg_match('/^[0-9a-f]{32}$/', $hash)) {
            return $this->redirect($request->headers->get('referer'));
        }

        $translator        = $this->get('translator');
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);
        $userCompanies     = $this->getUserCompanies();

        if (false === ($project instanceof Projects) || false === in_array($project->getIdCompanySubmitter(), $userCompanies)) {
            return new JsonResponse([
                'error'   => true,
                'message' => $translator->trans('partner-project-list_popup-project-tos-message-error')
            ]);
        }

        try {
            $projectManager = $this->get('unilend.service.project_manager');
            $projectManager->sendTermsOfSaleEmail($project);
        } catch (\Exception $exception) {
            switch ($exception->getCode()) {
                case ProjectManager::EXCEPTION_CODE_TERMS_OF_SALE_INVALID_EMAIL:
                    return new JsonResponse([
                        'error'   => true,
                        'message' => $translator->trans('partner-project-list_popup-project-tos-message-error-email')
                    ]);
                case ProjectManager::EXCEPTION_CODE_TERMS_OF_SALE_INVALID_PHONE_NUMBER:
                    return new JsonResponse([
                        'error'   => true,
                        'message' => $translator->trans('partner-project-list_popup-project-tos-message-error-phone-number')
                    ]);
                case ProjectManager::EXCEPTION_CODE_TERMS_OF_SALE_PDF_FILE_NOT_FOUND:
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
     * @return Companies[]
     */
    private function getUserCompanies()
    {
        /** @var UserPartner $user */
        $user      = $this->getUser();
        $companies = [$user->getCompany()];

        if (in_array(UserPartner::ROLE_ADMIN, $user->getRoles())) {
            $companies = $this->getCompanyTree($user->getCompany(), $companies);
        }

        return $companies;
    }

    /**
     * @param Companies $rootCompany
     * @param array     $tree
     *
     * @return Companies[]
     */
    private function getCompanyTree(Companies $rootCompany, array $tree)
    {
        $childCompanies = $this->get('doctrine.orm.entity_manager')
            ->getRepository('UnilendCoreBusinessBundle:Companies')
            ->findBy(['idParentCompany' => $rootCompany]);

        foreach ($childCompanies as $company) {
            $tree[] = $company;
            $tree = $this->getCompanyTree($company, $tree);
        }

        return $tree;
    }

    /**
     * @param Projects[] $projects
     * @param bool       $loadNotes
     * @param bool       $abandoned
     *
     * @return array
     */
    private function formatProject(array $projects, $loadNotes, $abandoned = false)
    {
        $display    = [];
        $translator = $this->get('translator');

        if ($abandoned) {
            $entityManager        = $this->get('doctrine.orm.entity_manager');
            $historyRepository    = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory');
            $abandonProjectStatus = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->findOneBy(['status' => ProjectsStatus::ABANDONED]);
        }

        foreach ($projects as $project) {
            $display[$project->getIdProject()] = [
                'id'         => $project->getIdProject(),
                'hash'       => $project->getHash(),
                'name'       => empty($project->getTitle()) ? $project->getIdCompany()->getName() : $project->getTitle(),
                'amount'     => $project->getAmount(),
                'duration'   => $project->getPeriod(),
                'status'     => $project->getStatus(),
                'submitter'  => [
                    'firstName' => $project->getIdClientSubmitter()->getPrenom(),
                    'lastName'  => $project->getIdClientSubmitter()->getNom(),
                    'entity'    => $project->getIdCompanySubmitter()->getName()
                ],
                'motive'     => $project->getIdBorrowingMotive() ? $translator->trans('borrowing-motive_motive-' . $project->getIdBorrowingMotive()) : '',
                'memos'      => $loadNotes ? $this->formatNotes($project->getPublicNotes()) : [],
                'hasChanged' => $loadNotes ? $this->hasProjectChanged($project) : false,
                'tos'        => []
            ];

            if ($termsOfSale = $project->getTermOfUser()) {
                $display[$project->getIdProject()]['tos'][] = $termsOfSale->getAdded()->format('d/m/Y');
            }

            if ($abandoned) {
                $history = $historyRepository->findOneBy([
                    'idProject' => $project->getIdProject(),
                    'idProjectStatus' => $abandonProjectStatus
                ]);
                $display[$project->getIdProject()]['abandonReason'] = $history ? $history->getContent() : '';
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
     *
     * @return bool
     */
    private function hasProjectChanged(Projects $project)
    {
        $entityManager                  = $this->get('doctrine.orm.entity_manager');
        $projectStatusRepositoryHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory');
        $lastLoginDate                  = $this->getUser()->getLastLoginDate();
        $lastLoginDate                  = $lastLoginDate ? new \DateTime($lastLoginDate) : null;
        $notes                          = $project->getPublicNotes();

        return (
            null === $lastLoginDate
            || count($notes) && $lastLoginDate < $notes[0]->getAdded()
            || $lastLoginDate < $projectStatusRepositoryHistory->findOneBy(['idProject' => $project->getIdProject()], ['added' => 'DESC', 'idProjectStatusHistory' => 'DESC'])->getAdded()
        );
    }
}
