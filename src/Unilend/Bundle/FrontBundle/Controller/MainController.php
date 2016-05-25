<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

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
        //TODO add Battenberg people
        //TODO add VideoHeroes

        return $this->render('UnilendFrontBundle:pages:homepage_acquisition.html.twig', array(
            'stats' => array(
                'numberProjects' => 233,
                'numberLenders' => 22548,
                'amountBorrowedInMillions' => 16
            ),
            'testimonialPeople' => array(
                array(
                    'type'           => 'emprunter',
                    'image'          => 'frontbundle/media/promos/homeacq-promo-emprunter-600x400.jpg',
                    'name'           => 'Jean-Marc',
                    'location'       => 'Marseille',
                    'quote'          => '"J&quot;ai pu emprunter 15&nbsp;000€ en quelques semaines et relancer la croissance de mon entreprise."',
                    'urlTestimonial' => ('emprunter/testimonials'),
                    'urlProject'     => ('project/12345'),
                    'labelCta'       => 'Présenter un projet',
                    'urlCta'         => ('projects/new')
                ),
                array(
                    'type'           => 'preter',
                    'image'          => 'frontbundle/media/promos/homeacq-promo-preter-600x400.jpg',
                    'name'           => 'Jane',
                    'location'       => 'Avignon',
                    'quote'          => '"En 2 ans, j&#39;ai multiplié par 3 mon portefeuille de prêt. Et mes intérêts sont à +8%."',
                    'urlTestimonial' => 'preter/testimonials',
                    'urlProject'     => false,
                    'labelCta'       => 'Devenir prêteur',
                    'urlCta'         => 'preter'
                )
            ),
            'videoHeroes' => array(
                'Lenders' => array(
                    array('name' => 'Julie', 'age' => '36 ans', 'info' => '540€ prêtes, 5% de bénefices', 'urlTestimonial' => ''),
                    array('name' => 'Celeste', 'age' => '28 ans', 'info' => '100 prêtes, 5% de bénefices', 'urlTestimonial' => ''),
                    array('name' => 'Julien', 'age' => '40 ans', 'info' => '300 prêtes, 6% de bénefices', 'urlTestimonial' => '')
                ),
                'Borrowers' => array(
                    array('name' => 'Didier', 'age' => '43 ans', 'info' => '10&nbsp;000€ empruntés, une entreprise relancée', 'urlTestimonial' => ''),
                    array('name' => 'Patrick', 'age' => '38 ans', 'info' => '20&nbsp;000€ empruntés, une entreprise relancée', 'urlTestimonial' => ''),
                    array('name' => 'Jean-Marc', 'age' => '55 ans', 'info' => '20&nbsp;000€ empruntés, une entreprise relancée', 'urlTestimonial' => '')
                )
            )
        ));
    }

}