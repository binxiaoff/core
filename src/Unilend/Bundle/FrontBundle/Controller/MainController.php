<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Doctrine\ORM\{EntityManager, OptimisticLockException};
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sonata\SeoBundle\Seo\SeoPage;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{JsonResponse, RedirectResponse, Request, Response};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{AddressType, Clients, OffresBienvenues, ProjectsStatus, Tree, Users, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Service\{NewsletterManager, ProjectManager, ProjectRequestManager, StatisticsManager, WelcomeOfferManager};
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\FrontBundle\Service\{ContentManager, ProjectDisplayManager, SourceManager, TestimonialManager};
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
            'sortType'           => strtolower(\projects::SORT_FIELD_END),
            'sortDirection'      => strtolower(\projects::SORT_DIRECTION_DESC)
        ];

        $template['projects'] = $projectDisplayManager->getProjectsList([], [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_DESC], null, 3, $client);

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
            [\projects_status::EN_FUNDING],
            [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_DESC]
        );

        $template['featureBorrower'] = $testimonialService->getFeaturedTestimonialBorrower();

        return $this->render('main/home_borrower.html.twig', $template);
    }

    /**
     * @Route("/simulateur-projet-etape1", name="project_simulator", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function projectSimulatorStepOneAction(Request $request): Response
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
     * @Route("/simulateur-projet", name="project_simulator_form", methods={"POST"})
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function projectSimulatorStepTwoAction(Request $request): RedirectResponse
    {
        $formData = $request->request->get('esim');
        $session  = $request->getSession();
        $user     = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_FRONT);

        $projectRequestManager = $this->get('unilend.service.project_request_manager');

        try {
            if (empty($formData['email'])) {
                throw new \InvalidArgumentException('Invalid email', ProjectRequestManager::EXCEPTION_CODE_INVALID_EMAIL);
            }

            if (empty($formData['amount'])) {
                throw new \InvalidArgumentException('Invalid amount = ' . $formData['amount'], ProjectRequestManager::EXCEPTION_CODE_INVALID_AMOUNT);
            }

            if (empty($formData['duration'])) {
                throw new \InvalidArgumentException('Invalid duration', ProjectRequestManager::EXCEPTION_CODE_INVALID_DURATION);
            }

            if (empty($formData['reason'])) {
                throw new \InvalidArgumentException('Invalid reason', ProjectRequestManager::EXCEPTION_CODE_INVALID_REASON);
            }

            if (false === empty($formData['siren'])) {
                $siren = $projectRequestManager->validateSiren($formData['siren']);
                $siren = $siren === false ? null : $siren;
                // We accept in the same field both siren and siret
                $siret = $projectRequestManager->validateSiret($formData['siren']);
                $siret = $siret === false ? null : $siret;
            } else {
                $siren = null;
                $siret = null;
            }

            if (empty($formData['company_name'])) {
                $companyName = null;
            } else {
                $companyName = $formData['company_name'];
            }

            $partner = $this->get('unilend.service.partner_manager')->getDefaultPartner();

            $project = $projectRequestManager->newProject($user, $partner, ProjectsStatus::INCOMPLETE_REQUEST, $formData['amount'], $siren, $siret, $companyName, $formData['email'], $formData['duration'], $formData['reason']);

            return $this->redirectToRoute('project_request_simulator_start', ['hash' => $project->getHash()]);
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
     * @param string                     $type
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function lenderTermsOfSalesAction(?UserInterface $client, string $type = ''): Response
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        $termsOfSaleManager     = $this->get('unilend.service.terms_of_sale_manager');

        if ($client instanceof Clients && $client->isNaturalPerson()) {
            $idTree = $termsOfSaleManager->getCurrentVersionForPerson();
        } else {
            $idTree = $termsOfSaleManager->getCurrentVersionForLegalEntity();
        }

        /** @var \tree $tree */
        $tree = $entityManagerSimulator->getRepository('tree');
        $tree->get(['id_tree' => $idTree]);
        $this->setCmsSeoData($tree);

        return $this->renderTermsOfUse($client, $tree, $type);
    }

    /**
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function cmsAction(Request $request, ?UserInterface $client): Response
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
        $this->setCmsSeoData($tree);

        switch ($tree->id_template) {
            case self::CMS_TEMPLATE_BIG_HEADER:
                return $this->renderCmsBigHeader($finalElements['content']);
            case self::CMS_TEMPLATE_NAV:
                return $this->renderCmsNav($tree, $finalElements['content'], $entityManager);
            case self::CMS_TEMPLATE_BORROWER_LANDING_PAGE:
                return $this->renderBorrowerLandingPage($request, $finalElements['content'], $finalElements['complement']);
            case self::CMS_TEMPLATE_TOS:
                return $this->renderTermsOfUse($client, $tree);
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
     * @param Clients|null $client
     * @param \tree        $tree
     * @param string       $lenderType
     *
     * @return Response
     */
    private function renderTermsOfUse(?Clients $client, \tree $tree, string $lenderType = ''): Response
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

        $content = [];
        foreach ($treeElements->select('id_tree = "' . $tree->id_tree . '" AND id_langue = "fr"') as $elt) {
            $elements->get($elt['id_element']);
            $content[$elements->slug]                = $elt['value'];
            $template['complement'][$elements->slug] = $elt['complement'];
        }

        $template = [
            'main_content' => $content['contenu-cgu']
        ];

        if (
            $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
            && $this->get('security.authorization_checker')->isGranted('ROLE_LENDER')
        ) {
            $dateAccept   = '';
            $userAccepted = $acceptedTermsOfUse->select('id_client = ' . $client->getIdClient() . ' AND id_legal_doc = ' . $tree->id_tree, 'added DESC', 0, 1);

            if (false === empty($userAccepted)) {
                $dateAccept = 'Sign&eacute; &eacute;lectroniquement le ' . date('d/m/Y', strtotime($userAccepted[0]['added']));
            }
            /** @var \settings $settings */
            $settings = $entityManagerSimulator->getRepository('settings');
            $settings->get('Date nouvelles CGV avec 2 mandats', 'type');
            $newTermsOfServiceDate = $settings->value;

            $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->getIdClient(), WalletType::LENDER);

            /** @var \loans $oLoans */
            $loans      = $entityManagerSimulator->getRepository('loans');
            $loansCount = $loans->counter('id_lender = ' . $wallet->getId() . ' AND added < "' . $newTermsOfServiceDate . '"');

            if ($client->isNaturalPerson()) {
                $mandateContent = $loansCount > 0 ? $content['mandat-de-recouvrement-avec-pret'] : $content['mandat-de-recouvrement'];
                $this->getTOSReplacementsForPerson($client, $dateAccept, $mandateContent, $template);
            } else {
                $mandateContent = $loansCount > 0 ? $content['mandat-de-recouvrement-avec-pret-personne-morale'] : $content['mandat-de-recouvrement-personne-morale'];
                $this->getTOSReplacementsForLegalEntity($client, $dateAccept, $mandateContent, $template);
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

        return $this->render('cms_templates/template_cgv.html.twig', ['cms' => $cms]);
    }

    /**
     * @param Clients $client
     * @param string  $dateAccept
     * @param string  $content
     * @param array   $template
     **/
    private function getTOSReplacementsForPerson(Clients $client, string $dateAccept, string $content, array &$template): void
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $clientAddress = $client->getIdAddress();

        if (null === $clientAddress) {
            try {
                $clientAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
            } catch (\Exception $exception) {
                $this->get('logger')->error('An exception occurred while getting main client address for ' . $client->getIdClient() . '. Terms of Use could not be generated. Message: ' . $exception->getMessage(), [
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'id_client' => $client->getIdClient()
                ]);
                exit;
            }
        }

        $keyWords = [
            '[Civilite]'            => $client->getCivilite(),
            '[Prenom]'              => $client->getPrenom(),
            '[Nom]'                 => $client->getNom(),
            '[date]'                => $client->getNaissance()->format('d/m/Y'),
            '[ville_naissance]'     => $client->getVilleNaissance(),
            '[adresse_fiscale]'     => null === $clientAddress ? '' : $clientAddress->getAddress() . ', ' . $clientAddress->getZip() . ', ' . $clientAddress->getCity() . ', ' . $clientAddress->getIdCountry()->getFr(),
            '[date_validation_cgv]' => $dateAccept
        ];

        $template['recovery_mandate'] = str_replace(array_keys($keyWords), array_values($keyWords), $content);
    }

    /**
     * @param Clients $client
     * @param string  $dateAccept
     * @param string  $content
     * @param array   $template
     */
    private function getTOSReplacementsForLegalEntity(Clients $client, string $dateAccept, string $content, array &$template): void
    {
        $entityManager  = $this->get('doctrine.orm.entity_manager');
        $company        = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
        $companyAddress = $company->getIdAddress();

        if (null === $companyAddress) {
            try {
                $companyAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
            } catch (\Exception $exception) {
                $this->get('logger')->error('An exception occurred while getting main company address for ' . $company->getIdCompany() . '. Terms of Use could not be generated. Message: ' . $exception->getMessage(), [
                    'file'       => $exception->getFile(),
                    'line'       => $exception->getLine(),
                    'class'      => __CLASS__,
                    'method'     => __FUNCTION__,
                    'id_company' => $company->getIdCompany()
                ]);
                exit;
            }
        }

        $keyWords = [
            '[Civilite]'            => $client->getCivilite(),
            '[Prenom]'              => $client->getPrenom(),
            '[Nom]'                 => $client->getNom(),
            '[Fonction]'            => $client->getFonction(),
            '[Raison_sociale]'      => $company->getName(),
            '[SIREN]'               => $company->getSiren(),
            '[adresse_fiscale]'     => null === $companyAddress ? '' : $companyAddress->getAddress() . ', ' . $companyAddress->getZip() . ', ' . $companyAddress->getCity() . ', ' . $companyAddress->getIdCountry()->getFr(),
            '[date_validation_cgv]' => $dateAccept
        ];

        $template['recovery_mandate'] = str_replace(array_keys($keyWords), array_values($keyWords), $content);
    }

    /**
     * @param string|null $route
     *
     * @return Response
     */
    public function footerAction(?string $route): Response
    {
        /** @var ContentManager $contentManager */
        $contentManager = $this->get('unilend.frontbundle.service.content_manager');

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
     * @return Response
     */
    public function aboutUsAction(): Response
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \tree $tree */
        $tree = $entityManagerSimulator->getRepository('tree');
        $tree->get(['slug' => 'qui-sommes-nous']);
        $this->setCmsSeoData($tree);
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
     * @param string|null $requestedDate
     *
     * @return Response
     */
    public function statisticsAction(?string $requestedDate = null): Response
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
                'regulatoryTable'                => $statistics['regulatoryData']
            ],
            'years' => array_merge($years, ['total']),
            'date'  => $date->format('Y-m-d')
        ];

        $this->setCmsSeoData($tree);
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
     * @return Response
     */
    public function statisticsFpfAction(Request $request): Response
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

        $statisticsManager     = $this->get('unilend.service.statistics_manager');
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
     * @Route("/cgv-popup", name="lender_tos_popup", condition="request.isXmlHttpRequest()")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return JsonResponse|Response
     */
    public function lastTermsOfServiceAction(Request $request, ?UserInterface $client): Response
    {
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        $tosDetails      = '';
        $newsletterOptIn = true;

        if (null !== $client) {
            $newsletterOptIn = empty($client->getOptin1());

            if ($request->isMethod(Request::METHOD_GET)) {
                $elementSlug = 'tos-new';
                /** @var \acceptations_legal_docs $acceptationsTos */
                $acceptationsTos = $entityManagerSimulator->getRepository('acceptations_legal_docs');

                if ($acceptationsTos->exist($client->getIdClient(), 'id_client')) {
                    $wallet                = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->getIdClient(), WalletType::LENDER);
                    $newTermsOfServiceDate = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Date nouvelles CGV avec 2 mandats'])->getValue();
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
                        $this->get('logger')->error('The block element ID: ' . $elements->id_element . ' doesn\'t exist');
                    }
                } else {
                    $this->get('logger')->error('The element slug: ' . $elementSlug . ' doesn\'t exist');
                }
            } elseif ($request->isMethod(Request::METHOD_POST)) {
                if ('true' === $request->request->get('terms')) {
                    try {
                        $this->get('unilend.service.terms_of_sale_manager')->acceptCurrentVersion($client);
                    } catch (OptimisticLockException $exception) {
                        $this->get('logger')->error('TOS could not be accepted by lender ' . $client->getIdClient() . ' - Message: ' . $exception->getMessage(), [
                            'id_client' => $client->getIdClient(),
                            'class'     => __CLASS__,
                            'function'  => __FUNCTION__,
                            'file'      => $exception->getFile(),
                            'line'      => $exception->getLine()
                        ]);
                    }
                }

                if ($newsletterOptIn) {
                    if ('true' === $request->request->get('newsletterOptIn')) {
                        $this->get(NewsletterManager::class)->subscribeNewsletter($client, $request->getClientIp());
                    } else {
                        $this->get(NewsletterManager::class)->unsubscribeNewsletter($client, $request->getClientIp());
                    }
                }

                return $this->json([]);
            }
        }

        return $this->render('partials/lender_tos_popup.html.twig', [
            'tosDetails'      => $tosDetails,
            'newsletterOptIn' => $newsletterOptIn
        ]);
    }

    /**
     * @Route("/temoignages", name="testimonials")
     *
     * @return Response
     */
    public function testimonialAction(): Response
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

    /**
     * @param \tree $tree
     */
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
