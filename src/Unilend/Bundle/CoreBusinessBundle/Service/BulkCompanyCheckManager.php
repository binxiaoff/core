<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\{Exception\FileException, File, UploadedFile};
use Unilend\Entity\Users;

class BulkCompanyCheckManager
{
    const INPUT_FILE_PENDING_ELIGIBILITY_PATH   = 'companies_eligibility/input/pending/';
    const INPUT_FILE_PROCESSED_ELIGIBILITY_PATH = 'companies_eligibility/input/processed/';
    const INPUT_FILE_ERROR_ELIGIBILITY_PATH     = 'companies_eligibility/input/errors/';
    const OUTPUT_FILE_ELIGIBILITY_PATH          = 'companies_eligibility/output/';

    const INPUT_FILE_PENDING_CREATION_PATH   = 'project_creation/input/pending/';
    const INPUT_FILE_PROCESSED_CREATION_PATH = 'project_creation/input/processed/';
    const INPUT_FILE_ERROR_CREATION_PATH     = 'project_creation/input/errors/';
    const OUTPUT_FILE_CREATION_PATH          = 'project_creation/output/';

    const INPUT_FILE_PENDING_DATA_RETRIEVAL_PATH   = 'company_data/input/pending/';
    const INPUT_FILE_PROCESSED_DATA_RETRIEVAL_PATH = 'company_data/input/processed/';
    const INPUT_FILE_ERROR_DATA_RETRIEVAL_PATH     = 'company_data/input/errors/';
    const OUTPUT_FILE_DATA_RETRIEVAL_PATH          = 'company_data/output/';

    /** @var string */
    private $baseDir;
    /** @var Filesystem */
    private $fileSystem;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var SlackManager */
    private $slackManager;

    /**
     * @param string                 $protectedPath
     * @param Filesystem             $filesystem
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     * @param SlackManager           $slackManager
     */
    public function __construct(
        $protectedPath,
        Filesystem $filesystem,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        SlackManager $slackManager
    )
    {
        $this->baseDir       = $protectedPath;
        $this->fileSystem    = $filesystem;
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
        $this->slackManager  = $slackManager;
    }

    /**
     * @return string
     */
    public function getEligibilityInputPendingDir(): string
    {
        return $this->baseDir . self::INPUT_FILE_PENDING_ELIGIBILITY_PATH;
    }

    /**
     * @return string
     */
    public function getEligibilityInputProcessedDir(): string
    {
        return $this->baseDir . self::INPUT_FILE_PROCESSED_ELIGIBILITY_PATH;
    }

    /**
     * @return string
     */
    public function getEligibilityInputErrorDir(): string
    {
        return $this->baseDir . self::INPUT_FILE_ERROR_ELIGIBILITY_PATH;
    }

    /**
     * @return string
     */
    public function getEligibilityOutputDir(): string
    {
        return $this->baseDir . self::OUTPUT_FILE_ELIGIBILITY_PATH;
    }

    /**
     * @return string
     */
    public function getProjectCreationInputPendingDir(): string
    {
        return $this->baseDir . self::INPUT_FILE_PENDING_CREATION_PATH;
    }

    /**
     * @return string
     */
    public function getProjectCreationInputProcessedDir(): string
    {
        return $this->baseDir . self::INPUT_FILE_PROCESSED_CREATION_PATH;
    }

    /**
     * @return string
     */
    public function getProjectCreationInputErrorDir(): string
    {
        return $this->baseDir . self::INPUT_FILE_ERROR_CREATION_PATH;
    }

    /**
     * @return string
     */
    public function getProjectCreationOutputDir(): string
    {
        return $this->baseDir . self::OUTPUT_FILE_CREATION_PATH;
    }

    /**
     * @return string
     */
    public function getCompanyDataInputPendingDir(): string
    {
        return $this->baseDir . self::INPUT_FILE_PENDING_DATA_RETRIEVAL_PATH;
    }

    /**
     * @return string
     */
    public function getCompanyDataInputProcessedDir(): string
    {
        return $this->baseDir . self::INPUT_FILE_PROCESSED_DATA_RETRIEVAL_PATH;
    }

    /**
     * @return string
     */
    public function getCompanyDataInputErrorDir(): string
    {
        return $this->baseDir . self::INPUT_FILE_ERROR_DATA_RETRIEVAL_PATH;
    }

    /**
     * @return string
     */
    public function getCompanyDataOutputDir(): string
    {
        return $this->baseDir . self::OUTPUT_FILE_DATA_RETRIEVAL_PATH;
    }

