<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\Twig\Loader;

use DateTimeImmutable;
use Doctrine\Common\Persistence\ObjectRepository;
use Exception;
use Faker\Provider\Base;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prediction\CallPrediction;
use Prophecy\Prediction\NoCallsPrediction;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Twig\Error\LoaderError;
use Twig\Source;
use Unilend\Entity\Interfaces\TwigTemplateInterface;
use Unilend\Twig\Loader\DatabaseLoader;

/**
 * @coversDefaultClass \Unilend\Twig\Loader\DatabaseLoader
 *
 * @internal
 */
class DatabaseLoaderTest extends TestCase
{
    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * @var ObjectRepository|ObjectProphecy
     */
    private $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->repository    = $this->prophesize(ObjectRepository::class);
        $this->defaultLocale = 'fr_FR';
    }

    /**
     * @return DatabaseLoader
     */
    public function createTestObject(): DatabaseLoader
    {
        return new DatabaseLoader(
            $this->repository->reveal(),
            $this->defaultLocale
        );
    }

    /**
     * @covers ::exists
     *
     * @dataProvider existDataProvider
     *
     * @param mixed $repositoryResult
     * @param bool  $expected
     *
     * @throws Exception
     */
    public function testExist($repositoryResult, $expected): void
    {
        $name = $this->getRandomName();

        $repositoryCall = $this->createRepositoryCall($name);

        $repositoryCall->willReturn($repositoryResult);

        $databaseTemplateLoader = $this->createTestObject();

        $result = $databaseTemplateLoader->exists($name);

        $repositoryCall->shouldHaveBeenCalled();
        static::assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function existDataProvider(): array
    {
        return [
            'exist'     => [$this->prophesize(TwigTemplateInterface::class)->reveal(), true],
            'not exist' => [null, false],
        ];
    }

    /**
     * @covers ::exists
     * @covers ::getCacheKey
     * @covers ::getSourceContext
     * @covers ::isFresh
     *
     * @dataProvider exceptionDataProvider
     *
     * @param string $methodName
     * @param array  $arguments
     */
    public function testExceptionOnMissingTemplate(string $methodName, array $arguments): void
    {
        $databaseTemplateLoader = $this->createTestObject();

        $name = $arguments['name'];

        $repositoryCall = $this->createRepositoryCall($name);

        $this->expectException(LoaderError::class);
        $this->expectExceptionMessage(sprintf('Template with name %s not found', $name));

        $repositoryCall->shouldBeCalled();

        $databaseTemplateLoader->{$methodName}(...array_values($arguments));
    }

    /**
     * @throws Exception
     *
     * @return array|mixed[]
     */
    public function exceptionDataProvider(): array
    {
        return [
            'isFresh'          => ['isFresh', ['name' => $this->getRandomName(), 'time' => (new DateTimeImmutable())->getTimestamp()]],
            'getCacheKey'      => ['getCacheKey', ['name' => $this->getRandomName()]],
            'getSourceContext' => ['getSourceContext', ['name' => $this->getRandomName()]],
        ];
    }

    /**
     * @covers ::isFresh
     *
     * @dataProvider isFreshDataProvider
     *
     * @param DateTimeImmutable|null $added
     * @param DateTimeImmutable      $updated
     * @param int                    $time
     * @param bool                   $expected
     *
     * @throws LoaderError
     * @throws Exception
     */
    public function testIsFresh(
        DateTimeImmutable $added,
        ?DateTimeImmutable $updated,
        int $time,
        bool $expected
    ): void {
        $name = $this->getRandomName();

        /** @var ObjectProphecy|TwigTemplateInterface $twigTemplate */
        $twigTemplate = $this->prophesize(TwigTemplateInterface::class);

        $twigTemplate->getUpdated()->willReturn($updated);
        $twigTemplate->getAdded()->willReturn($added);

        $repositoryCall = $this->createRepositoryCall($name)->willReturn($twigTemplate->reveal());

        $databaseLoader = $this->createTestObject();

        $result = $databaseLoader->isFresh($name, $time);

        $repositoryCall->shouldHaveBeenCalled();
        static::assertSame($expected, $result);
        $twigTemplate->getUpdated()->shouldHaveBeenCalled();
        $twigTemplate->getAdded()->should($updated ? new NoCallsPrediction() : new CallPrediction());
    }

    /**
     * @throws Exception
     */
    public function testGetCacheKey(): void
    {
        $name = $this->getRandomName();

        /** @var ObjectProphecy|TwigTemplateInterface $twigTemplate */
        $twigTemplate = $this->prophesize(TwigTemplateInterface::class);

        $twigTemplate->getName()->willReturn($name);

        $repositoryCall = $this->createRepositoryCall($name)->willReturn($twigTemplate->reveal());

        $databaseLoader = $this->createTestObject();

        $result = $databaseLoader->getCacheKey($name);

        $repositoryCall->shouldHaveBeenCalled();
        static::assertSame($name, $result);
    }

    /**
     * @covers ::getSourceContext
     *
     * @throws Exception
     */
    public function testGetSourceContext(): void
    {
        $name   = $this->getRandomName();
        $source = new Source($this->getRandomName(), $name);

        /** @var ObjectProphecy|TwigTemplateInterface $twigTemplate */
        $twigTemplate = $this->prophesize(TwigTemplateInterface::class);

        $twigTemplate->getSource()->willReturn($source);

        $repositoryCall = $this->createRepositoryCall($name)->willReturn($twigTemplate->reveal());

        $databaseLoader = $this->createTestObject();

        $result = $databaseLoader->getSourceContext($name);

        $repositoryCall->shouldHaveBeenCalled();
        static::assertSame($source, $result);
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function isFreshDataProvider(): array
    {
        $currentTime = new DateTimeImmutable();

        return [
            'updated greater than time' => [
                'added'    => new DateTimeImmutable(),
                'updated'  => new DateTimeImmutable('tomorrow'),
                'time'     => (new DateTimeImmutable('yesterday'))->getTimestamp(),
                'expected' => false,
            ],
            'updated lesser than time' => [
                'added'    => new DateTimeImmutable(),
                'updated'  => new DateTimeImmutable('yesterday'),
                'time'     => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'expected' => true,
            ],
            'updated same as time' => [
                'added'    => $currentTime,
                'updated'  => $currentTime,
                'time'     => $currentTime->getTimestamp(),
                'expected' => true,
            ],
            'added greater than time' => [
                'added'    => new DateTimeImmutable('tomorrow'),
                'updated'  => null,
                'time'     => (new DateTimeImmutable('yesterday'))->getTimestamp(),
                'expected' => false,
            ],
            'added lesser than time' => [
                'added'    => new DateTimeImmutable('yesterday'),
                'updated'  => null,
                'time'     => (new DateTimeImmutable('tomorrow'))->getTimestamp(),
                'expected' => true,
            ],
            'added same as time' => [
                'added'    => $currentTime,
                'updated'  => null,
                'time'     => $currentTime->getTimestamp(),
                'expected' => true,
            ],
        ];
    }

    /**
     * @throws Exception
     *
     * @return string
     */
    private function getRandomName(): string
    {
        return Base::lexify(str_repeat('?', random_int(1, 50)));
    }

    /**
     * @param string      $name
     * @param string|null $locale
     *
     * @return MethodProphecy|TwigTemplateInterface
     */
    private function createRepositoryCall(string $name, ?string $locale = null): MethodProphecy
    {
        return $this->repository->findOneBy(Argument::exact(['name' => $name, 'locale' => $locale ?? $this->defaultLocale]));
    }
}
