<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class LegalController extends Controller
{
    public function infoAction()
    {
        return $this->render('UnilendFrontBundle:Legal:info.html.twig', array(
            // ...
        ));
    }

}
