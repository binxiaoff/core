<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;
use Unilend\core\Loader;

class LenderOperationsController extends Controller
{
    const LAST_OPERATION_DATE = '2013-01-01';
    /**
     * This is a fictive transaction type,
     * it will be used only in indexage_vos_operaitons in order to get single repayment line with total of capital and interests repayment amount
     */
    const TYPE_REPAYMENT_TRANSACTION = 5;

    /**
     * @param Request $request
     * @return Response
     * @Route("/operations", name="lender_operations")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function indexAction(Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \projects_status $projectStatus */
        $projectStatus = $entityManager->getRepository('projects_status');
        /** @var \indexage_vos_operations $lenderOperationsIndex */
        $lenderOperationsIndex = $entityManager->getRepository('indexage_vos_operations');
        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');

        $client->get($this->getUser()->getClientId());
        $lender->get($client->id_client, 'id_client_owner');
        $this->lenderOperationIndexing($lenderOperationsIndex, $lender);

        $lenderOperations       = $lenderOperationsIndex->getLenderOperations([], $this->getUser()->getClientId(), date('Y-m-d', strtotime('-1 month')), date('Y-m-d'));
        $projectsFundedByLender = $lenderOperationsIndex->get_liste_libelle_projet('id_client = ' . $this->getUser()->getClientId() . ' AND DATE(date_operation) >= "' . date('Y-m-d', strtotime('-1 month')) . '" AND DATE(date_operation) <= "' . date('Y-m-d') . '"');

        unset($_SESSION['filtre_vos_operations']);
        unset($_SESSION['id_last_action']);

        $_SESSION['filtre_vos_operations']['start']          = date('d/m/Y', strtotime('-1 month'));
        $_SESSION['filtre_vos_operations']['end']            = date('d/m/Y');
        $_SESSION['filtre_vos_operations']['slide']          = 1;
        $_SESSION['filtre_vos_operations']['year']           = date('Y');
        $_SESSION['filtre_vos_operations']['operation']      = 1;
        $_SESSION['filtre_vos_operations']['project']        = null;
        $_SESSION['filtre_vos_operations']['id_last_action'] = 'operation';
        $_SESSION['filtre_vos_operations']['order']          = 'date_operation DESC, id_transaction DESC';
        $_SESSION['filtre_vos_operations']['type']           = '';
        $_SESSION['filtre_vos_operations']['id_client']      = $client->id_client;

        $loans = $this->commonLoans($request, $lender);

