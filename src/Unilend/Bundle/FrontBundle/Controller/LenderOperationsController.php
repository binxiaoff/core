<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\core\Loader;

class LenderOperationsController extends Controller
{
    const LAST_OPERATION_DATE = '2013-01-01';
    /**
     * This is a fictive transaction type,
     * it will be used only in indexage_vos_operaitons in order to get single repayment line with total of capital and interests repayment amount
     */
    const TYPE_REPAYMENT_TRANSACTION = 5;

    // This is public in order to make it useable for old PDF controller
    public static $transactionTypeList = [
        1 => [
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
            \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT,
            \transactions_types::TYPE_LENDER_BALANCE_TRANSFER
        ],
        2 => [
            \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
            \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
            \transactions_types::TYPE_DIRECT_DEBIT,
            \transactions_types::TYPE_LENDER_WITHDRAWAL,
            \transactions_types::TYPE_LENDER_BALANCE_TRANSFER
        ],
        3 => [
            \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT,
            \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT,
            \transactions_types::TYPE_DIRECT_DEBIT,
            \transactions_types::TYPE_LENDER_BALANCE_TRANSFER
        ],
        4 => [\transactions_types::TYPE_LENDER_WITHDRAWAL],
        5 => [\transactions_types::TYPE_LENDER_LOAN],
        6 => [
            self::TYPE_REPAYMENT_TRANSACTION,
            \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT,
            \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT
        ]
    ];

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
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');

        $client->get($this->getUser()->getClientId());
        $lender->get($client->id_client, 'id_client_owner');
        $this->lenderOperationIndexing($lenderOperationsIndex, $lender);

        /** @var SessionInterface $session */
        $session = $request->getSession();
        $filters = $this->getOperationFilters($request);
        $session->set('lenderOperationsFilters', $filters);

        $lenderOperations       = $lenderOperationsIndex->getLenderOperations(self::$transactionTypeList[$filters['operation']], $this->getUser()->getClientId(), $filters['startDate']->format('Y-m-d'), $filters['endDate']->format('Y-m-d'));
        $projectsFundedByLender = $lenderOperationsIndex->get_liste_libelle_projet('id_client = ' . $this->getUser()->getClientId() . ' AND DATE(date_operation) >= "' . $filters['startDate']->format('Y-m-d') . '" AND DATE(date_operation) <= "' . $filters['endDate']->format('Y-m-d') . '"');

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
                'repaidCapitalLabel'     => $this->get('translator')->trans('lender-operations_operations-table-repaid-capital-amount-collapse-details'),
                'repaidInterestsLabel'   => $this->get('translator')->trans('lender-operations_operations-table-repaid-interests-amount-collapse-details'),
                'currentFilters'         => $filters
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

        /** @var SessionInterface $session */
        $session = $request->getSession();
        $filters = $this->getOperationFilters($request);
        $session->set('lenderOperationsFilters', $filters);

        $transactionListFilter = self::$transactionTypeList[$filters['operation']];
        $startDate             = $filters['startDate']->format('Y-m-d');
        $endDate               = $filters['endDate']->format('Y-m-d');

        /** @var \indexage_vos_operations $lenderOperationsIndex */
        $lenderOperationsIndex  = $entityManager->getRepository('indexage_vos_operations');
        $lenderOperations       = $lenderOperationsIndex->getLenderOperations($transactionListFilter, $this->getUser()->getClientId(), $startDate, $endDate, $filters['project']);
        $projectsFundedByLender = $lenderOperationsIndex->get_liste_libelle_projet('type_transaction IN (' . implode(',', $transactionListFilter) . ') AND id_client = ' . $this->getUser()->getClientId() . ' AND LEFT(date_operation, 10) >= "' . $startDate . '" AND LEFT(date_operation, 10) <= "' . $endDate . '"');

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
                        'repaidCapitalLabel'     => $this->get('translator')->trans('lender-operations_operations-table-repaid-capital-amount-collapse-details'),
                        'repaidInterestsLabel'   => $this->get('translator')->trans('lender-operations_operations-table-repaid-interests-amount-collapse-details'),
                        'currentFilters'         => $filters
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
        /** @var SessionInterface $session */
        $session = $this->get('session');

