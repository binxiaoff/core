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
        /** @var \projects $project */
        $project  = $entityManager->getRepository('projects');
        $project->get($request->getSession()->get('esim/project_id'));
        $request->getSession()->remove('esim/project_id');

        return new Response('waiting for template ... ');
    }

}