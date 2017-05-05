<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsComments;
use Unilend\Bundle\FrontBundle\Security\User\UserPartner;

class ProjectsListController extends Controller
{
    /**
     * @Route("partenaire/emprunteurs", name="partner_projects_list")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectsListAction()
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $companies     = $this->getUserCompanies();

        $prospects = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')
            ->findBy(
                ['status' => \projects_status::SIMULATION, 'idCompanySubmitter' => $companies],
                ['added' => 'DESC']
            );

        $borrowers = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')
            ->getPartnerProjects($companies);

        $template = [
            'prospects'      => $this->formatProject($prospects),
            'borrowers'      => $this->formatProject($borrowers),
            'abandonReasons' => $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason')->findBy([], ['label' => 'ASC'])
        ];

        return $this->render('/partner_account/projects_list.html.twig', $template);
    }

    /**
     * @return Companies[]
     */
    private function getUserCompanies()
    {
        /** @var UserPartner $user */
        $user      = $this->getUser();
        $companies = [$user->getCompany()];

        if (in_array('ROLE_PARTNER_ADMIN', $user->getRoles())) {
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
                'hash'       => $project->getHash(),
                'name'       => empty($project->getTitle()) ? $project->getIdCompany()->getName() : $project->getTitle(),
                'amount'     => $project->getAmount(),
                'duration'   => $project->getPeriod(),
                'status'     => $projectStatusRepository->findOneBy(['status' => $project->getStatus()])->getLabel(),
                'submitter'  => [
                    'firstName' => $project->getIdClientSubmitter()->getPrenom(),
                    'lastName'  => $project->getIdClientSubmitter()->getNom(),
                    'entity'    => $project->getIdCompanySubmitter()->getName()
                ],
                'motive'     => $project->getIdBorrowingMotive() ? $translator->trans('borrowing-motive_motive-' . $project->getIdBorrowingMotive()) : '',
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
