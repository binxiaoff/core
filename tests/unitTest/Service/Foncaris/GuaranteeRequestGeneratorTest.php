<?php

declare(strict_types=1);

namespace Unilend\Test\unitTest\Service\Foncaris;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Provider\Base;
use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemInterface;
use NumberFormatter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Unilend\Entity\Clients;
use Unilend\Entity\Companies;
use Unilend\Entity\FoncarisRequest;
use Unilend\Entity\Interfaces\FileStorageInterface;
use Unilend\Entity\Project;
use Unilend\Entity\Tranche;
use Unilend\Repository\ConstantList\FoncarisFundingTypeRepository;
use Unilend\Repository\ConstantList\FoncarisSecurityRepository;
use Unilend\Service\Foncaris\GuaranteeRequestGenerator;

/**
 * @coversDefaultClass \Unilend\Service\Foncaris\GuaranteeRequestGenerator
 *
 * @internal
 */
class GuaranteeRequestGeneratorTest extends TestCase
{
    /** @var FilesystemInterface|ObjectProphecy */
    private $generatedDocumentFilesystem;
    /** @var FoncarisFundingTypeRepository|ObjectProphecy */
    private $foncarisFundingTypeRepository;
    /** @var FoncarisSecurityRepository|ObjectProphecy */
    private $foncarisSecurityRepository;
    /** @var NumberFormatter|ObjectProphecy */
    private $currencyFormatterNoDecimal;
    /** @var NumberFormatter|ObjectProphecy */
    private $percentageFormatter;
    /** @var ManagerRegistry|ObjectProphecy */
    private $managerRegistry;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->generatedDocumentFilesystem   = $this->prophesize(FilesystemInterface::class);
        $this->foncarisFundingTypeRepository = $this->prophesize(FoncarisFundingTypeRepository::class);
        $this->foncarisSecurityRepository    = $this->prophesize(FoncarisSecurityRepository::class);
        $this->currencyFormatterNoDecimal    = $this->prophesize(NumberFormatter::class);
        $this->percentageFormatter           = $this->prophesize(NumberFormatter::class);
        $this->managerRegistry               = $this->prophesize(ManagerRegistry::class);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->managerRegistry->getManagerForClass(FoncarisRequest::class)->willReturn($entityManager);