        return $this->render(
            '/pages/lender_operations/layout.html.twig',
            [
                'clientId'               => $lender->id_client_owner,
                'hash'                   => $this->getUser()->getHash(),
                'lenderOperations'       => $lenderOperations,
                'projectsFundedByLender' => $projectsFundedByLender,
                'detailedOperations'     => [self::TYPE_REPAYMENT_TRANSACTION],
                'loansStatusFilter'      => $projectStatus->select('status >= ' . \projects_status::REMBOURSEMENT, 'status ASC'),
                'firstLoanYear'          => $entityManager->getRepository('loans')->getFirstLoanYear($lender->id_lender_account),
                'lenderLoans'            => $loans['lenderLoans'],
                'loanStatus'             => $loans['loanStatus'],
                'seriesData'             => $loans['seriesData'],
                'repaidCapitalLabel'     => $translationManager->selectTranslation('lender-operations', 'operations-table-repaid-capital-amount-collapse-details'),
                'repaidInterestsLabel'   => $translationManager->selectTranslation('lender-operations', 'operations-table-repaid-interests-amount-collapse-details'),
                'currentFilters'         => $request->request->all()
            ]
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/operations/filterLoans", name="filter_loans")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function filterLoansAction(Request $request)
    {
        /** @var \lenders_accounts $lender */
        $lender = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        /** @var \projects_status $projectStatus */
        $projectStatus = $this->get('unilend.service.entity_manager')->getRepository('projects_status');

        $lender->get($this->getUser()->getClientId(), 'id_client_owner');
        $loans = $this->commonLoans($request, $lender);

        return $this->json(
            [
                'target'   => 'loans',
                'template' => $this->render('/pages/lender_operations/my_loans.html.twig',
                    [
                        'clientId'          => $lender->id_client_owner,
                        'hash'              => $this->getUser()->getHash(),
                        'loansStatusFilter' => $projectStatus->select('status >= ' . \projects_status::REMBOURSEMENT, 'status ASC'),
                        'firstLoanYear'     => $this->get('unilend.service.entity_manager')->getRepository('loans')->getFirstLoanYear($lender->id_lender_account),
                        'lenderLoans'       => $loans['lenderLoans'],
                        'loanStatus'        => $loans['loanStatus'],
                        'seriesData'        => $loans['seriesData'],
                        'currentFilters'    => $request->request->all()
                    ]
                )->getContent()
            ]
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/operations/filterOperations", name="filter_operations")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function filterOperationsAction(Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        $_SESSION['filtre_vos_operations']['start']          = $request->request->get('filter')['start'];
        $_SESSION['filtre_vos_operations']['end']            = $request->request->get('filter')['end'];
        $_SESSION['filtre_vos_operations']['slide']          = $request->request->get('filter')['slide'];
        $_SESSION['filtre_vos_operations']['year']           = $request->request->get('filter')['year'];
        $_SESSION['filtre_vos_operations']['operation']      = $request->request->get('filter')['operation'];
        $_SESSION['filtre_vos_operations']['project']        = $request->request->get('filter')['project'];
        $_SESSION['filtre_vos_operations']['id_last_action'] = $request->request->get('filter')['id_last_action'];
        $_SESSION['filtre_vos_operations']['order']          = $request->request->get('order', '');
        $_SESSION['filtre_vos_operations']['type']           = $request->request->get('type', '');
        $_SESSION['filtre_vos_operations']['id_client']      = $this->getUser()->getClientId();

        // tri start/end
        if (in_array($request->request->get('filter')['id_last_action'], array('start', 'end'))) {

            $tmpStartDate               = explode('/', $request->request->get('filter')['start']);
            $tmpEndDate                 = explode('/', $request->request->get('filter')['end']);
            $startTime                  = strtotime($tmpStartDate[2] . '-' . $tmpStartDate[1] . '-' . $tmpStartDate[0] . ' 00:00:00');    // date start
            $endTime                    = strtotime($tmpEndDate[2] . '-' . $tmpEndDate[1] . '-' . $tmpEndDate[0] . ' 00:00:00');            // date end
            $_SESSION['id_last_action'] = $request->request->get('filter')['id_last_action'];

        } elseif ($request->request->get('filter')['id_last_action'] == 'slide') {
            $numberOfMonths             = $request->request->get('filter')['slide'];
            $startTime                  = mktime(0, 0, 0, date("m") - $numberOfMonths, date("d"), date('Y')); // date start
            $endTime                    = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date end
            $_SESSION['id_last_action'] = $request->request->get('filter')['id_last_action'];
        } elseif ($request->request->get('filter')['id_last_action'] == 'year') {
            $year      = $request->request->get('filter')['year'];
            $startTime = mktime(0, 0, 0, 1, 1, $year);    // date start

            if (date('Y') == $year) {
                $endTime = mktime(0, 0, 0, date('m'), date('d'), $year);
            } // date end
            else {
                $endTime = mktime(0, 0, 0, 12, 31, $year);
            } // date end
            $_SESSION['id_last_action'] = $request->request->get('filter')['id_last_action'];
        } elseif (isset($_SESSION['id_last_action'])) {

            if (in_array($_SESSION['id_last_action'], array('start', 'end'))) {
                $tmpStartDate = explode('/', $request->request->get('filter')['start']);
                $tmpEndDate   = explode('/', $request->request->get('filter')['end']);
                $startTime    = strtotime($tmpStartDate[2] . '-' . $tmpStartDate[1] . '-' . $tmpStartDate[0] . ' 00:00:00');    // date start
                $endTime      = strtotime($tmpEndDate[2] . '-' . $tmpEndDate[1] . '-' . $tmpEndDate[0] . ' 00:00:00');            // date end
            } elseif ($_SESSION['id_last_action'] == 'slide') {
                $numberOfMonths = $request->request->get('filter')['slide'];
                $startTime      = mktime(0, 0, 0, date("m") - $numberOfMonths, date("d"), date('Y')); // date start
                $endTime        = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date end
            } elseif ($_SESSION['id_last_action'] == 'year') {
                $year      = $request->request->get('filter')['year'];
                $startTime = mktime(0, 0, 0, 1, 1, $year);    // date start
                $endTime   = mktime(0, 0, 0, 12, 31, $year); // date end
            }
        } else {
            $startTime = strtotime('-1 month'); // date start
            $endTime   = time();    // date end
        }

        $transactionTypeListForCombo = array(
            1 => array(
                \transactions_types::TYPE_LENDER_SUBSCRIPTION,
                \transactions_types::TYPE_LENDER_LOAN,
                \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
                \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
                self::TYPE_REPAYMENT_TRANSACTION,
                \transactions_types::TYPE_DIRECT_DEBIT,
                \transactions_types::TYPE_LENDER_WITHDRAWAL,
                \transactions_types::TYPE_WELCOME_OFFER,
                \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION,
                \transactions_types::TYPE_SPONSORSHIP_SPONSORED_REWARD,
                \transactions_types::TYPE_SPONSORSHIP_SPONSOR_REWARD,
                \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT,
                \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT
            ),
            2 => array(
                \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
                \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
                \transactions_types::TYPE_DIRECT_DEBIT,
                \transactions_types::TYPE_LENDER_WITHDRAWAL
            ),
            3 => array(
                \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
                \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
                \transactions_types::TYPE_DIRECT_DEBIT
            ),
            4 => array(\transactions_types::TYPE_LENDER_WITHDRAWAL),
            5 => array(\transactions_types::TYPE_LENDER_LOAN),
            6 => array(
                self::TYPE_REPAYMENT_TRANSACTION,
                \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT,
                \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT
            )
        );

        $transactionListFilter = $transactionTypeListForCombo[$request->request->get('filter', 1)['operation']];

        if (false === empty($request->request->get('filter')['project'])) {
            $projectFilter = $request->request->get('filter')['project'];
        } else {
            $projectFilter = null;
        }

        $columnOrder = $request->request->get('type', '');
        $orderBy     = $request->request->get('order', '');

        if ($columnOrder == 'order_operations') {
            if ($orderBy == 'asc') {
                $order = ' type_transaction ASC, id_transaction ASC';
            } else {
                $order = ' type_transaction DESC, id_transaction DESC';
            }
        } elseif ($columnOrder == 'order_projects') {
            if ($orderBy == 'asc') {
                $order = ' libelle_projet ASC , id_transaction ASC';
            } else {
                $order = ' libelle_projet DESC , id_transaction DESC';
            }
        } elseif ($columnOrder == 'order_date') {
            if ($orderBy == 'asc') {
                $order = ' date_operation ASC, id_transaction ASC';
            } else {
                $order = ' date_operation DESC, id_transaction DESC';
            }
        } elseif ($columnOrder == 'order_montant') {
            if ($orderBy == 'asc') {
                $order = ' montant_operation ASC, id_transaction ASC';
            } else {
                $order = ' montant_operation DESC, id_transaction DESC';
            }
        } elseif ($columnOrder == 'order_bdc') {
            if ($orderBy == 'asc') {
                $order = ' ABS(bdc) ASC, id_transaction ASC';
            } else {
                $order = ' ABS(bdc) DESC, id_transaction DESC';
            }
        } else {
            $order = 'date_operation DESC, id_transaction DESC';
        }
        /** @var \indexage_vos_operations $lenderOperationsIndex */
        $lenderOperationsIndex = $entityManager->getRepository('indexage_vos_operations');

        $lenderOperations       = $lenderOperationsIndex->getLenderOperations($transactionListFilter, $this->getUser()->getClientId(), date('Y-m-d', $startTime), date('Y-m-d', $endTime), $projectFilter, $order);
        $projectsFundedByLender = $lenderOperationsIndex->get_liste_libelle_projet('type_transaction IN (' . implode(',', $transactionListFilter) . ') AND id_client = ' . $this->getUser()->getClientId() . ' AND LEFT(date_operation,10) >= "' . date('Y-m-d', $startTime) . '" AND LEFT(date_operation,10) <= "' . date('Y-m-d', $endTime) . '"');

        $filters                    = $request->request->all();
        $filters['filter']['start'] = date('d/m/Y', $startTime);
        $filters['filter']['end']   = date('d/m/Y', $endTime);

        return $this->json(
            [
                'target'   => 'operations',
                'template' => $this->render('/pages/lender_operations/my_operations.html.twig',
                    [
                        'clientId'               => $this->getUser()->getClientId(),
                        'hash'                   => $this->getUser()->getHash(),
                        'detailedOperations'     => [self::TYPE_REPAYMENT_TRANSACTION],
                        'projectsFundedByLender' => $projectsFundedByLender,
                        'lenderOperations'       => $lenderOperations,
                        'repaidCapitalLabel'     => $translationManager->selectTranslation('lender-operations', 'operations-table-repaid-capital-amount-collapse-details'),
                        'repaidInterestsLabel'   => $translationManager->selectTranslation('lender-operations', 'operations-table-repaid-interests-amount-collapse-details'),
                        'currentFilters'         => $request->request->all()
                    ])->getContent()
            ]
        );
    }

    /**
     * @return Response
     * @Route("/operations/exportOperationsCsv", name="export_operations_csv")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function exportOperationsCsvAction()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \tax $tax */
        $tax = $entityManager->getRepository('tax');
        /** @var \tax_type $taxType */
        $taxType = $entityManager->getRepository('tax_type');
        /** @var \tax_type $aTaxType */
        $aTaxType = $taxType->select('id_tax_type !=' . \tax_type::TYPE_VAT);
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var \indexage_vos_operations $lenderIndexedOperations */
        $lenderIndexedOperations = $entityManager->getRepository('indexage_vos_operations');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        $post_debut            = $_SESSION['filtre_vos_operations']['start'];
        $post_fin              = $_SESSION['filtre_vos_operations']['end'];
        $post_nbMois           = $_SESSION['filtre_vos_operations']['slide'];
        $post_annee            = $_SESSION['filtre_vos_operations']['year'];
        $post_tri_type_transac = $_SESSION['filtre_vos_operations']['operation'];
        $projectIdFilter       = (false === empty($_SESSION['filtre_vos_operations']['project'])) ? $_SESSION['filtre_vos_operations']['project'] : null;
        $post_id_last_action   = $_SESSION['filtre_vos_operations']['id_last_action'];
        $post_id_client        = $_SESSION['filtre_vos_operations']['id_client'];

        $client->get($post_id_client, 'id_client');

        // tri start/end
        if (isset($post_id_last_action) && in_array($post_id_last_action, array('start', 'end'))) {
            $tmpStartDate = explode('/', $post_debut);
            $tmpEndDate   = explode('/', $post_fin);

            $startTime = strtotime($tmpStartDate[2] . '-' . $tmpStartDate[1] . '-' . $tmpStartDate[0] . ' 00:00:00');    // date start
            $endTime   = strtotime($tmpEndDate[2] . '-' . $tmpEndDate[1] . '-' . $tmpEndDate[0] . ' 00:00:00');            // date end

            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $post_id_last_action;

        } elseif (isset($post_id_last_action) && $post_id_last_action == 'slide') {// NB mois
            $numberOfMonths = $post_nbMois;
            $startTime      = mktime(0, 0, 0, date("m") - $numberOfMonths, date("d"), date('Y')); // date start
            $endTime        = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date end
            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $post_id_last_action;
        } elseif (isset($post_id_last_action) && $post_id_last_action == 'year') {// Annee
            $year      = $post_annee;
            $startTime = mktime(0, 0, 0, 1, 1, $year);    // date start
            $endTime   = mktime(0, 0, 0, 12, 31, $year); // date end
            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $post_id_last_action;
        } elseif (isset($_SESSION['id_last_action'])) {// si on a une session
            if (in_array($_SESSION['id_last_action'], array('start', 'end'))) {
                $tmpStartDate = explode('/', $post_debut);
                $tmpEndDate   = explode('/', $post_fin);
                $startTime    = strtotime($tmpStartDate[2] . '-' . $tmpStartDate[1] . '-' . $tmpStartDate[0] . ' 00:00:00');    // date start
                $endTime      = strtotime($tmpEndDate[2] . '-' . $tmpEndDate[1] . '-' . $tmpEndDate[0] . ' 00:00:00');            // date end
            } elseif ($_SESSION['id_last_action'] == 'slide') {
                $numberOfMonths = $post_nbMois;
                $startTime      = mktime(0, 0, 0, date("m") - $numberOfMonths, date("d"), date('Y')); // date start
                $endTime        = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date end
            } elseif ($_SESSION['id_last_action'] == 'year') {
                $year      = $post_annee;
                $startTime = mktime(0, 0, 0, 1, 1, $year);    // date start
                $endTime   = mktime(0, 0, 0, 12, 31, $year); // date end
            }
        } else {// Par defaut (on se base sur le 1M)
            if (isset($post_debut) && isset($post_fin)) {
                $tmpStartDate = explode('/', $post_debut);
                $tmpEndDate   = explode('/', $post_fin);
                $startTime    = strtotime($tmpStartDate[2] . '-' . $tmpStartDate[1] . '-' . $tmpStartDate[0] . ' 00:00:00');    // date start
                $endTime      = strtotime($tmpEndDate[2] . '-' . $tmpEndDate[1] . '-' . $tmpEndDate[0] . ' 00:00:00');            // date end
            } else {
                $startTime = mktime(0, 0, 0, date("m") - 1, 1, date('Y')); // date start
                $endTime   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date end
            }
        }

        $transactionTypeList = array(
            1 => array(
                \transactions_types::TYPE_LENDER_SUBSCRIPTION,
                \transactions_types::TYPE_LENDER_LOAN,
                \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
                \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
                self::TYPE_REPAYMENT_TRANSACTION,
                \transactions_types::TYPE_DIRECT_DEBIT,
                \transactions_types::TYPE_LENDER_WITHDRAWAL,
                \transactions_types::TYPE_WELCOME_OFFER,
                \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION,
                \transactions_types::TYPE_SPONSORSHIP_SPONSORED_REWARD,
                \transactions_types::TYPE_SPONSORSHIP_SPONSOR_REWARD,
                \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT,
                \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT
            ),
            2 => array(
                \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
                \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
                \transactions_types::TYPE_DIRECT_DEBIT,
                \transactions_types::TYPE_LENDER_WITHDRAWAL
            ),
            3 => array(
                \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
                \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
                \transactions_types::TYPE_DIRECT_DEBIT
            ),
            4 => array(\transactions_types::TYPE_LENDER_WITHDRAWAL),
            5 => array(\transactions_types::TYPE_LENDER_LOAN),
            6 => array(
                self::TYPE_REPAYMENT_TRANSACTION,
                \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT,
                \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT
            )
        );

        if (isset($post_tri_type_transac)) {
            $transactionListFilter = $transactionTypeList[$post_tri_type_transac];
        } else {
            $transactionListFilter = $transactionTypeList[1];
        }

        $order      = 'date_operation DESC, id_transaction DESC';
        $operations = $lenderIndexedOperations->getLenderOperations($transactionListFilter, $client->id_client, date('Y-m-d', $startTime), date('Y-m-d', $endTime), $projectIdFilter, $order);

        $content = '
        <meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8"/>
        <table border=1>
            <tr>
                <th>' . $translator->trans('lender-operations_operation') . '</th>
                <th>' . $translator->trans('lender-operations_loans-table-contract-column') . '</th>
                <th> ID ' . $translator->trans('lender-operations_project') . '</th>
                <th>' . $translator->trans('lender-operations_project') . '</th>
                <th>' . $translator->trans('lender-operations_date') . '</th>
                <th>' . $translator->trans('lender-operations_amount') . '</th>
                <th>' . $translator->trans('lender-operations_repaid-capital-amount') . '</th>
                <th>' . $translator->trans('lender-operations_repaid-interests-amount') . '</th>';
        foreach ($aTaxType as $aType) {
            $content .= '<th>' . $aType['name'] . '</th>';
        }
        $content .= '<th>' . $translator->trans('lender-operations_account-balance') . '</th>
                <td></td>
            </tr>';

        $asterix_on    = false;
        $aTranslations = array(
            \transactions_types::TYPE_LENDER_SUBSCRIPTION          => $translator->trans('preteur-operations-vos-operations_depot-de-fonds'),
            \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT    => $translator->trans('preteur-operations-vos-operations_depot-de-fonds'),
            \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT  => $translator->trans('preteur-operations-vos-operations_depot-de-fonds'),
            \transactions_types::TYPE_LENDER_WITHDRAWAL            => $translator->trans('preteur-operations-vos-operations_retrait-dargents'),
            \transactions_types::TYPE_WELCOME_OFFER                => $translator->trans('preteur-operations-vos-operations_offre-de-bienvenue'),
            \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION   => $translator->trans('preteur-operations-vos-operations_retrait-offre'),
            \transactions_types::TYPE_SPONSORSHIP_SPONSORED_REWARD => $translator->trans('preteur-operations-vos-operations_gain-filleul'),
            \transactions_types::TYPE_SPONSORSHIP_SPONSOR_REWARD   => $translator->trans('preteur-operations-vos-operations_gain-parrain')
        );

        foreach ($operations as $t) {
            if ($t['montant_operation'] >= 0) {
                $couleur = ' style="color:#40b34f;"';
            } else {
                $couleur = ' style="color:red;"';
            }
            $sProjectId = $t['id_projet'] == 0 ? '' : $t['id_projet'];

            if (in_array($t['type_transaction'], array(self::TYPE_REPAYMENT_TRANSACTION, \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT, \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT))) {

                foreach ($aTaxType as $aType) {
                    $aTax[$aType['id_tax_type']]['amount'] = 0;
                }

                if (self::TYPE_REPAYMENT_TRANSACTION == $t['type_transaction']) {
                    $aTax = $tax->getTaxListByRepaymentId($t['id_echeancier']);
                }

                $content .= '
                    <tr>
                        <td>' . $t['libelle_operation'] . '</td>
                        <td>' . $t['bdc'] . '</td>
                        <td>' . $sProjectId . '</td>
                        <td>' . $t['libelle_projet'] . '</td>
                        <td>' . date('d-m-Y', strtotime($t['date_operation'])) . '</td>
                        <td' . $couleur . '>' . $ficelle->formatNumber($t['montant_operation']) . '</td>
                        <td>' . $ficelle->formatNumber(bcdiv($t['montant_capital'], 100, 2)) . '</td>
                        <td>' . $ficelle->formatNumber(bcdiv($t['montant_interet'], 100, 2)) . '</td>';
                foreach ($aTaxType as $aType) {
                    $content .= '<td>';

                    if (isset($aTax[$aType['id_tax_type']])) {
                        $content .= $ficelle->formatNumber($aTax[$aType['id_tax_type']]['amount'] / 100, 2);
                    } else {
                        $content .= '0';
                    }
                    $content .= '</td>';
                }
                $content .= '
                        <td>' . $ficelle->formatNumber(bcdiv($t['solde'], 100, 2)) . '</td>
                        <td></td>
                    </tr>';

            } elseif (in_array($t['type_transaction'], array_keys($aTranslations))) {

                $array_type_transactions = [
                    1  => $translator->trans('lender-operations_operation-label-money-deposit'),
                    2  => [
                        1 => $translator->trans('lender-operations_operation-label-current-offer'),
                        2 => $translator->trans('lender-operations_operation-label-rejected-offer'),
                        3 => $translator->trans('lender-operations_operation-label-accepted-offer')
                    ],
                    3  => $translator->trans('lender-operations_operation-label-money-deposit'),
                    4  => $translator->trans('lender-operations_operation-label-money-deposit'),
                    5  => [
                        1 => $translator->trans('lender-operations_operation-label-refund'),
                        2 => $translator->trans('lender-operations_operation-label-recovery')
                    ],
                    7  => $translator->trans('lender-operations_operation-label-money-deposit'),
                    8  => $translator->trans('lender-operations_operation-label-money-withdrawal'),
                    16 => $translator->trans('lender-operations_operation-label-welcome-offer'),
                    17 => $translator->trans('lender-operations_operation-label-welcome-offer-withdrawal'),
                    19 => $translator->trans('lender-operations_operation-label-godson-gain'),
                    20 => $translator->trans('lender-operations_operation-label-godfather-gain'),
                    22 => $translator->trans('lender-operations_operation-label-anticipated-repayment'),
                    23 => $translator->trans('lender-operations_operation-label-anticipated-repayment'),
                    26 => $translator->trans('lender-operations_operation-label-lender-recovery')
                ];

                if (isset($array_type_transactions[$t['type_transaction']])) {
                    $t['libelle_operation'] = $array_type_transactions[$t['type_transaction']];
                } else {
                    $t['libelle_operation'] = '';
                }

                if ($t['type_transaction'] == \transactions_types::TYPE_LENDER_WITHDRAWAL && $t['montant_operation'] > 0) {
                    $type = "Annulation retrait des fonds - compte bancaire clos";
                } else {
                    $type = $t['libelle_operation'];
                }
                $content .= '
                    <tr>
                        <td>' . $type . '</td>
                        <td></td>
                        <td>' . $sProjectId . '</td>
                        <td></td>
                        <td>' . date('d-m-Y', strtotime($t['date_operation'])) . '</td>
                        <td' . $couleur . '>' . $ficelle->formatNumber($t['montant_operation'] / 100) . '</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td> 
                        <td>' . $ficelle->formatNumber(bcdiv($t['solde'], 100, 2)) . '</td>
                        <td></td>
                    </tr>
                    ';

            } elseif ($t['type_transaction'] == \transactions_types::TYPE_LENDER_LOAN) { // ongoing Offer

                //asterix pour les offres acceptees
                $asterix       = "";
                $offre_accepte = false;
                if ($t['libelle_operation'] == $translator->trans('lender-operations_operation-label-accepted-offer')) {
                    $asterix       = " *";
                    $offre_accepte = true;
                    $asterix_on    = true;
                }
                $content .= '
                    <tr>
                        <td>' . $t['libelle_operation'] . '</td>
                        <td>' . $t['bdc'] . '</td>
                        <td>' . $sProjectId . '</td>
                        <td>' . $t['libelle_projet'] . '</td>
                        <td>' . date('d-m-Y', strtotime($t['date_operation'])) . '</td>
                        <td' . (! $offre_accepte ? $couleur : '') . '>' . $ficelle->formatNumber($t['montant_operation']) . '</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>' . $ficelle->formatNumber($t['solde'] / 100) . '</td>
                        <td>' . $asterix . '</td>
                    </tr>
                   ';
            }
        }
        $content .= '
        </table>';

        if ($asterix_on) {
            $content .= '
            <div>* ' . $translator->trans('lender-operations_csv-export-asterisk-accepted-offer-specific-mention') . '</div>';

        }
        return new Response($content, Response::HTTP_OK, [
            'Content-type'        => 'application/force-download; charset=utf-8',
            'Expires'             => 0,
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'content-disposition' => "attachment;filename=" . 'operations_' . date('Y-m-d_H:i:s') . ".xls"
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/operations/exportLoansCsv", name="export_loans_csv")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function exportLoansCsvAction(Request $request)
    {
        /** @var \lenders_accounts $lender */
        $lender = $this->get('unilend.service.entity_manager')->getRepository('lenders_accounts');
        $lender->get($this->getUser()->getClientId(), 'id_client_owner');
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->get('unilend.service.entity_manager')->getRepository('echeanciers');
        $loans             = $this->commonLoans($request, $lender);

        \PHPExcel_Settings::setCacheStorageMethod(
            \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
            array('memoryCacheSize' => '2048MB', 'cacheTime' => 1200)
        );

        $oDocument    = new \PHPExcel();
        $oActiveSheet = $oDocument->setActiveSheetIndex(0);

        $oActiveSheet->setCellValue('A1', 'Projet');
        $oActiveSheet->setCellValue('B1', 'Numéro de projet');
        $oActiveSheet->setCellValue('C1', 'Montant');
        $oActiveSheet->setCellValue('D1', 'Statut');
        $oActiveSheet->setCellValue('E1', 'Taux d\'intérêts');
        $oActiveSheet->setCellValue('F1', 'Premier remboursement');
        $oActiveSheet->setCellValue('G1', 'Prochain remboursement prévu');
        $oActiveSheet->setCellValue('H1', 'Date dernier remboursement');
        $oActiveSheet->setCellValue('I1', 'Capital perçu');
        $oActiveSheet->setCellValue('J1', 'Intérêts perçus');
        $oActiveSheet->setCellValue('K1', 'Capital restant dû');
        $oActiveSheet->setCellValue('L1', 'Note');

        foreach ($loans['lenderLoans'] as $iRowIndex => $aProjectLoans) {
            $oActiveSheet->setCellValue('A' . ($iRowIndex + 2), $aProjectLoans['name']);
            $oActiveSheet->setCellValue('B' . ($iRowIndex + 2), $aProjectLoans['id']);
            $oActiveSheet->setCellValue('C' . ($iRowIndex + 2), $aProjectLoans['amount']);
            $oActiveSheet->setCellValue('D' . ($iRowIndex + 2), $this->get('unilend.service.translation_manager')->selectTranslation('lender-operations', 'project-status-label-' . $aProjectLoans['project_status']));
            $oActiveSheet->setCellValue('E' . ($iRowIndex + 2), round($aProjectLoans['rate'], 1));
            $oActiveSheet->setCellValue('F' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['start_date'])));
            $oActiveSheet->setCellValue('G' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['next_payment_date'])));
            $oActiveSheet->setCellValue('H' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['end_date'])));
            $oActiveSheet->setCellValue('I' . ($iRowIndex + 2), $repaymentSchedule->getRepaidCapital(['id_lender' => $lender->id_lender_account, 'id_project' => $aProjectLoans['id']]));
            $oActiveSheet->setCellValue('J' . ($iRowIndex + 2), $repaymentSchedule->getRepaidInterests(['id_lender' => $lender->id_lender_account, 'id_project' => $aProjectLoans['id']]));
            $oActiveSheet->setCellValue('K' . ($iRowIndex + 2), $repaymentSchedule->getOwedCapital(['id_lender' => $lender->id_lender_account, 'id_project' => $aProjectLoans['id']]));

