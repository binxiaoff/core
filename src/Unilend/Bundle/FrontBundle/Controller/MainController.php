<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectRequestManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\StatisticsManager;
use Unilend\Bundle\CoreBusinessBundle\Service\WelcomeOfferManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;
use Unilend\Bundle\FrontBundle\Security\User\UserBorrower;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\Bundle\FrontBundle\Service\ProjectDisplayManager;
use Unilend\Bundle\FrontBundle\Service\SourceManager;
use Unilend\Bundle\FrontBundle\Service\TestimonialManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;
use Unilend\core\Loader;

class MainController extends Controller
{
    const CMS_TEMPLATE_BIG_HEADER            = 1;
    const CMS_TEMPLATE_NAV                   = 2;
    const CMS_TEMPLATE_BORROWER_LANDING_PAGE = 3;

    const SLUG_PAGE_BECOME_LENDER = 'devenir-preteur';
    const SLUG_ELEMENT_NAV_IMAGE  = 'image-header';

    /**
     * @Route("/", name="home")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function homeAction()
    {
        /** @var TestimonialManager $testimonialService */
        $testimonialService = $this->get('unilend.frontbundle.service.testimonial_manager');
        /** @var AuthorizationChecker $authorizationChecker */
        $authorizationChecker = $this->get('security.authorization_checker');
        /** @var ProjectDisplayManager $projectDisplayManager */
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        /** @var WelcomeOfferManager $welcomeOfferManager */
        $welcomeOfferManager = $this->get('unilend.service.welcome_offer_manager');

        $template = [];
        $template['showWelcomeOffer']  = $welcomeOfferManager->displayOfferOnHome();
        $template['testimonialPeople'] = $testimonialService->getAllBattenbergTestimonials();
        $template['projects'] = $projectDisplayManager->getProjectsList(
            [\projects_status::EN_FUNDING],
            [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_DESC]
        );

        $template['sliderTestimonials'] = $testimonialService->getSliderInformation();

        if ($authorizationChecker->isGranted('ROLE_LENDER')) {
            $this->redirectToRoute('home_lender');
        } elseif ($authorizationChecker->isGranted('ROLE_BORROWER')) {
            $this->redirectToRoute('home_borrower');
        }

