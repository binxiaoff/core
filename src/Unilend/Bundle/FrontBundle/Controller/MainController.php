<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectRequestManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\WelcomeOfferManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;
use Unilend\Bundle\FrontBundle\Service\ProjectDisplayManager;
use Unilend\Bundle\FrontBundle\Service\TestimonialManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;

class MainController extends Controller
{
    const CMS_TEMPLATE_BIG_HEADER = 1;
    const CMS_TEMPLATE_NAV        = 2;
    const SLUG_PAGE_BECOME_LENDER = 'devenir-preteur';
    const SLUG_ELEMENT_NAV_IMAGE  = 'image-header';

    /**
     * @Route("/home/{type}", defaults={"type" = "acquisition"}, name="home")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function homeAction($type)
    {
        $aTemplateVariables = array();

        /** @var TestimonialManager $testimonialService */
        $testimonialService = $this->get('unilend.frontbundle.service.testimonial_manager');
        /** @var ProjectDisplayManager $projectDisplayManager */
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        /** @var ProjectManager $projectManager */
        $projectManager = $this->get('unilend.service.project_manager');
        /** @var WelcomeOfferManager $welcomeOfferManager */
        $welcomeOfferManager = $this->get('unilend.service.welcome_offer_manager');
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        $aTemplateVariables['testimonialPeople'] = $testimonialService->getActiveBattenbergTestimonials();
        $aTemplateVariables['videoHeroes']       = [
            'Lenders'   => $testimonialService->getActiveVideoHeroes('preter'),
            'Borrowers' => $testimonialService->getActiveVideoHeroes('emprunter')
        ];
        $aTemplateVariables['showWelcomeOffer']  = $welcomeOfferManager->displayOfferOnHome();
        $aTemplateVariables['loanPeriods']       = $projectManager->getPossibleProjectPeriods();
        $aTemplateVariables['projectAmountMax']  = $projectManager->getMaxProjectAmount();
        $aTemplateVariables['projectAmountMin']  = $projectManager->getMinProjectAmount();
        $aTemplateVariables['borrowingMotives']  = $translationManager->getTranslatedBorrowingMotiveList();
        $aTemplateVariables['showPagination']    = false;

        if (
            $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
            && $this->get('security.authorization_checker')->isGranted('ROLE_LENDER')
        ) {
            /** @var BaseUser $user */
            $user = $this->getUser();

            /** @var \lenders_accounts $lenderAccount */
            $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
            $lenderAccount->get($user->getClientId(), 'id_client_owner');

            $aTemplateVariables['projects'] = $projectDisplayManager->getProjectsList(
                [\projects_status::EN_FUNDING],
                [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_DESC],
                null,
                null,
                $lenderAccount
            );
        } else {
            $aTemplateVariables['projects'] = $projectDisplayManager->getProjectsList(
                [\projects_status::EN_FUNDING],
                [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_DESC]
            );
        }

        //TODO replace switch by cookie check
        switch($type) {
            case 'lender' :
                $sTemplateToRender = 'pages/homepage_lender.html.twig';
                break;
            case 'borrower' :
                $sTemplateToRender = 'pages/homepage_borrower.html.twig';
                break;
            case 'acquisition':
            default:
                $sTemplateToRender = 'pages/homepage_acquisition.html.twig';
                break;
        };

        return $this->render($sTemplateToRender, $aTemplateVariables);
    }

    /**
     * @Route("/esim-step-1", name="esim_step_1")
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
            $projectManager        = $this->get('unilend.service.project_manager');
            /** @var TranslationManager $translationManager */
            $translationManager = $this->get('unilend.service.translation_manager');

            $aProjectPeriods   = $projectManager->getPossibleProjectPeriods();
            $iProjectAmountMax = $projectManager->getMaxProjectAmount();
            $iProjectAmountMin = $projectManager->getMinProjectAmount();

            if (
                in_array($period, $aProjectPeriods)
                && $amount >= $iProjectAmountMin
                && $amount <= $iProjectAmountMax
            ){
                $estimatedRate                          = $projectRequestManager->getMonthlyRateEstimate();
                $estimatedMonthlyRepayment              = $projectRequestManager->getMonthlyPaymentEstimate($amount, $period, $estimatedRate);
                $sTranslationComplement                 = $translationManager->selectTranslation('home-borrower', 'simulator-step-2-text-segment-motive-' . $motiveId);
                $bmotiveSentenceComplementToBeDisplayed = (\borrowing_motive::OTHER == $motiveId) ? false : true;

                return new JsonResponse(array(
                    'estimatedRate'                         => $estimatedRate,
                    'estimatedMonthlyRepayment'             => $estimatedMonthlyRepayment,
                    'translationComplement'                 => $sTranslationComplement,
                    'motiveSentenceComplementToBeDisplayed' => $bmotiveSentenceComplementToBeDisplayed,
                    'period'                                => $period,
                    'amount'                                => $amount
                ));
            }

            return new JsonResponse('nok');
        }
        return new Response('not an ajax request');
    }

    /**
     * @Route("/esim-step-2", name="esim_step_2")
     * @Method("POST")
     */
    public function projectSimulatorStepTwoAction(Request $request)
    {
        $aFormData = $request->request->get('esim');

        /** @var ProjectRequestManager $projectRequestManager */
        $projectRequestManager = $this->get('unilend.service.project_request_manager');

        $session = $request->getSession();
        $session->set('esim/project_id', $projectRequestManager->saveSimulatorRequest($aFormData));

        return $this->redirectToRoute('project_request_step_1');
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     */
    public function cmsAction(Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \tree $tree */
        $tree = $entityManager->getRepository('tree');

        if (false === $tree->get(['slug' => substr($request->attributes->get('routeDocument')->getPath(), 1)])) {
            throw new NotFoundHttpException('Page with slug ' . $request->attributes->get('routeDocument')->getPath() . ' could not be found');
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

        switch ($tree->id_template) {
            case self::CMS_TEMPLATE_BIG_HEADER:
                return $this->renderCmsBigHeader($tree, $finalElements['content']);
            case self::CMS_TEMPLATE_NAV:
                return $this->renderCmsNav($tree, $finalElements['content'], $entityManager);
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
            'title' => $tree->meta_title
        ];

        return $this->render('pages/template-big-header.html.twig', ['cms' => $cms, 'page' => $page]);
    }

    /**
     * @param \tree         $currentPage
     * @param array         $content
     * @param EntityManager $entityManager
     * @return Response
     */
    private function renderCmsNav(\tree $currentPage, array $content, EntityManager $entityManager)
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
            'title' => $currentPage->meta_title,
            'next'  => $nextPage
        ];

        return $this->render('pages/template-nav.html.twig', ['navigation' => $navigation, 'cms' => $cms, 'page' => $page]);
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     */
    public function footerAction(Request $request)
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
                $partners[] = [
                    'alt' => $element['complement'],
                    'src' => $element['value']
                ];
            }
        }

        $response = $this->render('partials/site/footer.html.twig', ['menus' => $footerMenu, 'partners' => $partners]);
        $response->setSharedMaxAge(900);

        return $response;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function footerReviewsAction(Request $request)
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
     * @return JsonResponse
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

}
