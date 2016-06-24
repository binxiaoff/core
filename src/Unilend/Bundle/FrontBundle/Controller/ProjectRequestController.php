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

class ProjectRequestController extends Controller
{
    /**
     * @Route("/depot-de-dossier", name="project_request_step_1")
     */
    public function projectRequestStep1Action(Request $request)
    {
        $aFormData = $request->getSession()->get('SimulatorData');
        $request->getSession()->remove('SimulatorData');
        var_dump($aFormData);

        return new Response('waiting for template ... ');
    }

}