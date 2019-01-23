<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{JsonResponse, RedirectResponse, Request, Response};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, OffresBienvenues, ProjectsStatus, Tree, Users};
use Unilend\Bundle\CoreBusinessBundle\Repository\ProjectsRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\{ProjectRequestManager, StatisticsManager, WelcomeOfferManager};
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\FrontBundle\Service\{ContentManager, ProjectDisplayManager, SeoManager, SourceManager, TestimonialManager};
use Unilend\core\Loader;

class MainController extends Controller
{
    const CMS_TEMPLATE_BIG_HEADER            = 1;
    const CMS_TEMPLATE_NAV                   = 2;
    const CMS_TEMPLATE_BORROWER_LANDING_PAGE = 3;
    const CMS_TEMPLATE_TOS                   = 5;

    const SLUG_PAGE_BECOME_LENDER = 'lender_subscription_personal_information';
    const SLUG_ELEMENT_NAV_IMAGE  = 'image-header';
    /** anchors in path functions are not supported, see twig template for handing if the borrower esim special case */
    const SLUG_PAGE_BECOME_BORROWER = 'emprunter-homeemp-section-esim';

    /**
     * @Route("/", name="home")
     *
     * @return Response
     */
    public function homeAction(): Response
    {
        /** @var TestimonialManager $testimonialService */
        $testimonialService = $this->get('unilend.frontbundle.service.testimonial_manager');
        /** @var AuthorizationChecker $authorizationChecker */
        $authorizationChecker = $this->get('security.authorization_checker');
        /** @var WelcomeOfferManager $welcomeOfferManager */
        $welcomeOfferManager = $this->get('unilend.service.welcome_offer_manager');

        $template = [
            'showWelcomeOffer'   => $welcomeOfferManager->displayOfferOnHome(),
            'testimonialPeople'  => $testimonialService->getAllBattenbergTestimonials(),
            'sliderTestimonials' => $testimonialService->getSliderInformation(),
            'welcomeOfferAmount' => $this->get('unilend.service.welcome_offer_manager')->getWelcomeOfferAmount(OffresBienvenues::TYPE_HOME)
        ];

        if ($authorizationChecker->isGranted('ROLE_LENDER')) {
            return $this->redirectToRoute('home_lender');
        } elseif ($authorizationChecker->isGranted('ROLE_BORROWER')) {
            return $this->redirectToRoute('home_borrower');
        }

        return $this->render('main/home.html.twig', $template);
    }

    /**
     * @Route("/preter", name="home_lender")
     *
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function homeLenderAction(?UserInterface $client): Response
    {
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        $welcomeOfferManager   = $this->get('unilend.service.welcome_offer_manager');
        $testimonialService    = $this->get('unilend.frontbundle.service.testimonial_manager');

        $template = [
            'showWelcomeOffer'   => $welcomeOfferManager->displayOfferOnHome(),
            'amountWelcomeOffer' => $welcomeOfferManager->getWelcomeOfferAmount(OffresBienvenues::TYPE_HOME),
            'featureLender'      => $testimonialService->getFeaturedTestimonialLender(),
            'showPagination'     => false,
            'showSortable'       => false,
            'sortType'           => ProjectsRepository::SORT_FIELD_END,
            'sortDirection'      => 'desc'
        ];

        $template['projects'] = $projectDisplayManager->getProjectsList([], [ProjectsRepository::SORT_FIELD_END => 'DESC'], null, 3, $client);

        $translator        = $this->get('translator');
        $projectRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Projects');

        array_walk($template['projects'], function(&$project) use ($translator, $projectDisplayManager, $client, $projectRepository) {
            if (ProjectDisplayManager::VISIBILITY_FULL !== $projectDisplayManager->getVisibility($projectRepository->find($project['projectId']), $client)) {
                $project['title'] = $translator->trans('company-sector_sector-' . $project['company']['sectorId']);
            }
        });

        return $this->render('main/home_lender.html.twig', $template);
    }

    /**
     * @Route("/emprunter", name="home_borrower")
     *
     * @return Response
     */
    public function homeBorrowerAction(): Response
    {
        $projectManager        = $this->get('unilend.service.project_manager');
        $testimonialService    = $this->get('unilend.frontbundle.service.testimonial_manager');
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');

        $template = [];
        $template['testimonialPeople'] = $testimonialService->getBorrowerBattenbergTestimonials(true);
        $template['loanPeriods']       = $projectManager->getPossibleProjectPeriods();
        $template['projectAmountMax']  = $projectManager->getMaxProjectAmount();
        $template['projectAmountMin']  = $projectManager->getMinProjectAmount();
        $template['borrowingMotives']  = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BorrowingMotive')->findBy([], ['rank' => 'ASC']);
        $template['projects'] = $projectDisplayManager->getProjectsList(
            [ProjectsStatus::EN_FUNDING],
            [ProjectsRepository::SORT_FIELD_END => 'DESC']
        );

        $template['featureBorrower'] = $testimonialService->getFeaturedTestimonialBorrower();

        return $this->render('main/home_borrower.html.twig', $template);
    }

