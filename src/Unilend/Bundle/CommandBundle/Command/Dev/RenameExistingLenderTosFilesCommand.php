<?php

namespace Unilend\Bundle\CommandBundle\Command\Dev;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\{Input\InputInterface, Input\InputOption, Output\OutputInterface};
use Symfony\Component\Finder\Finder;
use Unilend\Bundle\CoreBusinessBundle\Service\Document\LenderTermsOfSaleGenerator;

class RenameExistingLenderTosFilesCommand extends Command
{
    /** @var Filesystem */
    private $filesystem;
    /** @var string */
    private $protectedPath;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LenderTermsOfSaleGenerator */
    private $lenderTermsOfSaleGenerator;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Filesystem                 $filesystem
     * @param EntityManagerInterface     $entityManager
     * @param LenderTermsOfSaleGenerator $lenderTermsOfSaleGenerator
     * @param LoggerInterface            $logger
     * @param string                     $protectedPath
     */
    public function __construct(
        Filesystem $filesystem,
        EntityManagerInterface $entityManager,
        LenderTermsOfSaleGenerator $lenderTermsOfSaleGenerator,
        LoggerInterface $logger,
        string $protectedPath
    )
    {
        $this->filesystem                 = $filesystem;
        $this->entityManager              = $entityManager;
        $this->logger                     = $logger;
        $this->lenderTermsOfSaleGenerator = $lenderTermsOfSaleGenerator;
        $this->protectedPath              = $protectedPath;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('unilend:dev_tools:lender:legacy_terms_of_sale:rename')
            ->setDescription('Renames already existing terms of sale pdf document')
            ->setHelp(<<<EOF
The <info>unilend:dev_tools:lender:legacy_terms_of_sale:rename</info> command renames the already existing pdf version of accepted lenders terms of sale and saves it in the database.
<info>php bin/console unilend:dev_tools:lender:legacy_terms_of_sale:rename</info>
EOF
            )
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Number of clients to process');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $limit = $input->getOption('limit');
        $limit = $limit ? $limit : 1000;

        // idLegalDoc before migration => idLegalDoc now (DEV-827)
        $idLegalDocCorrespondanceTable = [
            92  => 43,
            93  => 44,
            95  => 45,
            254 => 46,
            255 => 47,
            300 => 48,
            301 => 49,
            443 => 50,
            444 => 51,
            473 => 52,
            474 => 53
        ];

        $clientRepository            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $acceptedLegalDocsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:AcceptationsLegalDocs');

        $clientWithoutPdfName = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:AcceptationsLegalDocs')
            ->getLenderWithAcceptedIdLegalDocAndNoPdfValue($limit);

        $renamedPdfs   = 0;
        $savedPdfNames = 0;

        foreach ($clientWithoutPdfName as $clientWithTos) {
            try {
                $directoryName = $this->protectedPath . LenderTermsOfSaleGenerator::PATH . DIRECTORY_SEPARATOR . $clientWithTos['idClient'];
                if (false === $this->filesystem->exists($directoryName)) {
                    continue;
                }

                $finder = new Finder();
                $finder->files()->in($directoryName);

                foreach ($finder as $file) {
                    $hash               = null;
                    $idLegalDocFromName = null;
                    $fileName           = $file->getFilename();

                    if ('cgv_preteurs' !== substr($fileName, 0, 12)) {
                        $this->logger->warning($directoryName . ' does contain a file which does not seem to be a lender terms of sale. Filename: ' . $fileName, [
                            'class'          => __CLASS__,
                            'function'       => __FUNCTION__
                        ]);
                        continue;
                    }

                    $hash   = $this->getHashFromFileName($fileName);
                    $client = $clientRepository->findOneBy(['hash' => $hash]);
                    if (null === $client || $clientWithTos['idClient'] != $client->getIdClient()) {
                        $this->logger->warning('Folder client id and client from hash do not match. Filename: ' . $fileName, [
                            'class'     => __CLASS__,
                            'function'  => __FUNCTION__,
                            'id_client' => $clientWithTos['idClient'],
                            'hash'      => $hash,
                        ]);
                        continue;
                    }

                    $idLegalDocFromName = $this->getIdLegalDocFromFileName($fileName);
                    $idLegalDoc         = isset($idLegalDocCorrespondanceTable[$idLegalDocFromName]) ? $idLegalDocCorrespondanceTable[$idLegalDocFromName] : $idLegalDocFromName;
                    $accepted           = $acceptedLegalDocsRepository->findOneBy(['idClient' => $clientWithTos['idClient'], 'idLegalDoc' => $idLegalDoc]);

                    if (null === $accepted) {
                        $this->logger->warning('There is nor entry in acceptations_legal_docs for this client and legal doc. Filename: ' . $fileName, [
                            'class'        => __CLASS__,
                            'function'     => __FUNCTION__,
                            'id_client'    => $clientWithTos['idClient'],
                            'id_legal_doc' => $idLegalDoc
                        ]);
                        continue;
                    }

                    if (false === $this->filesystem->exists($this->lenderTermsOfSaleGenerator->getPath($accepted))) {
                        $this->filesystem->rename($file->getPathname(), $this->lenderTermsOfSaleGenerator->getPath($accepted));
                        $renamedPdfs++;
                    }

                    $accepted->setPdfName($this->lenderTermsOfSaleGenerator->getName($accepted));

                    $this->entityManager->flush();
                    $savedPdfNames++;
                }
            } catch (\Exception $exception) {
                $this->logger->error('An error occurred while renaming lender terms of sale pdf. Message: ' . $exception->getMessage(), [
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                    'id_client' => $clientWithTos['idClient']
                ]);
            }
        }

        $output->writeln('For ' . count($clientWithoutPdfName) . ' clients ' . $savedPdfNames . ' pdf names have been saved, and ' . $renamedPdfs . ' files have been renamed.');
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    private function getHashFromFileName(string $filename): string
    {

        $filename   = str_replace('cgv_preteurs-', '', $filename);
        $filename   = str_replace('.pdf', '', $filename);
        $endOfHash  = strrpos($filename, '-');
        $idLegalDoc = substr($filename, $endOfHash + 1);
        $clientHash = str_replace('-' . $idLegalDoc, '', $filename);

        return $clientHash;
    }

    /**
     * @param string $filename
     *
     * @return int
     */
    private function getIdLegalDocFromFileName(string $filename): int
    {
        $filename   = str_replace('.pdf', '', $filename);
        $separator  = strrpos($filename, '-');
        $idLegalDoc = substr($filename, $separator + 1);

        return (int)$idLegalDoc;
    }
}
