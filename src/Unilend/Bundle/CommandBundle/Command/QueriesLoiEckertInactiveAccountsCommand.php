<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{
    Input\InputInterface, Output\OutputInterface
};
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, ClientsStatus
};

class QueriesLoiEckertInactiveAccountsCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('queries:loi_eckert')
            ->setDescription('Extract information of inactive accounts according to Loi Eckert definition');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager       = $this->getContainer()->get('doctrine.orm.entity_manager');
        $filePath            = $this->getContainer()->getParameter('path.protected') . '/queries/' . 'loi_eckert.xlsx';

        /** @var \PHPExcel $document */
        $document    = new \PHPExcel();
        $activeSheet = $document->setActiveSheetIndex(0);
        $row         = 1;

        $activeSheet->setCellValue('A' . $row, 'id Client');
        $activeSheet->setCellValue('B' . $row, 'Client Type');
        $activeSheet->setCellValue('C' . $row, 'Sexe');
        $activeSheet->setCellValue('D' . $row, 'Date de Naissance');
        $activeSheet->setCellValue('E' . $row, 'Prénom');
        $activeSheet->setCellValue('F' . $row, 'Nom');
        $activeSheet->setCellValue('G' . $row, 'Email');
        $activeSheet->setCellValue('H' . $row, 'Dernière connection');
        $activeSheet->setCellValue('I' . $row, 'Dernier mouvement');
        $activeSheet->setCellValue('J' . $row, 'Montant disponible');
        $activeSheet->setCellValue('K' . $row, 'Statut du compte');
        $activeSheet->setCellValue('L' . $row, 'Date de validation');
        $row++;

        foreach ($entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findAllClientsForLoiEckert() as $client) {
            $activeSheet->setCellValue('A' . $row, $client['id_client']);
            $activeSheet->setCellValue('B' . $row, in_array($client['type'], [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER]) ? 'Personne physique' : 'Personne morale');
            $activeSheet->setCellValue('C' . $row, Clients::TITLE_MISTER == $client['civilite'] ? 'H' : 'F');
            $activeSheet->setCellValueExplicit('D' . $row, \PHPExcel_Shared_Date::PHPToExcel(\DateTime::createFromFormat('Y-m-d', $client['naissance'])), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
            $activeSheet->setCellValue('E' . $row, strtr($client['prenom'], \ficelle::$normalizeChars));
            $activeSheet->setCellValue('F' . $row, strtr($client['nom'], \ficelle::$normalizeChars));
            $activeSheet->setCellValue('G' . $row, $client['email']);

            if ('0000-00-00 00:00:00' == $client['lastlogin']) {
                $activeSheet->setCellValue('H' . $row, '');
            } else {
                $activeSheet->setCellValueExplicit('H' . $row, \PHPExcel_Shared_Date::PHPToExcel(\DateTime::createFromFormat('y-m-d H:i:s', $client['lastlogin'])), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
            }
            if (null == $client['lastMovement']) {
                $activeSheet->setCellValue('I' . $row, '');
            } else {
                $activeSheet->setCellValueExplicit('I' . $row, \PHPExcel_Shared_Date::PHPToExcel(\DateTime::createFromFormat('Y-m-d H:i:s', $client['lastMovement'])), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
            }
            $activeSheet->setCellValueExplicit('J' . $row, $client['availableBalance'], \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $activeSheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            $activeSheet->setCellValue('K' . $row, in_array($client['id_status'], ClientsStatus::GRANTED_LOGIN) ? 'compte actif' : 'compte desactivé');
            if (null == $client['validationDate']) {
                $activeSheet->setCellValue('L' . $row, '');
            } else {
                $activeSheet->setCellValueExplicit('L' . $row, \PHPExcel_Shared_Date::PHPToExcel(\DateTime::createFromFormat('Y-m-d H:i:s', $client['validationDate'])), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $activeSheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
            }
            $row += 1;
        }

        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = \PHPExcel_IOFactory::createWriter($document, 'Excel2007');
        $writer->save(str_replace(__FILE__, $filePath, __FILE__));
    }
}