        $this->generatedDocumentFilesystem->has(Argument::type('string'))->willReturn(false);
    }

    /**
     * @covers ::generate
     */
    public function testGenerateInvalidArgument(): void
    {
        $guaranteeRequestGenerator = $this->createTestObject();

        $this->expectException(\InvalidArgumentException::class);

        $guaranteeRequestGenerator->generate($this->prophesize(FileStorageInterface::class)->reveal());
    }

    /**
     * @dataProvider unneededGenerateDataProvider
     *
     * @param int $choice
     *
     * @throws FileExistsException
     */
    public function testUnneededGenerate(int $choice): void
    {
        $guaranteeRequestGenerator = $this->createTestObject();

        $foncarisRequest = $this->createFoncarisRequest();

        $foncarisRequest->setChoice($choice);
        $guaranteeRequestGenerator->generate($foncarisRequest);

        $this->generatedDocumentFilesystem->write(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @return array|int[][]
     */
    public function unneededGenerateDataProvider(): array
    {
        return [
            'no need'           => [FoncarisRequest::FONCARIS_GUARANTEE_NO_NEED],
            'already garenteed' => [FoncarisRequest::FONCARIS_GUARANTEE_ALREADY_GUARANTEED],
        ];
    }

    /**
     * @covers ::generate
     *
     * @throws FileExistsException
     */
    public function testExistingFile(): void
    {
        $guaranteeRequestGenerator = $this->createTestObject();

        $foncarisRequest = $this->createFoncarisRequest();

        $this->generatedDocumentFilesystem->has(Argument::type('string'))->willReturn(true);

        $guaranteeRequestGenerator->generate($foncarisRequest);

        $this->generatedDocumentFilesystem->write(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::generate
     *
     * @throws FileExistsException
     */
    public function testGenerate(): void
    {
        $guaranteeRequestGenerator = $this->createTestObject();

        $foncarisRequest = $this->createFoncarisRequest();

        $this->generatedDocumentFilesystem->write(Argument::any(), Argument::any())->willReturn(null);

        $guaranteeRequestGenerator->generate($foncarisRequest);

        $this->generatedDocumentFilesystem->write(Argument::any(), Argument::any())->shouldHaveBeenCalled();
    }

    /**
     * @covers ::getFilePath
     */
    public function testGetFilePathInvalidArgument(): void
    {
        $guaranteeRequestGenerator = $this->createTestObject();

        $this->expectException(\InvalidArgumentException::class);

        $guaranteeRequestGenerator->getFilePath($this->prophesize(FileStorageInterface::class)->reveal());
    }

    /**
     * @covers ::getFilePath
     */
    public function testGetFilePath(): void
    {
        $guaranteeRequestGenerator = $this->createTestObject();

        $foncarisRequest = $this->createFoncarisRequest();
        $path            = (new \ReflectionClassConstant(GuaranteeRequestGenerator::class, 'PATH'))->getValue();
        $filePrefix      = (new \ReflectionClassConstant(GuaranteeRequestGenerator::class, 'FILE_PREFIX'))->getValue();

        $returnedFilePath = $guaranteeRequestGenerator->getFilePath($foncarisRequest);

        static::assertStringContainsString(DIRECTORY_SEPARATOR, $returnedFilePath);

        [$mainDirectory, $subdirectory, $filename] = explode(DIRECTORY_SEPARATOR, $returnedFilePath);
        static::assertStringContainsString($path, $mainDirectory);
        static::assertStringContainsString((string) $foncarisRequest->getProject()->getId(), $subdirectory);
        static::assertStringStartsWith($filePrefix, $filename);
        static::assertStringContainsStringIgnoringCase($foncarisRequest->getProject()->getBorrowerCompany()->getName(), $filename);

        $relativeFilePath = Base::randomAscii();
        $foncarisRequest->setRelativeFilePath($relativeFilePath);
        $returnedFilePath = $guaranteeRequestGenerator->getFilePath($foncarisRequest);

        static::assertSame($relativeFilePath, $returnedFilePath);
    }

    /**
     * @return GuaranteeRequestGenerator
     */
    protected function createTestObject(): GuaranteeRequestGenerator
    {
        return new GuaranteeRequestGenerator(
            $this->generatedDocumentFilesystem->reveal(),
            $this->foncarisFundingTypeRepository->reveal(),
            $this->foncarisSecurityRepository->reveal(),
            $this->currencyFormatterNoDecimal->reveal(),
            $this->percentageFormatter->reveal(),
            $this->managerRegistry->reveal()
        );
    }

    /**
     * @return FoncarisRequest
     */
    protected function createFoncarisRequest(): FoncarisRequest
    {
        /** @var Companies|ObjectProphecy $borrowerCompany */
        $borrowerCompany = $this->prophesize(Companies::class);
        $borrowerCompany->getName()->willReturn(Base::randomLetter());
        $borrowerCompany->getSiren()->willReturn(Base::numerify(str_repeat('#', 9)));
        $borrowerCompany = $borrowerCompany->reveal();

        /** @var Project|ObjectProphecy $project */
        $project = $this->prophesize(Project::class);
        $project->getId()->willReturn(1);
        $project->getBorrowerCompany()->willReturn($borrowerCompany);
        $project->getTranches()->willReturn([new Tranche()]);
        $project->getSubmitterCompany()->willReturn($borrowerCompany);
        $project->getSubmitterClient()->willReturn(new Clients());
        $project = $project->reveal();

        $foncarisRequest = new FoncarisRequest();
        $foncarisRequest->setProject($project);

        $foncarisRequest->setChoice(FoncarisRequest::FONCARIS_GUARANTEE_NEED);

        return $foncarisRequest;
    }
}
