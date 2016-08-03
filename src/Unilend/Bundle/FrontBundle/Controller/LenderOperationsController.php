<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;

class LenderOperationsController extends Controller
{
    const LAST_OPERATION_DATE = '2013-01-01';

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
        /** @var \loans loans */
        $loan = $entityManager->getRepository('loans');
        /** @var \projects_status $projectStatus */
        $projectStatus = $entityManager->getRepository('projects_status');
        /** @var \indexage_vos_operations $lenderOperationsIndex */
        $lenderOperationsIndex = $entityManager->getRepository('indexage_vos_operations');
        /** @var \ifu $uniqueFiscalForm */
        $uniqueFiscalForm = $entityManager->getRepository('ifu');
        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        $lender->get($this->getUser()->getClientId(), 'id_client_owner');
        $this->indexOperations($lenderOperationsIndex, $lender);

        /**@todo the right date is month -1, for test perpose, i used month - 2 */

        $lenderOperations       = $lenderOperationsIndex->select('id_client= ' . $this->getUser()->getClientId() . ' AND DATE(date_operation) >= "' . date('Y-m-d', strtotime('-2 month')) . '" AND DATE(date_operation) <= "' . date('Y-m-d') . '"', 'date_operation DESC, id_projet DESC');
        $projectsFundedByLender = $lenderOperationsIndex->get_liste_libelle_projet('id_client = ' . $this->getUser()->getClientId() . ' AND DATE(date_operation) >= "' . date('Y-m-d', strtotime('-2 month')) . '" AND DATE(date_operation) <= "' . date('Y-m-d') . '"');
//        $this->lLoans           = $loan->select('id_lender = ' . $lender->id_lender_account . ' AND YEAR(added) = ' . date('Y') . ' AND status = 0', 'added DESC');
//        $this->liste_docs = $uniqueFiscalForm->select('id_client =' . $this->getUser()->getClientId() . ' AND statut = 1', 'annee ASC');

//        unset($_SESSION['filtre_vos_operations']);
//        unset($_SESSION['id_last_action']);
//
//        $_SESSION['filtre_vos_operations']['debut']            = date('d/m/Y', strtotime('-1 month'));
//        $_SESSION['filtre_vos_operations']['fin']              = date('d/m/Y');
//        $_SESSION['filtre_vos_operations']['nbMois']           = '1';
//        $_SESSION['filtre_vos_operations']['annee']            = date('Y');
//        $_SESSION['filtre_vos_operations']['tri_type_transac'] = 1;
//        $_SESSION['filtre_vos_operations']['tri_projects']     = 1;
//        $_SESSION['filtre_vos_operations']['id_last_action']   = 'order_operations';
//        $_SESSION['filtre_vos_operations']['order']            = '';
//        $_SESSION['filtre_vos_operations']['type']             = '';
//        $_SESSION['filtre_vos_operations']['id_client']        = $this->getUser()->getClientId();

        $loans = $this->commonLoans($request, $lender);

        $loanYears = array_count_values(array_column($loans['lenderLoans'], 'loan_year'));
        ksort($loanYears);