        if (false === $session->has('lenderOperationsFilters')) {
            return $this->redirectToRoute('lender_operations');
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
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

        $savedFilters          = $session->get('lenderOperationsFilters');
        $transactionListFilter = self::$transactionTypeList[$savedFilters['operation']];
        $startDate             = $savedFilters['startDate']->format('Y-m-d');
        $endDate               = $savedFilters['endDate']->format('Y-m-d');
        $operations            = $lenderIndexedOperations->getLenderOperations($transactionListFilter, $this->getUser()->getClientId(), $startDate, $endDate, $savedFilters['project']);
        $content               = '
        <meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8"/>
        <table border="1">
            <tr>
                <th>' . $translator->trans('lender-operations_operations-csv-operation-column') . '</th>
                <th>' . $translator->trans('lender-operations_operations-csv-contract-column') . '</th>
                <th>' . $translator->trans('lender-operations_operations-csv-project-id-column') . '</th>
                <th>' . $translator->trans('lender-operations_operations-csv-project-label-column') . '</th>
                <th>' . $translator->trans('lender-operations_operations-csv-operation-date-column') . '</th>
                <th>' . $translator->trans('lender-operations_operations-csv-operation-amount-column') . '</th>
                <th>' . $translator->trans('lender-operations_operations-csv-repaid-capital-amount-column') . '</th>
                <th>' . $translator->trans('lender-operations_operations-csv-perceived-interests-amount-column') . '</th>';
        foreach ($aTaxType as $aType) {
            $content .= '<th>' . $aType['name'] . '</th>';
        }
        $content .= '<th>' . $translator->trans('lender-operations_operations-csv-account-balance-column') . '</th>
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
            \transactions_types::TYPE_SPONSORSHIP_SPONSOR_REWARD   => $translator->trans('preteur-operations-vos-operations_gain-parrain'),
            \transactions_types::TYPE_LENDER_BALANCE_TRANSFER      => $translator->trans('preteur-operations-vos-operations_balance-transfer')
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

                if ($t['type_transaction'] == \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT) {
                    $recoveryManager = $this->get('unilend.service.recovery_manager');

                    $capital = $ficelle->formatNumber($t['montant_operation'], 2);
                    $amount  = $ficelle->formatNumber($recoveryManager->getAmountWithRecoveryTax($t['montant_operation']), 2);
                } else {
                    $capital = $ficelle->formatNumber($t['montant_capital'], 2);
                    $amount  = $ficelle->formatNumber($t['montant_operation'], 2);
                }

                $content .= '
                    <tr>
                        <td>' . $t['libelle_operation'] . '</td>
                        <td>' . $t['bdc'] . '</td>
                        <td>' . $sProjectId . '</td>
                        <td>' . $t['libelle_projet'] . '</td>
                        <td>' . date('d-m-Y', strtotime($t['date_operation'])) . '</td>
                        <td' . $couleur . '>' . $amount . '</td>
                        <td>' . $capital . '</td>
                        <td>' . $ficelle->formatNumber($t['montant_interet'], 2) . '</td>';
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
                        <td>' . $ficelle->formatNumber($t['solde'], 2) . '</td>
                        <td></td>
                    </tr>';

            } elseif (in_array($t['type_transaction'], array_keys($aTranslations))) {

                $array_type_transactions = [
                    \transactions_types::TYPE_LENDER_SUBSCRIPTION            => $translator->trans('lender-operations_operation-label-money-deposit'),
                    \transactions_types::TYPE_LENDER_LOAN                    => [
                        \bids::STATUS_BID_PENDING  => $translator->trans('lender-operations_operation-label-current-offer'),
                        \bids::STATUS_BID_REJECTED => $translator->trans('lender-operations_operation-label-rejected-offer'),
                        \bids::STATUS_BID_ACCEPTED => $translator->trans('lender-operations_operation-label-accepted-offer')
                    ],
                    \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT      => $translator->trans('lender-operations_operation-label-money-deposit'),
                    \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT    => $translator->trans('lender-operations_operation-label-money-deposit'),
                    self::TYPE_REPAYMENT_TRANSACTION                         => [
                        1 => $translator->trans('lender-operations_operation-label-refund'),
                        2 => $translator->trans('lender-operations_operation-label-recovery')
                    ],
                    \transactions_types::TYPE_DIRECT_DEBIT                   => $translator->trans('lender-operations_operation-label-money-deposit'),
                    \transactions_types::TYPE_LENDER_WITHDRAWAL              => $translator->trans('lender-operations_operation-label-money-withdrawal'),
                    \transactions_types::TYPE_WELCOME_OFFER                  => $translator->trans('lender-operations_operation-label-welcome-offer'),
                    \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION     => $translator->trans('lender-operations_operation-label-welcome-offer-withdrawal'),
                    \transactions_types::TYPE_SPONSORSHIP_SPONSORED_REWARD   => $translator->trans('lender-operations_operation-label-godson-gain'),
                    \transactions_types::TYPE_SPONSORSHIP_SPONSOR_REWARD     => $translator->trans('lender-operations_operation-label-godfather-gain'),
                    \transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT => $translator->trans('lender-operations_operation-label-anticipated-repayment'),
                    \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT   => $translator->trans('lender-operations_operation-label-anticipated-repayment'),
                    \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT      => $translator->trans('lender-operations_operation-label-lender-recovery'),
                    \transactions_types::TYPE_LENDER_BALANCE_TRANSFER        => $translator->trans('preteur-operations-vos-operations_balance-transfer')
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
                        <td' . $couleur . '>' . $ficelle->formatNumber($t['montant_operation'], 2) . '</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td> 
                        <td>' . $ficelle->formatNumber($t['solde'], 2) . '</td>
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
                        <td' . (! $offre_accepte ? $couleur : '') . '>' . $ficelle->formatNumber($t['montant_operation'], 2) . '</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>' . $ficelle->formatNumber($t['solde'], 2) . '</td>
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
            $oActiveSheet->setCellValue('D' . ($iRowIndex + 2), $this->get('translator')->trans('lender-operations_project-status-label-' . $aProjectLoans['project_status']));
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

        $array_type_transactions = [
            \transactions_types::TYPE_LENDER_SUBSCRIPTION          => $translator->trans('lender-operations_operation-label-money-deposit'),
            \transactions_types::TYPE_LENDER_LOAN                  => [
                1 => $translator->trans('lender-operations_operation-label-current-offer'),
                2 => $translator->trans('lender-operations_operation-label-rejected-offer'),
                3 => $translator->trans('lender-operations_operation-label-accepted-offer')
            ],
            \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT    => $translator->trans('lender-operations_operation-label-money-deposit'),
            \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT  => $translator->trans('lender-operations_operation-label-money-deposit'),
            \transactions_types::TYPE_DIRECT_DEBIT                 => $translator->trans('lender-operations_operation-label-money-deposit'),
            \transactions_types::TYPE_LENDER_WITHDRAWAL            => $translator->trans('lender-operations_operation-label-money-withdrawal'),
            \transactions_types::TYPE_WELCOME_OFFER                => $translator->trans('lender-operations_operation-label-welcome-offer'),
            \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION   => $translator->trans('lender-operations_operation-label-welcome-offer-withdrawal'),
            \transactions_types::TYPE_SPONSORSHIP_SPONSORED_REWARD => $translator->trans('lender-operations_operation-label-godson-gain'),
            \transactions_types::TYPE_SPONSORSHIP_SPONSOR_REWARD   => $translator->trans('lender-operations_operation-label-godfather-gain'),
            \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT => $translator->trans('lender-operations_operation-label-anticipated-repayment'),
            \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT    => $translator->trans('lender-operations_operation-label-lender-recovery'),
            \transactions_types::TYPE_LENDER_BALANCE_TRANSFER      => $translator->trans('preteur-operations-vos-operations_balance-transfer')
        ];

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
                $sOrderBy = 'p.status ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'title':
                $sOrderBy = 'p.title ' . $orderDirection . ', debut DESC';
                break;
            case 'note':
                $sOrderBy = 'p.risk ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'amount':
                $sOrderBy = 'amount ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'interest':
                $sOrderBy = 'rate ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'next':
                $sOrderBy = 'next_echeance ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'end':
                $sOrderBy = 'fin ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'repayment':
                $sOrderBy = 'last_perceived_repayment ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'start':
            default:
                $sOrderBy = 'debut ' . $orderDirection . ', p.title ASC';
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
        $documents = [];

        if ($projectStatus >= \projects_status::REMBOURSEMENT) {
            $documents[] = [
                'url'   => $this->get('assets.packages')->getUrl('') . '/pdf/contrat/' . $hash . '/' . $loanId,
                'label' => $this->get('translator')->trans('lender-operations_contract-type-' . $docTypeId),
                'type'  => 'bond'
            ];
        }

        if (in_array($projectId, $projectsInDept)) {
            $nbDeclarations++;
            $documents[] = [
                'url'   => $this->get('assets.packages')->getUrl('') . '/pdf/declaration_de_creances/' . $hash . '/' . $loanId,
                'label' => $this->get('translator')->trans('lender-operations_loans-table-declaration-of-debt-doc-tooltip'),
                'type'  => 'declaration'
            ];
        }
        return $documents;
    }

    /**
     * @param Request $request
     * @return array
     */
    private function getOperationFilters(Request $request)
    {
        if ($request->request->get('filter')) {
            $filters = [
                'start'          => $request->request->get('filter')['start'],
                'end'            => $request->request->get('filter')['end'],
                'slide'          => $request->request->get('filter')['slide'],
                'year'           => (int) $request->request->get('filter')['year'],
                'operation'      => $request->request->get('filter')['operation'],
                'project'        => $request->request->get('filter')['project'],
                'id_last_action' => $request->request->get('filter')['id_last_action']
            ];
        } elseif ($request->getSession()->has('lenderOperationsFilters')) {
            $filters = $request->getSession()->get('lenderOperationsFilters');
        } else {
            $filters = [
                'start'          => date('d/m/Y', strtotime('-1 month')),
                'end'            => date('d/m/Y'),
                'slide'          => 1,
                'year'           => date('Y'),
                'operation'      => 1,
                'project'        => null,
                'id_last_action' => 'operation'
            ];
        }

        switch ($filters['id_last_action']) {
            default:
            case 'start':
            case 'end':
                $filters['startDate'] = \DateTime::createFromFormat('d/m/Y', $filters['start']);
                $filters['endDate']   = \DateTime::createFromFormat('d/m/Y', $filters['end']);
                break;
            case 'slide':
                if (empty($filters['slide'])) {
                    $filters['slide'] = 1;
                }

                $filters['startDate'] = (new \DateTime('NOW'))->sub(new \DateInterval('P' . $filters['slide'] . 'M'));
                $filters['endDate']   = new \DateTime('NOW');
                break;
            case 'year':
                $filters['startDate'] = new \DateTime('first day of January ' . $filters['year']);
                $filters['endDate']   = new \DateTime('last day of December ' . $filters['year']);
                break;
        }

        $filters['id_client'] = $this->getUser()->getClientId();
        $filters['start']     = $filters['startDate']->format('d/m/Y');
        $filters['end']       = $filters['endDate']->format('d/m/Y');

        return $filters;
    }
}
