<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\Service\Translation;

use Faker\Provider\Base;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Unilend\Entity\Translations;
use Unilend\Repository\TranslationsRepository;
use Unilend\Service\Translation\TranslationLoader;

/**
 * @coversDefaultClass \Unilend\Service\Translation\TranslationLoader
 *
 * @internal
 */
class TranslationLoaderTest extends TestCase
{
    /**
     * @covers ::load
     *
     * @dataProvider loadProvider
     *
     * @param array|Translations[] $translations
     */
    public function testLoad(array $translations): void
    {
        $locale = Base::lexify('???');
        $domain = Base::lexify('???');

        $translationRepository = $this->prophesize(TranslationsRepository::class);
        $translationsFindCall  = $translationRepository->findBy(Argument::exact(['locale' => $locale]));
        $translationsFindCall->willReturn($translations);

        $translationLoader = new TranslationLoader(
            $translationRepository->reveal(),
            $locale
        );

        $messageCatalog = $translationLoader->load(null, $locale, $domain);

        $translationsFindCall->shouldBeCalled();
        static::assertSame($locale, $messageCatalog->getLocale());
        if ($translations) {
            static::assertContains($domain, $messageCatalog->getDomains());
            static::assertCount(count($translations), $messageCatalog->all($domain));
        }

        foreach ($translations as $translation) {
            $translationId = $translation->getSection() . TranslationLoader::SECTION_SEPARATOR . $translation->getName();

            static::assertTrue($messageCatalog->defines($translationId, $domain));
            static::assertSame($translation->getTranslation(), $messageCatalog->get($translationId, $domain));
        }
    }

    /**
     * @return array
     */
    public function loadProvider(): array
    {
        return [
            'zero'    => [[]],
            'one'     => [[$this->createTranslation()]],
            'several' => [
                array_map(
                    [$this, 'createTranslation'],
                    Base::randomElements(range(0, 10), 5)
                ),
            ],
        ];
    }

    /**
     * @return Translations
     */
    private function createTranslation(): Translations
    {
        return (new Translations())
            ->setName(Base::lexify('????'))
            ->setSection(Base::lexify('????'))
            ->setTranslation(Base::lexify('????'))
        ;
    }

    /**
     * @return string
     */
    private function getRandomString(): string
    {
        return Base::lexify(str_repeat('?', Base::randomDigitNotNull()));
    }
}