    /**
     * @Route("simulateur-projet-etape1", name="project_simulator", methods={"POST"}, condition="request.isXmlHttpRequest()")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function projectSimulatorStepOneAction(Request $request): JsonResponse
    {
        $period   = $request->request->getInt('period');
        $motiveId = $request->request->getInt('motiveId');

        $projectRequestManager = $this->get('unilend.service.project_request_manager');
        $projectManager        = $this->get('unilend.service.project_manager');
        $translator            = $this->get('translator');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $projectPeriods = $projectManager->getPossibleProjectPeriods();
        $amount         = $projectRequestManager->checkRequestedAmount((int) str_replace([' ', '€'], '', $request->request->get('amount')));

        if (in_array($period, $projectPeriods) && $amount) {
            $estimatedRate                           = $projectRequestManager->getMonthlyRateEstimate();
            $estimatedMonthlyRepayment               = $projectRequestManager->getMonthlyPaymentEstimate($amount, $period, $estimatedRate);
            $estimatedFundingDuration                = $projectManager->getAverageFundingDuration($amount);
            $isMotiveSentenceComplementToBeDisplayed = (false === in_array($motiveId, ['', \borrowing_motive::OTHER]));
            $translationComplement                   = $isMotiveSentenceComplementToBeDisplayed ? $translator->trans('home-borrower_simulator-step-2-text-segment-motive-' . $motiveId) : '';

            return new JsonResponse([
                'estimatedMonthlyRepayment'             => $translator->trans('home-borrower_simulator-footer-monthly-repayment-with-value', ['%monthlyRepayment%' => $ficelle->formatNumber($estimatedMonthlyRepayment, 0)]),
                'estimatedFundingDuration'              => $translator->transChoice('home-borrower_simulator-footer-funding-duration-with-value', $estimatedFundingDuration, ['%fundingDuration%' => $estimatedFundingDuration]),
                'amount'                                => $ficelle->formatNumber($amount, 0),
                'period'                                => $period,
                'motiveSentenceComplementToBeDisplayed' => $isMotiveSentenceComplementToBeDisplayed,
                'translationComplement'                 => $translationComplement
            ]);
        }

        return new JsonResponse('nok');
    }

    /**
     * @Route("simulateur-projet", name="project_simulator_form", methods={"POST"}, condition="request.isXmlHttpRequest()")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function projectSimulatorStepTwoAction(Request $request): JsonResponse
    {
        $entityManager         = $this->get('doctrine.orm.entity_manager');
        $projectRequestManager = $this->get('unilend.service.project_request_manager');
        $projectManager        = $this->get('unilend.service.project_manager');

        $user = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_FRONT);

        $errors = [];

        $email = filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL);
        if (empty($email)) {
            $errors[] = ProjectRequestManager::EXCEPTION_CODE_INVALID_EMAIL;
        }

        $amount = $projectRequestManager->checkRequestedAmount((int) str_replace([' ', '€'], '', $request->request->get('amount')));
        if (empty($amount)) {
            $errors[] = ProjectRequestManager::EXCEPTION_CODE_INVALID_AMOUNT;
        }

        $projectPeriods = $projectManager->getPossibleProjectPeriods();
        $period         = $request->request->getInt('duration');
        if (false === in_array($period, $projectPeriods)) {
            $errors[] = ProjectRequestManager::EXCEPTION_CODE_INVALID_DURATION;
        }

        $borrowingMotive = $request->request->getInt('reason');
        if (empty($borrowingMotive) || null === $entityManager->getRepository('UnilendCoreBusinessBundle:BorrowingMotive')->find($borrowingMotive)) {
            $errors[] = ProjectRequestManager::EXCEPTION_CODE_INVALID_REASON;
        }

        $siren = $request->request->get('siren');
        if (false === empty($siren)) {
            $siren = $projectRequestManager->validateSiren($siren);
            if (false === $siren) {
                $errors[] = ProjectRequestManager::EXCEPTION_CODE_INVALID_SIREN;
            }
            // We accept in the same field both siren and siret
            $siret = $projectRequestManager->validateSiret($siren);
            $siret = $siret === false ? null : $siret;
        } else {
            $siren = null;
            $siret = null;
        }

        if (false === empty($errors)) {
            return $this->json(['success' => false, 'error' => $errors], 400);
        }

        $companyName = $request->request->get('company_name');
        if (empty($companyName)) {
            $companyName = null;
        }

        $partner = $this->get('unilend.service.partner_manager')->getDefaultPartner();

        try {
            $project = $projectRequestManager->newProject($user, $partner, ProjectsStatus::INCOMPLETE_REQUEST, $amount, $siren, $siret, $companyName, $email, $period, $borrowingMotive);

            return $this->json([
                'success' => true,
                'data'    => [
                    'redirectTo' => $this->get('router')->generate('project_request_simulator_start', ['hash' => $project->getHash()])
                ]
            ]);
        } catch (\Exception $exception) {
            $this->get('logger')->error('Could not save project : ' . $exception->getMessage() . '. Form data = ' . json_encode($request->request->all()), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);

            return $this->json(['success' => false, 'error' => [$exception->getCode()]], 500);
        }
    }

    /**
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function cmsAction(Request $request, ?UserInterface $client, SeoManager $seoManager): Response
    {
        /** @var EntityManagerSimulator $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        $slug = substr($request->attributes->get('routeDocument')->getPath(), 1);

        /** @var \tree $tree */
        $tree = $entityManager->getRepository('tree');

