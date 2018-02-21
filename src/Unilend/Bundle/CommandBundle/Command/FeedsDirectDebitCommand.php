<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements;

class FeedsDirectDebitCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('feeds:direct_debit')
            ->setDescription('SFPMEI direct debit XML feed');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityMangerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \compteur_transferts $counter */
        $counter        = $entityMangerSimulator->getRepository('compteur_transferts');
        $counterId      = $counter->counter('type = 2') + 1;
        $counter->type  = 2;
        $counter->ordre = $counterId;
        $counter->create();

        $entityManger = $this->getContainer()->get('doctrine.orm.entity_manager');

        $directDebitRepository = $entityManger->getRepository('UnilendCoreBusinessBundle:Prelevements');
        $mandateRepository     = $entityManger->getRepository('UnilendCoreBusinessBundle:ClientsMandats');
        $settingsRepository    = $entityManger->getRepository('UnilendCoreBusinessBundle:Settings');

        $now           = new \DateTime();
        $bic           = $settingsRepository->findOneBy(['type' => 'Virement - BIC'])->getValue();
        $iban          = str_replace(' ', '', $settingsRepository->findOneBy(['type' => 'Virement - IBAN'])->getValue());
        $accountHolder = utf8_decode($settingsRepository->findOneBy(['type' => 'titulaire du compte'])->getValue());
        $ics           = $settingsRepository->findOneBy(['type' => 'ICS de SFPMEI'])->getValue();

        /** @var Prelevements[] $borrowerDirectDebits */
        $borrowerDirectDebits      = $directDebitRepository->findBy([
            'status'                          => Prelevements::STATUS_PENDING,
            'type'                            => Prelevements::CLIENT_TYPE_BORROWER,
            'typePrelevement'                 => Prelevements::TYPE_RECURRENT,
            'dateExecutionDemandePrelevement' => $now
        ]);
        $borrowerDirectDebitsCount = count($borrowerDirectDebits);
        $borrowerTotalAmount       = 0;
        $date                      = $now->format('Ymd');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
    <CstmrDrctDbtInitn>
        <GrpHdr>
            <MsgId>SFPMEI/' . $accountHolder . '/' . $date . '/' . $counterId . '</MsgId>
            <CreDtTm>' . date('Y-m-d\TH:i:s', mktime(date('H'), date('i'), 0, date('m'), date('d') + 1, date('Y'))) . '</CreDtTm>
            <NbOfTxs>' . $borrowerDirectDebitsCount . '</NbOfTxs>
            <CtrlSum>[#borrowerTotalAmount#]</CtrlSum>
            <InitgPty>
                <Nm>' . $accountHolder . '-SFPMEI' . '</Nm>
            </InitgPty>
        </GrpHdr>';

        foreach ($borrowerDirectDebits as $borrowerDirectDebit) {
            $sequence = 'RCUR';

            if ($borrowerDirectDebit->getNumPrelevement() > 1) {
                /** @var Prelevements $lastDirectDebit */
                $lastDirectDebit = $directDebitRepository->findOneBy([
                    'idProject'       => $borrowerDirectDebit->getIdProject(),
                    'status'          => Prelevements::STATUS_PENDING,
                    'type'            => Prelevements::CLIENT_TYPE_BORROWER,
                    'typePrelevement' => Prelevements::TYPE_RECURRENT,
                ], ['numPrelevement' => 'DESC']);

                if ($lastDirectDebit->getIban() != $borrowerDirectDebit->getIban() || $lastDirectDebit->getBic() != $borrowerDirectDebit->getBic()) {
                    $sequence = 'FRST';
                }
            } else {
                $sequence = 'FRST';
            }

            $directDebitAmount   = round(bcdiv($borrowerDirectDebit->getMontant(), 100, 4), 2);
            $borrowerTotalAmount = round(bcadd($borrowerTotalAmount, $directDebitAmount, 4), 2);
            $client              = $borrowerDirectDebit->getIdClient();
            $mandate             = $mandateRepository->findOneBy(['idProject' => $borrowerDirectDebit->getIdProject()]);

            $xml .= $this->getXMLElement([
                'iban'             => $iban,
                'bic'              => $bic,
                'ics'              => $ics,
                'id'               => $accountHolder . '/' . $date . '/' . $borrowerDirectDebit->getIdPrelevement(),
                'sequence'         => $sequence,
                'amount'           => $directDebitAmount,
                'completionDate'   => $borrowerDirectDebit->getDateEcheanceEmprunteur()->format('Y-m-d'),
                'mandateReference' => $borrowerDirectDebit->getMotif(),
                'mandateDate'      => $mandate->getUpdated()->format('Y-m-d'),
                'debitBic'         => $borrowerDirectDebit->getBic(),
                'debitIban'        => $borrowerDirectDebit->getIban(),
                'lastname'         => str_replace(array('"', '\'', '\\', '>', '<', '&'), '', $client->getNom()),
                'firstname'        => str_replace(array('"', '\'', '\\', '>', '<', '&'), '', $client->getPrenom()),
                'reference'        => $borrowerDirectDebit->getMotif()
            ]);

            $borrowerDirectDebit
                ->setStatus(Prelevements::STATUS_SENT)
                ->setAddedXml(new \DateTime());

            $entityManger->flush($borrowerDirectDebit);
        }

        $xml .= '
    </CstmrDrctDbtInitn>
</Document>';

        $xml = str_replace('[#borrowerTotalAmount#]', $borrowerTotalAmount, $xml);

        if (false === empty($borrowerDirectDebits)) {
            file_put_contents($this->getContainer()->getParameter('path.sftp') . 'sfpmei/emissions/prelevements/Unilend_Prelevements_' . $date . '.xml', $xml);
        }
    }

    /**
     * Help factorize if we have several direct debit types (lender/borrower)
     *
     * @param array $directDebit
     *
     * @return string
     */
    private function getXMLElement(array $directDebit)
    {
        return '
        <PmtInf>
            <PmtInfId>' . $directDebit['id'] . '</PmtInfId>
            <PmtMtd>DD</PmtMtd>
            <NbOfTxs>1</NbOfTxs>
            <CtrlSum>' . $directDebit['amount'] . '</CtrlSum>
            <PmtTpInf>
                <SvcLvl>
                    <Cd>SEPA</Cd>
                </SvcLvl>
                <LclInstrm>
                    <Cd>CORE</Cd>
                </LclInstrm>
                <SeqTp>' . $directDebit['sequence'] . '</SeqTp>
            </PmtTpInf>
            <ReqdColltnDt>' . date('Y-m-d', strtotime($directDebit['completionDate'])) . '</ReqdColltnDt>
            <Cdtr>
                <Nm>SFPMEI</Nm>
                <PstlAdr>
                    <Ctry>FR</Ctry>
                </PstlAdr>
            </Cdtr>
            <CdtrAcct>
                <Id>
                    <IBAN>' . $directDebit['iban'] . '</IBAN>
                </Id>
                <Ccy>EUR</Ccy>
            </CdtrAcct>
            <CdtrAgt>
                <FinInstnId>
                    <BIC>' . $directDebit['bic'] . '</BIC>
                </FinInstnId>
            </CdtrAgt>
            <ChrgBr>SLEV</ChrgBr>
            <CdtrSchmeId>
                <Id>
                    <PrvtId>
                        <Othr>
                            <Id>' . $directDebit['ics'] . '</Id>
                            <SchmeNm>
                                <Prtry>SEPA</Prtry>
                           </SchmeNm>
                       </Othr>
                   </PrvtId>
                </Id>
            </CdtrSchmeId>
            <DrctDbtTxInf>
                <PmtId>
                    <EndToEndId>' . $directDebit['id'] . '</EndToEndId>
                </PmtId>
                <InstdAmt Ccy="EUR">' . $directDebit['amount'] . '</InstdAmt>
                <DrctDbtTx>
                    <MndtRltdInf>
                        <MndtId>' . $directDebit['mandateReference'] . '</MndtId>
                        <DtOfSgntr>' . $directDebit['mandateDate'] . '</DtOfSgntr>
                        <AmdmntInd>false</AmdmntInd>
                    </MndtRltdInf>
                </DrctDbtTx>
                <DbtrAgt>
                    <FinInstnId>
                        <BIC>' . $directDebit['debitBic'] . '</BIC>
                    </FinInstnId>
                 </DbtrAgt>
                 <Dbtr>
                     <Nm>' . $directDebit['lastname'] . ' ' . $directDebit['firstname'] . '</Nm>
                     <PstlAdr>
                         <Ctry>FR</Ctry>
                     </PstlAdr>
                 </Dbtr>
                 <DbtrAcct>
                     <Id>
                         <IBAN>' . $directDebit['debitIban'] . '</IBAN>
                     </Id>
                 </DbtrAcct>
                 <RmtInf>
                    <Ustrd>' . $directDebit['reference'] . '</Ustrd>
                 </RmtInf>
            </DrctDbtTxInf>
        </PmtInf>';
    }
}
