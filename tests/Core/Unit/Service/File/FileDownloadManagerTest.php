<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Service\File;

use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterFactory;
use KLS\Core\Entity\File;
use KLS\Core\Entity\FileVersion;
use KLS\Core\Service\File\FileDownloadManager;
use KLS\Core\Service\FileSystem\FileSystemHelper;
use KLS\CreditGuaranty\FEI\Service\FinancingObjectUpdater;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\Core\Service\File\FileDownloadManager
 *
 * @internal
 */
class FileDownloadManagerTest extends TestCase
{
    use ProphecyTrait;
    use UserStaffTrait;

    /** @var FileSystemHelper|ObjectProphecy */
    private $fileSystemHelper;

    protected function setUp(): void
    {
        $this->fileSystemHelper = $this->prophesize(FileSystemHelper::class);
    }

    protected function tearDown(): void
    {
        $this->fileSystemHelper = null;
    }

    /**
     * @covers ::download
     *
     * @dataProvider downloadDataProvider
     */
    public function testDownload(FileVersion $fileVersion, string $mimeType, string $attachment): void
    {
        $this->fileSystemHelper->normalizeFileName(Argument::type('string'))->shouldBeCalledOnce()
            ->willReturn($fileVersion->getOriginalName())
        ;

        $response = $this->createTestObject()->download($fileVersion);

        static::assertSame($attachment, $response->headers->get('Content-Disposition'));
        static::assertSame($mimeType, $response->headers->get('Content-Type'));
    }

    /**
     * @covers ::downloadNewXlsxFile
     */
    public function testDownloadNewXlsxFile(): void
    {
        $style = (new StyleBuilder())->build();

        $writer = WriterFactory::createFromType(Type::XLSX);
        $row    = WriterEntityFactory::createRowFromArray(FinancingObjectUpdater::IMPORT_FILE_COLUMNS, $style);

        $response = $this->createTestObject()->downloadNewXlsxFile($writer, [$row], 'filename.xlsx');

        static::assertSame('attachment; filename=filename.xlsx', $response->headers->get('Content-Disposition'));
        static::assertSame(FileDownloadManager::XLSX_CONTENT_TYPE, $response->headers->get('Content-Type'));
    }

    public function downloadDataProvider(): iterable
    {
        yield 'download-image' => $this->getImageFileVersion();
        yield 'download-pdf' => $this->getPdfFileVersion();
    }

    protected function createTestObject(): FileDownloadManager
    {
        return new FileDownloadManager(
            $this->fileSystemHelper->reveal()
        );
    }

    private function getImageFileVersion(): array
    {
        $user = $this->createUserWithStaff();

        $fileVersion = new FileVersion(
            'path/to/my/image',
            $user,
            new File('filename.jpg'),
            'filesystem',
            null,
            'image/jpeg'
        );

        $fileVersion->setOriginalName('filename.jpg');

        return [
            $fileVersion,
            'image/jpeg',
            'attachment; filename=filename.jpg',
        ];
    }

    private function getPdfFileVersion(): array
    {
        $user = $this->createUserWithStaff();

        $fileVersion = new FileVersion(
            'path/to/my/image',
            $user,
            new File('filename.jpg'),
            'filesystem',
            null,
            'application/pdf'
        );

        $fileVersion->setOriginalName('filename.pdf');

        return [
            $fileVersion,
            'application/pdf',
            'attachment; filename=filename.pdf',
        ];
    }
}
