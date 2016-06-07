<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Service\Simulator\EntityManager;

class FeedsBankTransferCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('feeds:bank_transfer')
            ->setDescription('SFPMEI bank transfer XML feed');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');

        /** @var \virements $bankTransfer */
        $bankTransfer = $entityManager->getRepository('virements');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        /** @var \compteur_transferts $counter */
        $counter = $entityManager->getRepository('compteur_transferts');
        /** @var \companies $company */
        $company = $entityManager->getRepository('companies');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

        $settings->get('Virement - BIC', 'type');
        $bic = $settings->value;

        $settings->get('Virement - IBAN', 'type');
        $iban = str_replace(' ', '', $settings->value);

        $settings->get('titulaire du compte', 'type');
        $accountHolder = utf8_decode($settings->value);

        $settings->get('Retrait Unilend - BIC', 'type');
        $unilendBic = utf8_decode($settings->value);

        $settings->get('Retrait Unilend - IBAN', 'type');
        $unilendIban = utf8_decode($settings->value);

        $settings->get('Retrait Unilend - Titulaire du compte', 'type');
        $unilendAccountHolder = utf8_decode($settings->value);

        $pendingBankTransfers      = $bankTransfer->select('status = 0 AND added_xml = "0000-00-00 00:00:00"');
        $pendingBankTransfersCount = count($pendingBankTransfers);
        $totalAmount               = bcdiv($bankTransfer->sum('status = 0 AND added_xml = "0000-00-00 00:00:00"'), 100, 2);
        $counterId                 = $counter->counter('type = 1') + 1;
        $date                      = date('Ymd');

        $counter->type  = 1;
        $counter->ordre = $counterId;
        $counter->create();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03">
    <CstmrCdtTrfInitn>
        <GrpHdr>
            <MsgId>SFPMEI/' . $accountHolder . '/' . $date . '/' . $counterId . '</MsgId>
            <CreDtTm>' . date('Y-m-d\TH:i:s') . '</CreDtTm>
            <NbOfTxs>' . $pendingBankTransfersCount . '</NbOfTxs>
            <CtrlSum>' . $totalAmount . '</CtrlSum>
            <InitgPty>
                <Nm>' . $accountHolder . '-SFPMEI</Nm>
            </InitgPty>
        </GrpHdr>
        <PmtInf>
            <PmtInfId>' . $accountHolder . '/' . $date . '/' . $counterId . '</PmtInfId>
            <PmtMtd>TRF</PmtMtd>
            <NbOfTxs>' . $pendingBankTransfersCount . '</NbOfTxs>
            <CtrlSum>' . $totalAmount . '</CtrlSum>
            <PmtTpInf>
                <SvcLvl>
                    <Cd>SEPA</Cd>
                </SvcLvl>
            </PmtTpInf>
            <ReqdExctnDt>' . date('Y-m-d') . '</ReqdExctnDt>
            <Dbtr>
                <Nm>SFPMEI</Nm>
                <PstlAdr>
                    <Ctry>FR</Ctry>
                </PstlAdr>
            </Dbtr>
            <DbtrAcct>
                <Id>
                    <IBAN>' . str_replace(' ', '', $iban) . '</IBAN>
                </Id>
            </DbtrAcct>
            <DbtrAgt>
                <FinInstnId>
                    <BIC>' . str_replace(' ', '', $bic) . '</BIC>
                </FinInstnId>
            </DbtrAgt>
            <UltmtDbtr>
                <Nm>UNILEND - SFPMEI</Nm>
            </UltmtDbtr>';

        foreach ($pendingBankTransfers as $pendingBankTransfer) {
            $client->get($pendingBankTransfer['id_client'], 'id_client');

            if ($pendingBankTransfer['type'] == 4) {
                $recipientIban = $unilendIban;
                $recipientBic  = $unilendBic;
                $recipientName = $unilendAccountHolder;
            } elseif ($client->isBorrower()) {
                $company->get($pendingBankTransfer['id_client'], 'id_client_owner');

                $recipientIban = $company->iban;
                $recipientBic  = $company->bic;
                $recipientName = $company->name;
            } else {
                $lender->get($pendingBankTransfer['id_client'], 'id_client_owner');

                $recipientIban = $lender->iban;
                $recipientBic  = $lender->bic;

                if (in_array($client->type, array(\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER))) {
                    $company->get($pendingBankTransfer['id_client'], 'id_client_owner');
                    $recipientName = $company->name;
                } else {
                    $recipientName = $client->nom . ' ' . $client->prenom;
                }
            }

            $bankTransfer->get($pendingBankTransfer['id_virement'], 'id_virement');
            $bankTransfer->status    = 1;
            $bankTransfer->added_xml = date('Y-m-d H:i') . ':00';
            $bankTransfer->update();

            if (strncmp('FR', strtoupper(str_replace(' ', '', $recipientIban)), 2) === 0) {
                $frenchBic = '';
            } else {
                $frenchBic = '
                <CdtrAgt>
                    <FinInstnId>
                        <BIC>' . str_replace(' ', '', $recipientBic) . '</BIC>
                    </FinInstnId>
                </CdtrAgt>';
            }

            $xml .= '
            <CdtTrfTxInf>
                <PmtId>
                    <EndToEndId>' . $accountHolder . '/' . $date . '/' . $pendingBankTransfer['id_virement'] . '</EndToEndId>
                </PmtId>
                <Amt>
                    <InstdAmt Ccy="EUR">' . bcdiv($pendingBankTransfer['montant'], 100, 2) . '</InstdAmt>
                </Amt>' . $frenchBic . '
                <Cdtr>
                     <Nm>' . $recipientName . '</Nm>
                     <PstlAdr>
                         <Ctry>FR</Ctry>
                     </PstlAdr>
                </Cdtr>
                <CdtrAcct>
                        <Id>
                            <IBAN>' . str_replace(' ', '', $recipientIban) . '</IBAN>
                        </Id>
                </CdtrAcct>
                <RmtInf>
                     <Ustrd>' . str_replace(' ', '', $pendingBankTransfer['motif']) . '</Ustrd>
                </RmtInf>
            </CdtTrfTxInf>';
        }

        $xml .= '
        </PmtInf>
    </CstmrCdtTrfInitn>
</Document>';

        if (false === empty($pendingBankTransfers)) {
            file_put_contents($this->getContainer()->getParameter('path.sftp') . 'sfpmei/virements/Unilend_Virements_' . $date . '.xml', $xml);
        }
    }
}
