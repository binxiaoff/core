<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;

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
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        $em            = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger        = $this->getContainer()->get('monolog.logger.console');

        $bankTransfer = $em->getRepository('UnilendCoreBusinessBundle:Virements');
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
        /** @var \transactions $transaction */
        $transaction = $entityManager->getRepository('transactions');

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

        $pendingBankTransfers      = $bankTransfer->findBy(['status' => Virements::STATUS_PENDING, 'addedXml' => null]);
        $pendingBankTransfersCount = count($pendingBankTransfers);
        $totalAmount               = 0;
        $counterId                 = $counter->counter('type = 1') + 1;
        $date                      = date('Ymd');
        $negativeBalanceError      = [];
        $xmlBody                   = '';

        $counter->type  = 1;
        $counter->ordre = $counterId;
        $counter->create();

        foreach ($pendingBankTransfers as $pendingBankTransfer) {
            $transaction->get($pendingBankTransfer->getIdTransaction(), 'id_transaction');
            $client->get($pendingBankTransfer->getClient()->getIdClient(), 'id_client');
            if (\DateTime::createFromFormat('Y-m-d H:i:s', $transaction->date_transaction) < new \DateTime('today')) {
                if ($pendingBankTransfer->getType() == Virements::TYPE_UNILEND) {
                    $recipientIban = $unilendIban;
                    $recipientBic  = $unilendBic;
                    $recipientName = $unilendAccountHolder;
                } elseif ($client->isBorrower()) {
                    if (null === $pendingBankTransfer->getIdClient()) {
                        $logger->error('The client is null for transfer id: ' . $pendingBankTransfer->getIdVirement());
                        continue;
                    }
                    $company->get($pendingBankTransfer->getClient()->getIdClient(), 'id_client_owner');

                    $recipientIban = $company->iban;
                    $recipientBic  = $company->bic;
                    $recipientName = $company->name;
                } else {
                    if (null === $pendingBankTransfer->getIdClient()) {
                        $logger->error('The client is null for transfer id: ' . $pendingBankTransfer->getIdVirement());
                        continue;
                    }
                    $balance = $transaction->getSolde($pendingBankTransfer->getIdClient()->getIdClient());
                    if ($balance < 0) {
                        $pendingBankTransfersCount--;
                        $negativeBalanceError[] = ['id_client' => $pendingBankTransfer->getClient()->getIdClient(), 'balance' => $balance];
                        continue;
                    }
                    $lender->get($pendingBankTransfer->getClient()->getIdClient(), 'id_client_owner');

                    $recipientIban = $lender->iban;
                    $recipientBic  = $lender->bic;

                    if (in_array($client->type, [\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER])) {
                        $company->get($pendingBankTransfer->getClient()->getIdClient(), 'id_client_owner');
                        $recipientName = $company->name;
                    } else {
                        $recipientName = $client->nom . ' ' . $client->prenom;
                    }
                }

                $totalAmount = bcadd($pendingBankTransfer->getMontant(), $totalAmount);

                $pendingBankTransfer->setStatus(Virements::STATUS_SENT);
                $pendingBankTransfer->setAddedXml(new \DateTime());
                $em->flush($pendingBankTransfer);

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

                $xmlBody .= '
            <CdtTrfTxInf>
                <PmtId>
                    <EndToEndId>' . $accountHolder . '/' . $date . '/' . $pendingBankTransfer->getIdVirement() . '</EndToEndId>
                </PmtId>
                <Amt>
                    <InstdAmt Ccy="EUR">' . bcdiv($pendingBankTransfer->getMontant(), 100, 2) . '</InstdAmt>
                </Amt>' . $frenchBic . '
                <Cdtr>
                     <Nm>' . str_replace(['"', '\'', '\\', '>', '<', '&'], '', $recipientName) . '</Nm>
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
                     <Ustrd>' . str_replace(' ', '', $pendingBankTransfer->getMotif()) . '</Ustrd>
                </RmtInf>
            </CdtTrfTxInf>';
            }
        }

        $totalAmount = bcdiv($totalAmount, 100, 2);
        $xml         = '<?xml version="1.0" encoding="UTF-8"?>
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

        $xml .= $xmlBody;

        $xml .= '
        </PmtInf>
    </CstmrCdtTrfInitn>
</Document>';

        if (false === empty($negativeBalanceError)) {
            $settings->get('Adresse controle interne', 'type');
            $email = $settings->value;

            $details = '<ul>';
            foreach ($negativeBalanceError as $error) {
                $details .= '<li>' . 'id client : ' . $error['id_client'] . '; solde : ' . $error['balance'] . '</li>';
            }
            $details .= '<ul>';
            $varMail = ['details' => $details];
            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('solde-negatif-notification', $varMail);
            $message->setTo($email);
            $mailer = $this->getContainer()->get('mailer');
            $mailer->send($message);
        }

        if (false === empty($pendingBankTransfers)) {
            file_put_contents($this->getContainer()->getParameter('path.sftp') . 'sfpmei/emissions/virements/Unilend_Virements_' . $date . '.xml', $xml);
        }
    }
}