            $sRisk = isset($aProjectLoans['risk']) ? $aProjectLoans['risk'] : '';
            $sNote = $this->getProjectNote($sRisk);
            $oActiveSheet->setCellValue('L' . ($iRowIndex + 2), $sNote);
        }

        /** @var \PHPExcel_Writer_Excel5 $oWriter */
        $oWriter = \PHPExcel_IOFactory::createWriter($oDocument, 'Excel5');
        ob_start();
        $oWriter->save('php://output');
        $content = ob_get_clean();

        return new Response($content, Response::HTTP_OK, [
            'Content-type'        => 'application/force-download; charset=utf-8',
            'Expires'             => 0,
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'content-disposition' => "attachment;filename=" . 'prets_' . date('Y-m-d_H:i:s') . ".xls"
        ]);
    }

    /**
     * @param string $sRisk a letter that gives the risk value [A-H]
     * @return string
     */
    private function getProjectNote($sRisk)
    {
        switch ($sRisk) {
            case 'A':
                $sNote = '5';
                break;
            case 'B':
                $sNote = '4,5';
                break;
            case 'C':
                $sNote = '4';
                break;
            case 'D':
                $sNote = '3,5';
                break;
            case 'E':
                $sNote = '3';
                break;
            case 'F':
                $sNote = '2,5';
                break;
            case 'G':
                $sNote = '2';
                break;
            case 'H':
                $sNote = '1,5';
                break;
            default:
                $sNote = '';
        }
        return $sNote;
    }

    /**
     * @param \indexage_vos_operations $lenderOperationsIndex
     * @param \lenders_accounts $lender
     */
    private function lenderOperationIndexing(\indexage_vos_operations $lenderOperationsIndex, \lenders_accounts $lender)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \transactions $transaction */
        $transaction = $entityManager->getRepository('transactions');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        /** @var \lender_tax_exemption $taxExemption */
        $taxExemption = $entityManager->getRepository('lender_tax_exemption');

        $client->get($this->getUser()->getClientId());

        $array_type_transactions = array(
            \transactions_types::TYPE_LENDER_SUBSCRIPTION          => $translator->trans('lender-operations_operation-label-money-deposit'),
            \transactions_types::TYPE_LENDER_LOAN                  => array(
                1 => $translator->trans('lender-operations_operation-label-current-offer'),
                2 => $translator->trans('lender-operations_operation-label-rejected-offer'),
                3 => $translator->trans('lender-operations_operation-label-accepted-offer')
            ),
            \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT    => $translator->trans('lender-operations_operation-label-money-deposit'),
            \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT  => $translator->trans('lender-operations_operation-label-money-deposit'),
            \transactions_types::TYPE_DIRECT_DEBIT                 => $translator->trans('lender-operations_operation-label-money-deposit'),
            \transactions_types::TYPE_LENDER_WITHDRAWAL            => $translator->trans('lender-operations_operation-label-money-withdrawal'),
            \transactions_types::TYPE_WELCOME_OFFER                => $translator->trans('lender-operations_operation-label-welcome-offer'),
            \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION   => $translator->trans('lender-operations_operation-label-welcome-offer-withdrawal'),
            \transactions_types::TYPE_SPONSORSHIP_SPONSORED_REWARD => $translator->trans('lender-operations_operation-label-godson-gain'),
            \transactions_types::TYPE_SPONSORSHIP_SPONSOR_REWARD   => $translator->trans('lender-operations_operation-label-godfather-gain'),
            \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT => $translator->trans('lender-operations_operation-label-anticipated-repayment'),
            \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT    => $translator->trans('lender-operations_operation-label-lender-recovery')
        );

        $sLastOperation = $lenderOperationsIndex->getLastOperationDate($this->getUser()->getClientId());

        if (empty($sLastOperation)) {
            $date_debut_a_indexer = self::LAST_OPERATION_DATE;
        } else {
            $date_debut_a_indexer = substr($sLastOperation, 0, 10);
        }

        $operations = $transaction->getOperationsForIndexing($array_type_transactions, $date_debut_a_indexer, $this->getUser()->getClientId());

        foreach ($operations as $t) {
            if (0 == $lenderOperationsIndex->counter('id_transaction = ' . $t['id_transaction'] . ' AND libelle_operation = "' . $t['type_transaction_alpha'] . '"')) {

                $libelle_prelevements = $translator->trans('lender-operations_tax-and-social-deductions-label');
                if ($client->type == \clients::TYPE_PERSON || $client->type == \clients::TYPE_PERSON_FOREIGNER) {
                    if ($taxExemption->counter('id_lender = ' . $lender->id_lender_account . ' AND year = "' . substr($t['date_transaction'], 0, 4) . '"') > 0) {
                        $libelle_prelevements = $translator->trans('lender-operations_social-deductions-label');
                    }
                } else {
                    $libelle_prelevements = $this->get('translator')->trans('preteur-operations-vos-operations_retenues-a-la-source');
                }

                $lenderOperationsIndex->id_client           = $t['id_client'];
                $lenderOperationsIndex->id_transaction      = $t['id_transaction'];
                $lenderOperationsIndex->id_echeancier       = $t['id_echeancier'];
                $lenderOperationsIndex->id_projet           = $t['id_project'];
                $lenderOperationsIndex->type_transaction    = $t['type_transaction'];
                $lenderOperationsIndex->libelle_operation   = $t['type_transaction_alpha'];
                $lenderOperationsIndex->bdc                 = $t['bdc'];
                $lenderOperationsIndex->libelle_projet      = $t['title'];
                $lenderOperationsIndex->date_operation      = $t['date_tri'];
                $lenderOperationsIndex->solde               = $t['solde'];
                $lenderOperationsIndex->libelle_prelevement = $libelle_prelevements;
                $lenderOperationsIndex->montant_prelevement = $t['tax_amount'];

                if (self::TYPE_REPAYMENT_TRANSACTION == $t['type_transaction']) {
                    $lenderOperationsIndex->montant_operation = $t['capital'] + $t['interests'];
                } else {
                    $lenderOperationsIndex->montant_operation = $t['amount_operation'];
                }
                $lenderOperationsIndex->montant_capital = $t['capital'];
                $lenderOperationsIndex->montant_interet = $t['interests'] + $t['tax_amount'];
                $lenderOperationsIndex->create();
            }
        }
    }

    /**
     * @param Request $request
     * @param \lenders_accounts $lender
     * @return array
     */
    private function commonLoans(Request $request, \lenders_accounts $lender)
    {
        /** @var \loans $loanEntity */
        $loanEntity = $this->get('unilend.service.entity_manager')->getRepository('loans');
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

        $orderField     = $request->request->get('type', 'start');
        $orderDirection = strtoupper($request->request->get('order', 'ASC'));

        switch ($orderField) {
            case 'status':
                $orderField = 'status';
                $sOrderBy   = 'p.status ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'title':
                $orderField = 'title';
                $sOrderBy   = 'p.title ' . $orderDirection . ', debut DESC';
                break;
            case 'note':
                $orderField = 'note';
                $sOrderBy   = 'p.risk ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'amount':
                $orderField = 'amount';
                $sOrderBy   = 'amount ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'interest':
                $orderField = 'interest';
                $sOrderBy   = 'rate ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'next':
                $orderField = 'next';
                $sOrderBy   = 'next_echeance ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'end':
                $orderField = 'end';
                $sOrderBy   = 'fin ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'repayment':
                $orderField = 'repayment';
                $sOrderBy   = 'last_perceived_repayment ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'start':
            default:
                $orderField = 'start';
                $sOrderBy   = 'debut ' . $orderDirection . ', p.title ASC';
                break;
        }

        $projectsInDept = $project->getProjectsInDebt();
        $year           = empty($request->request->get('filter', null)['date']) ? null : $request->request->get('filter', null)['date'];
        $status         = empty($request->request->get('filter', null)['status']) ? null : $request->request->get('filter', null)['status'];
        $lenderLoans    = $loanEntity->getSumLoansByProject($lender->id_lender_account, $sOrderBy, $year, $status);
        $loanStatus     = [
            'no-problem'            => 0,
            'late-repayment'        => 0,
            'recovery'              => 0,
            'collective-proceeding' => 0,
            'default'               => 0,
            'refund-finished'       => 0,
        ];
        /** @var UserLender $user */
        $user               = $this->getUser();
        $lenderProjectLoans = [];

        foreach ($lenderLoans as $loanIndex => $aProjectLoans) {
            $loanData = [];
            /** @var \DateTime $startDateTime */
            $startDateTime = new \DateTime(date('Y-m-d'));
            /** @var \DateTime $endDateTime */
            $endDateTime = new \DateTime($aProjectLoans['fin']);
            /** @var \DateInterval $remainingDuration */
            $remainingDuration = $startDateTime->diff($endDateTime);

            $loanData['id']                       = $aProjectLoans['id_project'];
            $loanData['url']                      = $this->generateUrl('project_detail', ['projectSlug' => $aProjectLoans['slug']]);
            $loanData['name']                     = $aProjectLoans['title'];
            $loanData['rate']                     = round($aProjectLoans['rate'], 1);
            $loanData['risk']                     = $aProjectLoans['risk'];
            $loanData['amount']                   = round($aProjectLoans['amount']);
            $loanData['start_date']               = $aProjectLoans['debut'];
            $loanData['end_date']                 = $aProjectLoans['fin'];
            $loanData['next_payment_date']        = $aProjectLoans['next_echeance'];
            $loanData['monthly_repayment_amount'] = $aProjectLoans['monthly_repayment_amount'];
            $loanData['duration']                 = $remainingDuration->y * 12 + $remainingDuration->m;
            $loanData['status_change']            = $aProjectLoans['status_change'];
            $loanData['project_status']           = $aProjectLoans['project_status'];

            $lenderLoans[$loanIndex]['project_remaining_duration'] = $remainingDuration->y * 12 + $remainingDuration->m;

            switch ($aProjectLoans['project_status']) {
                case \projects_status::PROBLEME:
                case \projects_status::PROBLEME_J_X:
                    $lenderLoans[$loanIndex]['status_color'] = 'late';
                    $loanData['status']                      = 'late';

                    $lenderLoans[$loanIndex]['color'] = '#5FC4D0';
                    ++$loanStatus['late-repayment'];
                    break;
                case \projects_status::RECOUVREMENT:
                    $lenderLoans[$loanIndex]['status_color'] = 'completing';
                    $loanData['status']                      = 'completing';
                    $lenderLoans[$loanIndex]['color']        = '#FFCA2C';
                    ++$loanStatus['recovery'];
                    break;
                case \projects_status::PROCEDURE_SAUVEGARDE:
                case \projects_status::REDRESSEMENT_JUDICIAIRE:
                case \projects_status::LIQUIDATION_JUDICIAIRE:
                    $lenderLoans[$loanIndex]['status_color'] = 'problem';
                    $loanData['status']                      = 'problem';
                    $lenderLoans[$loanIndex]['color']        = '#F2980C';
                    ++$loanStatus['collective-proceeding'];
                    break;
                case \projects_status::DEFAUT:
                    $lenderLoans[$loanIndex]['status_color'] = 'defaulted';
                    $loanData['status']                      = 'defaulted';
                    $lenderLoans[$loanIndex]['color']        = '#F76965';
                    ++$loanStatus['default'];
                    break;
                case \projects_status::REMBOURSE:
                case \projects_status::REMBOURSEMENT_ANTICIPE:
                    $lenderLoans[$loanIndex]['status_color'] = 'completed';
                    $loanData['status']                      = 'completed';
                    $lenderLoans[$loanIndex]['color']        = '#1B88DB';
                    ++$loanStatus['refund-finished'];
                    break;
                case \projects_status::REMBOURSEMENT:
                default:
                    $lenderLoans[$loanIndex]['status_color'] = 'inprogress';
                    $loanData['status']                      = 'inprogress';
                    $lenderLoans[$loanIndex]['color']        = '#428890';
                    ++$loanStatus['no-problem'];
                    break;
            }

            if ($aProjectLoans['nb_loan'] == 1) {
                $loanData['count'] = [
                    'bond'        => 0,
                    'contract'    => 0,
                    'declaration' => 0
                ];
                (1 == $aProjectLoans['id_type_contract']) ? $loanData['count']['bond']++ : $loanData['count']['contract']++;

                $loans[0] = [
                    'rate'      => round($aProjectLoans['rate'], 1),
                    'amount'    => round($aProjectLoans['amount']),
                    'documents' => $this->getDocumentDetail(
                        $aProjectLoans['project_status'],
                        $user->getHash(),
                        $aProjectLoans['id_loan_if_one_loan'],
                        $aProjectLoans['id_type_contract'],
                        $projectsInDept,
                        $aProjectLoans['id_project'],
                        $loanData['count']['declaration']
                    )
                ];
            } else {
                $projectLoans                            = $loanEntity->select('id_lender = ' . $lender->id_lender_account . ' AND id_project = ' . $aProjectLoans['id_project']);
                $lenderLoans[$loanIndex]['contracts']    = [];
                $lenderLoans[$loanIndex]['loan_details'] = [];

                $loanData['count'] = [
                    'bond'        => 0,
                    'contract'    => 0,
                    'declaration' => 0
                ];

                foreach ($projectLoans as $partialLoan) {
                    (1 == $partialLoan['id_type_contract']) ? $loanData['count']['bond']++ : $loanData['count']['contract']++;

                    $loans[] = [
                        'rate'      => round($partialLoan['rate'], 1),
                        'amount'    => bcdiv($partialLoan['amount'], 100, 0),
                        'documents' => $this->getDocumentDetail(
                            $aProjectLoans['project_status'],
                            $user->getHash(),
                            $partialLoan['id_loan'],
                            $partialLoan['id_type_contract'],
                            $projectsInDept,
                            $aProjectLoans['id_project'],
                            $loanData['count']['declaration']
                        )
                    ];
                }
            }
            $loanData['loans']    = $loans;
            $lenderProjectLoans[] = $loanData;
            unset($loans, $loanData);
        }

        $chartColors = [
            'late-repayment'        => '#5FC4D0',
            'recovery'              => '#FFCA2C',
            'collective-proceeding' => '#F2980C',
            'default'               => '#F76965',
            'refund-finished'       => '#1B88DB',
            'no-problem'            => '#428890'
        ];
        $seriesData  = [];
        foreach ($loanStatus as $status => $count) {
            $seriesData[] = [
                'name'         => $this->get('translator')->transChoice('lender-operations_loans-chart-legend-loan-status-' . $status, $count, ['%count%' => $count]),
                'y'            => $count,
                'showInLegend' => true,
                'color'        => $chartColors[$status]
            ];
        }
        return ['lenderLoans' => $lenderProjectLoans, 'loanStatus' => $loanStatus, 'seriesData' => $seriesData];
    }

    /**
     * @param int $projectStatus
     * @param string $hash
     * @param int $loanId
     * @param int $docTypeId
     * @param array $projectsInDept
     * @param int $projectId
     * @param $nbDeclarations
     * @return array
     */
    private function getDocumentDetail($projectStatus, $hash, $loanId, $docTypeId, array $projectsInDept, $projectId, &$nbDeclarations = 0)
    {
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        $documents          = [];
        if ($projectStatus >= \projects_status::REMBOURSEMENT) {
            $documents[] = [
                'url'   => $this->get('assets.packages')->getUrl('') . '/pdf/contrat/' . $hash . '/' . $loanId,
                'label' => $translationManager->selectTranslation('lender-operations', 'contract-type-' . $docTypeId),
                'type'  => 'bond'
            ];
        }

        if (in_array($projectId, $projectsInDept)) {
            $nbDeclarations++;
            $documents[] = [
                'url'   => $this->get('assets.packages')->getUrl('') . '/pdf/declaration_de_creances/' . $hash . '/' . $loanId,
                'label' => $translationManager->selectTranslation('lender-operations', 'loans-table-declaration-of-debt-doc-tooltip'),
                'type'  => 'contract'
            ];
        }
        return $documents;
    }
}
