<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\Service\ServiceTerms;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Provider\Base;
use InvalidArgumentException;
use Knp\Snappy\Pdf;
use League\Flysystem\{FileExistsException, FilesystemInterface};
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionClassConstant;
use Twig\Environment;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\{AcceptationsLegalDocs, Clients, Interfaces\FileStorageInterface, LegalDocument};
use Unilend\Service\FileSystem\FileSystemHelper;
use Unilend\Service\ServiceTerms\ServiceTermsGenerator;

/**
 * @coversDefaultClass \Unilend\Service\ServiceTerms\ServiceTermsGenerator
 *
 * @internal
 */
class ServiceTermsGeneratorTest extends TestCase
{
    /** @var FilesystemInterface|ObjectProphecy */
    private $generatedDocumentFilesystem;
    /** @var Environment|ObjectProphecy */
    private $twig;
    /** @var Pdf|ObjectProphecy */
    private $snappy;
    /** @var string */
    private $publicDirectory;
    /** @var string */
    private $temporaryDirectory;
    /** @var FileSystemHelper|ObjectProphecy */
    private $fileSystemHelper;
    /** @var ManagerRegistry|ObjectProphecy */
    private $managerRegistry;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->generatedDocumentFilesystem = $this->prophesize(FilesystemInterface::class);
        $this->fileSystemHelper            = $this->prophesize(FileSystemHelper::class);
        $this->publicDirectory             = Base::lexify('?????/?????');
        $this->twig                        = $this->prophesize(Environment::class);
        $this->snappy                      = $this->prophesize(Pdf::class);
        $this->temporaryDirectory          = Base::lexify('?????/?????');

