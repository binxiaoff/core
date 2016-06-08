<?php

namespace Unilend\Bundle\CommandBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class CheckFundedProjectAmountsCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('check:funded_project_amounts')
            ->setDescription('Checks totals of transactions, bids, loans for funded projects');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \projects $projects */
        $projects       = $entityManager->getRepository('projects');
        /** @var \bids $bids */
        $bids           = $entityManager->getRepository('bids');
        /** @var \loans $loans */
        $loans          = $entityManager->getRepository('loans');
        /** @var \transactions $transactions */
        $transactions   = $entityManager->getRepository('transactions');
        /** @var \projects_check $projects_check */
        $projects_check = $entityManager->getRepository('projects_check');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        $settings->get('DebugMailFrom', 'type');
        $debugEmail = $settings->value;
        $settings->get('DebugMailIt', 'type');
        $sDestinatairesDebug = $settings->value;
        $sHeadersDebug  = 'MIME-Version: 1.0' . "\r\n";
        $sHeadersDebug .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $sHeadersDebug .= 'From: ' . $debugEmail . "\r\n";


        $lProjets = $projects->selectProjectsByStatus(\projects_status::FUNDE, ' AND DATE(p.date_fin) = "' . date('Y-m-d') . '"', '', array(), '', '', false);

        foreach ($lProjets as $p) {
            if ($projects_check->get($p['id_project'], 'id_project') === false ) {
                $montantBidsTotal = $bids->getSoldeBid($p['id_project']);
                $montantBidsOK    = $bids->sum('id_project = ' . $p['id_project'] . ' AND status = 1', 'amount');
                $montantBidsOK    = ($montantBidsOK / 100);
                $montantBidsKO    = $bids->sum('id_project = ' . $p['id_project'] . ' AND status = 2', 'amount');
                $montantBidsKO    = ($montantBidsKO / 100);

                $montantLoans = $loans->sum('id_project = ' . $p['id_project'], 'amount');
                $montantLoans = ($montantLoans / 100);

                $montantTransTotal = $transactions->sum('id_project = ' . $p['id_project'] . ' AND type_transaction = 2', 'montant');
                $montantTransTotal = abs($montantTransTotal / 100);
                $montantTransDegel = $transactions->sum('id_project = ' . $p['id_project'] . ' AND type_transaction = 2 AND id_bid_remb != 0', 'montant');
                $montantTransDegel = ($montantTransDegel / 100);

                $montantTransEnchere = $transactions->sum('id_project = ' . $p['id_project'] . ' AND type_transaction = 2 AND id_bid_remb != 0', 'montant');
                $montantTransEnchere = ($montantTransEnchere / 100);

                $diffMontantBidsEtProjet = abs($montantBidsOK - $p['amount']);
                $diffEntreBidsKoEtDegel  = ($montantTransEnchere - $montantBidsKO);

                $contenu = '';
                $contenu .= '<br>-------- PROJET ' . $p['id_project'] . ' --------<br><br>';
                $contenu .= 'Montant projet : ' . $p['amount'] . '<br>';
                $contenu .= '<br>--------BIDS--------<br>';
                $contenu .= 'montantBids : ' . $montantBidsTotal . '<br>';
                $contenu .= 'montantBidsOK : ' . $montantBidsOK . '<br>';
                $contenu .= 'montantBidsKO : ' . $montantBidsKO . '<br>';
                $contenu .= '<br>--------LOANS--------<br>';
                $contenu .= 'montantLoans : ' . $montantLoans . '<br>';
                $contenu .= '<br>--------TRANSACTIONS--------<br>';
                $contenu .= 'montantTransTotal : ' . $montantTransTotal . '<br>';
                $contenu .= 'montantTransDegel : ' . $montantTransDegel . '<br>';
                $contenu .= 'montantTransEnchere : ' . $montantTransEnchere . '<br>';
                $contenu .= '<br>--------PLUS--------<br>';
                $contenu .= 'diffMontantBidsEtProjet : ' . $diffMontantBidsEtProjet . '<br>';
                $contenu .= 'diffEntreBidsKoEtDegel : ' . $diffEntreBidsKoEtDegel . '<br>';
                $contenu .= '<br>-------- FIN PROJET ' . $p['id_project'] . ' --------<br>';

                $verif_no_good = false;

                if ($montantTransTotal != $p['amount']) {
                    $verif_no_good = true;
                }
                if ($montantLoans != $p['amount']) {
                    $verif_no_good = true;
                }
                if ($diffEntreBidsKoEtDegel != $diffMontantBidsEtProjet) {
                    $verif_no_good = true;
                }

                if ($verif_no_good == true) {
                    $subject = '[ALERTE] Une incoherence est présente dans le projet ' . $p['id_project'];
                    $message = '
                            <html>
                            <head>
                              <title>[ALERTE] Une incoherence est présente dans le projet ' . $p['id_project'] . '</title>
                            </head>
                            <body>
                                <p>[ALERTE] Une incoherence est présente dans le projet ' . $p['id_project'] . '</p>
                                <p>' . $contenu . '</p>
                            </body>
                            </html>';
                    mail($sDestinatairesDebug, $subject, $message, $sHeadersDebug);
                    $projects_check->status = 2;
                } else {
                    $projects_check->status = 1;
                }

                $projects_check->id_project = $p['id_project'];
                $projects_check->create();
            }
        }
    }
}
