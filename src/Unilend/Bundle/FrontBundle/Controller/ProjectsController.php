<?php
/**
 * Created by PhpStorm.
 * User: annabreyer
 * Date: 08/06/2016
 * Time: 18:34
 */

namespace Unilend\Bundle\FrontBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;
use Unilend\Bundle\FrontBundle\Service\ProjectDisplayManager;

class ProjectsController extends Controller
{

    /**
     * @Route("/project", name="projects")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function projectListAction()
    {
        /** @var ProjectDisplayManager $projectDisplayManager */
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        $rateRange = [\bids::BID_RATE_MIN, \bids::BID_RATE_MAX];
        $projectsStatus = [
            \projects_status::EN_FUNDING,
            \projects_status::FUNDE,
            \projects_status::FUNDING_KO,
            \projects_status::REMBOURSEMENT,
            \projects_status::REMBOURSE,
            \projects_status::PROBLEME,
            \projects_status::RECOUVREMENT,
            \projects_status::DEFAUT,
            \projects_status::REMBOURSEMENT_ANTICIPE,
            \projects_status::PROBLEME_J_X,
            \projects_status::PROCEDURE_SAUVEGARDE,
            \projects_status::REDRESSEMENT_JUDICIAIRE,
            \projects_status::LIQUIDATION_JUDICIAIRE
        ];

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
            && $this->get('security.authorization_checker')->isGranted('ROLE_LENDER')
        ) {
            /** @var BaseUser $user */
            $user = $this->getUser();
            $aTemplateVariables['projects'] = $projectDisplayManager->getProjectsForDisplay(
                $projectsStatus,
                'p.date_retrait_full DESC',
                $rateRange,
                $user->getClientId());
        } else {
            $aTemplateVariables['projects'] = $projectDisplayManager->getProjectsForDisplay(
                $projectsStatus,
                'p.date_retrait_full DESC',
                $rateRange);
        }

        return $this->render('pages/projects.html.twig', $aTemplateVariables);
    }

    /**
     * @Route("/project/{projectSlug}", name="project_show")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showProjectAction($projectSlug)
    {

    }

}
