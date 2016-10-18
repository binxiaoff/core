<?php
namespace Unilend\Bundle\FrontBundle\Controller;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Sonata\SeoBundle\Seo\SeoPage;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\BidManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;
use Unilend\Bundle\FrontBundle\Security\User\UserBorrower;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\Bundle\FrontBundle\Service\LenderAccountDisplayManager;
use Unilend\Bundle\FrontBundle\Service\ProjectDisplayManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;
use Unilend\core\Loader;

class ProjectsController extends Controller
{
    /**
     * @Route("/projets-a-financer/{page}/{sortType}/{sortDirection}", defaults={"page" = "1", "sortType" = "end", "sortDirection" = "desc"}, name="projects_list")
     * @Template("pages/projects.html.twig")
     *
     * @param int    $page
     * @param string $sortType
     * @param string $sortDirection
     * @return array
     */
    public function projectsListAction($page, $sortType, $sortDirection)
    {
        return $this->getProjectsList($page, $sortType, $sortDirection);
    }

    /**
     * @Route("/projects/{page}/{sortType}/{sortDirection}", defaults={"page" = "1", "sortType" = "end", "sortDirection" = "desc"}, requirements={"page" = "\d+"}, name="lender_projects")
     * @Template("lender_account/projects.html.twig")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param int    $page
     * @param string $sortType
     * @param string $sortDirection
     * @return array
     */
    public function lenderProjectsAction($page, $sortType, $sortDirection)
    {
        return $this->getProjectsList($page, $sortType, $sortDirection);
    }

    /**
     * @param int    $page
     * @param string $sortType
     * @param string $sortDirection
     * @return array
     */
    private function getProjectsList($page, $sortType, $sortDirection)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var ProjectDisplayManager $projectDisplayManager */
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        /** @var AuthorizationChecker $authorizationChecker */
        $authorizationChecker = $this->get('security.authorization_checker');

        /** @var BaseUser $user */
        $user = $this->getUser();

        $template      = [];
        $pagination    = $this->getPaginationStartAndLimit($page);
        $limit         = $pagination['limit'];
        $start         = $pagination['start'];
        $sort          = [];
        $sortDirection = strtoupper($sortDirection);

