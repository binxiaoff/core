<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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

        $lender->get($this->getUser()->getClientId(), 'id_client_owner');
        $this->lenderOperationIndexing($lenderOperationsIndex, $lender);

        $lenderOperations       = $lenderOperationsIndex->select('id_client= ' . $this->getUser()->getClientId() . ' AND DATE(date_operation) >= "' . date('Y-m-d', strtotime('-1 month')) . '" AND DATE(date_operation) <= "' . date('Y-m-d') . '"', 'date_operation DESC, id_projet DESC');
        $projectsFundedByLender = $lenderOperationsIndex->get_liste_libelle_projet('id_client = ' . $this->getUser()->getClientId() . ' AND DATE(date_operation) >= "' . date('Y-m-d', strtotime('-1 month')) . '" AND DATE(date_operation) <= "' . date('Y-m-d') . '"');

        unset($_SESSION['filtre_vos_operations']);
        unset($_SESSION['id_last_action']);

        $_SESSION['filtre_vos_operations']['debut']            = date('d/m/Y', strtotime('-1 month'));
        $_SESSION['filtre_vos_operations']['fin']              = date('d/m/Y');
        $_SESSION['filtre_vos_operations']['nbMois']           = 1;
        $_SESSION['filtre_vos_operations']['annee']            = date('Y');
        $_SESSION['filtre_vos_operations']['tri_type_transac'] = 1;
        $_SESSION['filtre_vos_operations']['tri_projects']     = 1;
        $_SESSION['filtre_vos_operations']['id_last_action']   = 'operation';
        $_SESSION['filtre_vos_operations']['order']            = '';
        $_SESSION['filtre_vos_operations']['type']             = '';
        $_SESSION['filtre_vos_operations']['id_client']        = $this->getUser()->getClientId();

        $loans     = $this->commonLoans($request, $lender);
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
                'repayedInterestLabel'   => $translationManager->selectTranslation('lender-operations', 'repayed-interest-amount'),
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
        $loans     = $this->commonLoans($request, $lender);
        $loanYears = array_count_values(array_column($loans['lenderLoans'], 'loan_year'));
        ksort($loanYears);
        return $this->json(
            [
                'target'   => 'loans',
                'template' => $this->render(':frontbundle/pages/lender_operations:my_loans.html.twig',
                    [
                        'loansStatusFilter' => $projectStatus->select('status >= ' . \projects_status::REMBOURSEMENT, 'status ASC'),
                        'loansYears'        => $loanYears,
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

        // On met en session les POST pour le PDF
        $_SESSION['filtre_vos_operations']['debut']            = $request->request->get('filter')['start'];
        $_SESSION['filtre_vos_operations']['fin']              = $request->request->get('filter')['end'];
        $_SESSION['filtre_vos_operations']['nbMois']           = $request->request->get('filter')['slide'];
        $_SESSION['filtre_vos_operations']['annee']            = $request->request->get('filter')['year'];
        $_SESSION['filtre_vos_operations']['tri_type_transac'] = $request->request->get('filter')['operation'];
        $_SESSION['filtre_vos_operations']['tri_projects']     = $request->request->get('filter')['project'];
        $_SESSION['filtre_vos_operations']['id_last_action']   = $request->request->get('filter')['id_last_action'];
        $_SESSION['filtre_vos_operations']['order']            = $request->request->get('order', '');
        $_SESSION['filtre_vos_operations']['type']             = $request->request->get('type', '');
        $_SESSION['filtre_vos_operations']['id_client']        = $this->getUser()->getClientId();

        // tri start/end
        if (in_array($request->request->get('filter')['id_last_action'], array('start', 'end'))) {

            $debutTemp                  = explode('/', $request->request->get('filter')['start']);
            $finTemp                    = explode('/', $request->request->get('filter')['end']);
            $date_debut_time            = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date start
            $date_fin_time              = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date end
            $_SESSION['id_last_action'] = $request->request->get('filter')['id_last_action'];

        } elseif ($request->request->get('filter')['id_last_action'] == 'slide') {
            $nbMois                     = $request->request->get('filter')['slide'];
            $date_debut_time            = mktime(0, 0, 0, date("m") - $nbMois, date("d"), date('Y')); // date start
            $date_fin_time              = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date end
            $_SESSION['id_last_action'] = $request->request->get('filter')['id_last_action'];
        } elseif ($request->request->get('filter')['id_last_action'] == 'year') {
            $year            = $request->request->get('filter')['year'];
            $date_debut_time = mktime(0, 0, 0, 1, 1, $year);    // date start

            if (date('Y') == $year) {
                $date_fin_time = mktime(0, 0, 0, date('m'), date('d'), $year);
            } // date end
            else {
                $date_fin_time = mktime(0, 0, 0, 12, 31, $year);
            } // date end
            $_SESSION['id_last_action'] = $request->request->get('filter')['id_last_action'];
        } elseif (isset($_SESSION['id_last_action'])) {

            if (in_array($_SESSION['id_last_action'], array('start', 'end'))) {
                $debutTemp       = explode('/', $request->request->get('filter')['start']);
                $finTemp         = explode('/', $request->request->get('filter')['end']);
                $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date start
                $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date end
            } elseif ($_SESSION['id_last_action'] == 'slide') {
                $nbMois          = $request->request->get('filter')['slide'];
                $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, date("d"), date('Y')); // date start
                $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date end
            } elseif ($_SESSION['id_last_action'] == 'year') {
                $year            = $request->request->get('filter')['year'];
                $date_debut_time = mktime(0, 0, 0, 1, 1, $year);    // date start
                $date_fin_time   = mktime(0, 0, 0, 12, 31, $year); // date end
            }
        } else {
            $date_debut_time = strtotime('-1 month'); // date start
            $date_fin_time   = time();    // date end
        }

        $array_type_transactions_liste_deroulante = array(
            1 => '1,2,3,4,5,7,8,16,17,19,20,23,26',
            2 => '3,4,7,8',
            3 => '3,4,7',
            4 => '8',
            5 => '2',
            6 => '5,23,26'
        );

        $tri_type_transac = $array_type_transactions_liste_deroulante[$request->request->get('filter')['operation']];

        if (false === empty($request->request->get('filter')['project'])) {
            $tri_project = ' AND id_projet = ' . $request->request->get('filter')['project'];
        } else {
            $tri_project = '';
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

        $lenderOperations       = $lenderOperationsIndex->select('type_transaction IN (' . $tri_type_transac . ') AND id_client = ' . $this->getUser()->getClientId() . ' AND LEFT(date_operation,10) >= "' . date('Y-m-d', $date_debut_time) . '" AND LEFT(date_operation,10) <= "' . date('Y-m-d', $date_fin_time) . '"' . $tri_project, $order);
        $projectsFundedByLender = $lenderOperationsIndex->get_liste_libelle_projet('type_transaction IN (' . $tri_type_transac . ') AND id_client = ' . $this->getUser()->getClientId() . ' AND LEFT(date_operation,10) >= "' . date('Y-m-d', $date_debut_time) . '" AND LEFT(date_operation,10) <= "' . date('Y-m-d', $date_fin_time) . '"');

        $filters                    = $request->request->all();
        $filters['filter']['start'] = date('d/m/Y', $date_debut_time);
        $filters['filter']['end']   = date('d/m/Y', $date_fin_time);

        return $this->json(
            [
                'target'   => 'operations',
                'template' => $this->render(':frontbundle/pages/lender_operations:my_operations.html.twig',
                    [
                        'detailedOperations'     => [\transactions_types::TYPE_LENDER_REPAYMENT, \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT, \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT],
                        'projectsFundedByLender' => $projectsFundedByLender,
                        'lenderOperations'       => $lenderOperations,
                        'repayedCapitalLabel'    => $translationManager->selectTranslation('lender-operations', 'repayed-capital-amount'),
                        'repayedInterestLabel'   => $translationManager->selectTranslation('lender-operations', 'repayed-interest-amount'),
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

        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        $this->transactions                      = $entityManager->getRepository('transactions');
        $this->wallets_lines                     = $entityManager->getRepository('wallets_lines');
        $this->bids                              = $entityManager->getRepository('bids');
        $this->loans                             = $entityManager->getRepository('loans');
        $this->echeanciers                       = $entityManager->getRepository('echeanciers');
        $this->projects                          = $entityManager->getRepository('projects');
        $this->companies                         = $entityManager->getRepository('companies');
        $this->clients                           = $entityManager->getRepository('clients');
        $this->echeanciers_recouvrements_prorata = $entityManager->getRepository('echeanciers_recouvrements_prorata');

        $this->ficelle = Loader::loadLib('ficelle');

        $post_debut            = $_SESSION['filtre_vos_operations']['debut'];
        $post_fin              = $_SESSION['filtre_vos_operations']['fin'];
        $post_nbMois           = $_SESSION['filtre_vos_operations']['nbMois'];
        $post_annee            = $_SESSION['filtre_vos_operations']['annee'];
        $post_tri_type_transac = $_SESSION['filtre_vos_operations']['tri_type_transac'];
        $post_tri_projects     = $_SESSION['filtre_vos_operations']['tri_projects'];
        $post_id_last_action   = $_SESSION['filtre_vos_operations']['id_last_action'];
        $post_id_client        = $_SESSION['filtre_vos_operations']['id_client'];

        $this->clients->get($post_id_client, 'id_client');

        // tri start/end
        if (isset($post_id_last_action) && in_array($post_id_last_action, array('start', 'end'))) {
            $debutTemp = explode('/', $post_debut);
            $finTemp   = explode('/', $post_fin);

            $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date start
            $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date end

            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $post_id_last_action;

        } elseif (isset($post_id_last_action) && $post_id_last_action == 'slide') {// NB mois
            $nbMois          = $post_nbMois;
            $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, 1, date('Y')); // date start
            $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date end
            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $post_id_last_action;
        } elseif (isset($post_id_last_action) && $post_id_last_action == 'year') {// Annee
            $year            = $post_annee;
            $date_debut_time = mktime(0, 0, 0, 1, 1, $year);    // date start
            $date_fin_time   = mktime(0, 0, 0, 12, 31, $year); // date end
            // On sauvegarde la derniere action
            $_SESSION['id_last_action'] = $post_id_last_action;
        } elseif (isset($_SESSION['id_last_action'])) {// si on a une session
            if (in_array($_SESSION['id_last_action'], array('start', 'end'))) {
                $debutTemp       = explode('/', $post_debut);
                $finTemp         = explode('/', $post_fin);
                $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date start
                $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date end
            } elseif ($_SESSION['id_last_action'] == 'slide') {
                $nbMois          = $post_nbMois;
                $date_debut_time = mktime(0, 0, 0, date("m") - $nbMois, 1, date('Y')); // date start
                $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date end
            } elseif ($_SESSION['id_last_action'] == 'year') {
                $year            = $post_annee;
                $date_debut_time = mktime(0, 0, 0, 1, 1, $year);    // date start
                $date_fin_time   = mktime(0, 0, 0, 12, 31, $year); // date end
            }
        } else {// Par defaut (on se base sur le 1M)
            if (isset($post_debut) && isset($post_fin)) {
                $debutTemp       = explode('/', $post_debut);
                $finTemp         = explode('/', $post_fin);
                $date_debut_time = strtotime($debutTemp[2] . '-' . $debutTemp[1] . '-' . $debutTemp[0] . ' 00:00:00');    // date start
                $date_fin_time   = strtotime($finTemp[2] . '-' . $finTemp[1] . '-' . $finTemp[0] . ' 00:00:00');            // date end
            } else {
                $date_debut_time = mktime(0, 0, 0, date("m") - 1, 1, date('Y')); // date start
                $date_fin_time   = mktime(0, 0, 0, date("m"), date("d"), date('Y'));    // date end
            }
        }

        $this->date_debut = date('Y-m-d', $date_debut_time);
        $this->date_fin   = date('Y-m-d', $date_fin_time);

        $array_type_transactions_liste_deroulante = array(
            1 => '1,2,3,4,5,7,8,16,17,19,20,23,26',
            2 => '3,4,7,8',
            3 => '3,4,7',
            4 => '8',
            5 => '2',
            6 => '5,23,26'
        );

        if (isset($post_tri_type_transac)) {
            $tri_type_transac = $array_type_transactions_liste_deroulante[$post_tri_type_transac];
        } else {
            $tri_type_transac = $array_type_transactions_liste_deroulante[1];
        }

        if (isset($post_tri_projects)) {
            if (in_array($post_tri_projects, array(0, 1))) {
                $tri_project = '';
            } else {
                $tri_project = ' AND le_id_project = ' . $post_tri_projects;
            }
        }

        $order = 'date_operation DESC, id_transaction DESC';
        if (isset($_POST['type']) && isset($_POST['order'])) {
            $this->type  = $_POST['type'];
            $this->order = $_POST['order'];

            if ($this->type == 'order_operations') {
                if ($this->order == 'asc') {
                    $order = ' type_transaction ASC, id_transaction ASC';
                } else {
                    $order = ' type_transaction DESC, id_transaction DESC';
                }
            } elseif ($this->type == 'order_projects') {
                if ($this->order == 'asc') {
                    $order = ' libelle_projet ASC , id_transaction ASC';
                } else {
                    $order = ' libelle_projet DESC , id_transaction DESC';
                }
            } elseif ($this->type == 'order_date') {
                if ($this->order == 'asc') {
                    $order = ' date_operation ASC, id_transaction ASC';
                } else {
                    $order = ' date_operation DESC, id_transaction DESC';
                }
            } elseif ($this->type == 'order_montant') {
                if ($this->order == 'asc') {
                    $order = ' montant_operation ASC, id_transaction ASC';
                } else {
                    $order = ' montant_operation DESC, id_transaction DESC';
                }
            } elseif ($this->type == 'order_bdc') {
                if ($this->order == 'asc') {
                    $order = ' ABS(bdc) ASC, id_transaction ASC';
                } else {
                    $order = ' ABS(bdc) DESC, id_transaction DESC';
                }
            } else {
                $order = 'date_operation DESC, id_transaction DESC';
            }
        }
        $this->indexage_vos_operations = $entityManager->getRepository('indexage_vos_operations');

        $this->lTrans = $this->indexage_vos_operations->select('type_transaction IN (' . $tri_type_transac . ') AND id_client = ' . $this->clients->id_client . ' AND DATE(date_operation) >= "' . $this->date_debut . '" AND DATE(date_operation) <= "' . $this->date_fin . '"' . $tri_project, $order);

        // si exoneré à la date de la transact on change le libelle

        $content = '
        <meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8"/>
        <table border=1>
            <tr>
                <th>' . $translationManager->selectTranslation('lender-operations', 'operation') . '</th>
                <th>' . $translationManager->selectTranslation('lender-operations', 'contract-number') . '</th>
                <th> ID ' . $translationManager->selectTranslation('lender-operations', 'project') . '</th>
                <th>' . $translationManager->selectTranslation('lender-operations', 'project') . '</th>
                <th>' . $translationManager->selectTranslation('lender-operations', 'date') . '</th>
                <th>' . $translationManager->selectTranslation('lender-operations', 'amount') . '</th>
                <th>' . $translationManager->selectTranslation('lender-operations', 'repayed-capital-amount') . '</th>
                <th>' . $translationManager->selectTranslation('lender-operations', 'repayed-interest-amount') . '</th>
                <th>Pr&eacute;l&egrave;vements obligatoires</th>
                <th>Retenue &agrave; la source</th>
                <th>CSG</th>
                <th>Pr&eacute;l&egrave;vements sociaux</th>
                <th>Contributions additionnelles</th>
                <th>Pr&eacute;l&egrave;vements solidarit&eacute;</th>
                <th>CRDS</th>
                <th>' . $translationManager->selectTranslation('lender-operations', 'account-balance') . '</th>
                <td></td>
            </tr>';

        $asterix_on = false;
        foreach ($this->lTrans as $t) {
            $t["libelle_operation"] = $t["libelle_operation"];
            $t["libelle_projet"]    = $t['libelle_projet'];
            if ($t['montant_operation'] > 0) {
                $couleur = ' style="color:#40b34f;"';
            } else {
                $couleur = ' style="color:red;"';
            }

            $sProjectId = $t['id_projet'] == 0 ? '' : $t['id_projet'];

            $solde = $t['solde'];
            // remb
            if (in_array($t['type_transaction'], array(\transactions_types::TYPE_LENDER_REPAYMENT, \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT, \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT))) {
                $this->echeanciers->get($t['id_echeancier'], 'id_echeancier');

                $retenuesfiscals = $this->echeanciers->prelevements_obligatoires + $this->echeanciers->retenues_source + $this->echeanciers->csg + $this->echeanciers->prelevements_sociaux + $this->echeanciers->contributions_additionnelles + $this->echeanciers->prelevements_solidarite + $this->echeanciers->crds;

                if ($t['type_transaction'] != \transactions_types::TYPE_LENDER_REPAYMENT) {
                    $this->echeanciers->prelevements_obligatoires    = 0;
                    $this->echeanciers->retenues_source              = 0;
                    $this->echeanciers->interets                     = 0;
                    $this->echeanciers->retenues_source              = 0;
                    $this->echeanciers->csg                          = 0;
                    $this->echeanciers->prelevements_sociaux         = 0;
                    $this->echeanciers->contributions_additionnelles = 0;
                    $this->echeanciers->prelevements_solidarite      = 0;
                    $this->echeanciers->crds                         = 0;
                    $this->echeanciers->capital                      = $t['montant_operation'];
                } elseif ($t['type_transaction'] == \transactions_types::TYPE_LENDER_REPAYMENT && $t['recouvrement'] == 1 && $this->echeanciers_recouvrements_prorata->get($t['id_transaction'], 'id_transaction')) {
                    $retenuesfiscals = $this->echeanciers_recouvrements_prorata->prelevements_obligatoires + $this->echeanciers_recouvrements_prorata->retenues_source + $this->echeanciers_recouvrements_prorata->csg + $this->echeanciers_recouvrements_prorata->prelevements_sociaux + $this->echeanciers_recouvrements_prorata->contributions_additionnelles + $this->echeanciers_recouvrements_prorata->prelevements_solidarite + $this->echeanciers_recouvrements_prorata->crds;

                    $this->echeanciers->prelevements_obligatoires    = $this->echeanciers_recouvrements_prorata->prelevements_obligatoires;
                    $this->echeanciers->retenues_source              = $this->echeanciers_recouvrements_prorata->retenues_source;
                    $this->echeanciers->interets                     = $this->echeanciers_recouvrements_prorata->interets;
                    $this->echeanciers->retenues_source              = $this->echeanciers_recouvrements_prorata->retenues_source;
                    $this->echeanciers->csg                          = $this->echeanciers_recouvrements_prorata->csg;
                    $this->echeanciers->prelevements_sociaux         = $this->echeanciers_recouvrements_prorata->prelevements_sociaux;
                    $this->echeanciers->contributions_additionnelles = $this->echeanciers_recouvrements_prorata->contributions_additionnelles;
                    $this->echeanciers->prelevements_solidarite      = $this->echeanciers_recouvrements_prorata->prelevements_solidarite;
                    $this->echeanciers->crds                         = $this->echeanciers_recouvrements_prorata->crds;
                    $this->echeanciers->capital                      = $this->echeanciers_recouvrements_prorata->capital;
                }
                $content .= '
                    <tr>
                        <td>' . $t['libelle_operation'] . '</td>
                        <td>' . $t['bdc'] . '</td>
                        <td>' . $sProjectId . '</td>
                        <td>' . $t['libelle_projet'] . '</td>
                        <td>' . date('d-m-Y', strtotime($t['date_operation'])) . '</td>
                        <td' . $couleur . '>' . $this->ficelle->formatNumber($t['montant_operation'] / 100) . '</td>
                        <td>' . $this->ficelle->formatNumber(($this->echeanciers->capital / 100)) . '</td>
                        <td>' . $this->ficelle->formatNumber(($this->echeanciers->interets / 100)) . '</td>
                        <td>' . $this->ficelle->formatNumber($this->echeanciers->prelevements_obligatoires) . '</td>
                        <td>' . $this->ficelle->formatNumber($this->echeanciers->retenues_source) . '</td>
                        <td>' . $this->ficelle->formatNumber($this->echeanciers->csg) . '</td>
                        <td>' . $this->ficelle->formatNumber($this->echeanciers->prelevements_sociaux) . '</td>
                        <td>' . $this->ficelle->formatNumber($this->echeanciers->contributions_additionnelles) . '</td>
                        <td>' . $this->ficelle->formatNumber($this->echeanciers->prelevements_solidarite) . '</td>
                        <td>' . $this->ficelle->formatNumber($this->echeanciers->crds) . '</td>
                        <td>' . $this->ficelle->formatNumber($solde / 100) . '</td>
                        <td></td>
                    </tr>';

            } elseif (in_array($t['type_transaction'], array(8, 1, 3, 4, 16, 17, 19, 20))) {

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
                if (isset($array_type_transactions[$t['type_transaction']])) {
                    $t['libelle_operation'] = $array_type_transactions[$t['type_transaction']];
                } else {
                    $t['libelle_operation'] = '';
                }

                if ($t['type_transaction'] == 8 && $t['montant_operation'] > 0) {
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
                        <td' . $couleur . '>' . $this->ficelle->formatNumber($t['montant_operation'] / 100) . '</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td> 
                        <td>' . $this->ficelle->formatNumber($solde / 100) . '</td>
                        <td></td>
                    </tr>
                    ';

            } elseif (in_array($t['type_transaction'], array(2))) {//offres en cours
                $bdc = $t['bdc'];
                if ($t['bdc'] == 0) {
                    $bdc = "";
                }
                //asterix pour les offres acceptees
                $asterix       = "";
                $offre_accepte = false;
                if ($t['libelle_operation'] == $translationManager->selectTranslation('lender-operations', 'operation-label-accepted-offer')) {
                    $asterix       = " *";
                    $offre_accepte = true;
                    $asterix_on    = true;
                }
                $content .= '
                    <tr>
                        <td>' . $t['libelle_operation'] . '</td>
                        <td>' . $bdc . '</td>
                        <td>' . $sProjectId . '</td>
                        <td>' . $t['libelle_projet'] . '</td>
                        <td>' . date('d-m-Y', strtotime($t['date_operation'])) . '</td>
                        <td' . (! $offre_accepte ? $couleur : '') . '>' . $this->ficelle->formatNumber($t['montant_operation'] / 100) . '</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>' . $this->ficelle->formatNumber($t['solde'] / 100) . '</td>
                        <td>' . $asterix . '</td>
                    </tr>
                   ';
            }
        }
        $content .= '
        </table>';

        if ($asterix_on) {
            $content .= '
            <div>* ' . $translationManager->selectTranslation('lender-operations', 'accepted-offer-specific-mention') . '</div>';

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
        $loans = $this->commonLoans($request, $lender);

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
            $oActiveSheet->setCellValue('A' . ($iRowIndex + 2), $aProjectLoans['title']);
            $oActiveSheet->setCellValue('B' . ($iRowIndex + 2), $aProjectLoans['id_project']);
            $oActiveSheet->setCellValue('C' . ($iRowIndex + 2), $aProjectLoans['amount']);
            $oActiveSheet->setCellValue('D' . ($iRowIndex + 2), $this->get('unilend.service.translation_manager')->selectTranslation('lender-operations','project-status-filter-' . $aProjectLoans['project_status']));
            $oActiveSheet->setCellValue('E' . ($iRowIndex + 2), round($aProjectLoans['rate'], 1));
            $oActiveSheet->setCellValue('F' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['debut'])));
            $oActiveSheet->setCellValue('G' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['next_echeance'])));
            $oActiveSheet->setCellValue('H' . ($iRowIndex + 2), date('d/m/Y', strtotime($aProjectLoans['fin'])));
            $oActiveSheet->setCellValue('I' . ($iRowIndex + 2), (string) round($repaymentSchedule->sum('id_lender = ' . $lender->id_lender_account . ' AND id_project = ' . $aProjectLoans['id_project'] . ' AND status = 1', 'capital'), 2));
            $oActiveSheet->setCellValue('J' . ($iRowIndex + 2), round($repaymentSchedule->sum('id_lender = ' . $lender->id_lender_account . ' AND id_project = ' . $aProjectLoans['id_project'] . ' AND status = 1', 'interets'), 2));
            $oActiveSheet->setCellValue('K' . ($iRowIndex + 2), round($repaymentSchedule->sum('id_lender = ' . $lender->id_lender_account . ' AND id_project = ' . $aProjectLoans['id_project'] . ' AND status = 0', 'capital'), 2));

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
        /** @var \loans $loanEntity */
        $loanEntity = $this->get('unilend.service.entity_manager')->getRepository('loans');
        /** @var \projects $project */
        $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

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

        foreach ($lenderLoans as $iLoandIndex => $aProjectLoans) {
            $lenderLoans[$iLoandIndex]['project_duration'] = (new \DateTime($aProjectLoans['debut']))->diff((new \DateTime($aProjectLoans['fin'])))->y * 12;
            switch ($aProjectLoans['project_status']) {
                case \projects_status::PROBLEME:
                case \projects_status::PROBLEME_J_X:
                    $lenderLoans[$iLoandIndex]['status_color'] = 'late';
                    $lenderLoans[$iLoandIndex]['color']        = '#5FC4D0';
                    ++$loanStatus['late-repayment'];
                    break;
                case \projects_status::RECOUVREMENT:
                    $lenderLoans[$iLoandIndex]['status_color'] = 'completing';
                    $lenderLoans[$iLoandIndex]['color']        = '#FFCA2C';
                    ++$loanStatus['recovery'];
                    break;
                case \projects_status::PROCEDURE_SAUVEGARDE:
                case \projects_status::REDRESSEMENT_JUDICIAIRE:
                case \projects_status::LIQUIDATION_JUDICIAIRE:
                    $lenderLoans[$iLoandIndex]['status_color'] = 'problem';
                    $lenderLoans[$iLoandIndex]['color']        = '#F2980C';
                    ++$loanStatus['collective-proceeding'];
                    break;
                case \projects_status::DEFAUT:
                    $lenderLoans[$iLoandIndex]['status_color'] = 'defaulted';
                    $lenderLoans[$iLoandIndex]['color']        = '#F76965';
                    ++$loanStatus['default'];
                    break;
                case \projects_status::REMBOURSE:
                case \projects_status::REMBOURSEMENT_ANTICIPE:
                    $lenderLoans[$iLoandIndex]['status_color'] = 'completed';
                    $lenderLoans[$iLoandIndex]['color']        = '#1B88DB';
                    ++$loanStatus['refund-finished'];
                    break;
                case \projects_status::REMBOURSEMENT:
                default:
                    $lenderLoans[$iLoandIndex]['status_color'] = 'inprogress';
                    $lenderLoans[$iLoandIndex]['color']        = '#428890';
                    ++$loanStatus['no-problem'];
                    break;
            }

            /** @var UserLender $user */
            $user = $this->getUser();

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
                // @todo getContractType
                $contractType = (1 == $aProjectLoans['id_type_contract']) ? 'bondsCount' : 'contractsCount';
                $lenderLoans[$iLoandIndex]['contracts'] = [
                    $contractType => 1
                ];
                $lenderLoans[$iLoandIndex]['documents'] = $this->getDocumentDetail(
                    $aProjectLoans['project_status'],
                    $user->getHash(),
                    $aProjectLoans['id_loan_if_one_loan'],
                    $aProjectLoans['id_type_contract'],
                    $projectsInDept,
                    $aProjectLoans['id_project']);
            } else {
                $loans                                     = $loanEntity->select('id_lender = ' . $lender->id_lender_account . ' AND id_project = ' . $aProjectLoans['id_project']);
                $lenderLoans[$iLoandIndex]['contracts']    = [];
                $lenderLoans[$iLoandIndex]['loan_details'] = [];

                foreach ($loans as $loan) {
                    $contractType = (1 == $loan['id_type_contract']) ? 'bondsCount' : 'contractsCount';
                    $lenderLoans[$iLoandIndex]['contracts'][$contractType] = isset($lenderLoans[$iLoandIndex]['contracts'][$contractType]) ? $lenderLoans[$iLoandIndex]['contracts'][$contractType] + 1 : 1;
                    $lenderLoans[$iLoandIndex]['loan_details'][] = [
                        'rate'      => $loan['rate'],
                        'amount'    => round($loan['amount'] / 100, 2),
                        'monthly'   => rand(20, 150),
                        'documents' => $this->getDocumentDetail(
                            $aProjectLoans['project_status'],
                            $user->getHash(),
                            $loan['id_loan'],
                            $loan['id_type_contract'],
                            $projectsInDept,
                            $aProjectLoans['id_project']
                        )
                    ];
                }
            }
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
        $documents          = [];
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
