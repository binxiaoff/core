<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class LegalController extends Controller
{
    /**
     * @Route("info-legal")
     */
    public function infoAction()
    {
        return $this->render('UnilendFrontBundle:LegalController:info.html.twig', array(
            // ...
        ));
    }

}