        if (
            in_array($sortType, [\projects::SORT_FIELD_SECTOR, \projects::SORT_FIELD_AMOUNT, \projects::SORT_FIELD_RATE, \projects::SORT_FIELD_RISK, \projects::SORT_FIELD_END])
            && in_array($sortDirection, [\projects::SORT_DIRECTION_ASC, \projects::SORT_DIRECTION_DESC])
        ) {
            $sort = [$sortType => $sortDirection];
        }

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')
            && $authorizationChecker->isGranted('ROLE_LENDER')
            && $user instanceof UserLender
        ) {
            /** @var \lenders_accounts $lenderAccount */
            $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
            $lenderAccount->get($user->getClientId(), 'id_client_owner');

            /** @var LenderAccountDisplayManager $lenderAccountDisplayManager */
            $lenderAccountDisplayManager = $this->get('unilend.frontbundle.service.lender_account_display_manager');

            $template['projects'] = $projectDisplayManager->getProjectsList([], $sort, $start, $limit, $lenderAccount);

            array_walk($template['projects'], function(&$project) use ($lenderAccountDisplayManager, $lenderAccount) {
                $project['lender'] = $lenderAccountDisplayManager->getActivityForProject($lenderAccount, $project['projectId'], $project['status']);
            });
        } else {
            $template['projects'] = $projectDisplayManager->getProjectsList([], $sort, $start, $limit);
        }

        $isFullyConnectedUser = ($user instanceof UserLender && $user->getClientStatus() == \clients_status::VALIDATED || $user instanceof UserBorrower);

        if (false === $isFullyConnectedUser) {
            array_walk($template['projects'], function(&$project) use ($translator) {
                $project['title'] = $translator->trans('company-sector_sector-' . $project['company']['sectorId']);
            });
        }

        /** @var \projects $projects */
        $projects = $this->get('unilend.service.entity_manager')->getRepository('projects');

        $template['projectsInFunding'] = $projects->countSelectProjectsByStatus(\projects_status::EN_FUNDING, ' AND display = ' . \projects::DISPLAY_PROJECT_ON);
        $template['pagination']        = $this->pagination($page, $limit);
        $template['showPagination']    = true;
        $template['showSortable']      = true;
        $template['currentPage']       = $page;
        $template['sortType']          = strtolower($sortType);
        $template['sortDirection']     = strtolower($sortDirection);

        return $template;
    }

    private function pagination($page, $limit)
    {
        /** @var ProjectDisplayManager $projectDisplayManager */
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');

        $totalNumberProjects = $projectDisplayManager->getTotalNumberOfDisplayedProjects();
        $totalPages          = ceil($totalNumberProjects / $limit);

        $paginationSettings = [
            'itemsPerPage'      => $limit,
            'totalItems'        => $totalNumberProjects,
            'totalPages'        => $totalPages,
            'currentIndex'      => $page,
            'currentIndexItems' => min($page * $limit, $totalNumberProjects),
            'remainingItems'    => ceil($totalNumberProjects - ($totalNumberProjects / $limit)),
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
     * @Route("/projects/detail/{projectSlug}", name="project_detail")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function projectDetailAction($projectSlug, Request $request)
    {
        $project = $this->checkProjectAndRedirect($projectSlug);

        if ($project instanceof RedirectResponse) {
            return $project;
        }

        /** @var ProjectDisplayManager $projectDisplayManager */
        $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
        /** @var AuthorizationChecker $authorizationChecker */
        $authorizationChecker = $this->get('security.authorization_checker');

        /** @var BaseUser $user */
        $user = $this->getUser();

        $template = [
            'project'  => $projectDisplayManager->getProjectData($project),
            'finance'  => $projectDisplayManager->getProjectFinancialData($project),
            'bidToken' => sha1('tokenBid-' . time() . '-' . uniqid())
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

        $firstBalanceSheet = current($template['finance']);
        $template['financeColumns'] = [
            'balanceSheet' => array_keys($firstBalanceSheet['balanceSheet']),
            'assets'       => array_keys($firstBalanceSheet['assets']),
            'debts'        => array_keys($firstBalanceSheet['debts']),
        ];

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')
            && $authorizationChecker->isGranted('ROLE_LENDER')
        ) {
            $request->getSession()->set('bidToken', $template['bidToken']);

            /** @var \lenders_accounts $lenderAccount */
            $lenderAccount = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
            $lenderAccount->get($user->getClientId(), 'id_client_owner');

            $productManager = $this->get('unilend.service_product.product_manager');
            /** @var \product $product */
            $product = $this->get('unilend.service.entity_manager')->getRepository('product');
            $product->get($project->id_product);

            $template['amountRest'] = $productManager->getAmountLenderCanStillBid($lenderAccount, $project);
            $template['amountMax']  = $productManager->getMaxEligibleAmount($product);

            /** @var \settings $settings */
            $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
            $settings->get('Pret min', 'type');
            $template['amountMin'] = (int) trim($settings->value);

            /** @var LenderAccountDisplayManager $lenderAccountDisplayManager */
            $lenderAccountDisplayManager = $this->get('unilend.frontbundle.service.lender_account_display_manager');
            $template['project']['lender'] = $lenderAccountDisplayManager->getActivityForProject($lenderAccount, $project->id_project, $project->status);

            if (false === empty($request->getSession()->get('bidResult'))) {
                $template['lender']['bidResult'] = $request->getSession()->get('bidResult');
                $request->getSession()->remove('bidResult');
            }

            $template['isLenderEligible'] = $productManager->isLenderEligible($lenderAccount, $project);
            if (false === $template['isLenderEligible']) {
                $reasons = $productManager->getLenderValidationReasons($lenderAccount, $project);
                $template['lenderNotEligibleReasons'] = $reasons;
            }
        }

        $isFullyConnectedUser       = ($user instanceof UserLender && in_array($user->getClientStatus(), [\clients_status::VALIDATED, \clients_status::MODIFICATION]) || $user instanceof UserBorrower);
        $isConnectedButNotValidated = ($user instanceof UserLender && false === in_array($user->getClientStatus(), [\clients_status::VALIDATED, \clients_status::MODIFICATION]));

        if (false === $isFullyConnectedUser) {
            /** @var TranslatorInterface $translator */
            $translator = $this->get('translator');
            /** @var EntityManager $entityManager */
            $entityManager = $this->get('unilend.service.entity_manager');
            /** @var \companies $company */
            $company = $entityManager->getRepository('companies');

            $template['project']['title'] = $translator->trans('company-sector_sector-' . $template['project']['company']['sectorId']);

            if (isset($template['project']['navigation']['previousProject']['title'])) {
                $company->get($template['project']['navigation']['previousProject']['id_company']);
                $template['project']['navigation']['previousProject']['title'] = $translator->trans('company-sector_sector-' . $company->sector);
            }

            if (isset($template['project']['navigation']['nextProject']['title'])) {
                $company->get($template['project']['navigation']['nextProject']['id_company']);
                $template['project']['navigation']['nextProject']['title'] = $translator->trans('company-sector_sector-' . $company->sector);
            }
        }

        $template['conditions'] = [
            'validatedUser'       => $isFullyConnectedUser,
            'notValidatedUser'    => $isConnectedButNotValidated,
            'bids'                => isset($template['project']['bids']) && $template['project']['status'] == \projects_status::EN_FUNDING,
            'myBids'              => isset($template['project']['lender']) && $template['project']['lender']['bids']['count'] > 0,
            'finance'             => $isFullyConnectedUser,
            'history'             => isset($template['project']['lender']['loans']['myLoanOnProject']['nbValid']) && $template['project']['lender']['loans']['myLoanOnProject']['nbValid'] > 0,
            'canBid'              => $isFullyConnectedUser && $user instanceof UserLender && $user->hasAcceptedCurrentTerms(),
            'warningLending'      => true,
            'warningTaxDeduction' => $template['project']['startDate'] >= '2016-01-01'
        ];
        $this->setProjectDetailsSeoData($template['project']['company']['sectorId'], $template['project']['company']['city'], $template['project']['amount']);

        return $this->render('pages/project_detail.html.twig', $template);
    }

    /**
     * @param string $projectSlug
     * @return \projects|RedirectResponse
     */
    private function checkProjectAndRedirect($projectSlug)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');

        if (false === $project->get($projectSlug, 'slug')) {
            /** @var \redirections $redirection */
            $redirection = $entityManager->getRepository('redirections');

            if ($redirection->get(['from_slug' => $projectSlug, 'status' => 1])) {
                return new RedirectResponse($redirection->to_slug, $redirection->type);
            }

            throw $this->createNotFoundException();
        }

        if (
            $project->status >= \projects_status::A_FUNDER
            && (
                $project->display == \projects::DISPLAY_PROJECT_ON
                || $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') && 28002 == $project->id_project
            )
        ) {
            return $project;
        }

        throw $this->createNotFoundException();
    }

    /**
     * @Route("/projects/monthly_repayment", name="estimate_monthly_repayment")
     * @Method({"POST"})
     */
    public function estimateMonthlyRepaymentAction(Request $request)

    {
        if (false === $request->isXMLHttpRequest()) {
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
     * @Route("/projects/bid/{projectId}", requirements={"projectId" = "^\d+$"}, name="place_bid")
     */
    public function placeBidAction($projectId, Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        $translator    = $this->get('translator');
        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');

        if (false === $project->get($projectId)) {
            return $this->redirectToRoute('home');
        }

        /** @var UserLender $user */
        $user = $this->getUser();

        if (false === ($user instanceof UserLender)
            || $user->getClientStatus() < \clients_status::VALIDATED
        ) {
            $request->getSession()->set('bidResult', ['error' => true, 'message' => $translator->trans('project-detail_side-bar-bids-user-logged-out')]);
            return $this->redirectToRoute('project_detail', ['projectSlug' => $project->slug]);
        }

        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $entityManager->getRepository('lenders_accounts');
        $lenderAccount->get($user->getClientId(), 'id_client_owner');

        $needsCIPValidation = true;
        $bidAmount          = null;
        $rate               = null;

        if (
            ($post = $request->request->get('invest'))
            && isset($post['amount'], $post['interest'], $post['bidToken'])
        ) {
            /** @var \clients_history_actions $clientHistoryActions */
            $clientHistoryActions = $entityManager->getRepository('clients_history_actions');
            $clientHistoryActions->histo(9, 'bid', $user->getClientId(), serialize(array('id_client' => $user->getClientId(), 'post' => $post, 'id_projet' => $projectId)));

            $bidAmount = floor($post['amount']); // the cents is not allowed
            $rate      = $post['interest'];

            if ($request->getSession()->get('bidToken') !== $post['bidToken']) {
                $request->getSession()->set('bidResult', ['error' => true, 'message' => $translator->trans('project-detail_side-bar-bids-invalid-security-token')]);
                return $this->redirectToRoute('project_detail', ['projectSlug' => $project->slug]);
            }

            $request->getSession()->remove('bidToken');
        } elseif ($request->getSession()->get('cipBid')) {
            $cipBid             = $request->getSession()->get('cipBid');
            $bidAmount          = isset($cipBid['amount']) ? $cipBid['amount'] : null;
            $rate               = isset($cipBid['rate']) ? $cipBid['rate'] : null;
            $needsCIPValidation = false;

            $request->getSession()->remove('cipBid');
        }

        if (empty($bidAmount) || empty($rate)) {
            return $this->redirectToRoute('home');
        }

        /** @var \bids $bids */
        $bids = $entityManager->getRepository('bids');
        $bids->unsetData();
        $bids->id_lender_account = $lenderAccount->id_lender_account;
        $bids->id_project        = $project->id_project;
        $bids->amount            = $bidAmount * 100;
        $bids->rate              = $rate;

        /** @var BidManager $bidManager */
        $bidManager = $this->get('unilend.service.bid_manager');
        try {
            $bidManager->bid($bids, true, $needsCIPValidation);
            /** @var MemcacheCachePool $oCachePool */
            $oCachePool = $this->get('memcache.default');
            $oCachePool->deleteItem(\bids::CACHE_KEY_PROJECT_BIDS . '_' . $project->id_project);
            $request->getSession()->set('bidResult', ['success' => true, 'message' => $translator->trans('project-detail_side-bar-bids-bid-placed-message')]);
        } catch (\Exception $exception) {
            if ('bids-not-eligible' === $exception->getMessage()) {
                $productManager = $this->get('unilend.service_product.product_manager');

                /** @var \product $product */
                $product = $entityManager->getRepository('product');
                $product->get($project->id_product);

                $amountMax  = $productManager->getMaxEligibleAmount($product);
                $reasons    = $productManager->getBidValidatorReasons($bids, $product);
                $amountRest = 0;

                foreach ($reasons as $reason) {
                    if ($reason === \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO) {
                        $amountRest = $productManager->getAmountLenderCanStillBid($lenderAccount, $project);
                    }
                    $currencyFormatter = new \NumberFormatter($request->getLocale(), \NumberFormatter::CURRENCY);
                    $amountRest        = $currencyFormatter->formatCurrency($amountRest, 'EUR');
                    $amountMax         = $currencyFormatter->formatCurrency($amountMax, 'EUR');

                    $this->addFlash('bid_not_eligible_reason', $translator->transChoice('project-detail_bid-not-eligible-reason-' . $reason, 0, ['%amountRest%' => $amountRest, '%amountMax%' => $amountMax]));
                }
            } elseif ('bids-cip-validation-needed' === $exception->getMessage()) {
                $request->getSession()->set('cipBid', ['amount' => $bidAmount, 'rate' => $rate, 'project' => $project->id_project]);
                return $this->redirectToRoute('cip_bid');
            } else {
                $request->getSession()->set('bidResult', ['error' => true, 'message' => $translator->trans('project-detail_side-bar-' . $exception->getMessage())]);
            }
        }

        return $this->redirectToRoute('project_detail', ['projectSlug' => $project->slug]);
    }

    /**
     * @Route("/projects/bids/{projectId}/{rate}", requirements={"projectId" = "^\d+$", "rate" = "^(?:\d+|\d*\.\d+)$"}, name="bids_on_project")
     * @Method({"POST"})
     */
    public function bidsListAction($projectId, $rate, Request $request)
    {
        if (false === $request->isXMLHttpRequest()) {
            return new Response('not an ajax request');
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var MemcacheCachePool $oCachePool */
        $oCachePool  = $this->get('memcache.default');
        $oCachedItem = $oCachePool->getItem(\bids::CACHE_KEY_PROJECT_BIDS . '_' . $projectId . '_' . $rate);

        $template = [];

        if (true === $oCachedItem->isHit()) {
            $template['bids'] = $oCachedItem->get();
        } else {
            /** @var \bids $bidEntity */
            $bidEntity = $entityManager->getRepository('bids');

            $bids = $bidEntity->select('id_project = ' . $projectId . ' AND rate LIKE ' . $rate, 'ordre ASC');
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

        /** @var BaseUser $user */
        $user = $this->getUser();

        $template['canSeeAutobid'] = false;

        if ($user instanceof UserLender) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AutoBidSettingsManager $oAutoBidSettingsManager */
            $autoBidSettingsManager = $this->get('unilend.service.autobid_settings_manager');
            /** @var \lenders_accounts $lenderAccount */
            $lenderAccount = $entityManager->getRepository('lenders_accounts');
            $lenderAccount->get($user->getClientId(), 'id_client_owner');

            $template['canSeeAutobid'] = $autoBidSettingsManager->isQualified($lenderAccount);

            array_walk($template['bids'], function(&$bid) use ($lenderAccount) {
                if ($bid['lenderId'] == $lenderAccount->id_lender_account) {
                    $bid['userInvolved'] = true;
                }
            });
        }

        return $this->render('partials/components/project-detail/bids-list-detail.html.twig', $template);
    }

    /**
     * @Route("/projects/export/income/{projectId}", requirements={"projectId" = "^\d+$"}, name="export_income_statement")
     */
    public function exportIncomeStatementAction($projectId)
    {
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

        if (false === $project->get($projectId, 'id_project')
            || $project->display != \projects::DISPLAY_PROJECT_ON
            || false === $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
            || false === $this->get('security.authorization_checker')->isGranted('ROLE_LENDER')
        ) {
            return new RedirectResponse('/');
        }

        /** @var \dates $dates */
        $dates = Loader::loadLib('dates');
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        $translations = $translationManager->getAllTranslationsForSection('preteur-projets'); //TODO replace by new translations once they are done

        /** @var \companies $oCompany */
        $oCompany = $this->get('unilend.service.entity_manager')->getRepository('companies');
        $oCompany->get($project->id_company, 'id_company');

        /** @var \companies_bilans $oAnnualAccounts */
        $oAnnualAccounts    = $this->get('unilend.service.entity_manager')->getRepository('companies_bilans');
        $aAnnualAccounts    = $oAnnualAccounts->select('id_company = "' . $oCompany->id_company . '" AND cloture_exercice_fiscal <= (SELECT cloture_exercice_fiscal FROM companies_bilans WHERE id_bilan = ' . $project->id_dernier_bilan . ')', 'cloture_exercice_fiscal DESC', 0, 3);

        /** @var \settings $oSetting */
        $oSetting = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $oSetting->get('Entreprises fundés au passage du risque lot 1', 'type');
        $aFundedCompanies     = explode(',', $oSetting->value);
        $bPreviousRiskProject = in_array($oCompany->id_company, $aFundedCompanies);


        $iRow         = 1;
        /** @var \PHPExcel $oDocument */
        $oDocument    = new \PHPExcel();
        $oActiveSheet = $oDocument->setActiveSheetIndex(0);
        $oActiveSheet->setCellValueByColumnAndRow(0, $iRow, 'Date de clôture');
        $oActiveSheet->setCellValueByColumnAndRow(1, $iRow, $dates->formatDate($aAnnualAccounts[0]['cloture_exercice_fiscal'], 'd/m/Y'));
        $oActiveSheet->setCellValueByColumnAndRow(2, $iRow, $dates->formatDate($aAnnualAccounts[1]['cloture_exercice_fiscal'], 'd/m/Y'));
        $oActiveSheet->setCellValueByColumnAndRow(3, $iRow, $dates->formatDate($aAnnualAccounts[2]['cloture_exercice_fiscal'], 'd/m/Y'));
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, 'Durée de l\'exercice');
        $oActiveSheet->setCellValueByColumnAndRow(1, $iRow, str_replace('[DURATION]', $aAnnualAccounts[0]['duree_exercice_fiscal'], $translations['annual-accounts-duration-months']));
        $oActiveSheet->setCellValueByColumnAndRow(2, $iRow, str_replace('[DURATION]', $aAnnualAccounts[1]['duree_exercice_fiscal'], $translations['annual-accounts-duration-months']));
        $oActiveSheet->setCellValueByColumnAndRow(3, $iRow, str_replace('[DURATION]', $aAnnualAccounts[2]['duree_exercice_fiscal'], $translations['annual-accounts-duration-months']));
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['compte-de-resultats']);
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['chiffe-daffaires']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['ca']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['resultat-brut-dexploitation']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['resultat_brute_exploitation']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['resultat-dexploitation']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['resultat_exploitation']);
        }
        if (false === $bPreviousRiskProject) {
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['resultat-financier']);
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['resultat_financier']);
            }
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['produit-exceptionnel']);
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['produit_exceptionnel']);
            }
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['charges-exceptionnelles']);
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['charges_exceptionnelles']);
            }
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['resultat-exceptionnel']);
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['resultat_exceptionnel']);
            }
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['resultat-net']);
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['resultat_net']);
            }
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['investissements']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAnnualAccounts[$i]['investissements']);
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
     * @Route("/projects/export/balance/{projectId}", requirements={"projectId" = "^\d+$"}, name="export_balance_sheet")
     */
    public function exportBalanceSheetAction($projectId, Request $request)
    {
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

        if (false === $project->get($projectId, 'id_project')
            || $project->display != \projects::DISPLAY_PROJECT_ON
            || false === $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')
            || false === $this->get('security.authorization_checker')->isGranted('ROLE_LENDER')
        ) {
            return new RedirectResponse('/');
        }

        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        $translations = $translationManager->getAllTranslationsForSection('preteur-projets'); //TODO replace by new translations once they are done

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
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['bilan']);
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['actif']);
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['immobilisations-corporelles']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['immobilisations_corporelles']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['immobilisations-incorporelles']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['immobilisations_incorporelles']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['immobilisations-financieres']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['immobilisations_financieres']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['stocks']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['stocks']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['creances-clients']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['creances_clients']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['disponibilites']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['disponibilites']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['valeurs-mobilieres-de-placement']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['valeurs_mobilieres_de_placement']);
        }
        if (false === $bPreviousRiskProject && ($aAssetsDebts[0]['comptes_regularisation_actif'] != 0 || $aAssetsDebts[1]['comptes_regularisation_actif'] != 0 || $aAssetsDebts[2]['comptes_regularisation_actif'] != 0)) {
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['comptes-regularisation']);
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['comptes_regularisation_actif']);
            }
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['total-bilan-actifs']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['immobilisations_corporelles'] + $aAssetsDebts[$i]['immobilisations_incorporelles'] + $aAssetsDebts[$i]['immobilisations_financieres'] + $aAssetsDebts[$i]['stocks'] + $aAssetsDebts[$i]['creances_clients'] + $aAssetsDebts[$i]['disponibilites'] + $aAssetsDebts[$i]['valeurs_mobilieres_de_placement'] + $aAssetsDebts[$i]['comptes_regularisation_actif']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['passif']);
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['capitaux-propres']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['capitaux_propres']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['provisions-pour-risques-charges']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['provisions_pour_risques_et_charges']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['amortissement-sur-immo']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['amortissement_sur_immo']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['dettes-financieres']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['dettes_financieres']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['dettes-fournisseurs']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['dettes_fournisseurs']);
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['autres-dettes']);
        for ($i = 0; $i < 3; $i++) {
            $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['autres_dettes']);
        }
        if (false === $bPreviousRiskProject && ($aAssetsDebts[0]['comptes_regularisation_passif'] != 0 || $aAssetsDebts[1]['comptes_regularisation_passif'] != 0 || $aAssetsDebts[2]['comptes_regularisation_passif'] != 0)) {
            $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['comptes-regularisation']);
            for ($i = 0; $i < 3; $i++) {
                $oActiveSheet->setCellValueByColumnAndRow($i + 1, $iRow, $aAssetsDebts[$i]['comptes_regularisation_passif']);
            }
        }
        $oActiveSheet->setCellValueByColumnAndRow(0, ++$iRow, $translations['total-bilan-passifs']);
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
     * @Route("/projects/export/bids/{projectId}", requirements={"projectId" = "^\d+$"}, name="export_bids")
     */
    public function exportBidsAction($projectId)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');

        if ($project->get($projectId, 'id_project')) {
            /** @var TranslationManager $translationManager */
            $translationManager = $this->get('unilend.service.translation_manager');
            $translations = $translationManager->getAllTranslationsForSection('preteur-projets'); //TODO replace by new translations once they are done

            if ($project->status == \projects_status::EN_FUNDING) {
                ob_start();
                echo "\xEF\xBB\xBF";
                echo '"N°";"' . $translations['taux-dinteret'] . '";"' . $translations['montant'] . '";"' . $translations['statuts'] . '"' . PHP_EOL;

                /** @var \bids $bids */
                $bids = $entityManager->getRepository('bids');

                $offset    = 0;
                $limit     = 1000;
                $bidStatus = [
                    \bids::STATUS_BID_PENDING  => $translations['enchere-en-cours'],
                    \bids::STATUS_BID_ACCEPTED => $translations['enchere-ok'],
                    \bids::STATUS_BID_REJECTED => $translations['enchere-ko']
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

        $pageTitle       = $translator->trans('seo_project-detail-title', $translationParams);
        $pageDescription = $translator->trans('seo_project-detail-description', $translationParams);

        $seoPage->setTitle($pageTitle)
            ->addMeta('name', 'description', $pageDescription);
    }
}
