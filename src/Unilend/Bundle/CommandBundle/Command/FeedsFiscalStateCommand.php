<?php


namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;


class FeedsFiscalStateCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('feeds:fiscal_state')
            ->setDescription('Generate the fiscal state file');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \echeanciers $echeanciers */
        $echeanciers  = $entityManager->getRepository('echeanciers');
        /** @var \bank_unilend $bank_unilend */
        $bank_unilend = $entityManager->getRepository('bank_unilend');
        /** @var \transactions $transactions */
        $transactions = $entityManager->getRepository('transactions');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        //load for constant use
        $entityManager->getRepository('transactions_types');

        $settings->get('EQ-Acompte d\'impôt sur le revenu', 'type');
        $prelevements_obligatoires = bcmul($settings->value, 100);

        $settings->get('EQ-Contribution additionnelle au Prélèvement Social', 'type');
        $txcontributions_additionnelles = bcmul($settings->value, 100);

        $settings->get('EQ-CRDS', 'type');
        $txcrds = bcmul($settings->value, 100);

        $settings->get('EQ-CSG', 'type');
        $txcsg = bcmul($settings->value, 100);

        $settings->get('EQ-Prélèvement de Solidarité', 'type');
        $txprelevements_solidarite = bcmul($settings->value, 100);

        $settings->get('EQ-Prélèvement social', 'type');
        $txprelevements_sociaux = bcmul($settings->value, 100);

        $settings->get('EQ-Retenue à la source', 'type');
        $tauxRetenuSource = bcmul($settings->value, 100);

        $mois          = date('m');
        $annee         = date('Y');
        $dateDebutTime = mktime(0, 0, 0, $mois - 1, 1, $annee);
        $dateDebutSql  = date('Y-m-d', $dateDebutTime);
        $dateFinTime   = mktime(0, 0, 0, $mois, 0, $annee);
        $dateFinSql    = date('Y-m-d', $dateFinTime);

        $Morale1                      = $echeanciers->getEcheanceBetweenDates($dateDebutSql, $dateFinSql, '0', \clients::TYPE_LEGAL_ENTITY);
        $PhysiqueNoExo                = $echeanciers->getEcheanceBetweenDates($dateDebutSql, $dateFinSql, '0', array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER));
        $Physique                     = $echeanciers->getEcheanceBetweenDates($dateDebutSql, $dateFinSql, '', array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER));
        $PhysiqueExo                  = $echeanciers->getEcheanceBetweenDates($dateDebutSql, $dateFinSql, '1', array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER));
        $etranger                     = $echeanciers->getEcheanceBetweenDatesEtranger($dateDebutSql, $dateFinSql);
        $PhysiqueNonExoPourLaPeriode  = $echeanciers->getEcheanceBetweenDates_exonere_mais_pas_dans_les_dates($dateDebutSql, $dateFinSql);

        $Morale1[1]['retenues_source']  = isset($Morale1[1]['retenues_source']) ? $Morale1[1]['retenues_source'] : 0 ;
        $etranger[1]                    = isset($etranger[1]) ? $etranger[1] : array('retenues_source' => 0, 'prelevements_obligatoires' => 0, 'interets' => 0);
        $etranger[2]                    = isset($etranger[2]) ? $etranger[2] : array('retenues_source' => 0, 'prelevements_obligatoires' => 0, 'interets' => 0);
        $PhysiqueNoExo[1]               = isset($PhysiqueNoExo[1]) ? $PhysiqueNoExo[1] : array('prelevements_obligatoires' => 0, 'interets' => 0);
        $PhysiqueNoExo[2]               = isset($PhysiqueNoExo[2]) ? $PhysiqueNoExo[2] : array('prelevements_obligatoires' => 0, 'interets' => 0);
        $PhysiqueNonExoPourLaPeriode[1] = isset($PhysiqueNonExoPourLaPeriode[1]) ? $PhysiqueNonExoPourLaPeriode[1] : array('prelevements_obligatoires' => 0, 'interets' => 0);
        $PhysiqueNonExoPourLaPeriode[2] = isset($PhysiqueNonExoPourLaPeriode[2]) ? $PhysiqueNonExoPourLaPeriode[1] : array('prelevements_obligatoires' => 0, 'interets' => 0);

        $prelevementRetenuSoucre[1]   = bcadd($Morale1[1]['retenues_source'], $etranger[1]['retenues_source']);
        $lesPrelevSurPhysiqueNoExo[1] = bcadd(bcsub($PhysiqueNoExo[1]['prelevements_obligatoires'], $etranger[1]['prelevements_obligatoires']), $PhysiqueNonExoPourLaPeriode[1]['prelevements_obligatoires']);
        $lesPrelevSurPhysiqueNoExo[2] = bcadd(bcsub($PhysiqueNoExo[2]['prelevements_obligatoires'] , $etranger[2]['prelevements_obligatoires']), $PhysiqueNonExoPourLaPeriode[2]['prelevements_obligatoires']);
        $PhysiqueNoExoInte[1]         = bcdiv(bcadd(bcsub($PhysiqueNoExo[1]['interets'], $etranger[1]['interets']), $PhysiqueNonExoPourLaPeriode[1]['interets']), 100);
        $PhysiqueNoExoInte[2]         = bcdiv(bcadd(bcsub($PhysiqueNoExo[2]['interets'], $etranger[2]['interets']), $PhysiqueNonExoPourLaPeriode[2]['interets']), 100);
        $MoraleInte                   = bcdiv(bcadd(array_sum(array_column($Morale1, 'interets')), array_sum(array_column($etranger, 'interets'))), 100);
        $PhysiqueExoInte              = bcdiv(array_sum(array_column($PhysiqueExo, 'interets')), 100);
        $lesPrelevSurPhysiqueExo      = array_sum(array_column($PhysiqueExo, 'prelevements_obligatoires'));
        $PhysiqueInte                 = bcdiv(bcsub(array_sum(array_column($Physique, 'interets')), array_sum(array_column($etranger, 'interets'))), 100);
        $lesPrelevSurPhysique         = bcsub(array_sum(array_column($Physique, 'prelevements_obligatoires')), array_sum(array_column($etranger, 'prelevements_obligatoires')));
        $csg                          = bcsub(array_sum(array_column($Physique, 'csg')), array_sum(array_column($etranger, 'csg')));
        $prelevements_sociaux         = bcsub(array_sum(array_column($Physique, 'prelevements_sociaux')), array_sum(array_column($etranger, 'prelevements_sociaux')));
        $contributions_additionnelles = bcsub(array_sum(array_column($Physique, 'contributions_additionnelles')), array_sum(array_column($etranger, 'contributions_additionnelles')));
        $prelevements_solidarite      = bcsub(array_sum(array_column($Physique, 'prelevements_solidarite')), array_sum(array_column($etranger, 'prelevements_solidarite')));
        $crds                         = bcsub(array_sum(array_column($Physique, 'crds')), array_sum(array_column($etranger, 'crds')));


        $table = '
        <style>
            table th,table td{width:80px;height:20px;border:1px solid black;}
            table td.dates{text-align:center;}
            .right{text-align:right;}
            .center{text-align:center;}
            .boder-top{border-top:1px solid black;}
            .boder-bottom{border-bottom:1px solid black;}
            .boder-left{border-left:1px solid black;}
            .boder-right{border-right:1px solid black;}
        </style>

        <table border="1" cellpadding="0" cellspacing="0" style=" background-color:#fff; font:11px/13px Arial, Helvetica, sans-serif; color:#000;width: 650px;">
            <tr>
                <th colspan="4">UNILEND</th>
            </tr>
            <tr>
                <th style="background-color:#C9DAF2;">P&eacute;riode :</th>
                <th style="background-color:#C9DAF2;">' . date('d/m/Y', $dateDebutTime) . '</th>
                <th style="background-color:#C9DAF2;">au</th>
                <th style="background-color:#C9DAF2;">' . date('d/m/Y', $dateFinTime) . '</th>
            </tr>
            <tr>
                <th style="background-color:#ECAEAE;" colspan="4">Pr&eacute;l&egrave;vements obligatoires</th>
            </tr>
            <tr>
                <th>&nbsp;</th>
                <th style="background-color:#F4F3DA;">Base (Int&eacute;r&ecirc;ts bruts)</th>
                <th style="background-color:#F4F3DA;">Montant pr&eacute;l&egrave;vements</th>
                <th style="background-color:#F4F3DA;">Taux</th>
            </tr>
            <tr>
                <th style="background-color:#E6F4DA;">Soumis au pr&eacute;l&egrave;vements (bons de caisse)</th>
                <td class="right">' . $ficelle->formatNumber($PhysiqueNoExoInte[1]) . '</td>
                <td class="right">' . $ficelle->formatNumber($lesPrelevSurPhysiqueNoExo[1]) . '</td>
                <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($prelevements_obligatoires) . '%</td>
            </tr>
            <tr>
                <th style="background-color:#E6F4DA;">Soumis au pr&eacute;l&egrave;vements (pr&ecirc;t IFP)</th>
                <td class="right">' . $ficelle->formatNumber($PhysiqueNoExoInte[2]) . '</td>
                <td class="right">' . $ficelle->formatNumber($lesPrelevSurPhysiqueNoExo[2]) . '</td>
                <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($prelevements_obligatoires) . '%</td>
            </tr>
            <tr>
                <th style="background-color:#E6F4DA;">Dispens&eacute;</th>
                <td class="right">' . $ficelle->formatNumber($PhysiqueExoInte) . '</td>
                <td class="right">' . $ficelle->formatNumber($lesPrelevSurPhysiqueExo) . '</td>
                <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber(0) . '%</td>
            </tr>
            <tr>
                <th style="background-color:#E6F4DA;">Total</th>
                <td class="right">' . $ficelle->formatNumber($PhysiqueInte) . '</td>
                <td class="right">' . $ficelle->formatNumber($lesPrelevSurPhysique) . '</td>
                <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($prelevements_obligatoires) . '%</td>
            </tr>
            <tr>
                <th style="background-color:#ECAEAE;" colspan="4">Retenue &agrave; la source (bons de caisse)</th>
            </tr>
            <tr>
                <th style="background-color:#E6F4DA;">Retenue &agrave; la source</th>
                <td class="right">' . $ficelle->formatNumber($MoraleInte) . '</td>
                <td class="right">' . $ficelle->formatNumber($prelevementRetenuSoucre[1]) . '</td>
                <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($tauxRetenuSource) . '%</td>
            </tr>
            <tr>
                <th style="background-color:#ECAEAE;" colspan="4">Pr&eacute;l&egrave;vements sociaux</th>
            </tr>
            <tr>
                <th style="background-color:#E6F4DA;">CSG</th>
                <td class="right">' . $ficelle->formatNumber($PhysiqueInte) . '</td>
                <td class="right">' . $ficelle->formatNumber($csg) . '</td>
                <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($txcsg) . '%</td>
            </tr>
            <tr>
                <th style="background-color:#E6F4DA;">Pr&eacute;l&egrave;vement social</th>
                <td class="right">' . $ficelle->formatNumber($PhysiqueInte) . '</td>
                <td class="right">' . $ficelle->formatNumber($prelevements_sociaux) . '</td>
                <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($txprelevements_sociaux) . '%</td>
            </tr>
            <tr>
                <th style="background-color:#E6F4DA;">Contribution additionnelle</th>
                <td class="right">' . $ficelle->formatNumber($PhysiqueInte) . '</td>
                <td class="right">' . $ficelle->formatNumber($contributions_additionnelles) . '</td>
                <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($txcontributions_additionnelles) . '%</td>
            </tr>
            <tr>
                <th style="background-color:#E6F4DA;">Pr&eacute;l&egrave;vements de solidarité</th>
                <td class="right">' . $ficelle->formatNumber($PhysiqueInte) . '</td>
                <td class="right">' . $ficelle->formatNumber($prelevements_solidarite) . '</td>
                <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($txprelevements_solidarite) . '%</td>
            </tr>
            <tr>
                <th style="background-color:#E6F4DA;">CRDS</th>
                <td class="right">' . $ficelle->formatNumber($PhysiqueInte) . '</td>
                <td class="right">' . $ficelle->formatNumber($crds) . '</td>
                <td style="background-color:#DDDAF4;" class="right">' . $ficelle->formatNumber($txcrds) . '%</td>
            </tr>
        </table>
        ';

        $filename = 'Unilend_etat_fiscal_' . date('Ymd');
        $sFilePath = $this->getContainer()->getParameter('path.sftp') . 'sfpmei/etat_fiscal/' . $filename . '.xls';
        file_put_contents($sFilePath, $table);

        $settings->get('Adresse notification etat fiscal', 'type');
        $destinataire = $settings->value;
        $sUrl     = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('router.request_context.host');

        $varMail = array(
            '$surl' => $sUrl,
            '$url'  => $sUrl
        );

        /** @var TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-etat-fiscal', $varMail, false);
        $message->setTo(explode(';', trim($destinataire)));
        $message->attach(\Swift_Attachment::fromPath($sFilePath));
        $mailer = $this->getContainer()->get('mailer');
        $mailer->send($message);

        /////////////////////////////////////////////////////
        // On retire de bank unilend la partie  pour letat //
        /////////////////////////////////////////////////////
        $dateRembtemp = mktime(date("H"), date("i"), date("s"), date("m") - 1, date("d"), date("Y"));
        $dateRemb     = date("Y-m", $dateRembtemp);
        $dateRembM    = date("m", $dateRembtemp);
        $dateRembY    = date("Y", $dateRembtemp);
        $etatRemb     = $bank_unilend->sumMontantEtat('status = 1 AND type IN(2) AND LEFT(added,7) = "' . $dateRemb . '"');
        $regulCom     = $transactions->sumByday(\transactions_types::TYPE_REGULATION_COMMISSION, $dateRembM, $dateRembY);

        $sommeRegulDuMois = 0;
        foreach ($regulCom as $r) {
            $sommeRegulDuMois += $r['montant_unilend'] * 100;
        }

        $etatRemb += $sommeRegulDuMois;

        if ($etatRemb > 0) {
            $transactions->id_client        = 0;
            $transactions->montant          = $etatRemb;
            $transactions->id_langue        = 'fr';
            $transactions->date_transaction = date('Y-m-d H:i:s');
            $transactions->status           = \transactions::STATUS_VALID;
            $transactions->etat             = \transactions::PAYMENT_STATUS_OK;
            $transactions->type_transaction = \transactions_types::TYPE_FISCAL_BANK_TRANSFER;
            $transactions->transaction      = \transactions::VIRTUAL;
            $transactions->create();

            $bank_unilend->id_transaction         = $transactions->id_transaction;
            $bank_unilend->id_echeance_emprunteur = 0;
            $bank_unilend->id_project             = 0;
            $bank_unilend->montant                = -$etatRemb;
            $bank_unilend->type                   = \bank_unilend::TYPE_DEBIT_UNILEND;
            $bank_unilend->status                 = \bank_unilend::STATUS_DEBITED_UNILEND_ACCOUNT;
            $bank_unilend->retrait_fiscale        = 1;
            $bank_unilend->create();
        }
    }
}
