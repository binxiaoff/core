<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Service\IfuManager;

class QueriesInfosbenQueryCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this->setName('unilend:feeds_out:ifu_infosben:generate')
            ->setDescription('Generate the lenders basic information for those who are the beneficiaries in a given year')
            ->addOption('year', null, InputOption::VALUE_REQUIRED, 'Optional. Define the year to export in format YYYY')
            ->setHelp(<<<EOF
The <info>unilend:feeds_out:ifu_infosben:generate</info> command generate a csv which contains the lenders basic information who are the beneficiaries in a given year.
Usage <info>bin/console unilend:feeds_out:ifu_infosben:generate [-year=2017]</info>
The <info>year</info> is optional. By default, it generates the file of the last year if we are on January, February or March, otherwise it generates the file for the current year.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ifuManager = $this->getContainer()->get('unilend.service.ifu_manager');

        $year = $input->getOption('year');
        if (empty($year)) {
            $year = $ifuManager->getYear();
        }

        $filePath = $ifuManager->getStorageRootPath();
        $filename = IfuManager::FILE_NAME_INFOSBEN;
        $file     = $filePath . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($file)) {
            unlink($file);
        }

        $walletsWithMovements = $ifuManager->getWallets($year);

        $data    = [];
        $headers = [
            'Cdos',
            'Cbéné',
            'CEtabl',
            'CGuichet',
            'RéfCompte',
            'NatCompte',
            'TypCompte',
            'CDRC'
        ];

        /** @var Wallet $wallet */
        foreach ($walletsWithMovements as $wallet) {
            $data[] = [
                1,
                $wallet->getWireTransferPattern(),
                14378,
                '',
                $wallet->getIdClient()->getIdClient(),
                4,
                6,
                'P'
            ];
        }

        $this->exportCSV($data, $file, $headers);
    }

    /**
     * @param       $data
     * @param       $filePath
     * @param array $headers
     *
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    private function exportCSV($data, $filePath, array $headers)
    {

        \PHPExcel_Settings::setCacheStorageMethod(
            \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
            ['memoryCacheSize' => '2048MB', 'cacheTime' => 1200]
        );

        $document    = new \PHPExcel();
        $activeSheet = $document->setActiveSheetIndex(0);

        foreach ($headers as $index => $columnName) {
            $activeSheet->setCellValueByColumnAndRow($index, 1, $columnName);
        }

        foreach ($data as $rowIndex => $row) {
            $colIndex = 0;
            foreach ($row as $cellValue) {
                $activeSheet->setCellValueByColumnAndRow($colIndex++, $rowIndex + 2, $cellValue);
            }
        }

        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = \PHPExcel_IOFactory::createWriter($document, 'Excel5');
        $writer->save(str_replace(__FILE__, $filePath, __FILE__));
    }
}
