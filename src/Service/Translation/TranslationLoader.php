<?php

declare(strict_types=1);

namespace Unilend\Service\Translation;

use Doctrine\DBAL\DBALException;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Unilend\Repository\TranslationsRepository;

class TranslationLoader implements LoaderInterface
{
    public const SECTION_SEPARATOR = '.';

    /** @var TranslationsRepository */
    private TranslationsRepository $translationRepository;
    /** @var string */
    private string $defaultLocale;

    /**
     * @param TranslationsRepository $translationRepository
     * @param string                 $defaultLocale
     */
    public function __construct(TranslationsRepository $translationRepository, string $defaultLocale)
    {
        $this->translationRepository = $translationRepository;
        $this->defaultLocale         = $defaultLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages'): MessageCatalogue
    {
        $catalogue = new MessageCatalogue($this->defaultLocale);

        try {
            foreach ($this->translationRepository->findBy(['locale' => $this->defaultLocale]) as $translation) {
                $catalogue->set(
                    $translation->getSection() . self::SECTION_SEPARATOR . $translation->getName(),
                    $translation->getTranslation(),
                    $domain
                );
            }
        } catch (DBALException $exception) {
            // May be thrown when during cache warmup when database has not been seeded
        }

        return $catalogue;
    }
}
