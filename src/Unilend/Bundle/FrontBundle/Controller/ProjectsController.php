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

class ProjectsController extends Controller
{

    /**
     * @Route("/project", name="projects")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function projectListAction()
    {

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