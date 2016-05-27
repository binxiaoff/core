<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Unilend\Bundle\FrontBundle\Service\TestimonialManager;
use Unilend\Service\ProjectManager;
use Unilend\Service\StatisticsManager;

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

        /** @var ProjectManager $projectManager */
        $projectManager = $this->get('unilend.service.project_manager');
        $aProjectsInFunding = $projectManager->getProjectsForDisplay(array(\projects_status::EN_FUNDING), 'p.date_retrait_full ASC');

        return $this->render('UnilendFrontBundle:pages:homepage_preter.html.twig', array(
            'stats' => array(
                'percentageFullyRepayedProjects' => 90,
                'averageYearlyInterestRateUnilend' => 8.84

            ),
            'videoHeroes' => array('Lenders' => $aVideoHeroesLenders, 'Borrowers' => $aVideoHeroesBorrowers),
            'projectList' => $aProjectsInFunding,
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
            ),
            'listItems' => array(
                array(
                    'id' => '12345',
                    'title' => 'Example project',
                    'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce vel dolor a ipsum consectetur vulputate. Proin viverra sodales sem facilisis aliquet. Integer tincidunt congue iaculis. Sed vel massa ex. Nam cursus scelerisque ligula, id ullamcorper neque elementum et. Quisque quis nisi tortor. Phasellus id dolor ut eros scelerisque pharetra at non tellus. Cras lacinia ex egestas eros gravida, ut porta lorem dignissim. Mauris tristique nulla tortor, eget mattis lorem dapibus in. Sed sed interdum neque. Ut semper eleifend urna, et hendrerit purus volutpat vitae. Quisque rutrum ex lectus, rutrum tempus mauris mollis quis. Duis et lacinia turpis. Praesent condimentum vitae erat ultrices mollis. Phasellus eget condimentum libero. Aenean accumsan viverra mauris, vel euismod ipsum consequat non.',
                    'permalink' => 'project/12345',
                    'image' => ('frontbundle/media/promos/homeacq-promo-emprunter-600x400.jpg'),
                    'locationCity' => 'Paris',
                    'locationPostCode' => '75002',
                    'categoryId' => 1,
                    'cost' => 150000.12,
                    'interest' => 7.2,
                    'totalOffers' => 1500,
                    'rating' => 3.5,
                    'dateExpires' => date('d F Y H:i:s'),
                    'daysLeft' => 4,
                    'currentUserInvolved' => false,
                    'currentUserTotalOffers' => 0
                ),
                array(
                    'id' => '12345',
                    'title' => 'Example project',
                    'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce vel dolor a ipsum consectetur vulputate. Proin viverra sodales sem facilisis aliquet. Integer tincidunt congue iaculis. Sed vel massa ex. Nam cursus scelerisque ligula, id ullamcorper neque elementum et. Quisque quis nisi tortor. Phasellus id dolor ut eros scelerisque pharetra at non tellus. Cras lacinia ex egestas eros gravida, ut porta lorem dignissim. Mauris tristique nulla tortor, eget mattis lorem dapibus in. Sed sed interdum neque. Ut semper eleifend urna, et hendrerit purus volutpat vitae. Quisque rutrum ex lectus, rutrum tempus mauris mollis quis. Duis et lacinia turpis. Praesent condimentum vitae erat ultrices mollis. Phasellus eget condimentum libero. Aenean accumsan viverra mauris, vel euismod ipsum consequat non.',
                    'permalink' => 'project/12345',
                    'image' => ('frontbundle/media/promos/homeacq-promo-emprunter-600x400.jpg'),
                    'locationCity' => 'Paris',
                    'locationPostCode' => '75002',
                    'categoryId' => 2,
                    'cost' => 150000.12,
                    'interest' => 7.2,
                    'totalOffers' => 1500,
                    'rating' => 3.5,
                    'dateExpires' => date('d F Y H:i:s'),
                    'daysLeft' => 4,
                    'currentUserInvolved' => false,
                    'currentUserTotalOffers' => 0
                ),
                array(
                    'id' => '12345',
                    'title' => 'Example project',
                    'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce vel dolor a ipsum consectetur vulputate. Proin viverra sodales sem facilisis aliquet. Integer tincidunt congue iaculis. Sed vel massa ex. Nam cursus scelerisque ligula, id ullamcorper neque elementum et. Quisque quis nisi tortor. Phasellus id dolor ut eros scelerisque pharetra at non tellus. Cras lacinia ex egestas eros gravida, ut porta lorem dignissim. Mauris tristique nulla tortor, eget mattis lorem dapibus in. Sed sed interdum neque. Ut semper eleifend urna, et hendrerit purus volutpat vitae. Quisque rutrum ex lectus, rutrum tempus mauris mollis quis. Duis et lacinia turpis. Praesent condimentum vitae erat ultrices mollis. Phasellus eget condimentum libero. Aenean accumsan viverra mauris, vel euismod ipsum consequat non.',
                    'permalink' => 'project/12345',
                    'image' => ('frontbundle/media/promos/homeacq-promo-emprunter-600x400.jpg'),
                    'locationCity' => 'Paris',
                    'locationPostCode' => '75002',
                    'categoryId' => 3,
                    'cost' => 150000.12,
                    'interest' => 7.2,
                    'totalOffers' => 1500,
                    'rating' => 3.5,
                    'dateExpires' => date('d F Y H:i:s'),
                    'daysLeft' => 4,
                    'currentUserInvolved' => false,
                    'currentUserTotalOffers' => 0
                ),
                array(
                    'id' => '12345',
                    'title' => 'Example project',
                    'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce vel dolor a ipsum consectetur vulputate. Proin viverra sodales sem facilisis aliquet. Integer tincidunt congue iaculis. Sed vel massa ex. Nam cursus scelerisque ligula, id ullamcorper neque elementum et. Quisque quis nisi tortor. Phasellus id dolor ut eros scelerisque pharetra at non tellus. Cras lacinia ex egestas eros gravida, ut porta lorem dignissim. Mauris tristique nulla tortor, eget mattis lorem dapibus in. Sed sed interdum neque. Ut semper eleifend urna, et hendrerit purus volutpat vitae. Quisque rutrum ex lectus, rutrum tempus mauris mollis quis. Duis et lacinia turpis. Praesent condimentum vitae erat ultrices mollis. Phasellus eget condimentum libero. Aenean accumsan viverra mauris, vel euismod ipsum consequat non.',
                    'permalink' => 'project/12345',
                    'image' => ('frontbundle/media/promos/homeacq-promo-emprunter-600x400.jpg'),
                    'locationCity' => 'Paris',
                    'locationPostCode' => '75002',
                    'categoryId' => 4,
                    'cost' => 150000.12,
                    'interest' => 7.2,
                    'totalOffers' => 1500,
                    'rating' => 3.5,
                    'dateExpires' => date('d F Y H:i:s'),
                    'daysLeft' => 4,
                    'currentUserInvolved' => false,
                    'currentUserTotalOffers' => 0
                )

            )



        ));
    }









}