        return $this->render(
            ':frontbundle/pages/lender_operations:index_layout.html.twig',
            [
                'lenderOperations'       => $lenderOperations,
                'projectsFundedByLender' => $projectsFundedByLender,
                'detailedOperations'     => [\transactions_types::TYPE_LENDER_REPAYMENT, \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT, \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT],
                'loansStatusFilter'      => $projectStatus->select('status >= ' . \projects_status::REMBOURSEMENT, 'status ASC'),
                'loansYears'             => $loanYears,
                'lenderLoans'            => $loans['lenderLoans'],
                'loanStatus'             => $loans['loanStatus'],
                'seriesData'             => $loans['seriesData'],
                'repayedCapitalLabel'    => $translationManager->selectTranslation('lender-operations', 'repayed-capital-amount'),
                'repayedInterestLabel'   => $translationManager->selectTranslation('lender-operations', 'repayed-interest-amount')
            ]);
    }

    /**
     * @param \indexage_vos_operations $lenderOperationsIndex
     * @param \lenders_accounts $lender
     */
    private function indexOperations(\indexage_vos_operations $lenderOperationsIndex, \lenders_accounts $lender)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $entityManager->getRepository('echeanciers');
        /** @var \echeanciers_recouvrements_prorata $recoveryRepayment */
        $recoveryRepayment = $entityManager->getRepository('echeanciers_recouvrements_prorata');
        /** @var \transactions $transaction */
        $transaction = $entityManager->getRepository('transactions');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \lenders_imposition_history $lenderTaxHistory */
        $lenderTaxHistory = $entityManager->getRepository('lenders_imposition_history');
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        $client->get($this->getUser()->getClientId());
        $settings->get('Recouvrement - commission ht', 'type');
        $commission_ht = $settings->value;
        $settings->get('TVA', 'type');
        $tva = $settings->value;

        $array_type_transactions = [
            1  => $translationManager->selectTranslation('lender-operations', 'operation-label-money-deposit'),
            2  => [
                1 => $translationManager->selectTranslation('lender-operations', 'operation-label-current-offer'),
                2 => $translationManager->selectTranslation('lender-operations', 'operation-label-rejected-offer'),
                3 => $translationManager->selectTranslation('lender-operations', 'operation-label-accepted-offer')
            ],
            3  => $translationManager->selectTranslation('lender-operations', 'operation-label-money-deposit'),
            4  => $translationManager->selectTranslation('lender-operations', 'operation-label-money-deposit'),
            5  => [
                1 => $translationManager->selectTranslation('lender-operations', 'operation-label-refund'),
                2 => $translationManager->selectTranslation('lender-operations', 'operation-label-recovery')
            ],
            7  => $translationManager->selectTranslation('lender-operations', 'operation-label-money-deposit'),
            8  => $translationManager->selectTranslation('lender-operations', 'operation-label-money-withdrawal'),
            16 => $translationManager->selectTranslation('lender-operations', 'operation-label-welcome-offer'),
            17 => $translationManager->selectTranslation('lender-operations', 'operation-label-welcome-offer-withdrawal'),
            19 => $translationManager->selectTranslation('lender-operations', 'operation-label-godson-gain'),
            20 => $translationManager->selectTranslation('lender-operations', 'operation-label-godfather-gain'),
            22 => $translationManager->selectTranslation('lender-operations', 'operation-label-anticipated-repayment'),
            23 => $translationManager->selectTranslation('lender-operations', 'operation-label-anticipated-repayment'),
            26 => $translationManager->selectTranslation('lender-operations', 'operation-label-lender-recovery')
        ];

        $sLastOperation = $lenderOperationsIndex->getLastOperationDate($this->getUser()->getClientId());

        if (empty($sLastOperation)) {
            $date_debut_a_indexer = self::LAST_OPERATION_DATE;
        } else {
            $date_debut_a_indexer = substr($sLastOperation, 0, 10);
        }

        $operations = $transaction->selectTransactionsOp($array_type_transactions, $date_debut_a_indexer, $this->getUser()->getClientId());

        foreach ($operations as $t) {
            if (0 == $lenderOperationsIndex->counter('id_transaction = ' . $t['id_transaction'] . ' AND libelle_operation = "' . $t['type_transaction_alpha'] . '"')) {
                $retenuesfiscals = 0.0;
                $capital         = 0.0;
                $interets        = 0.0;

                if ($repaymentSchedule->get($t['id_echeancier'], 'id_echeancier')) {
                    $retenuesfiscals = $repaymentSchedule->prelevements_obligatoires + $repaymentSchedule->retenues_source + $repaymentSchedule->csg + $repaymentSchedule->prelevements_sociaux + $repaymentSchedule->contributions_additionnelles + $repaymentSchedule->prelevements_solidarite + $repaymentSchedule->crds;
                    $capital         = $repaymentSchedule->capital;
                    $interets        = $repaymentSchedule->interets;
                }

                // si c'est un recouvrement on remplace les données
                if ($t['type_transaction'] == 5 && $t['recouvrement'] == 1 && $recoveryRepayment->get($t['id_transaction'], 'id_transaction')) {
                    $retenuesfiscals = $recoveryRepayment->prelevements_obligatoires + $recoveryRepayment->retenues_source + $recoveryRepayment->csg + $recoveryRepayment->prelevements_sociaux + $recoveryRepayment->contributions_additionnelles + $recoveryRepayment->prelevements_solidarite + $recoveryRepayment->crds;
                    $capital         = $recoveryRepayment->capital;
                    $interets        = $recoveryRepayment->interets;
                }

                // si exoneré à la date de la transact on change le libelle
                $libelle_prelevements = $translationManager->selectTranslation('lender-operations', 'tax-and-social-deductions');
                // on check si il s'agit d'une PM ou PP
                if ($client->type == 1 or $client->type == 3) {
                    // Si le client est exoneré on doit modifier le libelle de prelevement
                    // on doit checker si le client est exonéré
                    $exoneration = $lenderTaxHistory->is_exonere_at_date($lender->id_lender_account, $t['date_transaction']);

                    if ($exoneration) {
                        $libelle_prelevements = $translationManager->selectTranslation('lender-operations', 'social-deductions');
                    }
                } else {// PM
                    $libelle_prelevements = $translationManager->selectTranslation('lender-operations', 'deductions-at-source');
                }

                $lenderOperationsIndex->id_client           = $t['id_client'];
                $lenderOperationsIndex->id_transaction      = $t['id_transaction'];
                $lenderOperationsIndex->id_echeancier       = $t['id_echeancier'];
                $lenderOperationsIndex->id_projet           = $t['le_id_project'];
                $lenderOperationsIndex->type_transaction    = $t['type_transaction'];
                $lenderOperationsIndex->recouvrement        = $t['recouvrement'];
                $lenderOperationsIndex->libelle_operation   = $t['type_transaction_alpha'];
                $lenderOperationsIndex->bdc                 = $t['bdc'];
                $lenderOperationsIndex->libelle_projet      = $t['title'];
                $lenderOperationsIndex->date_operation      = $t['date_tri'];
                $lenderOperationsIndex->solde               = $t['solde'] * 100;
                $lenderOperationsIndex->montant_operation   = $t['amount_operation'];
                $lenderOperationsIndex->libelle_prelevement = $libelle_prelevements;
                $lenderOperationsIndex->montant_prelevement = $retenuesfiscals * 100;

                if ($t['type_transaction'] == 23) {
                    $lenderOperationsIndex->montant_capital = $t['montant'];
                    $lenderOperationsIndex->montant_interet = 0;
                } else {
                    $lenderOperationsIndex->montant_capital = $capital;
                    $lenderOperationsIndex->montant_interet = $interets;
                }


                if ($t['type_transaction'] == 5 && $t['recouvrement'] == 1) {
                    $taux_com         = $commission_ht;
                    $taux_tva         = $tva;
                    $montant          = $capital / 100 + $interets / 100;
                    $montant_avec_com = round($montant / (1 - $taux_com * (1 + $taux_tva)), 2);
                    $com_ht           = round($montant_avec_com * $taux_com, 2);
                    $com_tva          = round($com_ht * $taux_tva, 2);
                    $com_ttc          = round($com_ht + $com_tva, 2);

                    $lenderOperationsIndex->commission_ht  = $com_ht * 100;
                    $lenderOperationsIndex->commission_tva = $com_tva * 100;
                    $lenderOperationsIndex->commission_ttc = $com_ttc * 100;
                }
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
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        /** @var \loans $loan */
        $loan = $this->get('unilend.service.entity_manager')->getRepository('loans');
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');

        $orderField     = $request->request->get('type', 'start');
        $orderDirection = strtoupper($request->request->get('order', 'ASC'));

        switch ($orderField) {
            case 'status':
                $orderField = 'status';
                $sOrderBy   = 'project_status ' . $orderDirection . ', debut DESC, p.title ASC';
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
                $sOrderBy   = 'mensuel ' . $orderDirection . ', debut DESC, p.title ASC';
                break;
            case 'start':
            default:
                $orderField = 'start';
                $sOrderBy   = 'debut ' . $orderDirection . ', p.title ASC';
                break;
        }
        $projectsInDept = $project->getProjectsInDebt();
        $lenderLoans    = $loan->getSumLoansByProject($lender->id_lender_account, $sOrderBy, $request->request->get('year', null), $request->request->get('status', null));
        $loanStatus     = [
            'no-problem'            => 0,
            'late-repayment'        => 0,
            'recovery'              => 0,
            'collective-proceeding' => 0,
            'default'               => 0,
            'refund-finished'       => 0,
        ];

        foreach ($lenderLoans as $iLoandIndex => $aProjectLoans) {
            $lenderLoans[$iLoandIndex]['project_duration'] = (new \DateTime($aProjectLoans['debut']))->diff((new \DateTime($aProjectLoans['fin'])))->y * 12;
            switch ($aProjectLoans['project_status']) {
                case \projects_status::PROBLEME:
                case \projects_status::PROBLEME_J_X:
                    $lenderLoans[$iLoandIndex]['status_color'] = 'late';
                    $lenderLoans[$iLoandIndex]['color'] = '#5FC4D0';
                    ++$loanStatus['late-repayment'];
                    break;
                case \projects_status::RECOUVREMENT:
                    $lenderLoans[$iLoandIndex]['status_color'] = 'completing';
                    $lenderLoans[$iLoandIndex]['color'] = '#FFCA2C';
                    ++$loanStatus['recovery'];
                    break;
                case \projects_status::PROCEDURE_SAUVEGARDE:
                case \projects_status::REDRESSEMENT_JUDICIAIRE:
                case \projects_status::LIQUIDATION_JUDICIAIRE:
                    $lenderLoans[$iLoandIndex]['status_color'] = 'problem';
                    $lenderLoans[$iLoandIndex]['color'] = '#F2980C';
                    ++$loanStatus['collective-proceeding'];
                    break;
                case \projects_status::DEFAUT:
                    $lenderLoans[$iLoandIndex]['status_color'] = 'defaulted';
                    $lenderLoans[$iLoandIndex]['color'] = '#F76965';
                    ++$loanStatus['default'];
                    break;
                case \projects_status::REMBOURSE:
                case \projects_status::REMBOURSEMENT_ANTICIPE:
                    $lenderLoans[$iLoandIndex]['status_color'] = 'completed';
                    $lenderLoans[$iLoandIndex]['color'] = '#1B88DB';
                    ++$loanStatus['refund-finished'];
                    break;
                case \projects_status::REMBOURSEMENT:
                default:
                    $lenderLoans[$iLoandIndex]['status_color'] = 'inprogress';
                    $lenderLoans[$iLoandIndex]['color'] = '#428890';
                    ++$loanStatus['no-problem'];
                    break;
            }

            $chartColors = [
                'late-repayment' => '#5FC4D0',
                'recovery' => '#FFCA2C',
                'collective-proceeding' => '#F2980C',
                'default'  =>  '#F76965',
                'refund-finished' => '#1B88DB',
                'no-problem' => '#428890'
            ];

            /**
             * "documents": [{
             * "docType": "contract",
             * "docTypeName": "Contrat de prét",
             * "name": "contract-de-pret.pdf",
             * "url": "http://placehold.it/600x600.png",
             * "size": 123456789
             * }]
             */

            if ($aProjectLoans['nb_loan'] == 1) {

                $lenderLoans[$iLoandIndex]['documents'] = $this->getDocumentDetail(
                    $aProjectLoans['project_status'],
                    $client->hash,
                    $aProjectLoans['id_loan_if_one_loan'],
                    $aProjectLoans['id_type_contract'],
                    $projectsInDept,
                    $aProjectLoans['id_project']);
            } else {
                $lenderLoans[$iLoandIndex]['loan_details'] = $loan->select('id_lender = ' . $lender->id_lender_account . ' AND id_project = ' . $aProjectLoans['id_project']);

                foreach ($lenderLoans[$iLoandIndex]['loan_details'] as $key => $item) {
                    $lenderLoans[$iLoandIndex]['loan_details'][$key]['documents'] = $this->getDocumentDetail(
                        $aProjectLoans['project_status'],
                        $client->hash,
                        $item['id_loan'],
                        $item['id_type_contract'],
                        $projectsInDept,
                        $aProjectLoans['id_project']);
                }
            }
        }
        $seriesData = [];
        foreach ($loanStatus as $status => $count) {
            $seriesData[] = [
                'name'         => str_replace('%count%', $count, $translationManager->selectTranslation('lender-operations', 'loan-status-' . $status)),
                'y'            => $count,
                'showInLegend' => true,
                'color'        => $chartColors[$status]
            ];
        }
        return ['lenderLoans' => $lenderLoans, 'loanStatus' => $loanStatus, 'seriesData' => $seriesData];
    }

    /**
     * @param int $projectStatus
     * @param string $hash
     * @param int $loanId
     * @param int $docTypeId
     * @param array $projectsInDept
     * @param int $projectId
     * @return array
     */
    private function getDocumentDetail($projectStatus, $hash, $loanId, $docTypeId, array $projectsInDept, $projectId)
    {
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        $documents = [];
        if ($projectStatus >= \projects_status::REMBOURSEMENT) {
            $documents[] = [
                'url'         => $this->get('assets.packages')->getUrl('') . '/pdf/contrat/' . $hash . '/' . $loanId,
                'docTypeName' => $translationManager->selectTranslation('lender-operations', 'contract-type-' . $docTypeId),
                'docType'     => (1 == $docTypeId) ? 'bond' : 'contract'
            ];
        }

        if (in_array($projectId, $projectsInDept)) {
            $documents[] = [
                'url'         => $this->get('assets.packages')->getUrl('') . '/pdf/contrat/' . $hash . '/' . $loanId,
                'docTypeName' => $translationManager->selectTranslation('lender-operations', 'declaration-of-debt'),
                'docType'     => 'contract'
            ];
        }
        return $documents;
    }
}
