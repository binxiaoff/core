<?php

declare(strict_types=1);

namespace Unilend\Test\unitTest\Service\FileSystem;

use League\Flysystem\{FileNotFoundException, FilesystemInterface};
use PHPUnit\Framework\{Error\Warning, TestCase};
use Prophecy\Argument;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Unilend\Service\FileSystem\FileSystemHelper;

/**
 * @coversDefaultClass \Unilend\Service\FileSystem\FileSystemHelper
 *
 * @internal
 */
class FileSystemHelperTest extends TestCase
{
    /** @var string */
    private $destPath;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->destPath = '/path/to/destination';
    }

    /**
     * @covers ::writeLocalFileToFileSystem
     */
    public function testWriteStreamToFileSystem(): void
    {
        $fileResource = tmpfile();
        $fileMetaData = stream_get_meta_data($fileResource);

        $fileSystem   = $this->prophesize(FilesystemInterface::class);
        $streamWriter = $fileSystem->writeStream(Argument::exact($this->destPath), Argument::that(static function ($usedResource) use ($fileMetaData) {
            return stream_get_meta_data($usedResource)['uri'] === $fileMetaData['uri'];
        }));
        $streamWriter->willReturn(true);

        (new FileSystemHelper())->writeLocalFileToFileSystem($fileMetaData['uri'], $this->destPath, $fileSystem->reveal());

        $streamWriter->shouldHaveBeenCalled();
    }

    /**
     * @covers ::writeLocalFileToFileSystem
     */
    public function testWriteStreamToFileSystemNonexistentFile(): void
    {
        $this->expectException(Warning::class);

        $nonexistentSrcFile = '/path/to/nonexistent/file';
        $fileSystem         = $this->prophesize(FilesystemInterface::class);
        (new FileSystemHelper())->writeLocalFileToFileSystem($nonexistentSrcFile, $this->destPath, $fileSystem->reveal());

        $fileSystem->writeStream(Argument::any(), Argument::any())->shouldHaveBeenCalled();
    }

    /**
     * @covers ::writeLocalFileToFileSystem
     */
    public function testWriteStreamToFileSystemFailedWriting(): void
    {
        $this->expectException(RuntimeException::class);

        $fileResource = tmpfile();
        $fileMetaData = stream_get_meta_data($fileResource);
        $fileSystem   = $this->prophesize(FilesystemInterface::class);
        $fileSystem->writeStream(Argument::any(), Argument::any())->willReturn(false);

        (new FileSystemHelper())->writeLocalFileToFileSystem($fileMetaData['uri'], $this->destPath, $fileSystem->reveal());
    }

    /**
     * @dataProvider downloadDataProvider
     *
     * @covers ::download
     *
     * @param FilesystemInterface $filesystem
     * @param string              $filePath
     * @param string|null         $fileName
     *
     * @throws FileNotFoundException
     */
    public function testDownload(FilesystemInterface $filesystem, string $filePath, ?string $fileName = null): void
    {
        $response = (new FileSystemHelper())->download($filesystem, $filePath, $fileName);

        static::assertInstanceOf(StreamedResponse::class, $response);

        if (null === $fileName) {
            static::assertSame('attachment; filename=' . pathinfo($filePath, PATHINFO_FILENAME), $response->headers->get('Content-Disposition'));
        } else {
            static::assertSame('attachment; filename=' . $fileName, $response->headers->get('Content-Disposition'));
        }

        static::assertSame($response->headers->get('Content-Type'), 'application/pdf');
    }

    /**
     * @return array|int[][]
     */
    public function downloadDataProvider(): array
    {
        $fileResource = tmpfile();
        $fileMetaData = stream_get_meta_data($fileResource);
        $filePath     = $fileMetaData['uri'];
        $filesystem   = $this->prophesize(FilesystemInterface::class);

        $filesystem->readStream(Argument::exact($filePath))->willReturn($fileResource);

        $filesystem->getMimetype(Argument::exact($filePath))->willReturn('application/pdf');

        return [
            'file name defined'     => [$filesystem->reveal(), $filePath, 'fileName'],
            'file name not defined' => [$filesystem->reveal(), $filePath, null],
        ];
    }
}
