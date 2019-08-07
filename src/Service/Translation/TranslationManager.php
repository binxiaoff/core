<?php

declare(strict_types=1);

namespace Unilend\Service\Translation;

use Doctrine\ORM\EntityManagerInterface;
use Sonata\CacheBundle\Adapter\SymfonyCache;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\Translations;

class TranslationManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var TranslatorInterface */
    private $translator;
    /** @var SymfonyCache */
    private $symfonyCache;
    /** @var string */
    private $defaultLocale;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface    $translator
     * @param SymfonyCache           $symfonyCache
     * @param string                 $defaultLocale
     */
    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator, SymfonyCache $symfonyCache, string $defaultLocale)
    {
        $this->entityManager = $entityManager;
        $this->translator    = $translator;
        $this->symfonyCache  = $symfonyCache;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @param string $locale
     *
     * @return array
     */
    public function selectSections(string $locale): array
    {
        return $this->entityManager->getRepository(Translations::class)->getSections($locale);
    }

    /**
     * @param string $section
     *
     * @return array
     */
    public function selectNamesForSection(string $section): array
    {
        return $this->entityManager->getRepository(Translations::class)->getNamesForSection($section);
    }

    /**
     * @param string $section
     * @param string $name
     *
     * @return string
     */
    public function noCacheTrans(string $section, string $name): string
    {
        $translation = $this->entityManager->getRepository(Translations::class)->findOneBy(['section' => $section, 'name' => $name]);

        if (null === $translation) {
            throw new \InvalidArgumentException(sprintf('There is not translation for section %s and name %s', $section, $name));
        }

        return stripcslashes($translation->getTranslation());
    }

    /**
     * @param string $section
     * @param string $name
     * @param string $text
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addTranslation(string $section, string $name, string $text): void
    {
        $translation = new Translations();

        $translation
            ->setLocale($this->defaultLocale)
            ->setSection($section)
            ->setName($name)
            ->setTranslation($text)
        ;

        $this->entityManager->persist($translation);

        $this->entityManager->flush($translation);
    }

    /**
     * @param string $section
     * @param string $name
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteTranslation(string $section, string $name)
    {
        $translation = $this->entityManager->getRepository(Translations::class)->findOneBy(['section' => $section, 'name' => $name]);

        if (null !== $translation) {
            $this->entityManager->remove($translation);

            $this->entityManager->flush($translation);
        }
    }

    /**
     * @param string $section
     * @param string $name
     * @param string $text
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function modifyTranslation(string $section, string $name, string $text): void
    {
        $translation = $this->entityManager->getRepository(Translations::class)->findOneBy(['section' => $section, 'name' => $name]);

        if (null === $translation) {
            throw new \InvalidArgumentException(sprintf('There is not translation for section %s and name %s', $section, $name));
        }

        $translation->setTranslation($text);

        $this->entityManager->flush($translation);
    }

    /**
     * @param string      $section
     * @param string|null $locale
     *
     * @return array
     */
    public function getAllTranslationsForSection($section, $locale = null)
    {
        $translationsForSection = [];
        $translationCatalogue   = $this->translator->getCatalogue($locale);
        $allTranslation         = $translationCatalogue->all();
        $section                = $section . TranslationLoader::SECTION_SEPARATOR;
        $length                 = mb_strlen($section); // Same length as legacy

        foreach ($allTranslation as $translations) {
            foreach ($translations as $label => $translation) {
                $partOfLabel = mb_substr($label, 0, $length);
                if ($partOfLabel === $section) {
                    $translationLabelWithoutSection                          = mb_substr($label, $length);
                    $translationsForSection[$translationLabelWithoutSection] = $translation;
                }
            }
        }

        return $translationsForSection;
    }

    /**
     * Delete the translations directory in cache.
     */
    public function flush()
    {
        $this->symfonyCache->flush(['translations']);
    }
}
