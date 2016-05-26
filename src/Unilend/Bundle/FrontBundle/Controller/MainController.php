<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Unilend\Bundle\FrontBundle\Service\TestimonialManager;

class MainController extends Controller
{

    /**
     * @Route("/")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function homeAction()
    {
        //TODO replace mock data par stats from StatsService

        /** @var TestimonialManager $testimonialService */
        $testimonialService = $this->get('unilend.service.testimonial');
        $aBattenbergPeople = $testimonialService->getActiveBattenbergTestimonials();
        $aVideoHeroesLenders = $testimonialService->getActiveVideoHeroes('preter');
        $aVideoHeroesBorrowers = $testimonialService->getActiveVideoHeroes('emprunter');

        return $this->render('UnilendFrontBundle:pages:homepage_acquisition.html.twig', array(
            'stats' => array(
                'numberProjects'           => 233,
                'numberLenders'            => 22548,
                'amountBorrowedInMillions' => 16
            ),
            'testimonialPeople' => $aBattenbergPeople,
            'videoHeroes' => array('Lenders' => $aVideoHeroesLenders, 'Borrowers' => $aVideoHeroesBorrowers)
        ));
    }

}