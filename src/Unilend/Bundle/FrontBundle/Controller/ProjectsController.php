<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Knp\Snappy\GeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\{Security, Template};
use Sonata\SeoBundle\Seo\SeoPage;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{JsonResponse, RedirectResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{AttachmentType, Bids, Clients, ClientsHistoryActions, Loans, Projects, ProjectsStatus, UnderlyingContract, UnderlyingContractAttributeType, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Exception\BidException;
use Unilend\Bundle\CoreBusinessBundle\Repository\ProjectsRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\CIPManager;
use Unilend\Bundle\FrontBundle\Security\LoginAuthenticator;
use Unilend\Bundle\FrontBundle\Service\{LenderAccountDisplayManager, ProjectDisplayManager};
use Unilend\core\Loader;

class ProjectsController extends Controller
{
    /**
     * @Route("/projets-a-financer/{page}/{sortType}/{sortDirection}", name="projects_list",
     *     defaults={"page": "1", "sortType": "end", "sortDirection": "desc"},
     *     requirements={"page": "\d+"}, methods={"GET"})
     * @Template("projects/list.html.twig")
     *
     * @param int                        $page
     * @param string                     $sortType
     * @param string                     $sortDirection
     * @param UserInterface|Clients|null $client
     *
     * @return array
     */
    public function projectsListAction(int $page, string $sortType, string $sortDirection, ?UserInterface $client): array
    {
        return $this->getProjectsList($page, $sortType, $sortDirection, $client);
    }

    /**
     * @Route("/projets-a-financer/{page}/{sortType}/{sortDirection}", name="projects_list_json",
     *     defaults={"page": "1", "sortType": "end", "sortDirection": "desc"},
     *     requirements={"page": "\d+"}, methods={"POST"})
     * @Template("projects/list/map_item_template.html.twig")
     *
     * @param                            $page
     * @param                            $sortType
     * @param                            $sortDirection
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function projectsListMapListAction(int $page, string $sortType, string $sortDirection, ?UserInterface $client): array
    {
        return $this->getProjectsList($page, $sortType, $sortDirection, $client);
    }

    /**
     * @Route("/projets-list-all", name="projects_list_all", condition="request.isXmlHttpRequest()", methods={"POST"})
     *
     * @return Response
     */
    public function projectsListAllAction(): Response
    {
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        $translator            = $this->get('translator');
        $router                = $this->get('router');
        $projects              = $projectDisplayManager->getProjectsList();
        $projectsMapview       = [];

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        foreach ($projects as $project) {
            $status      = $project['finished'] ? 'expired' : 'active';
            $offerStatus = '';
            if (array_key_exists('lender', $project) && $project['lender']['bids']['count'] > 0) {
                if (count($project['lender']['bids']['inprogress']) > 0) {
                    $offerStatus = 'inprogress';
                } elseif (count($project['lender']['bids']['inprogress']) > 0) {
                    $offerStatus = 'accepted';
                }
            }

            $projectsMapview[] = [
                'id'          => 'marker' . $project['projectId'],
                'categoryId'  => $project['company']['sectorId'],
                'latLng'      => [$project['company']['latitude'], $project['company']['longitude']],
                'title'       => $translator->trans('company-sector_sector-' . $project['company']['sectorId']),
                'url'         => $router->generate('project_detail', ['projectSlug' => $project['slug']]),
                'city'        => $project['company']['city'],
                'zip'         => $project['company']['zip'],
                'rating'      => str_replace('.', '-', constant(Projects::class . '::RISK_' . $project['risk'])),
                'amount'      => $ficelle->formatNumber($project['amount'], 0) . '&nbsp;€',
                'interest'    => $ficelle->formatNumber($project['averageRate'], 1) . '&nbsp;%',
                'status'      => $status,
                'offers'      => $translator->transchoice('project-list_project-map-tooltip-offers-count', $project['bidsCount'], ['%count%' => $ficelle->formatNumber($project['bidsCount'], 0)]),
                'offerStatus' => $offerStatus,
                'groupName'   => $status
            ];
        }

        return new JsonResponse($projectsMapview);
    }

    /**
     * @Route("/projects/{page}/{sortType}/{sortDirection}", defaults={"page": "1", "sortType": "end", "sortDirection": "desc"}, requirements={"page": "\d+"}, name="lender_projects")
     * @Template("lender_account/projects.html.twig")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param int                        $page
     * @param string                     $sortType
     * @param string                     $sortDirection
     * @param UserInterface|Clients|null $client
     *
     * @return array
     */
    public function lenderProjectsAction(int $page, string $sortType, string $sortDirection, ?UserInterface $client): array
    {
        return $this->getProjectsList($page, $sortType, $sortDirection, $client);
    }

    /**
     * @param int    $page
     * @param string $sortType
     * @param string $sortDirection
     * @param Clients|null $client
     *
     * @return array
     */
    private function getProjectsList(int $page, string $sortType, string $sortDirection, ?Clients $client): array
    {
        $entityManager         = $this->get('doctrine.orm.entity_manager');
        $translator            = $this->get('translator');
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        $projectRepository     = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

        $template      = [];
        $pagination    = $this->getPaginationStartAndLimit($page);
        $limit         = $pagination['limit'];
        $start         = $pagination['start'];
        $sort          = [];
        $sortDirection = strtoupper($sortDirection);

        if (
            in_array($sortType, [ProjectsRepository::SORT_FIELD_SECTOR, ProjectsRepository::SORT_FIELD_AMOUNT, ProjectsRepository::SORT_FIELD_RATE, ProjectsRepository::SORT_FIELD_RISK, ProjectsRepository::SORT_FIELD_END])
            && in_array($sortDirection, ['ASC', 'DESC'])
        ) {
            $sort = [$sortType => $sortDirection];
        }

        $template['projects'] = $projectDisplayManager->getProjectsList([], $sort, $start, $limit, $client);

        array_walk($template['projects'], function(&$project) use ($translator, $projectDisplayManager, $client, $projectRepository) {
            if (ProjectDisplayManager::VISIBILITY_FULL !== $projectDisplayManager->getVisibility($projectRepository->find($project['projectId']), $client)) {
                $project['title'] = $translator->trans('company-sector_sector-' . $project['company']['sectorId']);
            }
        });

        $productRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Product');
        $products          = $productRepository->findAvailableProductsByClient($client);
        $projects          = $projectRepository->findBy(['status' => ProjectsStatus::EN_FUNDING, 'display' => Projects::DISPLAY_YES, 'idProduct' => $products]);

        $template['projectsInFunding'] = count($projects);
        $template['pagination']        = $this->pagination($page, $limit, $client);
        $template['showPagination']    = true;
        $template['showSortable']      = true;
        $template['currentPage']       = $page;
        $template['sortType']          = strtolower($sortType);
        $template['sortDirection']     = strtolower($sortDirection);

        return $template;
    }

    /**
     * @param int          $page
     * @param int          $limit
     * @param Clients|null $client
     *
     * @return array
     */
    private function pagination($page, $limit, ?Clients $client)
    {
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        $totalNumberProjects   = $projectDisplayManager->getTotalNumberOfDisplayedProjects($client);
        $totalPages            = $limit ? ceil($totalNumberProjects / $limit) : 1;

        $paginationSettings = [
            'itemsPerPage'      => $limit,
            'totalItems'        => $totalNumberProjects,
            'totalPages'        => $totalPages,
            'currentIndex'      => $page,
            'currentIndexItems' => min($page * $limit, $totalNumberProjects),
            'remainingItems'    => $limit ? ceil($totalNumberProjects - ($totalNumberProjects / $limit)) : 0,
            'pageUrl'           => 'projects'
        ];

        if ($totalPages > 1) {
            $paginationSettings['indexPlan'] = [$page - 1, $page, $page + 1];

            if ($page > $totalPages - 3) {
                $paginationSettings['indexPlan'] = [$totalPages - 3, $totalPages - 2, $totalPages - 1];
            } elseif ($page == $totalPages - 3) {
                $paginationSettings['indexPlan'] = [$totalPages - 4, $totalPages - 3, $totalPages - 2, $totalPages - 1];
            } elseif (4 == $page) {
                $paginationSettings['indexPlan'] = [2, 3, 4, 5];
            } elseif ($page < 4) {
                $paginationSettings['indexPlan'] = [2, 3, 4];
            }
        }

        return $paginationSettings;
    }

    /**
     * @param int $page
     *
     * @return array
     */
    private function getPaginationStartAndLimit($page)
    {
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('nombre-de-projets-par-page', 'type');
        $limit = (int) $settings->value;
        $start = ($page > 1) ? $limit * ($page - 1) : 0;

        return ['start' => $start, 'limit' => $limit];
    }

    /**
     * @Route("/projects/detail/{projectSlug}", name="project_detail", requirements={"projectSlug": "[a-z0-9-]+"})
     *
     * @param string                     $projectSlug
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function detailAction(string $projectSlug, Request $request, ?UserInterface $client): Response
    {
        $project = $this->checkProjectAndRedirect($projectSlug, $client);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        $authorizationChecker  = $this->get('security.authorization_checker');

        $template = [
            'project'             => $projectDisplayManager->getProjectData($project, $client),
            'bidToken'            => sha1('tokenBid-' . time() . '-' . uniqid()),
            'suggestAutolend'     => false,
            'recaptchaKey'        => $this->getParameter('google.recaptcha_key'),
            'displayLoginCaptcha' => $request->getSession()->get(LoginAuthenticator::SESSION_NAME_LOGIN_CAPTCHA, false)
        ];

        if (isset($template['project']['bids'])) {
            $template['project']['bids']['graph'] = [
                'summary' => array_reverse($template['project']['bids']['summary'], true)
            ];

            $index = 0;
            $template['project']['bids']['graph']['maxNotNullIndex'] = 0;
            foreach ($template['project']['bids']['graph']['summary'] as $rateSummary) {
                if ($rateSummary['activeBidsCount'] > 0) {
                    $template['project']['bids']['graph']['maxNotNullIndex'] = $index;
                }
                ++$index;
            }
        }

        $displayCipDisclaimer = false;

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')
            && $authorizationChecker->isGranted('ROLE_LENDER')
        ) {
            $request->getSession()->set('bidToken', $template['bidToken']);

            $productManager = $this->get('unilend.service_product.product_manager');
            /** @var \product $product */
            $product = $this->get('unilend.service.entity_manager')->getRepository('product');
            $product->get($project->id_product);

            /** @var \settings $settings */
            $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
            $settings->get('Pret min', 'type');
            $template['amountMin'] = (int) trim($settings->value);

            /** @var LenderAccountDisplayManager $lenderAccountDisplayManager */
            $lenderAccountDisplayManager = $this->get('unilend.frontbundle.service.lender_account_display_manager');
            $template['project']['lender'] = [
                'bids' => $lenderAccountDisplayManager->getBidsForProject($project->id_project, $client)
            ];

            if ($project->status >= ProjectsStatus::FUNDE) {
                $template['project']['lender']['loans'] = $lenderAccountDisplayManager->getLoansForProject($project->id_project, $client);
            }

            if (false === empty($request->getSession()->get('bidResult'))) {
                $template['lender']['bidResult'] = $request->getSession()->get('bidResult');
                $request->getSession()->remove('bidResult');
            }

            $reasons                              = $productManager->checkLenderEligibility($client, $project);
            $template['isLenderEligible']         = true;
            $template['lenderNotEligibleReasons'] = [];

            if (false === empty($reasons)) {
                $template['isLenderEligible']         = false;
                $template['lenderNotEligibleReasons'] = $reasons;
                $template['amountMax']                = $productManager->getMaxEligibleAmount($client, $product);
            }

            $cipManager           = $this->get('unilend.service.cip_manager');
            $productContracts     = $productManager->getAvailableContracts($product);
            $displayCipDisclaimer = in_array(UnderlyingContract::CONTRACT_MINIBON, array_column($productContracts, 'label')) && $cipManager->hasValidEvaluation($client);

            if (
                in_array($client->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])
                && empty($template['project']['lender']['bids']['count'])
                && ProjectsStatus::BID_TERMINATED <= $project->status
            ) {
                $template['suggestAutolend'] = true;
            }
        }

        $projectEntity = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project);
        $visibility    = $projectDisplayManager->getVisibility($projectEntity, $client);

        if (ProjectDisplayManager::VISIBILITY_FULL === $visibility) {
            $template['finance']        = $projectDisplayManager->getProjectFinancialData($project, true);
            $template['financeColumns'] = [
                'income_statement' => [],
                'assets'           => [],
                'debts'            => [],
            ];

            if (false === empty($template['finance'])) {
                $firstBalanceSheet          = current($template['finance']);
                $template['financeColumns'] = [
                    'income_statement' => array_keys($firstBalanceSheet['income_statement']['details']),
                    'assets'           => array_keys($firstBalanceSheet['assets']),
                    'debts'            => array_keys($firstBalanceSheet['debts']),
                ];
            }
        } else {
            $translator = $this->get('translator');

            $template['project']['title'] = $translator->trans('company-sector_sector-' . $template['project']['company']['sectorId']);

            if (isset($template['project']['navigation']['previous']['title']) && $template['project']['navigation']['previous']['project'] instanceof Projects) {
                /** @var Projects $previousProject */
                $previousProject = $template['project']['navigation']['previous']['project'];
                $template['project']['navigation']['previous']['title'] = $translator->trans('company-sector_sector-' . $previousProject->getIdCompany()->getSector());
            }

            if (isset($template['project']['navigation']['next']['title']) && $template['project']['navigation']['next']['project'] instanceof Projects) {
                /** @var Projects $nextProject */
                $nextProject = $template['project']['navigation']['next']['project'];
                $template['project']['navigation']['next']['title'] = $translator->trans('company-sector_sector-' . $nextProject->getIdCompany()->getSector());
            }
        }

        $template['conditions'] = [
            'visibility'           => $visibility,
            'bids'                 => isset($template['project']['bids']) && $template['project']['status'] == ProjectsStatus::EN_FUNDING,
            'myBids'               => isset($template['project']['lender']) && $template['project']['lender']['bids']['count'] > 0,
            'finance'              => ProjectDisplayManager::VISIBILITY_FULL === $visibility,
            'canBid'               => ProjectDisplayManager::VISIBILITY_FULL === $visibility
                && $client instanceof Clients
                && $client->isLender()
                && $this->get('unilend.service.terms_of_sale_manager')->hasAcceptedCurrentVersion($client),
            'warningLending'       => true,
            'warningTaxDeduction'  => $template['project']['startDate'] >= '2016-01-01',
            'displayCipDisclaimer' => $displayCipDisclaimer
        ];

        $this->setProjectDetailsSeoData($template['project']['company']['sectorId'], $template['project']['company']['city'], $template['project']['amount']);

        return $this->render('projects/detail.html.twig', $template);
    }

    /**
     * @param string       $projectSlug
     * @param Clients|null $client
     *
     * @return \projects|RedirectResponse
     */
    private function checkProjectAndRedirect(string $projectSlug, ?Clients $client)
    {
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \projects $project */
        $project = $entityManagerSimulator->getRepository('projects');

        if (false === $project->get($projectSlug, 'slug') || $project->slug !== $projectSlug) { // MySQL does not check collation (hôtellerie = hotellerie) so we strictly check in PHP
            throw $this->createNotFoundException();
        }

        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        $entityManager         = $this->get('doctrine.orm.entity_manager');
        $projectEntity         = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project);

        if (
            $project->status >= ProjectsStatus::A_FUNDER && $project->status < ProjectsStatus::EN_FUNDING
            || ProjectDisplayManager::VISIBILITY_NONE !== $projectDisplayManager->getVisibility($projectEntity, $client)
            || $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') && 28002 == $project->id_project
        ) {
            return $project;
        }

        throw $this->createNotFoundException();
    }

    /**
     * @Route("/projects/monthly_repayment", name="estimate_monthly_repayment", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function estimateMonthlyRepaymentAction(Request $request): Response
    {
        if (false === $request->isXmlHttpRequest()) {
            return new Response('not an ajax request');
        }

        if (
            empty($request->request->get('amount'))
            || empty($request->request->get('duration'))
            || empty($request->request->get('rate'))
        ) {
            return new JsonResponse([
                'error'   => true,
                'message' => 'Missing parameters'
            ]);
        }

        $amount   = $request->request->get('amount');
        $duration = $request->request->get('duration');
        $rate     = $request->request->get('rate');

        if (
            (int) $amount != $amount
            || (int) $duration != $duration
            || false === is_numeric($rate)
        ) {
            return new JsonResponse([
                'error'   => true,
                'message' => 'Wrong parameter value'
            ]);
        }

        $repayment = \repayment::getRepaymentSchedule($amount, $duration, $rate / 100)[1]['repayment'];

        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        return new JsonResponse([
            'success' => true,
            'message' => $translator->trans(
                'project-detail_monthly-repayment-estimation',
                ['%amount%' => $ficelle->formatNumber($repayment)]
            )
        ]);
    }

    /**
     * @Route("/projects/bid/{projectId}", requirements={"projectId": "\d+"}, name="place_bid", methods={"POST"})
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param int                        $projectId
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return RedirectResponse
     */
    public function placeBidAction(int $projectId, Request $request, ?UserInterface $client): RedirectResponse
    {
        if (
            ($post = $request->request->get('invest'))
            && isset($post['amount'], $post['interest'], $post['bidToken'])
        ) {
            $entityManagerSimulator = $this->get('unilend.service.entity_manager');
            $translator             = $this->get('translator');
            $entityManager          = $this->get('doctrine.orm.entity_manager');
            /** @var \projects $project */
            $project = $entityManagerSimulator->getRepository('projects');

            $formManager = $this->get('unilend.frontbundle.service.form_manager');

            if (false === $project->get($projectId)) {
                return $this->redirectToRoute('home');
            }

            if (false === $client->isValidated()) {
                $request->getSession()->set('bidResult', ['error' => true, 'message' => $translator->trans('project-detail_side-bar-bids-user-logged-out')]);
                return $this->redirectToRoute('project_detail', ['projectSlug' => $project->slug]);
            }

            $formManager->saveFormSubmission($client, ClientsHistoryActions::LENDER_BID, serialize(['id_client' => $client->getIdClient(), 'post' => $post, 'id_projet' => $projectId]), $request->getClientIp());

            $wallet    = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->getIdClient(), WalletType::LENDER);
            $bidAmount = floor($post['amount']); // the cents is not allowed
            $rate      = empty($post['interest']) ? 0.0 : (float) $post['interest'];

            if ($request->getSession()->get('bidToken') !== $post['bidToken']) {
                $request->getSession()->set('bidResult', ['error' => true, 'message' => $translator->trans('project-detail_side-bar-bids-invalid-security-token')]);
                return $this->redirectToRoute('project_detail', ['projectSlug' => $project->slug]);
            }

            $request->getSession()->remove('bidToken');

            $bidManager = $this->get('unilend.service.bid_manager');

            try {
                $projectEntity  = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);
                $bids           = $bidManager->bid($wallet, $projectEntity, $bidAmount, $rate);
                /** @var MemcacheCachePool $oCachePool */
                $oCachePool = $this->get('memcache.default');
                $oCachePool->deleteItem(\bids::CACHE_KEY_PROJECT_BIDS . '_' . $project->id_project);
                $request->getSession()->set('bidResult', ['success' => true, 'message' => $translator->trans('project-detail_side-bar-bids-bid-placed-message')]);
            } catch (BidException $exception) {
                if ('bids-not-eligible' === $exception->getMessage()) {
                    $productManager = $this->get('unilend.service_product.product_manager');

                    /** @var \product $product */
                    $product = $entityManagerSimulator->getRepository('product');
                    $product->get($project->id_product);

                    $amountMax = $productManager->getMaxEligibleAmount($client, $product);
                    $reasons   = $productManager->checkBidEligibility($bids);
                    $amountRest = 0;
                    foreach ($reasons as $reason) {
                        if ($reason === UnderlyingContractAttributeType::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO) {
                            $amountRest = $productManager->getAmountLenderCanStillBid($client, $project);
                        }
                        $currencyFormatter = $this->get('currency_formatter');
                        $amountRest        = $currencyFormatter->formatCurrency($amountRest, 'EUR');
                        $amountMax         = $currencyFormatter->formatCurrency($amountMax, 'EUR');

                        $this->addFlash('bid_not_eligible_reason', $translator->transChoice('project-detail_bid-not-eligible-reason-' . $reason, 0,['%amountRest%' => $amountRest, '%amountMax%' => $amountMax]));
                    }
                } else {
                    $request->getSession()->set('bidResult', ['error' => true, 'message' => $translator->trans('project-detail_side-bar-' . $exception->getMessage())]);
                }
            } catch (\Exception $exception) {
                $request->getSession()->set('bidResult', ['error' => true, 'message' => $translator->trans('project-detail_side-bar-bids-unknown-error')]);
            }

            return $this->redirectToRoute('project_detail', ['projectSlug' => $project->slug]);
        }

        return $this->redirectToRoute('home');
    }

    /**
     * todo: this controller can be "GET"
     *
     * @Route("/projects/bids/{projectId}/{rate}", name="bids_on_project",
     *     requirements={"projectId": "\d+", "rate": "(?:\d+|\d*\.\d+)"}, methods={"POST"})
     *
     * @param int                        $projectId
     * @param float                      $rate
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function bidsListAction(int $projectId, float $rate, Request $request, ?UserInterface $client): Response
    {
        if (false === $request->isXmlHttpRequest()) {
            return new Response('not an ajax request');
        }

        $template               = [];
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $oCachePool             = $this->get('memcache.default');
        $oCachedItem            = $oCachePool->getItem(\bids::CACHE_KEY_PROJECT_BIDS . '_' . $projectId . '_' . $rate);

        if (true === $oCachedItem->isHit()) {
            $template['bids'] = $oCachedItem->get();
        } else {
            /** @var \bids $bidEntity */
            $bidEntity = $entityManagerSimulator->getRepository('bids');

            $bids = $bidEntity->select('id_project = ' . $projectId . ' AND rate = ' . $rate, 'ordre ASC');
            $template['bids'] = [];

            foreach ($bids as $bid) {
                $template['bids'][] = [
                    'id'           => (int) $bid['ordre'],
                    'rate'         => (float) $bid['rate'],
                    'amount'       => $bid['amount'] / 100,
                    'status'       => (int) $bid['status'],
                    'lenderId'     => (int) $bid['id_lender_account'],
                    'userInvolved' => false,
                    'autobid'      => $bid['id_autobid'] > 0
                ];
            }

            $oCachedItem->set($template['bids'])->expiresAfter(300);
            $oCachePool->save($oCachedItem);
        }

        if ($client instanceof Clients && $client->isLender()) {
            $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

            array_walk($template['bids'], function(&$bid) use ($wallet) {
                if ($bid['lenderId'] == $wallet->getId()) {
                    $bid['userInvolved'] = true;
                }
            });
        }

        return $this->render('projects/detail/bids_list_detail.html.twig', $template);
    }

    /**
     * @Route("/projects/export/income/{projectId}", requirements={"projectId": "\d+"}, name="export_income_statement")
     *
     * @param int $projectId
     *
     * @return Response
     */
    public function exportIncomeStatementAction(int $projectId): Response
    {
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

        if (false === $project->get($projectId, 'id_project')
            || $project->display != Projects::DISPLAY_YES
            || false === $this->get('security.authorization_checker')->isGranted('ROLE_LENDER')
        ) {
            return new RedirectResponse('/');
        }

        $translator = $this->get('translator');

        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        $financeData           = $projectDisplayManager->getProjectFinancialData($project);

        $firstBalanceSheet     = current($financeData);
        $columns               = array_keys($firstBalanceSheet['income_statement']['details']);

        $iRow         = 1;
        /** @var \PHPExcel $oDocument */
        $oDocument    = new \PHPExcel();
        $oActiveSheet = $oDocument->setActiveSheetIndex(0);
        $oActiveSheet->setCellValueByColumnAndRow(0, $iRow, 'Date de clôture');
        $columnCount = 1;
        foreach ($financeData as $balanceSheet) {
            $oActiveSheet->setCellValueByColumnAndRow($columnCount++, $iRow, date('d/m/Y', strtotime($balanceSheet['closingDate'])));
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, 'Durée de l\'exercice');
        $columnCount = 1;
        foreach ($financeData as $balanceSheet) {
            $oActiveSheet->setCellValueByColumnAndRow($columnCount++, $iRow, str_replace('[DURATION]', $balanceSheet['monthDuration'], $translator->trans('preteur-projets_annual-accounts-duration-months')));
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_compte-de-resultats'));
        foreach ($columns as $column) {
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans($column));
            $columnCount = 1;
            foreach($financeData as $balanceSheet) {
                $oActiveSheet->setCellValueByColumnAndRow($columnCount++, $iRow, $balanceSheet['income_statement']['details'][$column][0]);
            }
        }
        /** @var \PHPExcel_Writer_CSV $oWriter */
        $oWriter = \PHPExcel_IOFactory::createWriter($oDocument, 'CSV');
        $oWriter->setUseBOM(true);
        $oWriter->setDelimiter(';');
        ob_start();
        $oWriter->save('php://output');
        $response = new Response(ob_get_clean());
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment;filename=resultats_' . $project->slug . '.csv');
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }

    /**
     * @Route("/projects/export/balance/{projectId}", requirements={"projectId": "\d+"}, name="export_balance_sheet")
     *
     * @param int $projectId
     *
     * @return Response
     */
    public function exportBalanceSheetAction(int $projectId): Response
    {
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

        if (false === $project->get($projectId, 'id_project')
            || $project->display != Projects::DISPLAY_YES
            || false === $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
            || false === $this->get('security.authorization_checker')->isGranted('ROLE_LENDER')
        ) {
            return new RedirectResponse('/');
        }

        $translator = $this->get('translator');

        /** @var \companies $oCompany */
        $oCompany = $this->get('unilend.service.entity_manager')->getRepository('companies');
        $oCompany->get($project->id_company, 'id_company');

        /** @var \companies_bilans $oAnnualAccounts */
        $oAnnualAccounts    = $this->get('unilend.service.entity_manager')->getRepository('companies_bilans');
        $aAnnualAccounts    = $oAnnualAccounts->select('id_company = "' . $oCompany->id_company . '" AND cloture_exercice_fiscal <= (SELECT cloture_exercice_fiscal FROM companies_bilans WHERE id_bilan = ' . $project->id_dernier_bilan . ')', 'cloture_exercice_fiscal DESC', 0, 3);
        $aAnnualAccountsIds = array_column($aAnnualAccounts, 'id_bilan');

        /** @var \companies_actif_passif $oAssetsDebts */
        $oAssetsDebts = $this->get('unilend.service.entity_manager')->getRepository('companies_actif_passif');
        $aAssetsDebts = $oAssetsDebts->select('id_bilan IN (' . implode(', ', $aAnnualAccountsIds) . ')', 'FIELD(id_bilan, ' . implode(', ', $aAnnualAccountsIds) . ') ASC');

        /** @var \settings $oSetting */
        $oSetting = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $oSetting->get('Entreprises fundés au passage du risque lot 1', 'type');
        $aFundedCompanies     = explode(',', $oSetting->value);
        $bPreviousRiskProject = in_array($oCompany->id_company, $aFundedCompanies);

        $iRow         = 1;
        /** @var \PHPExcel $oDocument */
        $oDocument    = new \PHPExcel();
        $oActiveSheet = $oDocument->setActiveSheetIndex(0);
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_bilan'));
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_actif'));
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_immobilisations-corporelles'));
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['immobilisations_corporelles']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_immobilisations-incorporelles'));
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['immobilisations_incorporelles']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_immobilisations-financieres'));
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['immobilisations_financieres']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_stocks'));
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['stocks']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_creances-clients'));
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['creances_clients']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_disponibilites'));
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['disponibilites']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_valeurs-mobilieres-de-placement'));
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['valeurs_mobilieres_de_placement']);
        }
        if (false === $bPreviousRiskProject && ($aAssetsDebts[0]['comptes_regularisation_actif'] != 0 || $aAssetsDebts[1]['comptes_regularisation_actif'] != 0 || $aAssetsDebts[2]['comptes_regularisation_actif'] != 0)) {
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_comptes-regularisation'));
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['comptes_regularisation_actif']);
            }
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_total-bilan-actifs'));
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['immobilisations_corporelles'] + $aAssetsDebts[$i]['immobilisations_incorporelles'] + $aAssetsDebts[$i]['immobilisations_financieres'] + $aAssetsDebts[$i]['stocks'] + $aAssetsDebts[$i]['creances_clients'] + $aAssetsDebts[$i]['disponibilites'] + $aAssetsDebts[$i]['valeurs_mobilieres_de_placement'] + $aAssetsDebts[$i]['comptes_regularisation_actif']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_passif'));
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_capitaux-propres'));
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['capitaux_propres']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_provisions-pour-risques-charges'));
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['provisions_pour_risques_et_charges']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_amortissement-sur-immo'));
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['amortissement_sur_immo']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_dettes-financieres'));
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['dettes_financieres']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_dettes-fournisseurs'));
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['dettes_fournisseurs']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_autres-dettes'));
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['autres_dettes']);
        }
        if (false === $bPreviousRiskProject && ($aAssetsDebts[0]['comptes_regularisation_passif'] != 0 || $aAssetsDebts[1]['comptes_regularisation_passif'] != 0 || $aAssetsDebts[2]['comptes_regularisation_passif'] != 0)) {
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_comptes-regularisation'));
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['comptes_regularisation_passif']);
            }
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translator->trans('preteur-projets_total-bilan-passifs'));
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['capitaux_propres'] + $aAssetsDebts[$i]['provisions_pour_risques_et_charges'] + $aAssetsDebts[$i]['amortissement_sur_immo'] + $aAssetsDebts[$i]['dettes_financieres'] + $aAssetsDebts[$i]['dettes_fournisseurs'] + $aAssetsDebts[$i]['autres_dettes'] + $aAssetsDebts[$i]['comptes_regularisation_passif']);
        }

        /** @var \PHPExcel_Writer_CSV $oWriter */
        $oWriter = \PHPExcel_IOFactory::createWriter($oDocument, 'CSV');
        $oWriter->setUseBOM(true);
        $oWriter->setDelimiter(';');
        ob_start();
        $oWriter->save('php://output');
        $response = new Response(ob_get_clean());
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment;filename=bilan_' . $project->slug . '.csv');
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }

    /**
     * @Route("/projects/export/bids/{projectId}", requirements={"projectId": "\d+"}, name="export_bids")
     *
     * @param int $projectId
     *
     * @return Response
     */
    public function exportBidsAction(int $projectId): Response
    {
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \projects $project */
        $project = $entityManagerSimulator->getRepository('projects');

        if ($project->get($projectId, 'id_project')) {
            $translator = $this->get('translator');

            if ($project->status == ProjectsStatus::EN_FUNDING) {
                ob_start();
                echo "\xEF\xBB\xBF";
                echo '"N°";"' . $translator->trans('preteur-projets_taux-dinteret') . '";"' . $translator->trans('preteur-projets_montant') . '";"' . $translator->trans('preteur-projets_statuts') . '"' . PHP_EOL;

                /** @var \bids $bids */
                $bids = $entityManagerSimulator->getRepository('bids');

                $offset    = 0;
                $limit     = 1000;
                $bidStatus = [
                    Bids::STATUS_PENDING  => $translator->trans('preteur-projets_enchere-en-cours'),
                    Bids::STATUS_ACCEPTED => $translator->trans('preteur-projets_enchere-ok'),
                    Bids::STATUS_REJECTED => $translator->trans('preteur-projets_enchere-ko')
                ];

                while ($bidsList = $bids->select('id_project = ' . $project->id_project, 'ordre ASC', $offset, $limit)) {
                    foreach ($bidsList as $bid) {
                        echo $bid['ordre'] . ';' . $bid['rate'] . ' %;' . bcdiv($bid['amount'], 100, 2) . ' €;"' . $bidStatus[$bid['status']] . '"' . PHP_EOL;
                    }
                    $offset += $limit;
                }
            }
        }
        $response = new Response(ob_get_clean());
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $project->slug . '_bids.csv');
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }

    /**
     * Sets the values meta-title and meta-description
     *
     * @param $companySectorId
     * @param $companyCity
     * @param $projectAmount
     */
    private function setProjectDetailsSeoData($companySectorId, $companyCity, $projectAmount)
    {
        /** @var SeoPage $seoPage */
        $seoPage = $this->get('sonata.seo.page');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $translationParams = [
            '%sector%' => $translator->trans('company-sector_sector-' . $companySectorId),
            '%city%'   => $companyCity,
            '%amount%' => $ficelle->formatNumber($projectAmount, 0)
        ];

        $pageTitle = $translator->trans('seo_project-detail-title', $translationParams);
        if ($pageTitle !== 'seo_project-detail-title') {
            $seoPage->setTitle($pageTitle);
        }

        $pageDescription = $translator->trans('seo_project-detail-description', $translationParams);
        if ($pageDescription !== 'seo_project-detail-description') {
            $seoPage->addMeta('name', 'description', $pageDescription);
        }
    }

    /**
     * @Route("/projects/pre-check-bid/{projectSlug}/{amount}/{rate}", name="pre_check_bid", condition="request.isXmlHttpRequest()",
     *     requirements={"projectSlug": "[a-z0-9-]+", "amount": "\d+", "rate": "\d{1,2}(\.\d|)"})
     *
     * @param string                     $projectSlug
     * @param int                        $amount
     * @param float                      $rate
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function preCheckBidAction(string $projectSlug, int $amount, float $rate, ?UserInterface $client): Response
    {
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $cipManager             = $this->get('unilend.service.cip_manager');
        $translator             = $this->get('translator');
        $productManager         = $this->get('unilend.service_product.product_manager');
        $currencyFormatter      = $this->get('currency_formatter');

        /** @var \projects $project */
        $project = $entityManagerSimulator->getRepository('projects');

        if (false === $project->get($projectSlug, 'slug')) {
            return new JsonResponse([
                'error'   => true,
                'messages' => ['Invalid parameters']
            ]);
        }

        /** @var \settings $settings */
        $settings = $entityManagerSimulator->getRepository('settings');
        $settings->get('Pret min', 'type');
        $amountMin = (int) trim($settings->value);

        if ($amount < $amountMin) {
            $amountMin = $currencyFormatter->formatCurrency($amountMin, 'EUR');
            return new JsonResponse([
                'error'   => true,
                'messages' => [$translator->trans('project-detail_bid-min-amount-error', ['%amountMin%' => $amountMin])]
            ]);
        }

        if (false === $client instanceof Clients || false === $client->isLender()) {
            return new JsonResponse([
                'error'    => true,
                'title'    => $translator->trans('project-detail_modal-bid-error-disconnected-lender-title'),
                'messages' => [$translator->trans('project-detail_modal-bid-error-disconnected-lender-message')]
            ]);
        }

        $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        if ($wallet->getAvailableBalance() < $amount) {
            return new JsonResponse([
                'error'    => true,
                'title'    => $translator->trans('project-detail_modal-bid-error-amount-title'),
                'messages' => [$translator->trans('project-detail_side-bar-bids-low-balance')]
            ]);
        }


        $bid = new Bids();
        $bid
            ->setProject($entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project))
            ->setWallet($wallet)
            ->setAmount($amount * 100)
            ->setRate($rate);

        $reasons = $productManager->checkBidEligibility($bid);

        if (false === empty($reasons)) {
            $pendingBidAmount = $entityManager->getRepository('UnilendCoreBusinessBundle:Bids')
                ->getSumByWalletAndProjectAndStatus($wallet, $bid->getProject(), [Bids::STATUS_PENDING]);

            $product = $entityManagerSimulator->getRepository('product');
            $product->get($project->id_product);

            $translatedReasons = [];
            $amountRest        = 0;
            $amountMax         = $productManager->getMaxEligibleAmount($client, $product);

            foreach ($reasons as $reason) {
                if ($reason === UnderlyingContractAttributeType::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO) {
                    $amountRest = $productManager->getAmountLenderCanStillBid($client, $project);
                }
                $amountRest = $currencyFormatter->formatCurrency($amountRest, 'EUR');
                $amountMax  = $currencyFormatter->formatCurrency($amountMax, 'EUR');

                $translatedReasons[] = $translator->transChoice('project-detail_bid-not-eligible-reason-' . $reason, $pendingBidAmount,['%amountRest%' => $amountRest, '%amountMax%' => $amountMax]);
            }

            return new JsonResponse([
                'error'    => true,
                'title'    => $translator->trans('project-detail_modal-bid-error-amount-title'),
                'messages' => $translatedReasons
            ]);
        }

        $this->addFlash('cipBid', ['amount' => $amount, 'rate' => $rate, 'project' => $project->id_project]);

        $validationNeeded = $cipManager->isCIPValidationNeeded($bid);
        $response         = [
            'validation'      => $validationNeeded,
            'isNaturalPerson' => $client->isNaturalPerson()
        ];

        if ($validationNeeded) {
            $evaluation = $cipManager->getCurrentEvaluation($client);

            if (null !== $evaluation && $cipManager->isValidEvaluation($evaluation)) {
                $advices    = [];
                $indicators = $cipManager->getIndicators($client);

                if (null !== $indicators[CIPManager::INDICATOR_TOTAL_AMOUNT]) {
                    /** @var \bids $bids */
                    $bids      = $entityManagerSimulator->getRepository('bids');
                    $totalBids = $bids->sum(
                        'id_lender_account = ' . $wallet->getId() . ' AND status IN (' . Bids::STATUS_PENDING . ', ' . Bids::STATUS_TEMPORARILY_REJECTED_AUTOBID . ')',
                        'ROUND(amount / 100)'
                    );
                    /** @var \loans $loans */
                    $loans      = $entityManagerSimulator->getRepository('loans');
                    $totalLoans = $loans->sum(
                        'id_lender = ' . $wallet->getId() . ' AND status = ' . Loans::STATUS_ACCEPTED,
                        'ROUND(amount / 100)'
                    );

                    $totalAmount = bcadd(bcadd($totalBids, $totalLoans, 2), $amount, 2);

                    if ($totalAmount > $indicators[CIPManager::INDICATOR_TOTAL_AMOUNT]) {
                        $advices[CIPManager::INDICATOR_TOTAL_AMOUNT] = true;
                    }
                }

                if (null !== $indicators[CIPManager::INDICATOR_AMOUNT_BY_MONTH]) {
                    /** @var \bids $bids */
                    $bids        = $entityManagerSimulator->getRepository('bids');
                    $totalBids = $bids->sum(
                        'id_lender_account = ' . $wallet->getId() . ' AND added >= DATE_SUB(NOW(), INTERVAL 1 MONTH) AND status IN (' . Bids::STATUS_PENDING . ', ' . Bids::STATUS_TEMPORARILY_REJECTED_AUTOBID . ')',
                        'ROUND(amount / 100)');
                    /** @var \loans $loans */
                    $loans      = $entityManagerSimulator->getRepository('loans');
                    $totalLoans = $loans->sum(
                        'id_lender = ' . $wallet->getId() . ' AND added >= DATE_SUB(NOW(), INTERVAL 1 MONTH) AND status = ' . Loans::STATUS_ACCEPTED,
                        'ROUND(amount / 100)'
                    );

                    $totalAmount = bcadd($totalBids, $totalLoans, 2);

                    if ($totalAmount > $indicators[CIPManager::INDICATOR_AMOUNT_BY_MONTH]) {
                        $advices[CIPManager::INDICATOR_AMOUNT_BY_MONTH] = true;
                    }
                }

                if (null !== $indicators[CIPManager::INDICATOR_PROJECT_DURATION] && $project->period > $indicators[CIPManager::INDICATOR_PROJECT_DURATION]) {
                    $advices[CIPManager::INDICATOR_PROJECT_DURATION] = true;
                }

                if (false === empty($advices)) {
                    $message = '';

                    if (isset($advices[CIPManager::INDICATOR_TOTAL_AMOUNT], $advices[CIPManager::INDICATOR_AMOUNT_BY_MONTH], $advices[CIPManager::INDICATOR_PROJECT_DURATION])) {
                        $message = $translator->trans('lender-evaluation_warning-not-advised-total-amount-monthly-amount-project-duration');
                    } elseif (isset($advices[CIPManager::INDICATOR_TOTAL_AMOUNT], $advices[CIPManager::INDICATOR_AMOUNT_BY_MONTH])) {
                        $message = $translator->trans('lender-evaluation_warning-not-advised-total-amount-monthly-amount');
                    } elseif (isset($advices[CIPManager::INDICATOR_TOTAL_AMOUNT], $advices[CIPManager::INDICATOR_PROJECT_DURATION])) {
                        $message = $translator->trans('lender-evaluation_warning-not-advised-total-amount-project-duration');
                    } elseif (isset($advices[CIPManager::INDICATOR_TOTAL_AMOUNT])) {
                        $message = $translator->trans('lender-evaluation_warning-not-advised-total-amount');
                    } elseif (isset($advices[CIPManager::INDICATOR_AMOUNT_BY_MONTH], $advices[CIPManager::INDICATOR_PROJECT_DURATION])) {
                        $message = $translator->trans('lender-evaluation_warning-not-advised-monthly-amount-project-duration');
                    } elseif (isset($advices[CIPManager::INDICATOR_AMOUNT_BY_MONTH])) {
                        $message = $translator->trans('lender-evaluation_warning-not-advised-monthly-amount');
                    } elseif (isset($advices[CIPManager::INDICATOR_PROJECT_DURATION])) {
                        $message = $translator->trans('lender-evaluation_warning-not-advised-project-duration');
                    }

                    $cipManager->saveLog($evaluation, \lender_evaluation_log::EVENT_BID_ADVICE, strip_tags($message));
                    $response['advices'] = $message;
                }
            } else {
                if (null === $evaluation) {
                    $evaluation = $cipManager->createEvaluation($client);
                }

                $cipManager->saveLog($evaluation, \lender_evaluation_log::EVENT_BID_EVALUATION_NEEDED);
                $response['questionnaire'] = true;
            }
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/var/dirs/{projectSlug}.pdf", name="project_dirs", requirements={"projectSlug": "[a-z0-9-]+"})
     *
     * @param string                     $projectSlug
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function dirsAction(string $projectSlug, ?UserInterface $client): Response
    {
        $project = $this->checkProjectAndRedirect($projectSlug, $client);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \product $product */
        $product = $entityManagerSimulator->getRepository('product');
        $product->get($project->id_product);

        $productManager     = $this->get('unilend.service_product.product_manager');
        $availableContracts = $productManager->getAvailableContracts($product);

        if (false === in_array(UnderlyingContract::CONTRACT_MINIBON, array_column($availableContracts, 'label'))) {
            throw $this->createNotFoundException();
        }

        $template = [
            'company' => $this->getDIRSCompany($project),
            'project' => $this->getDIRSProject($project),
            'unilend' => $this->getDIRSUnilend()
        ];

        $html = $this->renderView('/pdf/dirs.html.twig', $template);

        $filename   = $project->slug . '.pdf';
        $options    = [
            'page-size' => 'A4'
        ];

        /** @var GeneratorInterface $snappy */
        $snappy = $this->get('knp_snappy.pdf');

        if ($project->status >= ProjectsStatus::EN_FUNDING) {
            $outputFile = $this->getParameter('path.user') . 'dirs/' . $filename;
            $snappy->generateFromHtml($html, $outputFile, $options, true);
        }

        return new Response($snappy->getOutputFromHtml($html, $options), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename)
        ]);
    }

    /**
     * @param \projects $project
     *
     * @return array
     */
    private function getDIRSCompany(\projects $project): array
    {
        $entityManager              = $this->get('doctrine.orm.entity_manager');
        $company                    = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($project->id_company);
        $companyBalanceSheetManager = $this->get('unilend.service.company_balance_sheet_manager');

        $balanceDetails = $companyBalanceSheetManager->getBalanceSheetsByAnnualAccount([$project->id_dernier_bilan]);
        $balanceDetails = $balanceDetails[$project->id_dernier_bilan]['details'];
        $workingCapital = $balanceDetails['CJ'] - ($balanceDetails['DS'] + $balanceDetails['DT'] + $balanceDetails['DU'] + $balanceDetails['DV'] + $balanceDetails['DW'] + $balanceDetails['DX'] + $balanceDetails['DY'] + $balanceDetails['DZ'] + $balanceDetails['EA']);

        return [
            'name'             => $company->getName(),
            'siren'            => $company->getSiren(),
            'legal_status'     => $company->getForme(),
            'capital'          => str_replace(' ', '', $company->getCapital()),
            'address'          => trim($company->getIdAddress()->getAddress()),
            'post_code'        => $company->getIdAddress()->getZip(),
            'city'             => $company->getIdAddress()->getCity(),
            'commercial_court' => $company->getTribunalCom(),
            'creation_date'    => $company->getDateCreation()->format('Y-m-d'),
            'accounts_count'   => $project->balance_count,
            'working_capital'  => $workingCapital
        ];
    }

    /**
     * @param \projects $project
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    private function getDIRSProject(\projects $project): array
    {
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        $attachmentManager      = $this->get('unilend.service.attachment_manager');

        /** @var Projects $projectEntity */
        $projectEntity  = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project);
        $attachmentType = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->find(AttachmentType::DEBTS_STATEMENT);
        $attachment     = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->getProjectAttachmentByType($projectEntity, $attachmentType);

        /** @var \project_rate_settings $projectRateSettings */
        $projectRateSettings = $entityManagerSimulator->getRepository('project_rate_settings');
        $projectRateSettings->get($project->id_rate);
        $minimumBidRate = (float) $projectRateSettings->rate_min;

        $monthInterval = new \DateInterval('P1M');
        $startDate     = \DateTime::createFromFormat('Y-m-d H:i:s', $project->date_publication);
        $endDate       = \DateTime::createFromFormat('Y-m-d H:i:s', $project->date_retrait);
        $signatureDate = \DateTime::createFromFormat('Y-m-d H:i:s', $project->date_retrait)->add(new \DateInterval('P7D'));

        $repaymentDate     = \DateTime::createFromFormat('Y-m-d H:i:s', substr($project->date_retrait, 0, 8) . '01 00:00:00');
        $repaymentSchedule = \repayment::getRepaymentSchedule(1000, $project->period, $minimumBidRate / 100);

        foreach ($repaymentSchedule as $order => $repayment) {
            $repaymentSchedule[$order]['date'] = ucfirst(strftime('%B %Y', $repaymentDate->add($monthInterval)->getTimestamp()));
        }

        return [
            'slug'                => $project->slug,
            'duration'            => $project->period,
            'amount'              => $project->amount,
            'net_amount'          => round($project->amount * bcsub(1, round(bcdiv($project->commission_rate_funds, 100, 4), 2), 2)),
            'minimum_rate'        => $minimumBidRate,
            'start_date'          => $startDate,
            'end_date'            => $endDate,
            'signature_date'      => $signatureDate,
            'released_funds'      => $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->getLastYearReleasedFundsBySIREN($projectEntity->getIdCompany()->getSiren()),
            'debts_statement_img' => base64_encode(file_get_contents($attachmentManager->getFullPath($attachment))),
            'repayment_schedule'  => $repaymentSchedule
        ];
    }

    /**
     * @return array
     */
    private function getDIRSUnilend()
    {

        /** @var \settings $setting */
        $setting = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $setting->get('Declaration contrat pret - raison sociale', 'type');
        $unilendName = $setting->value;

        $setting->get('Facture - capital', 'type');
        $unilendCapital = $setting->value;

        $setting->get('Declaration contrat pret - adresse', 'type');
        $unilendAddress = $setting->value;

        $setting->get('Facture - RCS', 'type');
        $unilendRCS = $setting->value;

        return [
            'name'    => $unilendName,
            'capital' => $unilendCapital,
            'address' => $unilendAddress,
            'rcs'     => $unilendRCS,
        ];
    }
}
