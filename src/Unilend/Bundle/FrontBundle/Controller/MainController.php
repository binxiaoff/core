<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Unilend\Bundle\FrontBundle\Service\TestimonialManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use \Unilend\Bundle\CoreBusinessBundle\Service\StatisticsManager;

class MainController extends Controller
{

    /**
     * @Route("/")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function homeAction()
    {
        /** @var StatisticsManager $statsService */
        $statsService = $this->get('unilend.service.statistics_manager');

        /** @var TestimonialManager $testimonialService */
        $testimonialService    = $this->get('unilend.service.testimonial');
        $aBattenbergPeople     = $testimonialService->getActiveBattenbergTestimonials();
        $aVideoHeroesLenders   = $testimonialService->getActiveVideoHeroes('preter');
        $aVideoHeroesBorrowers = $testimonialService->getActiveVideoHeroes('emprunter');

        return $this->render('UnilendFrontBundle:pages:homepage_acquisition.html.twig', array(
            'stats'             => array(
                'numberProjects'           => $statsService->getNumberOfProjects(),
                'numberLenders'            => $statsService->getNumberOfLenders(),
                'amountBorrowedInMillions' => bcdiv($statsService->getAmountBorrowed(), 1000000)
            ),
            'testimonialPeople' => $aBattenbergPeople,
            'videoHeroes'       => array('Lenders' => $aVideoHeroesLenders, 'Borrowers' => $aVideoHeroesBorrowers)
        ));
    }

    /**
     * @Route("/lender")
     */
    public function homeLenderAction()
    {
        /** @var TestimonialManager $testimonialService */
        $testimonialService    = $this->get('unilend.service.testimonial');
        $aVideoHeroesLenders   = $testimonialService->getActiveVideoHeroes('preter');
        $aVideoHeroesBorrowers = $testimonialService->getActiveVideoHeroes('emprunter');

        $aRateRange = array(\bids::BID_RATE_MIN, \bids::BID_RATE_MAX);

        /** @var ProjectManager $projectManager */
        $projectManager     = $this->get('unilend.service.project_manager');
        $aProjectsInFunding = $projectManager->getProjectsForDisplay(array(\projects_status::EN_FUNDING), 'p.date_retrait_full ASC', $aRateRange);



        return $this->render('UnilendFrontBundle:pages:homepage_preter.html.twig', array(
            'stats' => array(
                'percentageFullyRepayedProjects' => 90,
                'averageYearlyInterestRateUnilend' => 8.84

            ),
            'videoHeroes' => array('Lenders' => $aVideoHeroesLenders, 'Borrowers' => $aVideoHeroesBorrowers),
            'projects' => $aProjectsInFunding,
            'filterItems' => array(
                array(
                    'column' => 'category',
                    'sortBy' => 'categoryId',
                    'label' => 'Category, projectListFilterCategory'
                ),
                array(
                    'column' => 'info',
                    'sortBy' => 'costId',
                    'label' => 'Cost, projectListFilterCost'
                ),
                array(
                    'column' => 'stats',
                    'sortBy' => 'interest',
                    'label' => 'Interest, projectListFilterInterest'
                ),
                array(
                    'column' => 'rating',
                    'sortBy' => 'rating',
                    'label' => 'Rating, projectListFilterRating'
                ),
                array(
                    'column' => 'period',
                    'sortBy' => 'daysLeft',
                    'label' => 'Time Remaining, projectListFilterPeriod'
                )
            )
        ));
    }

    /**
     * @Route("/borrower")
     */
    public function homeBorrowerAction()
    {
        /** @var TestimonialManager $testimonialService */
        $testimonialService    = $this->get('unilend.service.testimonial');
        $aVideoHeroesLenders   = $testimonialService->getActiveVideoHeroes('preter');
        $aVideoHeroesBorrowers = $testimonialService->getActiveVideoHeroes('emprunter');
        $aBattenbergPeople     = $testimonialService->getActiveBattenbergTestimonials();

        return $this->render('UnilendFrontBundle:pages:homepage_emprunter.html.twig', array(
            'stats' => array(),
            'videoHeroes' => array('Lenders' => $aVideoHeroesLenders, 'Borrowers' => $aVideoHeroesBorrowers),
            'testimonialPeople' => $aBattenbergPeople,

        ));
    }









}