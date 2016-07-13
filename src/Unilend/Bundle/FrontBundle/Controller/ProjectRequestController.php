<?php
/**
 * Created by PhpStorm.
 * User: annabreyer
 * Date: 23/06/2016
 * Time: 17:11
 */

namespace Unilend\Bundle\FrontBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProjectRequestController extends Controller
{
    /**
     * @Route("/depot-de-dossier", name="project_request_step_1")
     */
    public function projectRequestStep1Action(Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var ProjectManager $projectManager */
        $projectManager = $this->get('unilend.service.project_manager');
        /** @var \attachment_type $attachmentType */
        $attachmentType = $entityManager->getRepository('attachment_type');
        /** @var array $templateVariables */
        $templateVariables = [];
        /** @var \projects $project */
        $project  = $entityManager->getRepository('projects');
        $project->get($request->getSession()->get('esim/project_id'));
        $request->getSession()->remove('esim/project_id');

        $templateVariables['loanPeriods'] = $projectManager->getPossibleProjectPeriods();

        $attachmentTypes                      = $attachmentType->getAllTypesForProjects($this->getParameter('locale'), false);
        $templateVariables['attachmentTypes'] = $attachmentType->changeLabelWithDynamicContent($attachmentTypes);

        return $this->render('pages/project_request.html.twig', $templateVariables);
    }

}