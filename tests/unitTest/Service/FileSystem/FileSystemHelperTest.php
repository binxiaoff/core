<?php

declare(strict_types=1);

namespace Unilend\Test\unitTest\Service\FileSystem;

use Faker\Provider\{Base, File};
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
    /** @var string */
    private $srcPath;
    /** @var bool|resource */
    private $testFileResource;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->srcPath          = sys_get_temp_dir() . DIRECTORY_SEPARATOR . Base::lexify('?????');
        $this->destPath         = Base::lexify('/????/???');
        $this->testFileResource = $this->buildTestFile();
    }

    /**
     * @covers ::writeTempFileToFileSystem
     */
    public function testWriteTempFileToFileSystem(): void
    {
        $srcPath = $this->srcPath;

        $fileSystem   = $this->prophesize(FilesystemInterface::class);
        $streamWriter = $fileSystem->writeStream(Argument::exact($this->destPath), Argument::that(static function ($usedResource) use ($srcPath) {
            return stream_get_meta_data($usedResource)['uri'] === $srcPath;
        }));
        $streamWriter->willReturn(true);

        (new FileSystemHelper())->writeTempFileToFileSystem($this->srcPath, $fileSystem->reveal(), $this->destPath);

        $streamWriter->shouldHaveBeenCalled();
        static::assertFileNotExists($this->srcPath);
    }

    /**
     * @covers ::writeTempFileToFileSystem
     */
    public function testWriteTempFileToFileSystemNonexistentFile(): void
    {
        $this->expectException(Warning::class);

        $nonexistentSrcFile = Base::lexify('/????/???');
        $fileSystem         = $this->prophesize(FilesystemInterface::class);

        (new FileSystemHelper())->writeTempFileToFileSystem($nonexistentSrcFile, $fileSystem->reveal(), $this->destPath);
    }

    /**
     * @covers ::writeTempFileToFileSystem
     */
    public function testWriteTempFileToFileSystemFailedWriting(): void
    {
        $this->expectException(RuntimeException::class);

        $fileSystem = $this->prophesize(FilesystemInterface::class);
        $fileSystem->writeStream(Argument::any(), Argument::any())->willReturn(false);

        (new FileSystemHelper())->writeTempFileToFileSystem($this->srcPath, $fileSystem->reveal(), $this->destPath);
    }

    /**
     * @dataProvider downloadDataProvider
     *
     * @covers ::download
     *
     * @param string|false $mimeType
     * @param string|null  $fileName
     *
     * @throws FileNotFoundException
     */
    public function testDownload($mimeType, ?string $fileName = null): void
    {
        $filesystem = $this->prophesize(FilesystemInterface::class);
        $filesystem->readStream(Argument::exact($this->srcPath))->willReturn($this->testFileResource);
        $filesystem->getMimetype(Argument::exact($this->srcPath))->willReturn($mimeType);

        $response = (new FileSystemHelper())->download($filesystem->reveal(), $this->srcPath, $fileName);

        static::assertInstanceOf(StreamedResponse::class, $response);

        if (null === $fileName) {
            static::assertSame('attachment; filename=' . pathinfo($this->srcPath, PATHINFO_FILENAME), $response->headers->get('Content-Disposition'));
        } else {
            static::assertSame('attachment; filename=' . $fileName, $response->headers->get('Content-Disposition'));
        }

        if (false === $mimeType) {
            static::assertSame('application/octet-stream', $response->headers->get('Content-Type'));
        } else {
            static::assertSame($mimeType, $response->headers->get('Content-Type'));
        }
    }

    /**
     * @return array
     */
    public function downloadDataProvider(): array
    {
        return [
            'mime type detected, file name defined'         => [File::mimeType(), Base::lexify('???') . '.' . File::fileExtension()],
            'mime type not detected, file name defined'     => [false, Base::lexify('???') . '.' . File::fileExtension()],
            'mime type detected, file name not defined'     => [File::mimeType(), null],
            'mime type not detected, file name not defined' => [false, null],
        ];
    }

    /**
     * @return bool|resource
     */
    private function buildTestFile()
    {
        return fopen($this->srcPath, 'w+b');
    }
}
