<?php

namespace Unilend\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_granted('ROLE_USER')")
 */
class ReportingController extends AbstractController
{
    /**
     * @Route("/reporting", name="reporting")
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->render('reporting/index.html.twig');
    }
}
