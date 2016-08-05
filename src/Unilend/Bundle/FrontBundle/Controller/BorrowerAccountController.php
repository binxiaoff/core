<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Bundle\FrontBundle\Security\User\UserBorrower;

class BorrowerAccountController extends Controller
{

    /**
     * @Route("/espace-emprunteur/projets", name="borrower_account_projects")
     * @Template("borrower_account/projects.html.twig")
     */
    public function projectsAction(Request $request)
    {
        $projectsPreFunding  = $this->getProjectsPreFunding();
        $projectsFunding     = $this->getProjectsFunding();
        $projectsPostFunding = $this->getProjectsPostFunding();

        if ($request->isMethod('POST')) {
            if (isset($_POST['confirm_cloture_anticipation'])) {
                $project = $this->get('unilend.service.entity_manager')->getRepository('projects');

                if (is_numeric($this->params[0])) {
                    $project->get($this->params[0], 'id_project');
                } else {
                    $project->get($this->params[0], 'hash');
                }

                if ($project->id_company === $this->companies->id_company) {
                    $oNewClosureDate = new \DateTime();
                    $oNewClosureDate->modify("+5 minutes");

                    $project->date_retrait_full = $oNewClosureDate->format('Y-m-d H:i:s');
                    $project->date_retrait      = $oNewClosureDate->format('Y-m-d');
                    $project->update();

                    $_SESSION['cloture_anticipe'] = true;

                    return $this->redirect($this->generateUrl($request->get('_route')));
                }
            }

            if (isset($_POST['valider_demande_projet'])) {
                unset($_SESSION['forms']['nouvelle-demande']);

                $this->settings->get('Somme à emprunter max', 'type');
                $fMaxAmount = $this->settings->value;

                $this->settings->get('Somme à emprunter min', 'type');
                $fMinAmount = $this->settings->value;

                if (empty($_POST['montant']) || $fMinAmount > $_POST['montant'] || $fMaxAmount < $_POST['montant']) {
                    $_SESSION['forms']['nouvelle-demande']['errors']['montant'] = true;
                }
                if (empty($_POST['duree'])) {
                    $_SESSION['forms']['nouvelle-demande']['errors']['duree'] = true;
                }
                if (empty($_POST['commentaires'])) {
                    $_SESSION['forms']['nouvelle-demande']['errors']['commentaires'] = true;
                }
                if (empty($_SESSION['forms']['nouvelle-demande']['errors'])) {
                    $oClients = $this->loadData('clients');
                    $oClients->get($_SESSION['client']['id_client']);

                    $oCompanies = $this->loadData('companies');
                    $oCompanies->get($oClients->id_client, 'id_client_owner');

                    $oProject = $this->loadData('projects');

                    $oProject->id_company                           = $oCompanies->id_company;
                    $oProject->amount                               = str_replace(array(',', ' '), array('.', ''), $_POST['montant']);
                    $oProject->ca_declara_client                    = 0;
                    $oProject->resultat_exploitation_declara_client = 0;
                    $oProject->fonds_propres_declara_client         = 0;
                    $oProject->comments                             = $_POST['commentaires'];
                    $oProject->period                               = $_POST['duree'];
                    $oProject->create();

                    /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
                    $oProjectManager = $this->get('unilend.service.project_manager');
                    $oProjectManager->addProjectStatus(\users::USER_ID_FRONT, \projects_status::A_TRAITER, $oProject);

                    return $this->redirect($this->generateUrl($request->get('_route')));
                }
            }
        }

        return ['pre_funding_projects' => $projectsPreFunding, 'funding_projects' => $projectsFunding, 'post_funding_projects' => $projectsPostFunding];
    }

    /**
     * @Route("/espace-emprunteur/operations", name="borrower_account_operations")
     * @Template("borrower_account/operations.html.twig")
     */
    public function operationsAction()
    {

    }

    /**
     * @Route("/espace-emprunteur/profil", name="borrower_account_profile")
     * @Template("borrower_account/profile.html.twig")
     */
    public function profileAction()
    {

    }

    /**
     * @Route("/espace-emprunteur/contact", name="borrower_account_contact")
     * @Template("borrower_account/contact.html.twig")
     */
    public function contactAction()
    {

    }

    /**
     * @Route("/espace-emprunteur/faq", name="borrower_account_faq")
     * @Template("borrower_account/faq.html.twig")
     */
    public function faqAction()
    {

    }