    /**
     * @param string       $path
     * @param UploadedFile $file
     * @param Users        $user
     *
     * @return File
     * @throws FileException
     */
    public function uploadFile($path, UploadedFile $file, Users $user): File
    {
        if (false === is_dir($path)) {
            $this->fileSystem->mkdir($path);
        }
        $originalFileName = \URLify::transliterate($file->getClientOriginalName());
        $fileName         = $user->getIdUser() . '_' . $originalFileName;

        if ($this->fileSystem->exists($path . $fileName)) {
            $fileName = $user->getIdUser() . '_' . pathinfo($originalFileName, PATHINFO_FILENAME) . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        }

        return $file->move($path, $fileName);
    }

    /**
     * @param string $fileName
     *
     * @return null|Users
     */
    public function getUploadUser($fileName): ?Users
    {
        $fileNameParts = explode('_', $fileName);

        if (isset($fileNameParts[0]) && $user = $this->entityManager->getRepository(Users::class)->find($fileNameParts[0])) {
            return $user;
        }

        return null;
    }

    /**
     * @return array
     */
    public function getSirenListForEligibilityCheck(): array
    {
        $now          = new \DateTime();
        $inputDir     = $this->getEligibilityInputPendingDir();
        $processedDir = $this->getEligibilityInputProcessedDir();
        $errorDir     = $this->getEligibilityInputErrorDir();

        return $this->getSirenList(
            $inputDir,
            $processedDir . $now->format('Y-m') . DIRECTORY_SEPARATOR,
            $errorDir . $now->format('Y-m') . DIRECTORY_SEPARATOR
        );
    }

    /**
     * @return array
     */
    public function getSirenListForProjectCreation(): array
    {
        $now          = new \DateTime();
        $inputDir     = $this->getProjectCreationInputPendingDir();
        $processedDir = $this->getProjectCreationInputProcessedDir();
        $errorDir     = $this->getProjectCreationInputErrorDir();

        return $this->getSirenList(
            $inputDir,
            $processedDir . $now->format('Y-m') . DIRECTORY_SEPARATOR,
            $errorDir . $now->format('Y-m') . DIRECTORY_SEPARATOR
        );
    }

    /**
     * @return array
     */
    public function getSirenListForCompanyDataRetrieval(): array
    {
        $now          = new \DateTime();
        $inputDir     = $this->getCompanyDataInputPendingDir();
        $processedDir = $this->getCompanyDataInputProcessedDir();
        $errorDir     = $this->getCompanyDataInputErrorDir();

        return $this->getSirenList(
            $inputDir,
            $processedDir . $now->format('Y-m') . DIRECTORY_SEPARATOR,
            $errorDir . $now->format('Y-m') . DIRECTORY_SEPARATOR
        );
    }

    /**
     * @param string $inputDir
     * @param string $processedDir
     * @param string $errorDir
     *
     * @return array
     */
    private function getSirenList($inputDir, $processedDir, $errorDir): array
    {
        $now       = new \DateTime();
        $sirenList = [];

        if (false === is_dir($processedDir)) {
            $this->fileSystem->mkdir($processedDir);
        }
        if (false === is_dir($errorDir)) {
            $this->fileSystem->mkdir($errorDir);
        }

        if (is_dir($inputDir) && false !== $dirContent = scandir($inputDir)) {
            foreach ($dirContent as $file) {
                if (in_array($file, ['.', '..'])) {
                    continue;
                }
                if (false !== $excelReader = $this->readExcelFile($inputDir . $file)) {
                    try {
                        $sirenList[$file] = $excelReader->getSheet(0)->toArray();
                        $this->fileSystem->rename($inputDir . $file, $processedDir . $now->getTimestamp() . '_' . $file);
                        $message = 'Le fichier: *' . $file . '* est en cours de traitement';
                    } catch (\Exception $exception) {
                        $message = 'Impossible de récupérer le contenu du fichier: *' . $file . '*';
                        $this->fileSystem->rename($inputDir . $file, $errorDir . $now->getTimestamp() . '_' . $file);
                        $this->logger->warning(
                            'Could not get file content. File name: ' . $inputDir . $file . ' Error: ' . $exception->getMessage(),
                            ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                        );
                    }
                } else {
                    $message = 'Impossible de lire le fichier: *' . $file . '*';
                    $this->fileSystem->rename($inputDir . $file, $errorDir . $now->getTimestamp() . '_' . $file);
                    $this->logger->warning('Could not get Excel reader on file ' . $inputDir . $file, ['method' => __METHOD__]);
                }

                if ($user = $this->getUploadUser($file)) {
                    if (false === empty($user->getSlack())) {
                        $this->slackManager->sendMessage($message, $user->getSlack());
                    }
                }
            }
        }

        return $sirenList;
    }

    /**
     * @param $filePath
     *
     * @return \PHPExcel|boolean
     */
    private function readExcelFile($filePath)
    {
        try {
            $inputFileType = \PHPExcel_IOFactory::identify($filePath);
            $fileReader    = \PHPExcel_IOFactory::createReader($inputFileType);

            return $fileReader->load($filePath);
        } catch (\Exception $exception) {
            unset($exception);
        }

        return false;
    }
}
