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
    CONST ID_LEGAL_DOC_CORRESPONDANCE_TABLE = [
        92  => 44,
        93  => 45,
        95  => 46,
        254 => 47,
        255 => 48,
        301 => 49,
        443 => 50,
        444 => 51,
        473 => 52,
        474 => 53
    ];

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
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $clientRepository            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $acceptedLegalDocsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:AcceptationsLegalDocs');

        $renamedPdfs   = 0;
        $savedPdfNames = 0;

        $directoryFinder = new Finder();
        $directoryFinder->directories()->in($this->protectedPath . LenderTermsOfSaleGenerator::PATH);

        foreach ($directoryFinder as $directory) {
            $clientId = $directory->getFilename();

            try {
                $finder = new Finder();
                $finder->files()->in($directory->getPathname());

                foreach ($finder as $file) {
                    $hash               = null;
                    $idLegalDocFromName = null;
                    $fileName           = $file->getFilename();

                    if (false === $this->checkFileName($fileName)){
                        $this->logger->warning($directory->getPathname() . ' does contain a file which does not seem to be a lender terms of sale. Filename: ' . $fileName, [
                            'class'          => __CLASS__,
                            'function'       => __FUNCTION__
                        ]);
                        continue;
                    }

                    if ('CGV-UNILEND-PRETEUR' === substr($fileName, 0, 19)) {
                        $normalizedName = str_replace('CGV-UNILEND-PRETEUR', 'cgv_preteurs', $file->getPathname());

                        if (false === $this->filesystem->exists($normalizedName)) {
                            $this->filesystem->rename($file->getPathname(), $normalizedName);
                            $fileName = $file->getFilename();
                        } else {
                            $this->filesystem->remove($file);
                            continue;
                        }
                    }

                    $hash   = $this->getHashFromFileName($fileName);
                    $client = $clientRepository->findOneBy(['hash' => $hash]);
                    if (null === $client || $clientId != $client->getIdClient()) {
                        $this->logger->warning('Folder client id and client from hash do not match. Filename: ' . $fileName, [
                            'class'     => __CLASS__,
                            'function'  => __FUNCTION__,
                            'id_client' => $clientId,
                            'hash'      => $hash,
                        ]);
                        continue;
                    }

                    $idLegalDocFromName = $this->getIdLegalDocFromFileName($fileName);
                    $idLegalDoc         = isset(self::ID_LEGAL_DOC_CORRESPONDANCE_TABLE[$idLegalDocFromName]) ? self::ID_LEGAL_DOC_CORRESPONDANCE_TABLE[$idLegalDocFromName] : $idLegalDocFromName;
                    $accepted           = $acceptedLegalDocsRepository->findOneBy(['idClient' => $clientId, 'idLegalDoc' => $idLegalDoc]);

                    if (null === $accepted) {
                        $this->logger->warning('There is nor entry in acceptations_legal_docs for this client and legal doc. Filename: ' . $fileName, [
                            'class'        => __CLASS__,
                            'function'     => __FUNCTION__,
                            'id_client'    => $clientId,
                            'id_legal_doc' => $idLegalDoc
                        ]);
                        continue;
                    }

                    if (false === $this->filesystem->exists($this->lenderTermsOfSaleGenerator->getPath($accepted))) {
                        $this->filesystem->rename($file->getPathname(), $this->lenderTermsOfSaleGenerator->getPath($accepted));
                        $renamedPdfs++;
                    }

                    if (null === $accepted->getPdfName()) {
                        $accepted->setPdfName($this->lenderTermsOfSaleGenerator->getName($accepted));

                        $this->entityManager->flush($accepted);
                        $savedPdfNames++;
                    }
                }
            } catch (\Exception $exception) {
                $this->logger->error('An error occurred while renaming lender terms of sale pdf. Message: ' . $exception->getMessage(), [
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                    'id_client' => $clientId
                ]);
            }
        }

        $output->writeln($savedPdfNames . ' pdf names have been saved, and ' . $renamedPdfs . ' files have been renamed.');
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

    /**
     * @param string $fileName
     *
     * @return bool
     */
    private function checkFileName(string $fileName): bool
    {
        return 'cgv_preteurs' === substr($fileName, 0, 12) || 'CGV-UNILEND-PRETEUR' === substr($fileName, 0, 19);
    }
}
