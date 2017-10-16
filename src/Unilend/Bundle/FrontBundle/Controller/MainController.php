<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sonata\SeoBundle\Seo\SeoPage;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenues;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectRequestManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\CoreBusinessBundle\Service\StatisticsManager;
use Unilend\Bundle\CoreBusinessBundle\Service\WelcomeOfferManager;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\Bundle\FrontBundle\Service\ContentManager;
use Unilend\Bundle\FrontBundle\Service\ProjectDisplayManager;
use Unilend\Bundle\FrontBundle\Service\SourceManager;
use Unilend\Bundle\FrontBundle\Service\TestimonialManager;
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
     * @return Response
     */
    public function homeAction()
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

        return $this->render('pages/homepage_acquisition.html.twig', $template);
    }

    /**
     * @Route("/preter", name="home_lender")
     * @return Response
     */
    public function homeLenderAction()
    {
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        $entityManager         = $this->get('doctrine.orm.entity_manager');
        $authorizationChecker  = $this->get('security.authorization_checker');
        $welcomeOfferManager   = $this->get('unilend.service.welcome_offer_manager');
        $testimonialService    = $this->get('unilend.frontbundle.service.testimonial_manager');
        $user                  = $this->getUser();
        $client                = null;

        $template = [
            'showWelcomeOffer'   => $welcomeOfferManager->displayOfferOnHome(),
            'amountWelcomeOffer' => $welcomeOfferManager->getWelcomeOfferAmount(OffresBienvenues::TYPE_HOME),
            'featureLender'      => $testimonialService->getFeaturedTestimonialLender(),
            'showPagination'     => false,
            'showSortable'       => false,
            'sortType'           => strtolower(\projects::SORT_FIELD_END),
            'sortDirection'      => strtolower(\projects::SORT_DIRECTION_DESC)
        ];

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')
            && $authorizationChecker->isGranted('ROLE_LENDER')
        ) {
            $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($user->getClientId());
        }

        $template['projects'] = $projectDisplayManager->getProjectsList([], [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_DESC], null, 3, $client);

        $translator        = $this->get('translator');
        $projectRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Projects');

        array_walk($template['projects'], function(&$project) use ($translator, $projectDisplayManager, $user, $projectRepository) {
            if (ProjectDisplayManager::VISIBILITY_FULL !== $projectDisplayManager->getVisibility($projectRepository->find($project['projectId']), $user)) {
                $project['title'] = $translator->trans('company-sector_sector-' . $project['company']['sectorId']);
            }
        });

        return $this->render('pages/homepage_lender.html.twig', $template);
    }

    /**
     * @Route("/emprunter", name="home_borrower")
     * @return Response
     */
    public function homeBorrowerAction()
    {
        $projectManager = $this->get('unilend.service.project_manager');
        $testimonialService = $this->get('unilend.frontbundle.service.testimonial_manager');
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        /** @var \borrowing_motive $borrowingMotive */
        $borrowingMotive = $this->get('unilend.service.entity_manager')->getRepository('borrowing_motive');

        $template = [];
        $template['testimonialPeople'] = $testimonialService->getBorrowerBattenbergTestimonials(true);
        $template['loanPeriods']       = $projectManager->getPossibleProjectPeriods();
        $template['projectAmountMax']  = $projectManager->getMaxProjectAmount();
        $template['projectAmountMin']  = $projectManager->getMinProjectAmount();
        $template['borrowingMotives']  = $borrowingMotive->select('rank');
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
     *
     * @param Request $request
     * @return Response
     */
    public function projectSimulatorStepOneAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $period   = $request->request->getInt('period');
            $motiveId = $request->request->getInt('motiveId');

            /** @var ProjectRequestManager $projectRequestManager */
            $projectRequestManager = $this->get('unilend.service.project_request_manager');
            /** @var ProjectManager $projectManager */
            $projectManager = $this->get('unilend.service.project_manager');
            /** @var TranslatorInterface $translator */
            $translator = $this->get('translator');
            /** @var \ficelle $ficelle */
            $ficelle = Loader::loadLib('ficelle');

            $projectPeriods = $projectManager->getPossibleProjectPeriods();
            $amount         = filter_var(str_replace([' ', '€'], '', $request->request->get('amount')), FILTER_VALIDATE_INT, ['options' => ['min_range' => $projectManager->getMinProjectAmount(), 'max_range' => $projectManager->getMaxProjectAmount()]]);

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
        return new Response('not an ajax request');
    }

    /**
     * @Route("/simulateur-projet", name="project_simulator_form")
     * @Method("POST")
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function projectSimulatorStepTwoAction(Request $request)
    {
        $formData = $request->request->get('esim');
        $session  = $request->getSession();

        try {
            /** @var ProjectRequestManager $projectRequestManager */
            $projectRequestManager = $this->get('unilend.service.project_request_manager');
            $project               = $projectRequestManager->saveSimulatorRequest($formData);

            $session->remove('esim');

            return $this->redirectToRoute('project_request_simulator_start', ['hash' => $project->hash]);
        } catch (\Exception $exception) {
            $this->get('logger')->warning('Could not save project : ' . $exception->getMessage() . '. Form data = ' . json_encode($formData), ['class' => __CLASS__, 'function' => __FUNCTION__]);

            if ($exception instanceof \InvalidArgumentException) {
                $this->addFlash('projectSimulatorError', $exception->getCode());
            }

            $session->set('esim', $formData);

            return $this->redirect($this->generateUrl('home_borrower') . '#homeemp-section-esim');
        }
    }

    /**
     * @Route("/cgv_preteurs/{type}", name="lenders_terms_of_sales", requirements={"type": "morale"})
     *
     * @param string $type
     * @return Response
     */
    public function lenderTermsOfSalesAction($type = '')
    {
        /** @var EntityManagerSimulator $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        $settings->get('Lien conditions generales inscription preteur particulier', 'type');

        $idTree = $settings->value;
        $user   = $this->getUser();

        if ($user instanceof UserLender) {
            /** @var \clients $client */
            $client = $entityManager->getRepository('clients');
            $client->get($user->getClientId());

            if (in_array($client->type, [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
                $settings->get('Lien conditions generales inscription preteur societe', 'type');
                $idTree = $settings->value;
            }
        }

        /** @var \tree $tree */
        $tree = $entityManager->getRepository('tree');
        $tree->get(['id_tree' => $idTree]);
        $this->setCmsSeoData($tree);

        return $this->renderTermsOfUse($tree, $type);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function cmsAction(Request $request)
    {
        /** @var EntityManagerSimulator $entityManager */
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
        $this->setCmsSeoData($tree);

        switch ($tree->id_template) {
            case self::CMS_TEMPLATE_BIG_HEADER:
                return $this->renderCmsBigHeader($finalElements['content']);
            case self::CMS_TEMPLATE_NAV:
                return $this->renderCmsNav($tree, $finalElements['content'], $entityManager);
            case self::CMS_TEMPLATE_BORROWER_LANDING_PAGE:
                return $this->renderBorrowerLandingPage($request, $finalElements['content'], $finalElements['complement']);
            case self::CMS_TEMPLATE_TOS:
                return $this->renderTermsOfUse($tree);
            default:
                return new RedirectResponse('/');
        }
    }

    /**
     * @param array $content
     * @return Response
     */
    private function renderCmsBigHeader(array $content)
    {
        $cms = [
            'title'         => $content['titre'],
            'header_image'  => $content['image-header'],
            'left_content'  => $content['bloc-gauche'],
            'right_content' => $content['bloc-droite']
        ];

        return $this->render('pages/template-big-header.html.twig', ['cms' => $cms]);
    }

    /**
     * @param \tree         $currentPage
     * @param array         $content
     * @param EntityManagerSimulator $entityManager
     * @param string|null   $pageId
     * @return Response
     */
    private function renderCmsNav(\tree $currentPage, array $content, EntityManagerSimulator $entityManager, $pageId = null)
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

        return $this->render('pages/template-nav.html.twig', ['navigation' => $navigation, 'cms' => $cms, 'page' => $page]);
    }

    /**
     * @param Request $request
     * @param array   $content
     * @param array   $complement
     *
     * @return Response
     */
    private function renderBorrowerLandingPage(Request $request, array $content, array $complement)
    {
        $borrowingReasons = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BorrowingMotive')->findBy([], ['rank' => 'ASC']);
        $sessionHandler   = $request->getSession();
        $isPartnerFunnel  = $content['tunnel-partenaire'] == 1;

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
                    'amount'  => empty($sessionHandler->get('projectRequest')['values']['amount']) ? (empty($request->query->getInt('montant')) ? '' : $request->query->get('montant')) : $sessionHandler->get('projectRequest')['values']['amount'],
                    'siren'   => empty($sessionHandler->get('projectRequest')['values']['siren']) ? (empty($request->query->getInt('siren')) ? '' : $request->query->get('siren')) : $sessionHandler->get('projectRequest')['values']['siren'],
                    'email'   => empty($sessionHandler->get('projectRequest')['values']['email']) ? (empty($request->query->get('email')) ? '' : filter_var($request->query->get('email'), FILTER_SANITIZE_EMAIL)) : $sessionHandler->get('projectRequest')['values']['email'],
                    'partner' => $content['partenaire'],
                    'reasons' => $borrowingReasons,
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

        return $this->render('pages/template_borrower_landing_page.html.twig', $template);
    }

    /**
     * @param \tree  $tree
     * @param string $lenderType
     * @return Response
     */
    private function renderTermsOfUse(\tree $tree, $lenderType = '')
    {
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \acceptations_legal_docs $acceptedTermsOfUse */
        $acceptedTermsOfUse = $entityManagerSimulator->getRepository('acceptations_legal_docs');

        /** @var \tree_elements $treeElements */
        $treeElements = $entityManagerSimulator->getRepository('tree_elements');
        /** @var \elements $elements */
        $elements = $entityManagerSimulator->getRepository('elements');

        $content  = [];
        foreach ($treeElements->select('id_tree = "' . $tree->id_tree . '" AND id_langue = "fr"') as $elt) {
            $elements->get($elt['id_element']);
            $content[$elements->slug] = $elt['value'];
            $template['complement'][$elements->slug] = $elt['complement'];
        }

        $template = [
            'main_content' => $content['contenu-cgu']
        ];

        /** @var UserLender $user */
        $user = $this->getUser();
        /** @var \clients $client */
        $client = $entityManagerSimulator->getRepository('clients');

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') && $client->get($user->getClientId(), 'id_client')) {
            $dateAccept    = '';
            $userAccepted = $acceptedTermsOfUse->select('id_client = ' . $client->id_client . ' AND id_legal_doc = ' . $tree->id_tree, 'added DESC', 0, 1);

            if (false === empty($userAccepted)) {
                $dateAccept = 'Sign&eacute; &eacute;lectroniquement le ' . date('d/m/Y', strtotime($userAccepted[0]['added']));
            }
            /** @var \settings $settings */
            $settings = $entityManagerSimulator->getRepository('settings');
            $settings->get('Date nouvelles CGV avec 2 mandats', 'type');
            $sNewTermsOfServiceDate = $settings->value;

            $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->id_client, WalletType::LENDER);

            /** @var \loans $oLoans */
            $loans      = $entityManagerSimulator->getRepository('loans');
            $loansCount = $loans->counter('id_lender = ' . $wallet->getId() . ' AND added < "' . $sNewTermsOfServiceDate . '"');

            if ($wallet->getIdClient()->isNaturalPerson()) {
                $this->getTOSReplacementsForPerson($client, $dateAccept, $loansCount, $content, $template);
            } else {
                $this->getTOSReplacementsForLegalEntity($client, $dateAccept, $loansCount, $content, $template);
            }
        } elseif ($lenderType !== '') {
            $template['recovery_mandate'] = str_replace(
                [
                    '[Civilite]',
                    '[Prenom]',
                    '[Nom]',
                    '[Fonction]',
                    '[Raison_sociale]',
                    '[SIREN]',
                    '[adresse_fiscale]',
                    '[date_validation_cgv]'
                ],
                explode(';', $content['contenu-variables-par-defaut-morale']),
                $content['mandat-de-recouvrement-personne-morale']
            );
        } else {
            $template['recovery_mandate'] = str_replace(
                [
                    '[Civilite]',
                    '[Prenom]',
                    '[Nom]',
                    '[date]',
                    '[ville_naissance]',
                    '[adresse_fiscale]',
                    '[date_validation_cgv]'
                ],
                explode(';', $content['contenu-variables-par-defaut']),
                $content['mandat-de-recouvrement']
            );
        }

        $cms = [
            'title'         => $tree->title,
            'header_image'  => $tree->img_menu,
            'left_content'  => '',
            'right_content' => $template
        ];

        return $this->render('pages/static_pages/template-cgv.html.twig', ['cms' => $cms]);
    }

    private function getTOSReplacementsForPerson(\clients $client, $dateAccept, $loansCount, $content, &$template)
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');

        /** @var \clients_adresses $clientAddresses */
        $clientAddresses = $entityManagerSimulator->getRepository('clients_adresses');
        $clientAddresses->get($client->id_client, 'id_client');

        if ($clientAddresses->id_pays_fiscal == 0) {
            $clientAddresses->id_pays_fiscal = 1;
        }

        /** @var \pays_v2 $country */
        $country = $entityManagerSimulator->getRepository('pays_v2');
        $country->get($clientAddresses->id_pays_fiscal, 'id_pays');

        $aReplacements = [
            '[Civilite]'            => $client->civilite,
            '[Prenom]'              => $client->prenom,
            '[Nom]'                 => $client->nom,
            '[date]'                => date('d/m/Y', strtotime($client->naissance)),
            '[ville_naissance]'     => $client->ville_naissance,
            '[adresse_fiscale]'     => $clientAddresses->adresse_fiscal . ', ' . $clientAddresses->ville_fiscal . ', ' . $clientAddresses->cp_fiscal . ', ' . $country->fr,
            '[date_validation_cgv]' => $dateAccept
        ];

        $template['recovery_mandate'] = $loansCount > 0 ? $content['mandat-de-recouvrement-avec-pret'] : $content['mandat-de-recouvrement'];
        $template['recovery_mandate'] = str_replace(array_keys($aReplacements), array_values($aReplacements), $template['recovery_mandate']);
    }

    private function getTOSReplacementsForLegalEntity(\clients $client, $dateAccept, $loansCount, $content, &$template)
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');

        /** @var \clients_adresses $clientAddresses */
        $clientAddresses = $entityManagerSimulator->getRepository('clients_adresses');
        $clientAddresses->get($client->id_client, 'id_client');

        if ($clientAddresses->id_pays_fiscal == 0) {
            $clientAddresses->id_pays_fiscal = 1;
        }

        /** @var \companies $companies */
        $company = $entityManagerSimulator->getRepository('companies');
        $company->get($client->id_client, 'id_client_owner');

        if ($company->id_pays == 0) {
            $company->id_pays = 1;
        }

        /** @var \pays_v2 $country */
        $country = $entityManagerSimulator->getRepository('pays_v2');
        $country->get($company->id_pays, 'id_pays');

        $aReplacements = [
            '[Civilite]'            => $client->civilite,
            '[Prenom]'              => $client->prenom,
            '[Nom]'                 => $client->nom,
            '[Fonction]'            => $client->fonction,
            '[Raison_sociale]'      => $company->name,
            '[SIREN]'               => $company->siren,
            '[adresse_fiscale]'     => $company->adresse1 . ', ' . $company->zip . ', ' . $company->city . ', ' . $country->fr,
            '[date_validation_cgv]' => $dateAccept
        ];

        $template['recovery_mandate'] = $loansCount > 0 ? $content['mandat-de-recouvrement-avec-pret-personne-morale'] : $content['mandat-de-recouvrement-personne-morale'];
        $template['recovery_mandate'] = str_replace(array_keys($aReplacements), array_values($aReplacements), $template['recovery_mandate']);
    }

    /**
     * @param string $route
     * @return Response
     */
    public function footerAction($route)
    {
        /** @var ContentManager $contentManager */
        $contentManager = $this->get('unilend.frontbundle.service.content_manager');

        return $this->render('partials/site/footer.html.twig', [
            'menus'             => $contentManager->getFooterMenu(),
            'displayDisclaimer' => $route !== 'project_detail'
        ]);
    }

    /**
     * @return Response
     */
    public function footerReviewsAction()
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

        return $this->render('partials/site/reviews.html.twig', ['reviews' => $reviews]);
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
        if ($request->isXmlHttpRequest()) {
            /** @var \accept_cookies $acceptCookies */
            $acceptCookies = $this->get('unilend.service.entity_manager')->getRepository('accept_cookies');

            $acceptCookies->ip        = $request->getClientIp();
            $acceptCookies->id_client = $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ? $this->getUser()->getClientId() : 0;
            $acceptCookies->create();

            $response = new JsonResponse(true);
            // Remove the httpOnly version. This line can be remove after 31/01/2018, when the last httpOnly cookie will be expired.
            $response->headers->removeCookie('acceptCookies');
            $response->headers->setCookie(new Cookie("acceptCookies", $acceptCookies->id_accept_cookies, time() + (365 * 24 * 3600), '/', null, false, false));

            return $response;
        }

        return new Response('not an ajax request');
    }

    /**
     * @Route("/qui-sommes-nous", name="about_us")
     *
     * @return Response
     */
    public function aboutUsAction()
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \tree $tree */
        $tree = $entityManagerSimulator->getRepository('tree');
        $tree->get(['slug' => 'qui-sommes-nous']);
        $this->setCmsSeoData($tree);
        $response = $this->render('pages/static_pages/about_us.html.twig');

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
     * @param  string $requestedDate
     * @return Response
     */
    public function statisticsAction($requestedDate = null)
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \tree $tree */
        $tree = $entityManagerSimulator->getRepository('tree');
        $tree->get(['slug' => 'statistiques']);
        /** @var StatisticsManager $statisticsManager */
        $statisticsManager = $this->get('unilend.service.statistics_manager');

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
                'regulatoryTable'                => $statistics['regulatoryData'],
            ],
            'years' => array_merge($years, ['total']),
            'date'  => $date->format('Y-m-d')
        ];

        $this->setCmsSeoData($tree);
        $response = $this->render('pages/static_pages/statistics.html.twig', $template);

        $finalElements = [
            'contenu'      => $response->getContent(),
            'complement'   => '',
            'image-header' => '1682x400_0005_Statistiques.jpg'
        ];

        return $this->renderCmsNav($tree, $finalElements, $entityManagerSimulator, 'apropos-statistiques');
    }

    /**
     * @Route("/indicateurs-de-performance", name="statistics_fpf")
     * @return Response
     */
    public function statisticsFpfAction(Request $request)
    {
        if ($request->getClientIp() != '92.154.10.41') {
            return $this->render('/pages/static_pages/error.html.twig');
        }

        $requestedDate = $request->request->filter('date', FILTER_SANITIZE_STRING);

        if (empty($requestedDate)) {
            $date  = new \DateTime('NOW');
        } else {
            $date             = \DateTime::createFromFormat('d/m/Y', $requestedDate);
            $firstHistoryDate = new \DateTime(StatisticsManager::START_FPF_STATISTIC_HISTORY);
            if ($date < $firstHistoryDate) {
                $date  = new \DateTime('NOW');
            }
        }
        $years             = range(2013, $date->format('Y'));
        $statisticsManager = $this->get('unilend.service.statistics_manager');
        $data              = $statisticsManager->getPerformanceIndicatorAtDate($date);

        $template = [
            'data'           => $data,
            'years'          => $years,
            'date'           => $date->format('Y-m-d'),
            'availableDates' => $statisticsManager->getAvailableDatesForFPFStatistics()
        ];
        $response = $this->render('pages/static_pages/statistics-fpf.html.twig', $template);

        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \tree $tree */
        $tree = $entityManagerSimulator->getRepository('tree');
        $tree->get(['slug' => 'indicateurs-de-performance']);
        $this->setCmsSeoData($tree);

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
    public function lenderFaqAction()
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
    public function borrowerFaqAction()
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
    public function siteMapAction()
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

        return $this->render('pages/static_pages/sitemap.html.twig', $template);
    }

    /**
     * @Route("/cgv-popup", name="lender_tos_popup", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @Security("has_role('ROLE_LENDER')")
     * @return Mixed
     */
    public function lastTermsOfServiceAction(Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var UserLender $user */
        $user = $this->getUser();
        /** @var \clients $client */
        $client = $entityManagerSimulator->getRepository('clients');
        $tosDetails = '';

        if ($client->get($user->getClientId())) {
            if ($request->isMethod('GET')) {
                /** @var \blocs $block */
                $block = $entityManagerSimulator->getRepository('blocs');
                $block->get('cgv', 'slug');

                $elementSlug = 'tos-new';
                /** @var \acceptations_legal_docs $acceptationsTos */
                $acceptationsTos = $entityManagerSimulator->getRepository('acceptations_legal_docs');
                /** @var \settings $settings */
                $settings = $entityManagerSimulator->getRepository('settings');

                if ($acceptationsTos->exist($client->id_client, 'id_client')) {
                    $settings->get('Date nouvelles CGV avec 2 mandats', 'type');
                    $wallet                = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->id_client, WalletType::LENDER);
                    $newTermsOfServiceDate = $settings->value;
                    /** @var \loans $loans */
                    $loans = $entityManagerSimulator->getRepository('loans');

                    if (0 < $loans->counter('id_lender = ' . $wallet->getId() . ' AND added < "' . $newTermsOfServiceDate . '"')) {
                        $elementSlug = 'tos-update-lended';
                    } else {
                        $elementSlug = 'tos-update';
                    }
                }

                /** @var \elements $elements */
                $elements = $entityManagerSimulator->getRepository('elements');

                if ($elements->get($elementSlug, 'slug')) {
                    /** @var \blocs_elements $blockElement */
                    $blockElement = $entityManagerSimulator->getRepository('blocs_elements');

                    if ($blockElement->get($elements->id_element, 'id_element')) {
                        $tosDetails = $blockElement->value;
                    } else {
                        $this->get('logger')->error('The block element id : ' . $elements->id_element . ' doesn\'t exist');
                    }
                } else {
                    $this->get('logger')->error('The element slug : ' . $elementSlug . ' doesn\'t exist');
                }
            } elseif ($request->isMethod('POST')) {
                if ('true' === $request->request->get('terms')) {
                    $clientManager = $this->get('unilend.service.client_manager');
                    $clientManager->acceptLastTos($client);
                }
                return $this->json([]);
            }
        }

        return $this->render('partials/site/lender_tos_popup.html.twig', ['tosDetails' => $tosDetails]);
    }

    /**
     * @Route("/temoignages", name="testimonials")
     *
     * @return Response
     */
    public function testimonialAction()
    {
        /** @var TestimonialManager $testimonialService */
        $testimonialService = $this->get('unilend.frontbundle.service.testimonial_manager');
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \tree $tree */
        $tree = $entityManagerSimulator->getRepository('tree');
        $tree->get(['slug' => 'temoignages']);
        $this->setCmsSeoData($tree);

        $template['testimonialPeople'] = $testimonialService->getBorrowerBattenbergTestimonials(false);
        $response                      = $this->render('pages/static_pages/testimonials.html.twig', $template);
        $finalElements                 = [
            'contenu'      => $response->getContent(),
            'complement'   => '',
            'image-header' => ''
        ];

        return $this->renderCmsNav($tree, $finalElements, $entityManagerSimulator, 'apropos-statistiques');
    }

    private function getProjectCountForCategoryTreeMap($countByCategory)
    {
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

    private function setCmsSeoData(\tree $tree)
    {
        /** @var SeoPage $seoPage */
        $seoPage = $this->get('sonata.seo.page');

        if (false === empty($tree->meta_title)) {
            $seoPage->setTitle($tree->meta_title);
        }

        if (false === empty($tree->meta_description)) {
            $seoPage->addMeta('name', 'description', $tree->meta_description);
        }

        if (false === empty($tree->meta_keywords)) {
            $seoPage->addMeta('name', 'keywords', $tree->meta_keywords);
        }

        if (empty($tree->indexation)) {
            $seoPage->addMeta('name', 'robots', 'noindex');
        }
    }
}
