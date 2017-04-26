<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsComments;
use Unilend\Bundle\FrontBundle\Security\User\UserPartner;

class PartnerAccountController extends Controller
{
    /**
     * @Route("partenaire/depot", name="partner_project_request")
     * @Method("GET")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectRequestAction()
    {
        return $this->render('/partner_account/project_request.html.twig');
    }

    /**
     * @Route("partenaire/depot", name="partner_project_request_form")
     * @Method("POST")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return RedirectResponse
     */
    public function projectRequestFormAction()
    {
        return $this->redirectToRoute('partner_project_request_eligibility_test');
    }

    /**
     * @Route("partenaire/depot/eligibilite", name="partner_project_request_eligibility_test")
     * @Route("partenaire/depot/eligibilite/{hash}", name="partner_project_request_eligibility", requirements={"hash":"[0-9a-z]{32}"})
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectRequestEligibilityAction()
    {
        return $this->render('/partner_account/project_request_eligibility.html.twig');
    }


    /**
     * @Route("partenaire/depot/details", name="partner_project_request_details_form")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectRequestDetailsAction()
    {
        return $this->render('/partner_account/project_request_details.html.twig');
    }


    /**
     * @Route("partenaire/depot/fichiers", name="partner_project_request_files_form")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectRequestFilesAction()
    {
        return $this->render('/partner_account/project_request_documents.html.twig');
    }

    /**
     * @Route("partenaire/depot/fin", name="partner_project_request_end", requirements={"hash": "[0-9a-f-]{32,36}"})
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectRequestEndAction()
    {
        return $this->render('/partner_account/project_request_end.html.twig');
    }

    /**
     * @Route("partenaire/emprunteurs", name="partner_projects_list")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectsListAction()
    {
        /** @var UserPartner $user */
        $user          = $this->getUser();
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $companies = [$user->getCompany()];

        if (in_array('ROLE_PARTNER_ADMIN', $user->getRoles())) {
            $companies = $this->getCompanyTree($user->getCompany(), $companies);
        }

        $prospects = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')
            ->findBy(
                ['status' => \projects_status::SIMULATION, 'idCompanySubmitter' => $companies],
                ['added' => 'DESC']
            );

        $borrowers = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')
            ->getPartnerProjects($companies);

        $template = [
            'prospects' => $this->formatProject($prospects),
            'borrowers' => $this->formatProject($borrowers)
        ];

        return $this->render('/partner_account/projects_list.html.twig', $template);
    }

    /**
     * @Route("partenaire/utilisateurs", name="partner_users")
     * @Security("has_role('ROLE_PARTNER_ADMIN')")
     *
     * @return Response
     */
    public function usersAction()
    {
        return $this->render('/partner_account/users.html.twig');
    }

    /**
     * @Route("partenaire/performance", name="partner_statistics")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function statisticsAction()
    {
        return $this->render('/partner_account/statistics.html.twig');
    }

    /**
     * @Route("partenaire/contact", name="partner_contact")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function contactAction()
    {
        return $this->render('/partner_account/contact.html.twig');
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
     * @return array
     */
    private function formatProject(array $projects)
    {
        $display                 = [];
        $translator              = $this->get('translator');
        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $projectStatusRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus');

        foreach ($projects as $project) {
            $display[$project->getIdProject()] = [
                'id'         => $project->getIdProject(),
                'name'       => empty($project->getTitle()) ? $project->getIdCompany()->getName() : $project->getTitle(),
                'submitter'  => [
                    'firstName' => $project->getIdClientSubmitter()->getPrenom(),
                    'lastName'  => $project->getIdClientSubmitter()->getNom(),
                    'entity'    => $project->getIdCompanySubmitter()->getName()
                ],
                'motive'     => $project->getIdBorrowingMotive() ? $translator->trans('borrowing-motive_motive-' . $project->getIdBorrowingMotive()) : '',
                'amount'     => $project->getAmount(),
                'duration'   => $project->getPeriod(),
                'status'     => $projectStatusRepository->findOneBy(['status' => $project->getStatus()])->getLabel(),
                'memos'      => $this->formatNotes($project->getNotes()),
                'hasChanged' => $this->hasProjectChanged($project)
            ];
        }

        return $display;
    }

    /**
     * @param ProjectsComments[] $notes
     *
     * @return array
     */
    private function formatNotes(array $notes)
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
        $lastLoginDate = $this->getUser()->getLastLoginDate();
        $lastLoginDate = $lastLoginDate ? new \DateTime($lastLoginDate) : null;
        $notes = $project->getNotes();

        return (
            null === $lastLoginDate
            || count($notes) && $lastLoginDate < $project->getNotes()[0]->getAdded()
            || $lastLoginDate < $projectStatusRepositoryHistory->findOneBy(['idProject' => $project->getIdProject()], ['added' => 'DESC', 'idProjectStatusHistory' => 'DESC'])->getAdded()
        );
    }
}