        $this->managerRegistry = $this->prophesize(ManagerRegistry::class);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->managerRegistry->getManagerForClass(AcceptationsLegalDocs::class)->willReturn($entityManager->reveal());
    }

    /**
     * @covers ::generate
     */
    public function testGenerateInvalidArgument(): void
    {
        $serviceTermsGenerator = $this->createTestObject();

        $this->expectException(InvalidArgumentException::class);

        $serviceTermsGenerator->generate($this->prophesize(FileStorageInterface::class)->reveal());
    }

    /**
     * @covers ::generate
     *
     * @throws FileExistsException
     */
    public function testExistingFile(): void
    {
        $serviceTermsGenerator = $this->createTestObject();

        $acceptationLegalDoc = $this->createAcceptationLegalDoc();

        $this->generatedDocumentFilesystem->has(Argument::type('string'))->willReturn(true);

        $serviceTermsGenerator->generate($acceptationLegalDoc);

        $this->generatedDocumentFilesystem->has(Argument::type('string'))->shouldHaveBeenCalled();
        $this->generatedDocumentFilesystem->write(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::generate
     *
     * @throws FileExistsException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function testGenerate(): void
    {
        $renderedLegalDocument = $this->getRandomText();
        $this->twig->render(Argument::type('string'), Argument::cetera())->willReturn($renderedLegalDocument);
        $serviceTermsGenerator = $this->createTestObject();

        $acceptationLegalDoc = $this->createAcceptationLegalDoc();

        $serviceTermsGenerator->generate($acceptationLegalDoc);

        $acceptationLegalDoc->getLegalDoc()->getProphecy()->getContent()->shouldHaveBeenCalled();
        $this->twig->render(Argument::type('string'), Argument::cetera())->shouldHaveBeenCalled();

        $generateFromHtmlArgument = ['content' => Argument::exact($renderedLegalDocument), 'renderedFilePath' => Argument::type('string')];
        $this->snappy->generateFromHtml(...array_values($generateFromHtmlArgument))->shouldHaveBeenCalled();

        $calls = $this->snappy->findProphecyMethodCalls(
            'generateFromHtml',
            new Argument\ArgumentsWildcard(array_values($generateFromHtmlArgument))
        );

        $renderedFilePathArgumentIndex = array_flip(array_keys($generateFromHtmlArgument))['renderedFilePath'];

        $this->fileSystemHelper->writeTempFileToFileSystem(
            Argument::exact((reset($calls)->getArguments())[$renderedFilePathArgumentIndex]),
            Argument::type(FilesystemInterface::class),
            Argument::exact($serviceTermsGenerator->getFilePath($acceptationLegalDoc))
        )->shouldHaveBeenCalled();
    }

    /**
     * @covers ::getFilePath
     */
    public function testGetFilePathInvalidArgument(): void
    {
        $serviceTermsGenerator = $this->createTestObject();

        $this->expectException(InvalidArgumentException::class);

        $serviceTermsGenerator->getFilePath($this->prophesize(FileStorageInterface::class)->reveal());
    }

    /**
     * @covers ::getFilePath
     */
    public function testGetFilePathWhenRelativePathEmpty(): void
    {
        $serviceTermsGenerator = $this->createTestObject();

        $acceptationLegalDoc = $this->createAcceptationLegalDoc();
        $path                = (new ReflectionClassConstant(ServiceTermsGenerator::class, 'PATH'))->getValue();
        $filePrefix          = (new ReflectionClassConstant(ServiceTermsGenerator::class, 'FILE_PREFIX'))->getValue();

        $returnedFilePath = $serviceTermsGenerator->getFilePath($acceptationLegalDoc);

        static::assertStringContainsString(DIRECTORY_SEPARATOR, $returnedFilePath);

        [$mainDirectory, $subdirectory, $filename] = explode(DIRECTORY_SEPARATOR, $returnedFilePath);
        static::assertStringContainsString($path, $mainDirectory);
        static::assertSame((string) $acceptationLegalDoc->getClient()->getIdClient(), $subdirectory);
        static::assertStringStartsWith($filePrefix, $filename);
        static::assertStringContainsString($acceptationLegalDoc->getClient()->getHash(), $filename);
        static::assertStringContainsString((string) $acceptationLegalDoc->getLegalDoc()->getId(), $filename);
    }

    /**
     * @covers ::getFilePath
     */
    public function testGetFilePathWhenRelativePathSet(): void
    {
        $serviceTermsGenerator = $this->createTestObject();

        $acceptationLegalDoc = $this->createAcceptationLegalDoc();

        $relativeFilePath = implode(DIRECTORY_SEPARATOR, array_fill(0, Base::randomDigitNotNull(), $this->getRandomText()));
        $acceptationLegalDoc->setRelativeFilePath($relativeFilePath);

        $returnedFilePath = $serviceTermsGenerator->getFilePath($acceptationLegalDoc);

        static::assertSame($relativeFilePath, $returnedFilePath);
    }

    /**
     * @covers ::getFilesystem
     */
    public function testGetFilesystem(): void
    {
        $serviceTermsGenerator = $this->createTestObject();

        $filesystem = $serviceTermsGenerator->getFileSystem();

        static::assertSame($this->generatedDocumentFilesystem->reveal(), $filesystem);
    }

    /**
     * @param string $testedPath
     *
     * @return bool
     */
    protected function isPathTemporary(string $testedPath): bool
    {
        return 0 === mb_strpos($testedPath, $this->temporaryDirectory);
    }

    /**
     * @return ServiceTermsGenerator
     */
    protected function createTestObject(): ServiceTermsGenerator
    {
        return new ServiceTermsGenerator(
            $this->generatedDocumentFilesystem->reveal(),
            $this->fileSystemHelper->reveal(),
            $this->publicDirectory,
            $this->temporaryDirectory,
            $this->twig->reveal(),
            $this->snappy->reveal(),
            $this->managerRegistry->reveal()
        );
    }

    /**
     * @return AcceptationsLegalDocs
     */
    protected function createAcceptationLegalDoc(): AcceptationsLegalDocs
    {
        $clients = $this->prophesize(Clients::class);
        $clients->getIdClient()->willReturn(Base::randomDigitNotNull());
        $clients->getHash()->willReturn(hash('sha256', $this->getRandomText()));

        $legalDocument = $this->prophesize(LegalDocument::class);
        $legalDocument->getId()->willReturn(Base::randomDigitNotNull());
        $legalDocument->getContent()->willReturn($this->getRandomText());

        return (new AcceptationsLegalDocs())
            ->setClient($clients->reveal())
            ->setLegalDoc($legalDocument->reveal())
        ;
    }

    /**
     * @return string
     */
    protected function getRandomText(): string
    {
        return Base::asciify(str_repeat('*', Base::randomDigitNotNull()));
    }
}