    private function getProjectsPreFunding()
    {
        $statusPreFunding   = array(
            \projects_status::A_FUNDER,
            \projects_status::A_TRAITER,
            \projects_status::COMITE,
            \projects_status::EN_ATTENTE_PIECES,
            \projects_status::PREP_FUNDING,
            \projects_status::REJETE,
            \projects_status::REJET_ANALYSTE,
            \projects_status::REJET_COMITE,
            \projects_status::REVUE_ANALYSTE
        );
        $projectsPreFunding = $this->getCompany()->getProjectsForCompany(null, $statusPreFunding);

        foreach ($projectsPreFunding as $key => $project) {
            switch ($project['project_status']) {
                case \projects_status::EN_ATTENTE_PIECES:
                case \projects_status::A_TRAITER:
                    $projectsPreFunding[$key]['project_status_label'] = 'waiting-for-documents';
                    break;
                case \projects_status::REVUE_ANALYSTE:
                case \projects_status::COMITE:
                    $projectsPreFunding[$key]['project_status_label'] = 'analyzing';
                    break;
                case \projects_status::REJET_ANALYSTE:
                case \projects_status::REJET_COMITE:
                case \projects_status::REJETE:
                    $projectsPreFunding[$key]['project_status_label'] = 'refused';
                    break;
                case \projects_status::PREP_FUNDING:
                case \projects_status::A_FUNDER:
                    $projectsPreFunding[$key]['project_status_label'] = 'waiting-for-being-on-line';
                    break;
            }
            $predictAmountAutoBid                        = $this->get('unilend.service.autobid_settings_manager')->predictAmount($project['risk'], $project['period']);
            $projectsPreFunding[$key]['predict_autobid'] = round(($predictAmountAutoBid / $project['amount']) * 100, 1);
        }
        return $projectsPreFunding;
    }

    private function getProjectsFunding()
    {
        /** @var \bids $bids */
        $bids = $this->get('unilend.service.entity_manager')->getRepository('bids');
        /** @var \projects $projects */
        $projects        = $this->get('unilend.service.entity_manager')->getRepository('projects');
        $projectsFunding = $this->getCompany()->getProjectsForCompany(null, \projects_status::EN_FUNDING);

        foreach ($projectsFunding as $key => $project) {
            $projectsFunding[$key]['average_ir']       = $projects->getAverageInterestRate($project['id_project'], $project['project_status']);
            $iSumBids                                  = $bids->getSoldeBid($project['id_project']);
            $projectsFunding[$key]['funding_progress'] = ((1 - ($project['amount'] - $iSumBids) / $project['amount']) * 100);
            $projectsFunding[$key]['ended']            = \DateTime::createFromFormat('Y-m-d H:i:s', $project['date_retrait_full']);
        }
        return $projectsFunding;
    }

    private function getProjectsPostFunding()
    {
        $aStatusPostFunding = array(
            \projects_status::DEFAUT,
            \projects_status::FUNDE,
            \projects_status::PROBLEME,
            \projects_status::RECOUVREMENT,
            \projects_status::REMBOURSE,
            \projects_status::REMBOURSEMENT,
            \projects_status::REMBOURSEMENT_ANTICIPE
        );

        /** @var \projects $projects */
        $projects            = $this->get('unilend.service.entity_manager')->getRepository('projects');
        $projectsPostFunding = $this->getCompany()->getProjectsForCompany(null, $aStatusPostFunding);
        $repaymentSchedule   = $this->get('unilend.service.entity_manager')->getRepository('echeanciers_emprunteur');

        foreach ($projectsPostFunding as $key => $project) {
            $projectsPostFunding[$key]['average_ir']          = $projects->getAverageInterestRate($project['id_project'], $project['project_status']);
            $projectsPostFunding[$key]['outstanding_capital'] = $this->calculateOutstandingCapital($project['id_project']);
            $aNextRepayment                                   = $repaymentSchedule->select(
                'status_emprunteur = 0 AND id_project = ' . $project['id_project'],
                'date_echeance_emprunteur ASC',
                '',
                1
            );
            $aNextRepayment                                   = array_shift($aNextRepayment);
            $projectsPostFunding[$key]['monthly_payment']     = ($aNextRepayment['montant'] + $aNextRepayment['commission'] + $aNextRepayment['tva']) / 100;
            $projectsPostFunding[$key]['next_maturity_date']  = \DateTime::createFromFormat('Y-m-d H:i:s', $aNextRepayment['date_echeance_emprunteur']);
            $projects->get($project['id_project']);
            $projectsPostFunding[$key]['ended']               = $this->get('unilend.service.project_manager')->getProjectEndDate($projects);
        }

        usort($projectsPostFunding, function ($aFirstArray, $aSecondArray) {
            return $aFirstArray['date_retrait'] < $aSecondArray['date_retrait'];
        });

        return $projectsPostFunding;
    }

    private function getClient()
    {
        /** @var UserBorrower $user */
        $user     = $this->getUser();
        $clientId = $user->getClientId();
        /** @var \clients $client */
        $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
        $client->get($clientId);

        return $client;
    }

    private function getCompany()
    {
        /** @var UserBorrower $user */
        $user     = $this->getUser();
        $clientId = $user->getClientId();
        /** @var \companies $companies */
        $company = $this->get('unilend.service.entity_manager')->getRepository('companies');
        $company->get($clientId, 'id_client_owner');

        return $company;
    }

    private function calculateOutstandingCapital($projectId)
    {
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->get('unilend.service.entity_manager')->getRepository('echeanciers');

        $aPayment      = $repaymentSchedule->getLastOrder($projectId);
        $iPaymentOrder = (isset($aPayment)) ? $aPayment['ordre'] + 1 : 1;

        return $repaymentSchedule->reste_a_payer_ra($projectId, $iPaymentOrder);
    }
}
