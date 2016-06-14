<?php

namespace Unilend\Bundle\FrontBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;


class BorrowerAccountController extends Controller
{

    /**
     * @Route("/espace-emprunteur/projets", name="borrower_account")
     */
    public function showBorrowerProjectsAction()
    {
        return new Response('Il y aura un jour la page d\'entree de l\'espace emprunteur');
    }

}
