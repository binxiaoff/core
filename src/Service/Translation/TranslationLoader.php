<?php

namespace Unilend\Service\Translation;

use Doctrine\ORM\EntityManagerInterface;
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
     * @param EntityManagerInterface $entityManager
     * @param string                 $defaultLocale
     */
    public function __construct(EntityManagerInterface $entityManager, string $defaultLocale)
    {
        $this->translationRepository = $entityManager->getRepository(Translations::class);
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
