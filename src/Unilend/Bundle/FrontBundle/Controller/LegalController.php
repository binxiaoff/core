<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class LegalController extends Controller
{
    /**
     * @Route("/infos-legales")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function infoAction()
    {
        return $this->render('UnilendFrontBundle:pages:legal.html.twig', array(
            'lang' => 'fr_FR',
            'site' => '',
            'env' => 'dev',
            'page' => array(
                'id' => 'id',
                'classNames' => 'classNames',
                'displayTitle' => 'displayTitle'
            )
        ));
    }

}

