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
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
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
     * @Route("/operations", name="lender_operations")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     * @return Response
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

        $filters = $this->getOperationFilters($request);

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
                        'currentFilters'    => $request->request->get('filter', [])
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

        $filters = $this->getOperationFilters($request);

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
                        1 => $translator->trans('lender-operations_operation-label-current-offer'),
                        2 => $translator->trans('lender-operations_operation-label-rejected-offer'),
                        3 => $translator->trans('lender-operations_operation-label-accepted-offer')
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
                if ($client->type == Clients::TYPE_PERSON || $client->type == Clients::TYPE_PERSON_FOREIGNER) {
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
        $orderDirection = in_array($orderDirection, ['ASC', 'DESC']) ? $orderDirection : 'ASC';

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
        $filters        = $request->request->get('filter', []);
        $year           = isset($filters['date']) && false !== filter_var($filters['date'], FILTER_VALIDATE_INT) ? $filters['date'] : null;
        $status         = isset($filters['status']) && false !== filter_var($filters['status'], FILTER_VALIDATE_INT) ? $filters['status'] : null;
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
        $defaultValues = [
            'start'          => date('d/m/Y', strtotime('-1 month')),
            'end'            => date('d/m/Y'),
            'slide'          => 1,
            'year'           => date('Y'),
            'operation'      => 1,
            'project'        => null,
            'id_last_action' => 'operation'
        ];

        if ($request->request->get('filter')) {
            $filters    = $request->request->get('filter');
            $start      = isset($filters['start']) && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $filters['start']) ? $filters['start'] : $defaultValues['start'];
            $end        = isset($filters['end']) && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $filters['end']) ? $filters['end'] : $defaultValues['end'];
            $slide      = isset($filters['slide']) && in_array($filters['slide'], [1, 3, 6, 12]) ? $filters['slide'] : $defaultValues['slide'];
            $year       = isset($filters['year']) && false !== filter_var($filters['year'], FILTER_VALIDATE_INT) ? $filters['year'] : $defaultValues['year'];
            $operation  = isset($filters['operation']) && array_key_exists($filters['operation'], self::$transactionTypeList) ? $filters['operation'] : $defaultValues['operation'];
            $project    = isset($filters['project']) && false !== filter_var($filters['project'], FILTER_VALIDATE_INT) ? $filters['project'] : $defaultValues['project'];
            $lastAction = isset($filters['id_last_action']) ? $filters['id_last_action'] : $defaultValues['id_last_action'];
            $filters    = [
                'start'          => $start,
                'end'            => $end,
                'slide'          => $slide,
                'year'           => $year,
                'operation'      => $operation,
                'project'        => $project,
                'id_last_action' => $lastAction
            ];
        } elseif ($request->getSession()->has('lenderOperationsFilters')) {
            $filters = $request->getSession()->get('lenderOperationsFilters');
        } else {
            $filters = $defaultValues;
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

        /** @var SessionInterface $session */
        $session = $request->getSession();
        $session->set('lenderOperationsFilters', $filters);

        unset($filters['id_last_action']);

        return $filters;
    }

    /**
     * @Route("/operations/pdf", name="lender_operations_pdf")
     * @Security("has_role('ROLE_LENDER')")
     * @param Request $request
     * @return Response
     */
    public function downloadOperationPDF(Request $request)
    {

        $lenderOperations = array ( 0 => array ( 'id' => '84658910', 'available_balance' => '11.38', 'committed_balance' => '40.00', 'amount' => '2.63', 'label' => 'repayment', 'id_project' => '69289', 'date' => '2017-04-01', 'operationDate' => '2017-04-01 11:08:06', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '281944', 'id_repayment_schedule' => '12516403', 'title' => 'Azapp', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.35', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.34', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.059999999999999998, ), ), ), ), 1 => array ( 'id' => '84615260', 'available_balance' => '8.75', 'committed_balance' => '40.00', 'amount' => '1.50', 'label' => 'repayment', 'id_project' => '35022', 'date' => '2017-04-01', 'operationDate' => '2017-04-01 11:04:05', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '124791', 'id_repayment_schedule' => '5615019', 'title' => 'Eléphants & Co', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.33', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.20', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 2 => array ( 'id' => '84497633', 'available_balance' => '7.25', 'committed_balance' => '40.00', 'amount' => '-40.00', 'label' => 'bid', 'id_project' => '75943', 'date' => '2017-03-30', 'operationDate' => '2017-03-30 15:22:19', 'id_bid' => '4878044', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'HD Loc - 2', ), 3 => array ( 'id' => '84473582', 'available_balance' => '47.25', 'committed_balance' => '0.00', 'amount' => '5.95', 'label' => 'repayment', 'id_project' => '19', 'date' => '2017-03-30', 'operationDate' => '2017-03-30 11:14:10', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '656', 'id_repayment_schedule' => '31275', 'title' => 'Comelec', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '5.25', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.83', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.13, ), ), ), ), 4 => array ( 'id' => '84112175', 'available_balance' => '41.30', 'committed_balance' => '0.00', 'amount' => '3.42', 'label' => 'repayment', 'id_project' => '60452', 'date' => '2017-03-28', 'operationDate' => '2017-03-28 11:20:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '228733', 'id_repayment_schedule' => '10223467', 'title' => 'Financière de Pontarlier', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.15', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.02, ), ), ), ), 5 => array ( 'id' => '83783414', 'available_balance' => '37.88', 'committed_balance' => '0.00', 'amount' => '1.16', 'label' => 'repayment', 'id_project' => '71550', 'date' => '2017-03-27', 'operationDate' => '2017-03-27 11:14:12', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '294119', 'id_repayment_schedule' => '12888164', 'title' => 'Holding Hurtiger', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.82', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.40', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.059999999999999998, ), ), ), ), 6 => array ( 'id' => '83684141', 'available_balance' => '36.72', 'committed_balance' => '0.00', 'amount' => '0.95', 'label' => 'repayment', 'id_project' => '43851', 'date' => '2017-03-27', 'operationDate' => '2017-03-27 11:06:06', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '188373', 'id_repayment_schedule' => '8474775', 'title' => 'BDP Holding', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.72', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.27', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 7 => array ( 'id' => '83617822', 'available_balance' => '35.77', 'committed_balance' => '0.00', 'amount' => '2.32', 'label' => 'repayment', 'id_project' => '32093', 'date' => '2017-03-27', 'operationDate' => '2017-03-27 10:08:16', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '116317', 'id_repayment_schedule' => '5335570', 'title' => 'S.T.N.L', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.24', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.09', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.01, ), ), ), ), 8 => array ( 'id' => '83524864', 'available_balance' => '33.45', 'committed_balance' => '0.00', 'amount' => '3.97', 'label' => 'repayment', 'id_project' => '25956', 'date' => '2017-03-26', 'operationDate' => '2017-03-26 11:06:07', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '100890', 'id_repayment_schedule' => '4809804', 'title' => 'Kep Technologies Integrated Systems', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.75', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.26', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 9 => array ( 'id' => '83493940', 'available_balance' => '29.48', 'committed_balance' => '0.00', 'amount' => '0.90', 'label' => 'repayment', 'id_project' => '63182', 'date' => '2017-03-26', 'operationDate' => '2017-03-26 11:04:06', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '251602', 'id_repayment_schedule' => '11176510', 'title' => 'Prat Optic', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.77', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.15', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.02, ), ), ), ), 10 => array ( 'id' => '83318695', 'available_balance' => '28.58', 'committed_balance' => '0.00', 'amount' => '35.00', 'label' => 'lender_loan', 'id_project' => '74773', 'date' => '2017-03-23', 'operationDate' => '2017-03-23 14:08:12', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '313226', 'id_repayment_schedule' => NULL, 'title' => 'Finae Conseil', ), 11 => array ( 'id' => '82850888', 'available_balance' => '28.58', 'committed_balance' => '35.00', 'amount' => '10.19', 'label' => 'repayment', 'id_project' => '45044', 'date' => '2017-03-21', 'operationDate' => '2017-03-21 11:22:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '186357', 'id_repayment_schedule' => '8442807', 'title' => 'Pascal et Béatrix Décoration - 6', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '9.98', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.24', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 12 => array ( 'id' => '82795763', 'available_balance' => '18.39', 'committed_balance' => '35.00', 'amount' => '2.69', 'label' => 'repayment', 'id_project' => '57436', 'date' => '2017-03-21', 'operationDate' => '2017-03-21 11:17:13', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '215968', 'id_repayment_schedule' => '9706255', 'title' => 'Diet Plus Shop', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.31', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.45', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.070000000000000007, ), ), ), ), 13 => array ( 'id' => '82392740', 'available_balance' => '15.70', 'committed_balance' => '35.00', 'amount' => '1.20', 'label' => 'repayment', 'id_project' => '37121', 'date' => '2017-03-19', 'operationDate' => '2017-03-19 11:10:08', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '151725', 'id_repayment_schedule' => '6846519', 'title' => 'Air Vide Maintenance', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.04', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.19', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 14 => array ( 'id' => '82365848', 'available_balance' => '14.50', 'committed_balance' => '35.00', 'amount' => '1.51', 'label' => 'repayment', 'id_project' => '37779', 'date' => '2017-03-19', 'operationDate' => '2017-03-19 11:08:06', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '156489', 'id_repayment_schedule' => '6992589', 'title' => 'Becom', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.30', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.24', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 15 => array ( 'id' => '82338665', 'available_balance' => '12.99', 'committed_balance' => '35.00', 'amount' => '8.12', 'label' => 'repayment', 'id_project' => '4669', 'date' => '2017-03-19', 'operationDate' => '2017-03-19 11:05:09', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '36355', 'id_repayment_schedule' => '1908276', 'title' => 'Magunivers', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '7.54', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.67', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.089999999999999997, ), ), ), ), 16 => array ( 'id' => '82318691', 'available_balance' => '4.87', 'committed_balance' => '35.00', 'amount' => '1.49', 'label' => 'repayment', 'id_project' => '63304', 'date' => '2017-03-19', 'operationDate' => '2017-03-19 11:03:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '250129', 'id_repayment_schedule' => '11123482', 'title' => 'Dentaltech C.S.A.', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.27', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.26', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 17 => array ( 'id' => '82243532', 'available_balance' => '3.38', 'committed_balance' => '35.00', 'amount' => '1.20', 'label' => 'repayment', 'id_project' => '73288', 'date' => '2017-03-18', 'operationDate' => '2017-03-18 11:09:05', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '286162', 'id_repayment_schedule' => '12621031', 'title' => 'Ethik & Nature', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.01', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.22', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 18 => array ( 'id' => '82101839', 'available_balance' => '2.18', 'committed_balance' => '35.00', 'amount' => '-35.00', 'label' => 'bid', 'id_project' => '74773', 'date' => '2017-03-16', 'operationDate' => '2017-03-16 14:10:03', 'id_bid' => '4706612', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'Finae Conseil', ), 19 => array ( 'id' => '82088321', 'available_balance' => '37.18', 'committed_balance' => '0.00', 'amount' => '8.16', 'label' => 'repayment', 'id_project' => '12123', 'date' => '2017-03-16', 'operationDate' => '2017-03-16 11:17:39', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '65887', 'id_repayment_schedule' => '3265543', 'title' => 'Pascal et Béatrix Décoration - 2', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '7.91', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.29', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 20 => array ( 'id' => '81863539', 'available_balance' => '29.02', 'committed_balance' => '0.00', 'amount' => '4.74', 'label' => 'repayment', 'id_project' => '4085', 'date' => '2017-03-15', 'operationDate' => '2017-03-15 11:04:15', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '28384', 'id_repayment_schedule' => '1544306', 'title' => 'Actual', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '4.43', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.37', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.059999999999999998, ), ), ), ), 21 => array ( 'id' => '81694981', 'available_balance' => '24.28', 'committed_balance' => '0.00', 'amount' => '50.00', 'label' => 'lender_loan', 'id_project' => '61430', 'date' => '2017-03-13', 'operationDate' => '2017-03-13 17:43:46', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '305579', 'id_repayment_schedule' => NULL, 'title' => 'SA Curty Matériels ', ), 22 => array ( 'id' => '81660758', 'available_balance' => '24.28', 'committed_balance' => '50.00', 'amount' => '8.42', 'label' => 'repayment', 'id_project' => '62236', 'date' => '2017-03-13', 'operationDate' => '2017-03-13 11:15:05', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '241762', 'id_repayment_schedule' => '10893406', 'title' => 'G. David Investissements', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '8.32', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.11', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.01, ), ), ), ), 23 => array ( 'id' => '81212074', 'available_balance' => '15.86', 'committed_balance' => '50.00', 'amount' => '1.50', 'label' => 'repayment', 'id_project' => '44520', 'date' => '2017-03-10', 'operationDate' => '2017-03-10 11:14:12', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '204733', 'id_repayment_schedule' => '9274003', 'title' => 'Cabinet Kupiec et Debergh', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.24', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 24 => array ( 'id' => '81191719', 'available_balance' => '14.36', 'committed_balance' => '50.00', 'amount' => '4.61', 'label' => 'repayment', 'id_project' => '18150', 'date' => '2017-03-10', 'operationDate' => '2017-03-10 11:12:25', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '79810', 'id_repayment_schedule' => '3888387', 'title' => 'Gaïa', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '4.09', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.61', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.089999999999999997, ), ), ), ), 25 => array ( 'id' => '81151885', 'available_balance' => '9.75', 'committed_balance' => '50.00', 'amount' => '0.95', 'label' => 'repayment', 'id_project' => '39189', 'date' => '2017-03-10', 'operationDate' => '2017-03-10 11:10:07', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '192435', 'id_repayment_schedule' => '8718492', 'title' => 'HD Loc', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.71', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.28', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 26 => array ( 'id' => '80896865', 'available_balance' => '8.80', 'committed_balance' => '50.00', 'amount' => '6.86', 'label' => 'repayment', 'id_project' => '30559', 'date' => '2017-03-08', 'operationDate' => '2017-03-08 11:05:05', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '113059', 'id_repayment_schedule' => '5252482', 'title' => 'Paris Canal', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '6.80', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.06', ), ), ), ), 27 => array ( 'id' => '80861548', 'available_balance' => '1.94', 'committed_balance' => '50.00', 'amount' => '-50.00', 'label' => 'bid', 'id_project' => '61430', 'date' => '2017-03-07', 'operationDate' => '2017-03-07 14:02:10', 'id_bid' => '4626965', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'SA Curty Matériels ', ), 28 => array ( 'id' => '80593148', 'available_balance' => '51.94', 'committed_balance' => '0.00', 'amount' => '3.46', 'label' => 'repayment', 'id_project' => '35110', 'date' => '2017-03-05', 'operationDate' => '2017-03-05 11:04:09', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '144141', 'id_repayment_schedule' => '6599829', 'title' => 'Récréalire', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.30', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.19', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 29 => array ( 'id' => '80390918', 'available_balance' => '48.48', 'committed_balance' => '0.00', 'amount' => '5.95', 'label' => 'repayment', 'id_project' => '19', 'date' => '2017-03-02', 'operationDate' => '2017-03-02 11:14:14', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '656', 'id_repayment_schedule' => '31274', 'title' => 'Comelec', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '5.22', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.86', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.13, ), ), ), ), 30 => array ( 'id' => '80147149', 'available_balance' => '42.53', 'committed_balance' => '0.00', 'amount' => '2.63', 'label' => 'repayment', 'id_project' => '69289', 'date' => '2017-03-01', 'operationDate' => '2017-03-01 11:05:15', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '281944', 'id_repayment_schedule' => '12516400', 'title' => 'Azapp', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.34', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.35', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.059999999999999998, ), ), ), ), 31 => array ( 'id' => '80123587', 'available_balance' => '39.90', 'committed_balance' => '0.00', 'amount' => '1.50', 'label' => 'repayment', 'id_project' => '35022', 'date' => '2017-03-01', 'operationDate' => '2017-03-01 11:03:05', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '124791', 'id_repayment_schedule' => '5615018', 'title' => 'Eléphants & Co', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.33', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.20', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 32 => array ( 'id' => '80056969', 'available_balance' => '38.40', 'committed_balance' => '0.00', 'amount' => '60.00', 'label' => 'lender_loan', 'id_project' => '71550', 'date' => '2017-02-28', 'operationDate' => '2017-02-28 13:44:11', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '294119', 'id_repayment_schedule' => NULL, 'title' => 'Holding Hurtiger', ), 33 => array ( 'id' => '80016040', 'available_balance' => '38.40', 'committed_balance' => '60.00', 'amount' => '5.04', 'label' => 'repayment', 'id_project' => '1037', 'date' => '2017-02-28', 'operationDate' => '2017-02-28 11:21:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '10430', 'id_repayment_schedule' => '575072', 'title' => 'Hôtel-Restaurant du Jura', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '4.17', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '1.04', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.17000000000000001, ), ), ), ), 34 => array ( 'id' => '79915111', 'available_balance' => '33.36', 'committed_balance' => '60.00', 'amount' => '3.42', 'label' => 'repayment', 'id_project' => '60452', 'date' => '2017-02-28', 'operationDate' => '2017-02-28 11:16:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '228733', 'id_repayment_schedule' => '10223464', 'title' => 'Financière de Pontarlier', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.27', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.17', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.02, ), ), ), ), 35 => array ( 'id' => '79608257', 'available_balance' => '29.94', 'committed_balance' => '60.00', 'amount' => '0.95', 'label' => 'repayment', 'id_project' => '43851', 'date' => '2017-02-27', 'operationDate' => '2017-02-27 11:09:16', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '188373', 'id_repayment_schedule' => '8474772', 'title' => 'BDP Holding', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.71', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.28', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 36 => array ( 'id' => '79492733', 'available_balance' => '28.99', 'committed_balance' => '60.00', 'amount' => '3.97', 'label' => 'repayment', 'id_project' => '25956', 'date' => '2017-02-26', 'operationDate' => '2017-02-26 11:07:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '100890', 'id_repayment_schedule' => '4809803', 'title' => 'Kep Technologies Integrated Systems', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.73', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.28', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 37 => array ( 'id' => '79461743', 'available_balance' => '25.02', 'committed_balance' => '60.00', 'amount' => '0.90', 'label' => 'repayment', 'id_project' => '63182', 'date' => '2017-02-26', 'operationDate' => '2017-02-26 11:06:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '251602', 'id_repayment_schedule' => '11176507', 'title' => 'Prat Optic', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.76', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.16', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.02, ), ), ), ), 38 => array ( 'id' => '79331372', 'available_balance' => '24.12', 'committed_balance' => '60.00', 'amount' => '2.32', 'label' => 'repayment', 'id_project' => '32093', 'date' => '2017-02-25', 'operationDate' => '2017-02-25 11:09:02', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '116317', 'id_repayment_schedule' => '5335569', 'title' => 'S.T.N.L', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.23', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.10', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.01, ), ), ), ), 39 => array ( 'id' => '78628954', 'available_balance' => '21.80', 'committed_balance' => '60.00', 'amount' => '10.18', 'label' => 'repayment', 'id_project' => '45044', 'date' => '2017-02-21', 'operationDate' => '2017-02-21 11:09:02', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '186357', 'id_repayment_schedule' => '8442804', 'title' => 'Pascal et Béatrix Décoration - 6', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '9.96', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.26', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 40 => array ( 'id' => '78573604', 'available_balance' => '11.62', 'committed_balance' => '60.00', 'amount' => '2.69', 'label' => 'repayment', 'id_project' => '57436', 'date' => '2017-02-21', 'operationDate' => '2017-02-21 11:08:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '215968', 'id_repayment_schedule' => '9706252', 'title' => 'Diet Plus Shop', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.47', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.070000000000000007, ), ), ), ), 41 => array ( 'id' => '78320399', 'available_balance' => '8.93', 'committed_balance' => '60.00', 'amount' => '40.00', 'label' => 'lender_loan', 'id_project' => '73288', 'date' => '2017-02-20', 'operationDate' => '2017-02-20 09:30:52', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '286162', 'id_repayment_schedule' => NULL, 'title' => 'Ethik & Nature', ), 42 => array ( 'id' => '78217847', 'available_balance' => '8.93', 'committed_balance' => '100.00', 'amount' => '1.20', 'label' => 'repayment', 'id_project' => '37121', 'date' => '2017-02-19', 'operationDate' => '2017-02-19 11:09:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '151725', 'id_repayment_schedule' => '6846516', 'title' => 'Air Vide Maintenance', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.04', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.19', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 43 => array ( 'id' => '78173465', 'available_balance' => '7.73', 'committed_balance' => '100.00', 'amount' => '1.50', 'label' => 'repayment', 'id_project' => '37779', 'date' => '2017-02-19', 'operationDate' => '2017-02-19 11:07:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '156489', 'id_repayment_schedule' => '6992586', 'title' => 'Becom', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.25', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 44 => array ( 'id' => '78135857', 'available_balance' => '6.23', 'committed_balance' => '100.00', 'amount' => '1.49', 'label' => 'repayment', 'id_project' => '63304', 'date' => '2017-02-19', 'operationDate' => '2017-02-19 11:03:02', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '250129', 'id_repayment_schedule' => '11123479', 'title' => 'Dentaltech C.S.A.', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.27', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.26', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 45 => array ( 'id' => '78007321', 'available_balance' => '4.74', 'committed_balance' => '100.00', 'amount' => '-40.00', 'label' => 'bid', 'id_project' => '73288', 'date' => '2017-02-18', 'operationDate' => '2017-02-18 10:03:52', 'id_bid' => '4452659', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'Ethik & Nature', ), 46 => array ( 'id' => '77721013', 'available_balance' => '44.74', 'committed_balance' => '60.00', 'amount' => '8.11', 'label' => 'repayment', 'id_project' => '4669', 'date' => '2017-02-16', 'operationDate' => '2017-02-16 11:05:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '36355', 'id_repayment_schedule' => '1908275', 'title' => 'Magunivers', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '7.49', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.72', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.10000000000000001, ), ), ), ), 47 => array ( 'id' => '77491699', 'available_balance' => '36.63', 'committed_balance' => '60.00', 'amount' => '-60.00', 'label' => 'bid', 'id_project' => '71550', 'date' => '2017-02-13', 'operationDate' => '2017-02-13 12:32:52', 'id_bid' => '4289441', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'Holding Hurtiger', ), 48 => array ( 'id' => '77450989', 'available_balance' => '96.63', 'committed_balance' => '0.00', 'amount' => '8.41', 'label' => 'repayment', 'id_project' => '62236', 'date' => '2017-02-13', 'operationDate' => '2017-02-13 11:08:02', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '241762', 'id_repayment_schedule' => '10893403', 'title' => 'G. David Investissements', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '8.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.14', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.02, ), ), ), ), 49 => array ( 'id' => '77450113', 'available_balance' => '88.22', 'committed_balance' => '0.00', 'amount' => '8.14', 'label' => 'repayment', 'id_project' => '12123', 'date' => '2017-02-13', 'operationDate' => '2017-02-13 11:07:33', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '65887', 'id_repayment_schedule' => '3265542', 'title' => 'Pascal et Béatrix Décoration - 2', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '7.85', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.35', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.059999999999999998, ), ), ), ), 50 => array ( 'id' => '77287942', 'available_balance' => '80.08', 'committed_balance' => '0.00', 'amount' => '4.74', 'label' => 'repayment', 'id_project' => '4085', 'date' => '2017-02-12', 'operationDate' => '2017-02-12 11:08:05', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '28384', 'id_repayment_schedule' => '1544305', 'title' => 'Actual', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '4.40', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.40', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.059999999999999998, ), ), ), ), 51 => array ( 'id' => '77056754', 'available_balance' => '75.34', 'committed_balance' => '0.00', 'amount' => '1.49', 'label' => 'repayment', 'id_project' => '44520', 'date' => '2017-02-10', 'operationDate' => '2017-02-10 11:14:05', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '204733', 'id_repayment_schedule' => '9274000', 'title' => 'Cabinet Kupiec et Debergh', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.28', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.25', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 52 => array ( 'id' => '76991923', 'available_balance' => '73.85', 'committed_balance' => '0.00', 'amount' => '4.61', 'label' => 'repayment', 'id_project' => '18150', 'date' => '2017-02-10', 'operationDate' => '2017-02-10 11:11:10', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '79810', 'id_repayment_schedule' => '3888386', 'title' => 'Gaïa', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '4.06', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.64', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.089999999999999997, ), ), ), ), 53 => array ( 'id' => '76938043', 'available_balance' => '69.24', 'committed_balance' => '0.00', 'amount' => '0.95', 'label' => 'repayment', 'id_project' => '39189', 'date' => '2017-02-10', 'operationDate' => '2017-02-10 11:09:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '192435', 'id_repayment_schedule' => '8718489', 'title' => 'HD Loc', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.71', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.28', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 54 => array ( 'id' => '76655015', 'available_balance' => '68.29', 'committed_balance' => '0.00', 'amount' => '6.85', 'label' => 'repayment', 'id_project' => '30559', 'date' => '2017-02-08', 'operationDate' => '2017-02-08 11:32:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '113059', 'id_repayment_schedule' => '5252481', 'title' => 'Paris Canal', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '6.77', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.09', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.01, ), ), ), ), 55 => array ( 'id' => '76299659', 'available_balance' => '61.44', 'committed_balance' => '0.00', 'amount' => '3.46', 'label' => 'repayment', 'id_project' => '35110', 'date' => '2017-02-05', 'operationDate' => '2017-02-05 11:02:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '144141', 'id_repayment_schedule' => '6599826', 'title' => 'Récréalire', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.20', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 56 => array ( 'id' => '75982153', 'available_balance' => '57.98', 'committed_balance' => '0.00', 'amount' => '60.00', 'label' => 'lender_loan', 'id_project' => '69289', 'date' => '2017-02-01', 'operationDate' => '2017-02-01 14:34:35', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '281944', 'id_repayment_schedule' => NULL, 'title' => 'Azapp', ), 57 => array ( 'id' => '75874490', 'available_balance' => '57.98', 'committed_balance' => '60.00', 'amount' => '1.50', 'label' => 'repayment', 'id_project' => '35022', 'date' => '2017-02-01', 'operationDate' => '2017-02-01 11:07:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '124791', 'id_repayment_schedule' => '5615017', 'title' => 'Eléphants & Co', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.32', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.21', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 58 => array ( 'id' => '75866072', 'available_balance' => '56.48', 'committed_balance' => '60.00', 'amount' => '-60.00', 'label' => 'bid', 'id_project' => '69289', 'date' => '2017-02-01', 'operationDate' => '2017-02-01 08:45:05', 'id_bid' => '4171531', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'Azapp', ), 59 => array ( 'id' => '75814130', 'available_balance' => '116.48', 'committed_balance' => '0.00', 'amount' => '5.04', 'label' => 'repayment', 'id_project' => '1037', 'date' => '2017-01-31', 'operationDate' => '2017-01-31 11:17:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '10430', 'id_repayment_schedule' => '575071', 'title' => 'Hôtel-Restaurant du Jura', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '4.14', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '1.07', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.17000000000000001, ), ), ), ), 60 => array ( 'id' => '75740486', 'available_balance' => '111.44', 'committed_balance' => '0.00', 'amount' => '5.95', 'label' => 'repayment', 'id_project' => '19', 'date' => '2017-01-30', 'operationDate' => '2017-01-30 12:02:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '656', 'id_repayment_schedule' => '31273', 'title' => 'Comelec', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '5.18', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.90', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.13, ), ), ), ), 61 => array ( 'id' => '75311165', 'available_balance' => '105.49', 'committed_balance' => '0.00', 'amount' => '3.42', 'label' => 'repayment', 'id_project' => '60452', 'date' => '2017-01-28', 'operationDate' => '2017-01-28 11:57:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '228733', 'id_repayment_schedule' => '10223461', 'title' => 'Financière de Pontarlier', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.26', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.18', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.02, ), ), ), ), 62 => array ( 'id' => '75114575', 'available_balance' => '102.07', 'committed_balance' => '0.00', 'amount' => 60, 'label' => 'refused-bid', 'id_project' => '54727', 'date' => '2017-01-28', 'operationDate' => '2017-01-28 10:25:04', 'id_bid' => '3991594', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'Plein Sud Etiquettes', ), 63 => array ( 'id' => '75103865', 'available_balance' => '42.07', 'committed_balance' => '60.00', 'amount' => '-60.00', 'label' => 'bid', 'id_project' => '54727', 'date' => '2017-01-27', 'operationDate' => '2017-01-27 18:23:49', 'id_bid' => '3991594', 'id_autobid' => NULL, 'id_loan' => '', 'id_repayment_schedule' => NULL, 'title' => 'Plein Sud Etiquettes', ), 64 => array ( 'id' => '74975399', 'available_balance' => '102.07', 'committed_balance' => '0.00', 'amount' => '0.95', 'label' => 'repayment', 'id_project' => '43851', 'date' => '2017-01-27', 'operationDate' => '2017-01-27 11:17:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '188373', 'id_repayment_schedule' => '8474769', 'title' => 'BDP Holding', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.71', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.28', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 65 => array ( 'id' => '74860631', 'available_balance' => '101.12', 'committed_balance' => '0.00', 'amount' => '3.97', 'label' => 'repayment', 'id_project' => '25956', 'date' => '2017-01-26', 'operationDate' => '2017-01-26 11:22:04', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '100890', 'id_repayment_schedule' => '4809802', 'title' => 'Kep Technologies Integrated Systems', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '3.71', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.30', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 66 => array ( 'id' => '74829587', 'available_balance' => '97.15', 'committed_balance' => '0.00', 'amount' => '0.90', 'label' => 'repayment', 'id_project' => '63182', 'date' => '2017-01-26', 'operationDate' => '2017-01-26 11:17:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '251602', 'id_repayment_schedule' => '11176504', 'title' => 'Prat Optic', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '0.76', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.16', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.02, ), ), ), ), 67 => array ( 'id' => '74731376', 'available_balance' => '96.25', 'committed_balance' => '0.00', 'amount' => '2.32', 'label' => 'repayment', 'id_project' => '32093', 'date' => '2017-01-25', 'operationDate' => '2017-01-25 11:12:03', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '116317', 'id_repayment_schedule' => '5335568', 'title' => 'S.T.N.L', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.22', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.11', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.01, ), ), ), ), 68 => array ( 'id' => '74248372', 'available_balance' => '93.93', 'committed_balance' => '0.00', 'amount' => '10.18', 'label' => 'repayment', 'id_project' => '45044', 'date' => '2017-01-21', 'operationDate' => '2017-01-21 11:57:02', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '186357', 'id_repayment_schedule' => '8442801', 'title' => 'Pascal et Béatrix Décoration - 6', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '9.92', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.30', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 69 => array ( 'id' => '74192863', 'available_balance' => '83.75', 'committed_balance' => '0.00', 'amount' => '2.69', 'label' => 'repayment', 'id_project' => '57436', 'date' => '2017-01-21', 'operationDate' => '2017-01-21 11:52:06', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '215968', 'id_repayment_schedule' => '9706249', 'title' => 'Diet Plus Shop', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '2.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.47', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.070000000000000007, ), ), ), ), 70 => array ( 'id' => '73968976', 'available_balance' => '81.06', 'committed_balance' => '0.00', 'amount' => '1.50', 'label' => 'repayment', 'id_project' => '37779', 'date' => '2017-01-20', 'operationDate' => '2017-01-20 18:22:54', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '156489', 'id_repayment_schedule' => '6992583', 'title' => 'Becom', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.29', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.25', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), 71 => array ( 'id' => '73930882', 'available_balance' => '79.56', 'committed_balance' => '0.00', 'amount' => '1.20', 'label' => 'repayment', 'id_project' => '37121', 'date' => '2017-01-20', 'operationDate' => '2017-01-20 18:22:14', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '151725', 'id_repayment_schedule' => '6846513', 'title' => 'Air Vide Maintenance', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.03', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.20', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.029999999999999999, ), ), ), ), 72 => array ( 'id' => '73822558', 'available_balance' => '78.36', 'committed_balance' => '0.00', 'amount' => '8.10', 'label' => 'repayment', 'id_project' => '4669', 'date' => '2017-01-20', 'operationDate' => '2017-01-20 18:19:26', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '36355', 'id_repayment_schedule' => '1908274', 'title' => 'Magunivers', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '7.44', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.77', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.11, ), ), ), ), 73 => array ( 'id' => '73808230', 'available_balance' => '70.26', 'committed_balance' => '0.00', 'amount' => '1.49', 'label' => 'repayment', 'id_project' => '63304', 'date' => '2017-01-20', 'operationDate' => '2017-01-20 18:05:27', 'id_bid' => NULL, 'id_autobid' => NULL, 'id_loan' => '250129', 'id_repayment_schedule' => '11123476', 'title' => 'Dentaltech C.S.A.', 'detail' => array ( 'label' => 'Voici le détail de votre remboursement :', 'items' => array ( 0 => array ( 'label' => 'Capital remboursé', 'value' => '1.26', ), 1 => array ( 'label' => 'Intérêts remboursés ', 'value' => '0.27', ), 2 => array ( 'label' => 'Cotisations sociales', 'value' => -0.040000000000000001, ), ), ), ), );

        return $this->render('pdf/lender_operations.html.twig', ['lenderOperations' => $lenderOperations]);
    }
}
