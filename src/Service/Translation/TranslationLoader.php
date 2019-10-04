<?php

declare(strict_types=1);

namespace Unilend\Service\Translation;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Unilend\Entity\Translations;
use Unilend\Repository\TranslationsRepository;

class TranslationLoader implements LoaderInterface
{
    public const SECTION_SEPARATOR = '.';

    /** @var TranslationsRepository */
    private $translationRepository;
    /** @var string */
    private $defaultLocale;

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
    public function load($resource, $locale, $domain = 'messages')
    {
        $catalogue = new MessageCatalogue($this->defaultLocale);

        /** @var Translations $translation */
        foreach ($this->translationRepository->findBy(['locale' => $this->defaultLocale]) as $translation) {
            $catalogue->set($translation->getSection() . self::SECTION_SEPARATOR . $translation->getName(), $translation->getTranslation(), $domain);
        }

        return $catalogue;
    }
}
