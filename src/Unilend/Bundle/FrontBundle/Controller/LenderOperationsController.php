<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
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

    CONST LOAN_STATUS_FILTER = [
        'repayment'      => [\projects_status::REMBOURSEMENT],
        'repaid'         => [\projects_status::REMBOURSE, \projects_status::REMBOURSEMENT_ANTICIPE],
        'late-repayment' => [\projects_status::PROBLEME, \projects_status::PROBLEME_J_X],
        'problem'        => [\projects_status::RECOUVREMENT, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::DEFAUT]
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
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \indexage_vos_operations $lenderOperationsIndex */
        $lenderOperationsIndex = $entityManagerSimulator->getRepository('indexage_vos_operations');
        /** @var \lenders_accounts $lender */
        $lender = $entityManagerSimulator->getRepository('lenders_accounts');
        /** @var \clients $client */
        $client = $entityManagerSimulator->getRepository('clients');

        $client->get($this->getUser()->getClientId());
        $lender->get($client->id_client, 'id_client_owner');
        $this->lenderOperationIndexing($lenderOperationsIndex, $lender);

        $filters = $this->getOperationFilters($request);

        $lenderOperations       = $lenderOperationsIndex->getLenderOperations(self::$transactionTypeList[$filters['operation']], $this->getUser()->getClientId(), $filters['startDate']->format('Y-m-d'), $filters['endDate']->format('Y-m-d'));
        $projectsFundedByLender = $lenderOperationsIndex->get_liste_libelle_projet('id_client = ' . $this->getUser()->getClientId() . ' AND DATE(date_operation) >= "' . $filters['startDate']->format('Y-m-d') . '" AND DATE(date_operation) <= "' . $filters['endDate']->format('Y-m-d') . '"');

        $loans = $this->commonLoans($request, $lender);

        return $this->render('/pages/lender_operations/layout.html.twig', [
            'clientId'               => $lender->id_client_owner,
            'hash'                   => $this->getUser()->getHash(),
            'lenderOperations'       => $lenderOperations,
            'projectsFundedByLender' => $projectsFundedByLender,
            'detailedOperations'     => [self::TYPE_REPAYMENT_TRANSACTION],
            'loansStatusFilter'      => self::LOAN_STATUS_FILTER,
            'firstLoanYear'          => $entityManagerSimulator->getRepository('loans')->getFirstLoanYear($lender->id_lender_account),
            'lenderLoans'            => $loans['lenderLoans'],
            'seriesData'             => $loans['seriesData'],
            'repaidCapitalLabel'     => $this->get('translator')->trans('lender-operations_operations-table-repaid-capital-amount-collapse-details'),
            'repaidInterestsLabel'   => $this->get('translator')->trans('lender-operations_operations-table-repaid-interests-amount-collapse-details'),
            'currentFilters'         => $filters
        ]);
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

        $lender->get($this->getUser()->getClientId(), 'id_client_owner');
        $loans = $this->commonLoans($request, $lender);

        return $this->json([
            'target'   => 'loans .panel-table',
            'template' => $this->render(
                '/pages/lender_operations/my_loans_table.html.twig',
                ['lenderLoans' => $loans['lenderLoans']]
            )->getContent()
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/operations/filterOperations", name="filter_operations")
     * @Security("has_role('ROLE_LENDER')")
     */
    public function filterOperationsAction(Request $request)
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');

        $filters = $this->getOperationFilters($request);

        $transactionListFilter = self::$transactionTypeList[$filters['operation']];
        $startDate             = $filters['startDate']->format('Y-m-d');
        $endDate               = $filters['endDate']->format('Y-m-d');

        /** @var \indexage_vos_operations $lenderOperationsIndex */
        $lenderOperationsIndex  = $entityManagerSimulator->getRepository('indexage_vos_operations');
        $lenderOperations       = $lenderOperationsIndex->getLenderOperations($transactionListFilter, $this->getUser()->getClientId(), $startDate, $endDate, $filters['project']);
        $projectsFundedByLender = $lenderOperationsIndex->get_liste_libelle_projet('type_transaction IN (' . implode(',', $transactionListFilter) . ') AND id_client = ' . $this->getUser()->getClientId() . ' AND LEFT(date_operation, 10) >= "' . $startDate . '" AND LEFT(date_operation, 10) <= "' . $endDate . '"');

        return $this->json([
            'target'   => 'operations',
            'template' => $this->render('/pages/lender_operations/my_operations.html.twig',[
                'clientId'               => $this->getUser()->getClientId(),
                'hash'                   => $this->getUser()->getHash(),
                'detailedOperations'     => [self::TYPE_REPAYMENT_TRANSACTION],
                'projectsFundedByLender' => $projectsFundedByLender,
                'lenderOperations'       => $lenderOperations,
                'repaidCapitalLabel'     => $this->get('translator')->trans('lender-operations_operations-table-repaid-capital-amount-collapse-details'),
                'repaidInterestsLabel'   => $this->get('translator')->trans('lender-operations_operations-table-repaid-interests-amount-collapse-details'),
                'currentFilters'         => $filters
            ])->getContent()
        ]);
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

        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \tax $tax */
        $tax = $entityManagerSimulator->getRepository('tax');
        /** @var \tax_type $taxType */
        $taxType = $entityManagerSimulator->getRepository('tax_type');
        /** @var \tax_type $aTaxType */
        $aTaxType = $taxType->select('id_tax_type !=' . \tax_type::TYPE_VAT);
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var \indexage_vos_operations $lenderIndexedOperations */
        $lenderIndexedOperations = $entityManagerSimulator->getRepository('indexage_vos_operations');
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
                        1 => $translator->trans('lender-operations_operation-label-repayment'),
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

            if(in_array($aProjectLoans['project_status'], [ProjectsStatus::REMBOURSE, ProjectsStatus::REMBOURSEMENT_ANTICIPE])) {
                $oActiveSheet->mergeCells('G' . ($iRowIndex + 2) . ':K'. ($iRowIndex + 2));
                $oActiveSheet->setCellValue(
                    'F' . ($iRowIndex + 2),
                    $this->get('translator')->trans(
                        'lender-operations_loans-table-project-status-label-repayment-finished-on-date',
                        ['%date%' => date('d/m/Y', $aProjectLoans['final_repayment_date'])]
                    )
                );
            } elseif (in_array($aProjectLoans['project_status'], [ProjectsStatus::PROCEDURE_SAUVEGARDE, ProjectsStatus::REDRESSEMENT_JUDICIAIRE, ProjectsStatus::LIQUIDATION_JUDICIAIRE, ProjectsStatus::RECOUVREMENT])) {
                $oActiveSheet->mergeCells('G' . ($iRowIndex + 2) . ':K'. ($iRowIndex + 2));
                $oActiveSheet->setCellValue(
                    'F' . ($iRowIndex + 2),
                    $this->get('translator')->transChoice(
                        'lender-operations_loans-table-project-procedure-in-progress',
                        $aProjectLoans['count']['declaration']
                    )
                );
            } else {
                $oActiveSheet->setCellValue('G' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['next_payment_date'])));
                $oActiveSheet->setCellValue('H' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['end_date'])));
                $oActiveSheet->setCellValue('I' . ($iRowIndex + 2), $repaymentSchedule->getRepaidCapital(['id_lender' => $lender->id_lender_account, 'id_project' => $aProjectLoans['id']]));
                $oActiveSheet->setCellValue('J' . ($iRowIndex + 2), $repaymentSchedule->getRepaidInterests(['id_lender' => $lender->id_lender_account, 'id_project' => $aProjectLoans['id']]));
                $oActiveSheet->setCellValue('K' . ($iRowIndex + 2), $repaymentSchedule->getOwedCapital(['id_lender' => $lender->id_lender_account, 'id_project' => $aProjectLoans['id']]));
            }

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
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \transactions $transaction */
        $transaction = $entityManagerSimulator->getRepository('transactions');
        /** @var \clients $client */
        $client = $entityManagerSimulator->getRepository('clients');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        /** @var \lender_tax_exemption $taxExemption */
        $taxExemption = $entityManagerSimulator->getRepository('lender_tax_exemption');

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
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        /** @var \loans $loan */
        $loan = $entityManagerSimulator->getRepository('loans');
        /** @var \projects $project */
        $project = $entityManagerSimulator->getRepository('projects');
        /** @var \clients $client */
        $client = $entityManagerSimulator->getRepository('clients');
        $client->get($lender->id_client_owner);
        $notificationsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Notifications');

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

        /** @var UserLender $user */
        $user               = $this->getUser();
        $projectsInDept     = $project->getProjectsInDebt();
        $filters            = $request->request->get('filter', []);
        $year               = isset($filters['date']) && false !== filter_var($filters['date'], FILTER_VALIDATE_INT) ? $filters['date'] : null;
        $status             = isset($filters['status']) && in_array($filters['status'], array_keys(self::LOAN_STATUS_FILTER)) ? self::LOAN_STATUS_FILTER[$filters['status']] : null;
        $loanStatus         = array_fill_keys(array_keys(self::LOAN_STATUS_FILTER), 0);
        $lenderLoans        = $loan->getSumLoansByProject($lender->id_lender_account, $sOrderBy, $year, $status);
        $lenderProjectLoans = [];

        foreach ($lenderLoans as $projectLoans) {
            $loanData = [];
            /** @var \DateTime $startDateTime */
            $startDateTime = new \DateTime(date('Y-m-d'));
            /** @var \DateTime $endDateTime */
            $endDateTime = new \DateTime($projectLoans['fin']);
            /** @var \DateInterval $remainingDuration */
            $remainingDuration = $startDateTime->diff($endDateTime);

            $loanData['id']                       = $projectLoans['id_project'];
            $loanData['url']                      = $this->generateUrl('project_detail', ['projectSlug' => $projectLoans['slug']]);
            $loanData['name']                     = $projectLoans['title'];
            $loanData['rate']                     = round($projectLoans['rate'], 1);
            $loanData['risk']                     = $projectLoans['risk'];
            $loanData['amount']                   = round($projectLoans['amount']);
            $loanData['start_date']               = $projectLoans['debut'];
            $loanData['end_date']                 = $projectLoans['fin'];
            $loanData['next_payment_date']        = $projectLoans['next_echeance'];
            $loanData['monthly_repayment_amount'] = $projectLoans['monthly_repayment_amount'];
            $loanData['duration']                 = $remainingDuration->y * 12 + $remainingDuration->m;
            $loanData['final_repayment_date']     = $projectLoans['final_repayment_date'];
            $loanData['remaining_capital_amount'] = $projectLoans['remaining_capital'];
            $loanData['project_status']           = $projectLoans['project_status'];

            switch ($projectLoans['project_status']) {
                case \projects_status::PROBLEME:
                case \projects_status::PROBLEME_J_X:
                    $loanData['status'] = 'late';
                    ++$loanStatus['late-repayment'];
                    break;
                case \projects_status::RECOUVREMENT:
                    $loanData['status'] = 'completing';
                    ++$loanStatus['problem'];
                    break;
                case \projects_status::PROCEDURE_SAUVEGARDE:
                case \projects_status::REDRESSEMENT_JUDICIAIRE:
                case \projects_status::LIQUIDATION_JUDICIAIRE:
                    $loanData['status'] = 'problem';
                    ++$loanStatus['problem'];
                    break;
                case \projects_status::DEFAUT:
                    $loanData['status'] = 'defaulted';
                    ++$loanStatus['problem'];
                    break;
                case \projects_status::REMBOURSE:
                case \projects_status::REMBOURSEMENT_ANTICIPE:
                    $loanData['status'] = 'completed';
                    ++$loanStatus['repaid'];
                    break;
                case \projects_status::REMBOURSEMENT:
                default:
                    $loanData['status'] = 'inprogress';
                    ++$loanStatus['repayment'];
                    break;
            }
            try {
                $loanData['activity'] = [
                    'unread_count' => $notificationsRepository->countUnreadNotificationsForClient($lender->id_lender_account, $projectLoans['id_project'])
                ];
            } catch (\Exception $exception) {
                unset($exception);
                $loanData['activity'] = [
                    'unread_count' => 0
                ];
            }

            $projectLoansDetails = $entityManager->getRepository('UnilendCoreBusinessBundle:Loans')
                ->findBy([
                    'idLender' => $lender->id_lender_account,
                    'idProject' => $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectLoans['id_project'])
                ]);
            $loans               = [];
            $loanData['count']   = [
                'bond'        => 0,
                'contract'    => 0,
                'declaration' => 0
            ];

            foreach ($projectLoansDetails as $partialLoan) {
                (1 == $partialLoan->getIdTypeContract()->getIdContract()) ? $loanData['count']['bond']++ : $loanData['count']['contract']++;

                $loans[] = [
                    'rate'      => round($partialLoan->getRate(), 1),
                    'amount'    => bcdiv($partialLoan->getAmount(), 100, 0),
                    'documents' => $this->getDocumentDetail(
                        $projectLoans['project_status'],
                        $user->getHash(),
                        $partialLoan->getIdLoan(),
                        $partialLoan->getIdTypeContract()->getIdContract(),
                        $projectsInDept,
                        $projectLoans['id_project'],
                        $loanData['count']['declaration']
                    )
                ];
            }

            $loanData['loans']    = $loans;
            $lenderProjectLoans[] = $loanData;
            unset($loans, $loanData);
        }

        $seriesData  = [];
        $chartColors = [
            'late-repayment' => '#5FC4D0',
            'problem'        => '#F2980C',
            'repaid'         => '#1B88DB',
            'repayment'      => '#428890'
        ];

        foreach ($loanStatus as $status => $count) {
            if ($count) {
                $seriesData[] = [
                    'name'         => $this->get('translator')->transChoice('lender-operations_loans-chart-legend-loan-status-' . $status, $count, ['%count%' => $count]),
                    'y'            => $count,
                    'showInLegend' => true,
                    'color'        => $chartColors[$status],
                    'status'       => $status
                ];
            }
        }

        return ['lenderLoans' => $lenderProjectLoans, 'seriesData' => $seriesData];
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
     * @Route("/operations/projectNotifications/{projectId}", name="lender_loans_notifications", requirements={"projectId": "\d+"})
     * @Security("has_role('ROLE_LENDER')")
     * @Method("GET")
     *
     * @param int $projectId
     *
     * @return Response
     */
    public function loadProjectNotificationsAction($projectId)
    {
        try {
            $data = $this->getProjectInformation($projectId);
            $code               = Response::HTTP_OK;
        } catch (\Exception $exception) {
            $data = [];
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
            $this->get('logger')->error('Exception while getting client notifications for id_project: ' . $projectId . ' Message: ' . $exception->getMessage(), ['id_client' => $this->getUser()->getClientId(), 'class' => __CLASS__, 'function' => __FUNCTION__]);
        }

        return new JsonResponse(
            [
                'tpl' => $this->renderView(':frontbundle/pages/lender_operations:my_loans_details_activity.html.twig', ['projectNotifications' => $data, 'code' => $code]),
            ],
            $code
        );
    }

    /**
     * @param int $projectId
     *
     * @return array
     */
    private function getProjectInformation($projectId)
    {
        $entityManager      = $this->get('doctrine.orm.entity_manager');
        $project            = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);
        $projectManager     = $this->get('unilend.service.project_manager');
        $projectInformation = $projectManager->getProjectEventsDetail($projectId, $this->getUser()->getClientId());
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        $data       = [];

        foreach ($projectInformation['projectStatus'] as $projectStatus) {
            $titleAndContent = $this->getProjectStatusTitleAndContent($projectStatus, $project, $translator);
            $data[]          = [
                'id'        => count($data),
                'projectId' => $projectId,
                'type'      => 'account',
                'image'     => 'account',
                'title'     => $titleAndContent['title'],
                'content'   => $titleAndContent['content'],
                'datetime'  => $projectStatus['added'],
                'iso-8601'  => $projectStatus['added']->format('c'),
                'status'    => 'read'
            ];
        }
        foreach ($projectInformation['projectNotifications'] as $projectNotification) {
            $data[] = [
                'id'        => count($data),
                'projectId' => $projectNotification->getIdProject()->getIdProject(),
                'image'     => 'information',
                'type'      => 'account',
                'title'     => $projectNotification->getSubject(),
                'datetime'  => $projectNotification->getNotificationDate(),
                'iso-8601'  => $projectNotification->getNotificationDate()->format('c'),
                'content'   => $projectNotification->getContent(),
                'status'    => 'read'
            ];
        }
        foreach ($projectInformation['recoveryAndEarlyRepayments'] as $repayment) {
            $titleAndContent = $this->getRepaymentTitleAndContent($repayment, $project, $translator, $ficelle);

            if (empty($titleAndContent['content'])) {
                continue;
            }
            $data[] = [
                'id'        => count($data),
                'projectId' => $projectId,
                'image'     => 'remboursement',
                'type'      => 'remboursement',
                'title'     => $titleAndContent['title'],
                'content'   => $titleAndContent['content'],
                'datetime'  => $repayment['added'],
                'iso-8601'  => $repayment['added']->format('c'),
                'status'    => 'read'
            ];
        }
        foreach ($projectInformation['scheduledRepayments'] as $repayment) {
            $titleAndContent = $this->getRepaymentTitleAndContent($repayment, $project, $translator, $ficelle);

            if (empty($titleAndContent['content'])) {
                continue;
            }
            $data[] = [
                'id'        => count($data),
                'projectId' => $projectId,
                'image'     => 'remboursement',
                'type'      => 'remboursement',
                'title'     => $titleAndContent['title'],
                'content'   => $titleAndContent['content'],
                'datetime'  => $repayment->getDateEcheanceReel(),
                'iso-8601'  => $repayment->getDateEcheanceReel()->format('c'),
                'status'    => 'read'
            ];
        }

        if (false === empty($data)) {
            usort($data, [$this, 'sortArrayByDate']);
        }

        foreach ($data as $index => $row) {
            $data[$index]['status'] = ($row['datetime']->format('Y-m-d H:i:s') > $this->getUser()->getLastLoginDate()) ? 'unread' : 'read';
        }
        return $data;
    }

    /**
     * @param array               $projectStatus
     * @param Projects            $project
     * @param TranslatorInterface $translator
     *
     * @return array
     */
    private function getProjectStatusTitleAndContent(array $projectStatus, Projects $project, TranslatorInterface $translator)
    {
        switch ($projectStatus['status']) {
            case ProjectsStatus::PROBLEME:
                $title   = $translator->trans('lender-notifications_late-repayment-title');
                $content = $translator->trans('lender-notifications_late-repayment-content', [
                    '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                    '%company%'    => $project->getIdCompany()->getName()
                ]);
                break;
            case ProjectsStatus::PROBLEME_J_X:
                $title   = $translator->trans('lender-notifications_late-repayment-x-days-title');
                $content = $translator->trans('lender-notifications_late-repayment-x-days-content', [
                    '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                    '%company%'    => $project->getIdCompany()->getName()
                ]);
                break;
            case ProjectsStatus::RECOUVREMENT:
                $title   = $translator->trans('lender-notifications_recovery-title');
                $content = $translator->trans('lender-notifications_recovery-content', [
                    '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                    '%company%'    => $project->getIdCompany()->getName()
                ]);
                break;
            case ProjectsStatus::PROCEDURE_SAUVEGARDE:
                $title   = $translator->trans('lender-notifications_precautionary-process-title');
                $content = $translator->trans('lender-notifications_precautionary-process-content', [
                    '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                    '%company%'    => $project->getIdCompany()->getName()
                ]);
                break;
            case ProjectsStatus::REDRESSEMENT_JUDICIAIRE:
                $title   = $translator->trans('lender-notifications_receivership-title');
                $content = $translator->trans('lender-notifications_receivership-content', [
                    '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                    '%company%'    => $project->getIdCompany()->getName()
                ]);
                break;
            case ProjectsStatus::LIQUIDATION_JUDICIAIRE:
                $title   = $translator->trans('lender-notifications_compulsory-liquidation-title');
                $content = $translator->trans('lender-notifications_compulsory-liquidation-content', [
                    '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                    '%company%'    => $project->getIdCompany()->getName()
                ]);
                break;
            case ProjectsStatus::DEFAUT:
                $title   = $translator->trans('lender-notifications_company-failure-title');
                $content = $translator->trans('lender-notifications_company-failure-content', [
                    '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]) . '#project-section-info',
                    '%company%'    => $project->getIdCompany()->getName()
                ]);
                break;
            default:
                $title   = $projectStatus['label'];
                $content = '';
                break;
        }
        $content .= (false === empty($projectStatus['siteContent'])) ? '<br>' . $projectStatus['siteContent'] : '';

        return ['title' => $title, 'content' => $content];
    }

    /**
     * @param array|Echeanciers   $repayment
     * @param Projects            $project
     * @param TranslatorInterface $translator
     * @param \ficelle            $ficelle
     * @return array
     */
    private function getRepaymentTitleAndContent($repayment, Projects $project, TranslatorInterface $translator, \ficelle $ficelle)
    {
        if ($repayment instanceof Echeanciers) {
            $title   = $translator->trans('lender-notifications_repayment-title');
            $content = $translator->trans('lender-notifications_repayment-content', [
                '%amount%'     => $ficelle->formatNumber($repayment->getMontant() / 100, 2),
                '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]),
                '%company%'    => $project->getIdCompany()->getName()
            ]);
        } else {
            switch ($repayment['typeTransaction']) {
                case \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT:
                    $title   = $translator->trans('lender-notifications_early-repayment-title');
                    $content = $translator->trans('lender-notifications_early-repayment-content', [
                        '%amount%'     => $ficelle->formatNumber($repayment['amount'] / 100, 2),
                        '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]),
                        '%company%'    => $project->getIdCompany()->getName()
                    ]);
                    break;
                case \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT:
                    $title   = $translator->trans('lender-notifications_recovery-repayment-title');
                    $content = $translator->trans('lender-notifications_recovery-repayment-content', [
                        '%amount%'     => $ficelle->formatNumber($repayment['amount'] / 100, 2),
                        '%projectUrl%' => $this->generateUrl('project_detail', ['projectSlug' => $project->getSlug()]),
                        '%company%'    => $project->getIdCompany()->getName()
                    ]);
                    break;
                default:
                    $title   = '';
                    $content = '';
                    break;
            }
        }

        return ['title' => $title, 'content' => $content];
    }

    /**
     * @param array $a
     * @param array $b
     *
     * @return mixed
     */
    private function sortArrayByDate(array $a, array $b)
    {
        return $b['datetime']->getTimestamp() - $a['datetime']->getTimestamp();
    }
}
