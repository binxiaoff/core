<?php

declare(strict_types=1);

namespace Unilend\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
