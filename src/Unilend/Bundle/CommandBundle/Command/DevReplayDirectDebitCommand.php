<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class DevReplayDirectDebitCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('dev:feeds:direct_debit')
            ->setDescription('Replay SFPMEI direct debit XML feed')
            ->addArgument(
                'debit_date',
                InputArgument::REQUIRED,
                'The date when the direct debit must be reexecuted'
            )
            ->addOption(
                'execution_date',
                null,
                InputOption::VALUE_OPTIONAL,
                'Execution date of direct debit'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');

        /** @var \prelevements $directDebit */
        $directDebit = $entityManager->getRepository('prelevements');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \compteur_transferts $counter */
        $counter = $entityManager->getRepository('compteur_transferts');
        /** @var \clients_mandats $mandate */
        $mandate = $entityManager->getRepository('clients_mandats');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

        $date = $input->getArgument('debit_date');

        if (1 !== preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date)) {
            $output->writeln('<error>Invalid debit date format: "Y-m-d" expected</error>');
            return;
        }

        $date       = \DateTime::createFromFormat('Y-m-d', $date);
        $outputFile = $this->getContainer()->getParameter('path.sftp') . 'sfpmei/emissions/prelevements/Unilend_Prelevements_' . $date->format('Ymd') . '.xml';

        if (file_exists($outputFile)) {
            $output->writeln('<error>Output file already exists: ' . $outputFile . '</error>');
            return;
        }

        $executionDate = $input->getOption('execution_date');

        if (false === empty($executionDate)) {
            if (1 !== preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $executionDate)) {
                $output->writeln('<error>Invalid execution date format: "Y-m-d" expected</error>');
                return;
            }

            $executionDate = \DateTime::createFromFormat('Y-m-d', $executionDate);
        }

        $settings->get('Virement - BIC', 'type');
        $bic = $settings->value;

        $settings->get('Virement - IBAN', 'type');
        $iban = str_replace(' ', '', $settings->value);

        $settings->get('titulaire du compte', 'type');
        $accountHolder = $settings->value;

        $settings->get('ICS de SFPMEI', 'type');
        $ics = $settings->value;

        $borrowerDirectDebits      = $directDebit->select('status = ' . \prelevements::STATUS_PENDING . ' AND type = ' . \prelevements::CLIENT_TYPE_BORROWER . ' AND type_prelevement = 1 AND date_execution_demande_prelevement = "' . $date->format('Y-m-d') . '"');
        $borrowerDirectDebitsCount = count($borrowerDirectDebits);
        $borrowerTotalAmount       = bcdiv($directDebit->sum('status = ' . \prelevements::STATUS_PENDING . ' AND type = ' . \prelevements::CLIENT_TYPE_BORROWER . ' AND type_prelevement = 1 AND date_execution_demande_prelevement = "' . $date->format('Y-m-d') . '"'), 100, 2);
        $counterId                 = $counter->counter('type = 2') + 1;

        $counter->type  = 2;
        $counter->ordre = $counterId;
        $counter->create();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02">
    <CstmrDrctDbtInitn>
        <GrpHdr>
            <MsgId>SFPMEI/' . $accountHolder . '/' . $date->format('Ymd') . '/' . $counterId . '</MsgId>
            <CreDtTm>' . (new \DateTime())->format('Y-m-d\TH:i:00') . '</CreDtTm>
            <NbOfTxs>' . $borrowerDirectDebitsCount . '</NbOfTxs>
            <CtrlSum>' . $borrowerTotalAmount . '</CtrlSum>
            <InitgPty>
                <Nm>' . $accountHolder . '-SFPMEI' . '</Nm>
            </InitgPty>
        </GrpHdr>';

        foreach ($borrowerDirectDebits as $borrowerDirectDebit) {
            $sequence = 'RCUR';
            if ($borrowerDirectDebit['num_prelevement'] >= 2) {
                $lastDirectDebit = $directDebit->select('status = ' . \prelevements::STATUS_SENT . ' AND type = ' . \prelevements::CLIENT_TYPE_BORROWER . ' AND type_prelevement = 1 AND id_project = ' . $borrowerDirectDebit['id_project'], 'num_prelevement DESC', 0, 1);
                $lastIban        = $lastDirectDebit[0]['iban'];
                $lastBic         = $lastDirectDebit[0]['bic'];

                if ($lastIban != $borrowerDirectDebit['iban'] || $lastBic != $borrowerDirectDebit['bic']) {
                    $sequence = 'FRST';
                }
            } else {
                $sequence = 'FRST';
            }

            $client->get($borrowerDirectDebit['id_client'], 'id_client');
            $mandate->get($borrowerDirectDebit['id_project'], 'id_project');

            $xml .= $this->getXMLElement([
                'iban'             => $iban,
                'bic'              => $bic,
                'ics'              => $ics,
                'id'               => $accountHolder . '/' . $date->format('Ymd') . '/' . $borrowerDirectDebit['id_prelevement'],
                'sequence'         => $sequence,
                'amount'           => bcdiv($borrowerDirectDebit['montant'], 100, 2),
                'completionDate'   => empty($executionDate) ? $borrowerDirectDebit['date_echeance_emprunteur'] : $executionDate->format('Y-m-d'),
                'mandateReference' => $borrowerDirectDebit['motif'],
                'mandateDate'      => date('Y-m-d', strtotime($mandate->updated)),
                'debitBic'         => $borrowerDirectDebit['bic'],
                'debitIban'        => $borrowerDirectDebit['iban'],
                'lastname'         => str_replace(['"', '\'', '\\', '>', '<', '&'], '', $client->nom),
                'firstname'        => str_replace(['"', '\'', '\\', '>', '<', '&'], '', $client->prenom),
                'reference'        => $borrowerDirectDebit['motif']
            ]);

            $directDebit->get($borrowerDirectDebit['id_prelevement'], 'id_prelevement');
            $directDebit->status    = \prelevements::STATUS_SENT;
            $directDebit->added_xml = date('Y-m-d H:i:00');
            $directDebit->update();
        }

        $xml .= '
    </CstmrDrctDbtInitn>
</Document>';

        if (false === empty($borrowerDirectDebits)) {
            file_put_contents($outputFile, $xml);
        }
    }

    /**
     * Help factorize if we have several direct debit types (lender/borrower)
     *
     * @param array $directDebit
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