        return $this->render('pages/homepage_acquisition.html.twig', $template);
    }

    /**
     * @Route("/preter", name="home_lender")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function homeLenderAction()
    {
        /** @var ProjectDisplayManager $projectDisplayManager */
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        /** @var AuthorizationChecker $authorizationChecker */
        $authorizationChecker = $this->get('security.authorization_checker');
        /** @var WelcomeOfferManager $welcomeOfferManager */
        $welcomeOfferManager = $this->get('unilend.service.welcome_offer_manager');
        /** @var TestimonialManager $testimonialService */
        $testimonialService = $this->get('unilend.frontbundle.service.testimonial_manager');

        $template                     = [];
        $template['showWelcomeOffer'] = $welcomeOfferManager->displayOfferOnHome();
        $template['featureLender']    = $testimonialService->getFeaturedTestimonialLender();
        $template['showPagination']   = false;
        $template['showSortable']     = false;
        $template['sortType']         = strtolower(\projects::SORT_FIELD_END);
        $template['sortDirection']    = strtolower(\projects::SORT_DIRECTION_DESC);

        /** @var BaseUser $user */
        $user = $this->getUser();

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')
            && $authorizationChecker->isGranted('ROLE_LENDER')
        ) {
            /** @var \lenders_accounts $lenderAccount */
            $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
            $lenderAccount->get($user->getClientId(), 'id_client_owner');

            $template['projects'] = $projectDisplayManager->getProjectsList(
                [\projects_status::EN_FUNDING],
                [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_DESC],
                null,
                null,
                $lenderAccount
            );
        } else {
            $template['projects'] = $projectDisplayManager->getProjectsList(
                [\projects_status::EN_FUNDING],
                [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_DESC]
            );
        }

        $isFullyConnectedUser = ($user instanceof UserLender && $user->getClientStatus() == \clients_status::VALIDATED || $user instanceof UserBorrower);

        if (false === $isFullyConnectedUser) {
            /** @var Translator $translator */
            $translator = $this->get('translator');
            array_walk($template['projects'], function(&$project) use ($translator) {
                $project['title'] = $translator->trans('company-sector_sector-' . $project['company']['sectorId']);
            });
        }

        return $this->render('pages/homepage_lender.html.twig', $template);
    }

    /**
     * @Route("/emprunter", name="home_borrower")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function homeBorrowerAction()
    {
        /** @var ProjectManager $projectManager */
        $projectManager = $this->get('unilend.service.project_manager');
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        /** @var TestimonialManager $testimonialService */
        $testimonialService = $this->get('unilend.frontbundle.service.testimonial_manager');
        /** @var ProjectDisplayManager $projectDisplayManager */
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');

        $template = [];
        $template['testimonialPeople'] = $testimonialService->getBorrowerBattenbergTestimonials(true);
        $template['loanPeriods']       = $projectManager->getPossibleProjectPeriods();
        $template['projectAmountMax']  = $projectManager->getMaxProjectAmount();
        $template['projectAmountMin']  = $projectManager->getMinProjectAmount();
        $template['borrowingMotives']  = $translationManager->getTranslatedBorrowingMotiveList();
        $template['projects'] = $projectDisplayManager->getProjectsList(
            [\projects_status::EN_FUNDING],
            [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_DESC]
        );

        $template['featureBorrower'] = $testimonialService->getFeaturedTestimonialBorrower();

        return $this->render('pages/homepage_borrower.html.twig', $template);
    }

    /**
     * @Route("/simulateur-projet-etape1", name="project_simulator")
     * @Method("POST")
     */
    public function projectSimulatorStepOneAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            $period   = $request->request->get('period');
            $amount   = $request->request->get('amount');
            $motiveId = $request->request->get('motiveId');

            /** @var ProjectRequestManager $projectRequestManager */
            $projectRequestManager = $this->get('unilend.service.project_request_manager');
            /** @var ProjectManager $projectManager */
            $projectManager = $this->get('unilend.service.project_manager');
            /** @var TranslatorInterface $translator */
            $translator = $this->get('translator');
            /** @var \ficelle $ficelle */
            $ficelle = Loader::loadLib('ficelle');

            $projectPeriods   = $projectManager->getPossibleProjectPeriods();
            $projectAmountMax = $projectManager->getMaxProjectAmount();
            $projectAmountMin = $projectManager->getMinProjectAmount();

            if (
                in_array($period, $projectPeriods)
                && $amount >= $projectAmountMin
                && $amount <= $projectAmountMax
            ){
                $estimatedRate                           = $projectRequestManager->getMonthlyRateEstimate();
                $estimatedMonthlyRepayment               = $projectRequestManager->getMonthlyPaymentEstimate($amount, $period, $estimatedRate);
                $isMotiveSentenceComplementToBeDisplayed = (false === in_array($motiveId, ['', \borrowing_motive::OTHER]));
                $translationComplement                   = $isMotiveSentenceComplementToBeDisplayed ? $translator->trans('home-borrower_simulator-step-2-text-segment-motive-' . $motiveId) : '';

                return new JsonResponse([
                    'estimatedRate'                         => $ficelle->formatNumber($estimatedRate, 1),
                    'estimatedMonthlyRepayment'             => $ficelle->formatNumber($estimatedMonthlyRepayment, 0),
                    'amount'                                => $ficelle->formatNumber($amount, 0),
                    'period'                                => $period,
                    'motiveSentenceComplementToBeDisplayed' => $isMotiveSentenceComplementToBeDisplayed,
                    'translationComplement'                 => $translationComplement
                ]);
            }

            return new JsonResponse('nok');
        }
        return new Response('not an ajax request');
    }

    /**
     * @Route("/simulateur-projet", name="project_simulator_form")
     * @Method("POST")
     */
    public function projectSimulatorStepTwoAction(Request $request)
    {
        $aFormData = $request->request->get('esim');

        /** @var ProjectRequestManager $projectRequestManager */
        $projectRequestManager = $this->get('unilend.service.project_request_manager');
        $project               = $projectRequestManager->saveSimulatorRequest($aFormData);

        $session = $request->getSession();
        $session->set('esim/project_id', $project->id_project);

        return $this->redirectToRoute('project_request_simulator_start', ['hash' => $project->hash]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function cmsAction(Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \redirections $redirection */
        $redirection = $entityManager->getRepository('redirections');

        $slug = substr($request->attributes->get('routeDocument')->getPath(), 1);

        if ($redirection->get(['from_slug' => $slug, 'status' => 1])) {
            return new RedirectResponse($redirection->to_slug, $redirection->type);
        }

        /** @var \tree $tree */
        $tree = $entityManager->getRepository('tree');

        if (false === $tree->get(['slug' => $slug, 'status' => 1])) {
            throw new NotFoundHttpException('Page with slug ' . $slug . ' could not be found');
        }

        /** @var MemcacheCachePool $cachePool */
        $cachePool  = $this->get('memcache.default');
        $cachedItem = $cachePool->getItem('Home_Tree_Childs_Elements_' . $tree->id_tree);

        if (true||false === $cachedItem->isHit()) {
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

        switch ($tree->id_template) {
            case self::CMS_TEMPLATE_BIG_HEADER:
                return $this->renderCmsBigHeader($tree, $finalElements['content']);
            case self::CMS_TEMPLATE_NAV:
                return $this->renderCmsNav($tree, $finalElements['content'], $entityManager);
            case self::CMS_TEMPLATE_BORROWER_LANDING_PAGE:
                return $this->renderBorrowerLandingPage($request, $tree, $finalElements['content'], $finalElements['complement'], $entityManager);
            default:
                return new RedirectResponse('/');
        }
    }

    /**
     * @param \tree $tree
     * @param array $content
     * @return Response
     */
    private function renderCmsBigHeader(\tree $tree, array $content)
    {
        $cms = [
            'title'         => $content['titre'],
            'header_image'  => $content['image-header'],
            'left_content'  => $content['bloc-gauche'],
            'right_content' => $content['bloc-droite']
        ];

        $page = [
            'title'       => $tree->meta_title,
            'description' => $tree->meta_description,
            'keywords'    => $tree->meta_keywords,
            'isIndexable' => $tree->indexation == 1
        ];

        return $this->render('pages/template-big-header.html.twig', ['cms' => $cms, 'page' => $page]);
    }

    /**
     * @param \tree         $currentPage
     * @param array         $content
     * @param EntityManager $entityManager
     * @param string|null   $pageId
     * @return Response
     */
    private function renderCmsNav(\tree $currentPage, array $content, EntityManager $entityManager, $pageId = null)
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
                'label'       => $page['menu_title'],
                'slug'        => $page['slug'],
                'selected'    => $selected,
                'highlighted' => $page['slug'] === self::SLUG_PAGE_BECOME_LENDER
            ];
        }

        $cms = [
            'header_image' => $content['image-header'],
            'content'      => $content['contenu']
        ];

        $page = [
            'id'          => $pageId,
            'title'       => $currentPage->meta_title,
            'description' => $currentPage->meta_description,
            'keywords'    => $currentPage->meta_keywords,
            'isIndexable' => $currentPage->indexation == 1,
            'next'        => $nextPage
        ];

        return $this->render('pages/template-nav.html.twig', ['navigation' => $navigation, 'cms' => $cms, 'page' => $page]);
    }

    /**
     * @param Request       $request
     * @param \tree         $tree
     * @param array         $content
     * @param array         $complement
     * @param EntityManager $entityManager
     * @return Response
     */
    private function renderBorrowerLandingPage(Request $request, \tree $tree, array $content, array $complement, EntityManager $entityManager)
    {
        $sessionHandler = $request->getSession();

        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

        $settings->get('Google Tag Manager', 'type');
        $googleTagManagerId = $settings->value;

        $settings->get('Somme à emprunter min', 'type');
        $minimumAmount = $settings->value;

        $settings->get('Somme à emprunter max', 'type');
        $maximumAmount = $settings->value;

        $isPartnerFunnel = $content['tunnel-partenaire'] == 1;

        if ($isPartnerFunnel) {
            $sourceManager = $this->get('unilend.frontbundle.service.source_manager');
            $sourceManager->setSource(SourceManager::SOURCE1, '');
            $sourceManager->setSource(SourceManager::SOURCE2, '');
            $sourceManager->setSource(SourceManager::SOURCE3, '');
        }

        $template = [
            'cms'      => [
                'title'                       => $content['titre'],
                'center_blocks'               => [
                    1 => [
                        'text'      => $content['texte-bloc-1'],
                        'image'     => $content['image-bloc-1'],
                        'image_alt' => $complement['image-bloc-1']
                    ],
                    2 => [
                        'text'      => $content['texte-bloc-2'],
                        'image'     => $content['image-bloc-2'],
                        'image_alt' => $complement['image-bloc-2']
                    ],
                    3 => [
                        'text'      => $content['texte-bloc-3'],
                        'image'     => $content['image-bloc-3'],
                        'image_alt' => $complement['image-bloc-3']
                    ]
                ],
                'center_button_text'          => $content['texte-bouton-centre'],
                'right_block_title'           => $content['titre-bloc-droite'],
                'form_validation_button_text' => $content['texte-bouton-validation-formulaire'],
                'partner_logo'                => $content['logo-partenaire'],
                'partner_logo_alt'            => $complement['logo-partenaire'],
                'footer'                      => [
                    $content['image-footer-1'] => $complement['image-footer-1'],
                    $content['image-footer-2'] => $complement['image-footer-2'],
                    $content['image-footer-3'] => $complement['image-footer-3'],
                    $content['image-footer-4'] => $complement['image-footer-4'],
                    $content['image-footer-5'] => $complement['image-footer-5'],
                    $content['image-footer-6'] => $complement['image-footer-6'],
                    $content['image-footer-7'] => $complement['image-footer-7']
                ],
                'partner_funnel'              => $isPartnerFunnel
            ],
            'page'     => [
                'title'       => $tree->meta_title,
                'description' => $tree->meta_description,
                'keywords'    => $tree->meta_keywords,
                'isIndexable' => $tree->indexation == 1
            ],
            'form'     => [
                'message' => empty($sessionHandler->get('projectRequest')['message']) ? '' : $sessionHandler->get('projectRequest')['message'],
                'values'  => [
                    'amount' => empty($sessionHandler->get('projectRequest')['values']['amount']) ? (empty($request->query->get('montant')) ? '' : $request->query->get('montant')) : $sessionHandler->get('projectRequest')['values']['amount'],
                    'siren'  => empty($sessionHandler->get('projectRequest')['values']['siren']) ? (empty($request->query->get('siren')) ? '' : $request->query->get('siren')) : $sessionHandler->get('projectRequest')['values']['siren'],
                    'email'  => empty($sessionHandler->get('projectRequest')['values']['email']) ? (empty($request->query->get('email')) ? '' : $request->query->get('email')) : $sessionHandler->get('projectRequest')['values']['email']
                ],
                'errors'  => empty($sessionHandler->get('projectRequest')['errors']) ? [] : $sessionHandler->get('projectRequest')['errors']
            ],
            'settings' => [
                'googleTagManagerId' => $googleTagManagerId,
                'minimumAmount'      => $minimumAmount,
                'maximumAmount'      => $maximumAmount
            ]
        ];

        $session = [];

        /**
         * If borrower is redirected to Unilend
         * We save data to session
         */
        foreach (['prenom', 'nom', 'mobile'] as $fieldName) {
            if ($request->query->get($fieldName)) {
                $session['values'][$fieldName] = $request->query->get($fieldName);
            }
        }

        $sessionHandler->set('projectRequest', $session);
        $sessionHandler->set('partnerProjectRequest', $isPartnerFunnel);

        return $this->render('pages/template-borrower-landing-page.html.twig', $template);
    }

    /**
     * @return Response
     */
    public function footerAction()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \menus $menus */
        $menus = $entityManager->getRepository('menus');
        /** @var \tree_menu $subMenus */
        $subMenus = $entityManager->getRepository('tree_menu');
        /** @var \tree $page */
        $page = $entityManager->getRepository('tree');

        $footerMenu = [];
        foreach ($menus->select('status = 1', 'id_menu ASC') as $menu) {
            $children = [];
            foreach ($subMenus->select('status = 1 AND id_menu = ' . $menu['id_menu'], 'ordre ASC') as $subMenu) {
                $children[] = [
                    'title'  => $subMenu['nom'],
                    'target' => $subMenu['target'],
                    'url'    => ($subMenu['complement'] === 'L' && $page->get(['id_tree' => $subMenu['value']])) ? '/' . $page->slug : $subMenu['value']
                ];
            }

            $footerMenu[] = [
                'title'    => $menu['nom'],
                'children' => $children
            ];
        }

        /** @var \blocs $block */
        $block = $entityManager->getRepository('blocs');
        /** @var \blocs_elements $blockElement */
        $blockElement = $entityManager->getRepository('blocs_elements');
        /** @var \elements $elements */
        $elements = $entityManager->getRepository('elements');

        $partners = [];
        if ($block->get('partenaires', 'slug')) {
            $elementsId = array_column($elements->select('status = 1 AND id_bloc = ' . $block->id_bloc, 'ordre ASC'), 'id_element');
            foreach ($blockElement->select('status = 1 AND id_bloc = ' . $block->id_bloc, 'FIELD(id_element, ' . implode(', ', $elementsId) . ') ASC') as $element) {
                if (false === empty($element['value'])) {
                    $partners[] = [
                        'alt' => $element['complement'],
                        'src' => $element['value']
                    ];
                }
            }
        }

        $response = $this->render('partials/site/footer.html.twig', ['menus' => $footerMenu, 'partners' => $partners]);
        $response->setSharedMaxAge(900);

        return $response;
    }

    /**
     * @return Response
     */
    public function footerReviewsAction()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \blocs $block */
        $block = $entityManager->getRepository('blocs');
        /** @var \blocs_elements $blockElement */
        $blockElement = $entityManager->getRepository('blocs_elements');
        /** @var \elements $elements */
        $elements = $entityManager->getRepository('elements');

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

        $response = $this->render('partials/site/reviews.html.twig', ['reviews' => $reviews]);
        $response->setSharedMaxAge(900);

        return $response;
    }

    /**
     * @Route("/accept-cookies", name="accept_cookies")
     * @Method("POST")
     *
     * @param Request $request
     * @return Response
     */
    public function acceptCookiesAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) {
            /** @var \accept_cookies $acceptCookies */
            $acceptCookies = $this->get('unilend.service.entity_manager')->getRepository('accept_cookies');

            $acceptCookies->ip        = $request->getClientIp();
            $acceptCookies->id_client = $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ? $this->getUser()->getClientId() : 0;
            $acceptCookies->create();

            $response = new JsonResponse(true);
            $response->headers->setCookie(new Cookie("acceptCookies", $acceptCookies->id_accept_cookies, time() + (365 * 24 * 3600), '/'));

            return $response;
        }

        return new Response('not an ajax request');
    }

    /**
     * @Route("/qui-sommes-nous", name="about_us")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function aboutUsAction()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \tree $tree */
        $tree = $entityManager->getRepository('tree');
        $tree->get(['slug' => 'qui-sommes-nous']);

        $response = $this->render('pages/static_pages/about_us.html.twig');

        $finalElements = [
            'contenu'      => $response->getContent(),
            'complement'   => '',
            'image-header' => 'apropos-header-1682x400.jpg'
        ];

        return $this->renderCmsNav($tree, $finalElements, $entityManager);
    }

    /**
     * @Route("/statistiques", name="statistics")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function statisticsAction()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \tree $tree */
        $tree = $entityManager->getRepository('tree');
        $tree->get(['slug' => 'statistiques']);

        /** @var StatisticsManager $statisticsManager */
        $statisticsManager = $this->get('unilend.service.statistics_manager');
        $years = array_merge(['2013-2014'], range(2015, date('Y')));
        $template = [
            'data' => [
                'projectCountForCategoryTreeMap' => $this->getProjectCountForCategoryTreeMap(),
                'regulatoryTable' => $statisticsManager->getRegulatoryData(),
            ],
            'years' => array_merge($years, ['total'])
        ];

        $response = $this->render('pages/static_pages/statistics.html.twig', $template);

        $finalElements = [
            'contenu'      => $response->getContent(),
            'complement'   => '',
            'image-header' => '1682x400_0005_Statistiques.jpg'
        ];

        return $this->renderCmsNav($tree, $finalElements, $entityManager, 'apropos-statistiques');
    }

    /**
     * @Route("/faq-preteur", name="lender_faq")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function lenderFaqAction()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        $settings->get('URL FAQ preteur', 'type');

        return $this->redirect($settings->value);
    }

    /**
     * @Route("/faq-emprunteur", name="borrower_faq")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function borrowerFaqAction()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        $settings->get('URL FAQ emprunteur', 'type');

        return $this->redirect($settings->value);
    }


    /**
     * @Route("/plan-du-site", name="sitemap")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function siteMapAction()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \tree $pages */
        $pages = $entityManager->getRepository('tree');
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

        return $this->render('pages/static_pages/sitemap.html.twig', $template);
    }

    /**
     * @Route("/cgv-popup", name="tos_popup", condition="request.isXmlHttpRequest()")
     *
     * @Security("has_role('ROLE_LENDER')")
     */
    public function lastTermsOfServiceAction(Request $request)
    {
        $user = $this->getUser();
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $tosDetails = '';
        if ($client->get($user->getClientId())) {
            if ($request->isMethod('GET')) {
                /** @var \blocs $block */
                $block = $this->get('unilend.service.entity_manager')->getRepository('blocs');
                $block->get('cgv', 'slug');

                $elementSlug = 'tos-new';
                /** @var \acceptations_legal_docs $acceptationsTos */
                $acceptationsTos = $this->get('unilend.service.entity_manager')->getRepository('acceptations_legal_docs');
                if ($acceptationsTos->exist($client->id_client, 'id_client')) {
                    /** @var \settings $settings */
                    $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');

                    $settings->get('Date nouvelles CGV avec 2 mandats', 'type');
                    $newTermsOfServiceDate = $settings->value;

                    /** @var \lenders_accounts $lenderAccount */
                    $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
                    $lenderAccount->get($client->id_client, 'id_client_owner');

                    /** @var \loans $loans */
                    $loans = $this->get('unilend.service.entity_manager')->getRepository('loans');
                    if (0 < $loans->counter('id_lender = ' . $lenderAccount->id_lender_account . ' AND added < "' . $newTermsOfServiceDate . '"')) {
                        $elementSlug = 'tos-update-lended';
                    } else {
                        $elementSlug = 'tos-update';
                    }
                }
                /** @var \elements $elements */
                $elements = $this->get('unilend.service.entity_manager')->getRepository('elements');
                if ($elements->get($elementSlug, 'slug')) {
                    /** @var \blocs_elements $blockElement */
                    $blockElement = $this->get('unilend.service.entity_manager')->getRepository('blocs_elements');

                    if ($blockElement->get($elements->id_element, 'id_element')) {
                        $tosDetails = $blockElement->value;
                    } else {
                        $this->get('logger')->error('The block element id : ' . $elements->id_element . 'doesn\'t exist');
                    }
                } else {
                    $this->get('logger')->error('The element slug : ' . $elementSlug . 'doesn\'t exist');
                }
            } elseif ($request->isMethod('POST')) {
                if ('true' === $request->request->get('terms')) {
                    $clientManager = $this->get('unilend.service.client_manager');
                    $clientManager->acceptLastTos($client);
                }
                return $this->json([]);
            }
        }

        return $this->render('partials/site/tos_popup.html.twig', ['tosDetails' => $tosDetails]);
    }

    /**
     * @Route("/temoignages", name="testimonials")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function testimonialAction()
    {
        /** @var TestimonialManager $testimonialService */
        $testimonialService = $this->get('unilend.frontbundle.service.testimonial_manager');
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \tree $tree */
        $tree = $entityManager->getRepository('tree');
        $tree->get(['slug' => 'temoignages']);

        $template['testimonialPeople'] = $testimonialService->getBorrowerBattenbergTestimonials(false);
        $response = $this->render('pages/static_pages/testimonials.html.twig', $template);

        $finalElements = [
            'contenu'      => $response->getContent(),
            'complement'   => '',
            'image-header' => ''
        ];

        return $this->renderCmsNav($tree, $finalElements, $entityManager, 'apropos-statistiques');
    }

    private function getProjectCountForCategoryTreeMap()
    {
        /** @var StatisticsManager $statisticsManager */
        $statisticsManager = $this->get('unilend.service.statistics_manager');
        $countByCategory = $statisticsManager->getProjectCountByCategory();
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
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