        if (false === $tree->get(['slug' => $slug, 'status' => Tree::STATUS_ONLINE])) {
            throw new NotFoundHttpException('Page with slug ' . $slug . ' could not be found');
        }

        /** @var MemcacheCachePool $cachePool */
        $cachePool  = $this->get('memcache.default');
        $cachedItem = $cachePool->getItem('Home_Tree_Childs_Elements_' . $tree->id_tree);

        if (false === $cachedItem->isHit()) {
            $content    = [];
            $complement = [];

            /** @var \tree_elements $treeElements */
            $treeElements = $entityManager->getRepository('tree_elements');

            foreach ($treeElements->selectWithDefinition('id_tree = ' . $tree->id_tree, 'ordre ASC') as $element) {
                $content[$element['slug']]    = $element['value'];
                $complement[$element['slug']] = $element['complement'];
            }

            $finalElements = [
                'content'    => $content,
                'complement' => $complement
            ];

            $cachedItem->set($finalElements)->expiresAfter(3600);
            $cachePool->save($cachedItem);
        } else {
            $finalElements = $cachedItem->get();
        }

        $seoManager->setCmsSeoData($tree);

        switch ($tree->id_template) {
            case self::CMS_TEMPLATE_BIG_HEADER:
                return $this->renderCmsBigHeader($finalElements['content']);
            case self::CMS_TEMPLATE_NAV:
                return $this->renderCmsNav($tree, $finalElements['content'], $entityManager);
            case self::CMS_TEMPLATE_BORROWER_LANDING_PAGE:
                return $this->renderBorrowerLandingPage($request, $finalElements['content'], $finalElements['complement']);
            case self::CMS_TEMPLATE_TOS:
                return $this->redirectToRoute('lenders_terms_of_sales');
            default:
                return new RedirectResponse('/');
        }
    }

    /**
     * @param array $content
     *
     * @return Response
     */
    private function renderCmsBigHeader(array $content): Response
    {
        $cms = [
            'title'         => $content['titre'],
            'header_image'  => $content['image-header'],
            'left_content'  => $content['bloc-gauche'],
            'right_content' => $content['bloc-droite']
        ];

        return $this->render('cms_templates/template_big_header.html.twig', ['cms' => $cms]);
    }

    /**
     * @param \tree         $currentPage
     * @param array         $content
     * @param EntityManagerSimulator $entityManager
     * @param string|null   $pageId
     *
     * @return Response
     */
    private function renderCmsNav(\tree $currentPage, array $content, EntityManagerSimulator $entityManager, ?string $pageId = null): Response
    {
        /** @var \tree $pages */
        $pages = $entityManager->getRepository('tree');

        $selected   = false;
        $navigation = [];
        $nextPage   = [];
        foreach ($pages->select('status = 1 AND prive = 0 AND id_parent = ' . $currentPage->id_parent, 'ordre ASC') as $page) {
            // If previous page was current page, it means we are now processing "next" page
            if ($selected) {
                /** @var \tree_elements $treeElements */
                $treeElements = $entityManager->getRepository('tree_elements');

                foreach ($treeElements->selectWithDefinition('id_tree = ' . $page['id_tree'], 'ordre ASC') as $element) {
                    if ($element['slug'] === self::SLUG_ELEMENT_NAV_IMAGE) {
                        $nextPage = [
                            'label'        => $page['menu_title'],
                            'slug'         => $page['slug'],
                            'header_image' => $element['value']
                        ];
                        break;
                    }
                }
            }

            $selected = $page['id_tree'] == $currentPage->id_tree;

            $navigation[$page['id_tree']] = [
                'label'                => $page['menu_title'],
                'slug'                 => $page['slug'],
                'selected'             => $selected,
                'highlighted_lender'   => $page['slug'] === self::SLUG_PAGE_BECOME_LENDER,
                'highlighted_borrower' => $page['slug'] === self::SLUG_PAGE_BECOME_BORROWER
            ];
        }

        $cms = [
            'header_image' => $content['image-header'],
            'content'      => $content['contenu']
        ];

        $page = [
            'id'          => $pageId,
            'title'       => $currentPage->meta_title,
            'next'        => $nextPage
        ];

        return $this->render('cms_templates/template_nav.html.twig', ['navigation' => $navigation, 'cms' => $cms, 'page' => $page]);
    }

    /**
     * @param Request $request
     * @param array   $content
     * @param array   $complement
     *
     * @return Response
     */
    private function renderBorrowerLandingPage(Request $request, array $content, array $complement): Response
    {
        $borrowingReasons = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BorrowingMotive')->findBy([], ['rank' => 'ASC']);
        $sessionHandler   = $request->getSession();
        $isPartnerFunnel  = isset($content['tunnel-partenaire']) ? $content['tunnel-partenaire'] == 1 : false;

        if ($isPartnerFunnel) {
            $sourceManager = $this->get('unilend.frontbundle.service.source_manager');
            $sourceManager->setSource(SourceManager::SOURCE1, '');
            $sourceManager->setSource(SourceManager::SOURCE2, '');
            $sourceManager->setSource(SourceManager::SOURCE3, '');
        }

        $template = [
            'cms'  => [
                'partner_logo'     => $content['logo-partenaire'],
                'partner_logo_alt' => $complement['logo-partenaire'],
                'partner_funnel'   => $isPartnerFunnel
            ],
            'form' => [
                'values' => [
                    'amount'           => empty($sessionHandler->get('projectRequest')['values']['amount']) ? (empty($request->query->getInt('montant')) ? '' : $request->query->get('montant')) : $sessionHandler->get('projectRequest')['values']['amount'],
                    'siren'            => empty($sessionHandler->get('projectRequest')['values']['siren']) ? (empty($request->query->getInt('siren')) ? '' : $request->query->get('siren')) : $sessionHandler->get('projectRequest')['values']['siren'],
                    'email'            => empty($sessionHandler->get('projectRequest')['values']['email']) ? (empty($request->query->get('email')) ? '' : filter_var($request->query->get('email'), FILTER_SANITIZE_EMAIL)) : $sessionHandler->get('projectRequest')['values']['email'],
                    'partner'          => $content['partenaire'] ?? '',
                    'reasons'          => $borrowingReasons,
                    'availablePeriods' => $this->get('unilend.service.project_manager')->getPossibleProjectPeriods(),
                ],
            ],
        ];

        $session = [];

        /**
         * If borrower is redirected to Unilend
         * We save data to session
         */
        foreach (['prenom', 'nom', 'mobile'] as $fieldName) {
            if ($request->query->get($fieldName)) {
                $session['values'][$fieldName] = filter_var($request->query->get($fieldName), FILTER_SANITIZE_STRING);
            }
        }

        $sessionHandler->set('projectRequest', $session);
        $sessionHandler->set('partnerProjectRequest', $isPartnerFunnel);

        return $this->render('cms_templates/template_borrower_landing_page.html.twig', $template);
    }

    /**
     * @param string|null    $route
     * @param ContentManager $contentManager
     *
     * @return Response
     */
    public function footerAction(?string $route, ContentManager $contentManager): Response
    {
        return $this->render('partials/footer.html.twig', [
            'menus'             => $contentManager->getFooterMenu(),
            'displayDisclaimer' => $route !== 'project_detail'
        ]);
    }

    /**
     * @return Response
     */
    public function footerReviewsAction(): Response
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \blocs $block */
        $block = $entityManagerSimulator->getRepository('blocs');
        /** @var \blocs_elements $blockElement */
        $blockElement = $entityManagerSimulator->getRepository('blocs_elements');
        /** @var \elements $elements */
        $elements = $entityManagerSimulator->getRepository('elements');

        $reviews = [];
        if ($block->get('carousel-revue-presse-footer', 'slug')) {
            $elementsDescription = $elements->select('status = 1 AND id_bloc = ' . $block->id_bloc, 'ordre ASC');
            $elementsDescription = array_combine(array_column($elementsDescription, 'id_element'), $elementsDescription);

            foreach ($blockElement->select('status = 1 AND id_bloc = ' . $block->id_bloc, 'FIELD(id_element, ' . implode(', ', array_keys($elementsDescription)) . ') ASC') as $element) {
                if (1 === preg_match('/ (?<reviewId>[0-9]+)$/', $elementsDescription[$element['id_element']]['name'], $matches)) {
                    switch ($elementsDescription[$element['id_element']]['type_element']) {
                        case 'Texte':
                            $reviews[$matches['reviewId']]['quote'] = $element['value'];
                            break;
                        case 'Image':
                            $reviews[$matches['reviewId']]['name']  = $element['complement'];
                            $reviews[$matches['reviewId']]['image'] = $element['value'];
                            break;
                        default:
                            /** @var LoggerInterface $logger */
                            $logger = $this->get('monolog.logger');
                            $logger->notice('Could not match review ID with pattern "' . $elementsDescription[$element['id_element']]['name'] . '"');
                            break;
                    }
                }
            }
        }

        return $this->render('partials/reviews.html.twig', ['reviews' => $reviews]);
    }

    /**
     * @Route("/qui-sommes-nous", name="about_us")
     *
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param SeoManager             $seoManager
     *
     * @return Response
     */
    public function aboutUsAction(EntityManagerSimulator $entityManagerSimulator, SeoManager $seoManager): Response
    {
        /** @var \tree $tree */
        $tree = $entityManagerSimulator->getRepository('tree');
        $tree->get(['slug' => 'qui-sommes-nous']);
        $seoManager->setCmsSeoData($tree);
        $response = $this->render('static_pages/about_us.html.twig');

        $finalElements = [
            'contenu'      => $response->getContent(),
            'complement'   => '',
            'image-header' => 'apropos-header-1682x400.jpg'
        ];

        return $this->renderCmsNav($tree, $finalElements, $entityManagerSimulator);
    }

    /**
     * @Route("/statistiques", name="statistics")
     * @Route("/statistiques/{requestedDate}", name="historic_statistics", requirements={"requestedDate": "20[0-9]{2}-[0-9]{2}-[0-9]{2}"})
     *
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param SeoManager             $seoManager
     * @param StatisticsManager      $statisticsManager
     * @param string|null            $requestedDate
     *
     * @return Response
     */
    public function statisticsAction(EntityManagerSimulator $entityManagerSimulator, SeoManager $seoManager, StatisticsManager $statisticsManager, ?string $requestedDate = null): Response
    {
        /** @var \tree $tree */
        $tree = $entityManagerSimulator->getRepository('tree');
        $tree->get(['slug' => 'statistiques']);

        if (false === empty($requestedDate)) {
            $firstHistoryDate = new \DateTime(StatisticsManager::START_FRONT_STATISTICS_HISTORY);
            $date             = \DateTime::createFromFormat('Y-m-d', $requestedDate);
            if ($date < $firstHistoryDate) {
                return $this->redirectToRoute('statistics');
            }
            $years = array_merge(['2013-2014'], range(2015, $date->format('Y')));
        } else {
            $date  = new \DateTime('NOW');
            $years = array_merge(['2013-2014'], range(2015, date('Y')));
        }

        $statistics = $statisticsManager->getStatisticsAtDate($date);
        $template = [
            'data'  => [
                'projectCountForCategoryTreeMap' => $this->getProjectCountForCategoryTreeMap($statistics['projectCountByCategory']),
                'regulatoryTable'                => $statistics['regulatoryData']
            ],
            'years' => array_merge($years, ['total']),
            'date'  => $date->format('Y-m-d')
        ];

        $seoManager->setCmsSeoData($tree);
        $response = $this->render('static_pages/statistics.html.twig', $template);

        $finalElements = [
            'contenu'      => $response->getContent(),
            'complement'   => '',
            'image-header' => '1682x400_0005_Statistiques.jpg'
        ];

        return $this->renderCmsNav($tree, $finalElements, $entityManagerSimulator, 'apropos-statistiques');
    }

    /**
     * @Route("/indicateurs-de-performance", name="statistics_fpf", methods={"GET"})
     *
     * @param Request                $request
     * @param StatisticsManager      $statisticsManager
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param SeoManager             $seoManager
     *
     * @return Response
     */
    public function statisticsFpfAction(Request $request, StatisticsManager $statisticsManager, EntityManagerSimulator $entityManagerSimulator, SeoManager $seoManager): Response
    {
        $now         = new \DateTime('NOW');
        $publishDate = new \DateTime('First day of November 2017');
        $publishDate->setTime(0, 0, 0);

        if (
            $this->getParameter('kernel.environment') === 'prod'
            && $now < $publishDate
            && $request->getClientIp() != '92.154.10.41'
        ) {
            return $this->render('/pages/exception/error.html.twig');
        }

        $date = $request->query->filter('date', FILTER_SANITIZE_STRING);

        if (
            false === empty($date)
            && preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-[0-9]{4}$/", $date)
        ) {
            $requestedDate    = \DateTime::createFromFormat('d-m-Y', $date);
            $firstHistoryDate = new \DateTime(StatisticsManager::START_FPF_STATISTIC_HISTORY);
            if ($requestedDate < $firstHistoryDate) {
                return $this->redirectToRoute('statistics_fpf');
            }
        } else {
            $requestedDate  = $now;
        }

        $years                 = range(2013, $requestedDate->format('Y'));
        $data                  = $statisticsManager->getPerformanceIndicatorAtDate($requestedDate);
        $data['incidenceRate'] = $statisticsManager->getIncidenceRatesOfLast36Months($requestedDate);

        $template = [
            'data'           => $data,
            'years'          => $years,
            'date'           => $requestedDate,
            'availableDates' => $statisticsManager->getAvailableDatesForFPFStatistics()
        ];
        $response = $this->render('static_pages/statistics_fpf.html.twig', $template);

        /** @var \tree $tree */
        $tree = $entityManagerSimulator->getRepository('tree');
        $tree->get(['slug' => 'indicateurs-de-performance']);
        $seoManager->setCmsSeoData($tree);

        $finalElements = [
            'contenu'      => $response->getContent(),
            'complement'   => '',
            'image-header' => '1682x400_0005_Statistiques.jpg',
        ];

        return $this->renderCmsNav($tree, $finalElements, $entityManagerSimulator, 'apropos-statistiques-fpf');
    }

    /**
     * @Route("/faq-preteur", name="lender_faq")
     *
     * @return Response
     */
    public function lenderFaqAction(): Response
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');

        /** @var \settings $settings */
        $settings = $entityManagerSimulator->getRepository('settings');
        $settings->get('URL FAQ preteur', 'type');

        return $this->redirect($settings->value);
    }

    /**
     * @Route("/faq-emprunteur", name="borrower_faq")
     *
     * @return Response
     */
    public function borrowerFaqAction(): Response
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');

        /** @var \settings $settings */
        $settings = $entityManagerSimulator->getRepository('settings');
        $settings->get('URL FAQ emprunteur', 'type');

        return $this->redirect($settings->value);
    }


    /**
     * @Route("/plan-du-site", name="sitemap")
     *
     * @return Response
     */
    public function siteMapAction(): Response
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \tree $pages */
        $pages = $entityManagerSimulator->getRepository('tree');
        $template = [];
        $pagesBySections = [];

        foreach ($pages->select('status = 1 AND prive = 1 AND id_parent = 1', 'ordre ASC') as $template) {
            foreach ($pages->select('status = 1 AND prive = 0 AND status_menu = 1 AND id_parent = ' . $template['id_tree'], 'ordre ASC') as $section) {
                $pagesBySections[$section['title']]['title']    = $section['title'];
                $pagesBySections[$section['title']]['slug']     = $section['slug'];
                $pagesBySections[$section['title']]['children'] = $pages->select('status = 1 AND prive = 0 AND id_parent = ' . $section['id_tree'], 'ordre ASC');
            }
        }
        $template['sections'] =  $pagesBySections;

        return $this->render('static_pages/sitemap.html.twig', $template);
    }

    /**
     * @Route("/temoignages", name="testimonials")
     *
     * @return Response
     */
    public function testimonialAction(TestimonialManager $testimonialService, EntityManagerSimulator $entityManagerSimulator, SeoManager $seoManager): Response
    {
        /** @var \tree $tree */
        $tree = $entityManagerSimulator->getRepository('tree');
        $tree->get(['slug' => 'temoignages']);
        $seoManager->setCmsSeoData($tree);

        $template['testimonialPeople'] = $testimonialService->getBorrowerBattenbergTestimonials(false);
        $response                      = $this->render('static_pages/testimonials.html.twig', $template);
        $finalElements                 = [
            'contenu'      => $response->getContent(),
            'complement'   => '',
            'image-header' => ''
        ];

        return $this->renderCmsNav($tree, $finalElements, $entityManagerSimulator, 'apropos-statistiques');
    }

    /**
     * @param array $countByCategory
     *
     * @return array
     */
    private function getProjectCountForCategoryTreeMap(array $countByCategory): array
    {
        /** @var TranslatorInterface $translator */
        $translator     = $this->get('translator');
        $dataForTreeMap = [];

        foreach ($countByCategory as $category => $count) {
            $dataForTreeMap[] = [
                'name'      => $translator->trans('company-sector_sector-' . $category),
                'value'     => (int) $count,
                'svgIconId' => '#category-sm-' . $category
            ];
        }
        return $dataForTreeMap;
    }
}
