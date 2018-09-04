<?php

namespace Unilend\Bundle\TranslationBundle\Service;

use Doctrine\ORM\EntityManager;
use Sonata\CacheBundle\Adapter\SymfonyCache;
use Symfony\Component\Translation\Translator;
use Unilend\Bundle\CoreBusinessBundle\Entity\Translations;

class TranslationManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var Translator */
    private $translator;
    /** @var SymfonyCache */
    private $symfonyCache;
    /** @var string */
    private $defaultLocale;

    /**
     * @param EntityManager $entityManager
     * @param Translator    $translator
     * @param SymfonyCache  $symfonyCache
     * @param string        $defaultLocale
     */
    public function __construct(EntityManager $entityManager, Translator $translator, SymfonyCache $symfonyCache, string $defaultLocale)
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
        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:Translations')->getSections($locale);
    }

    /**
     * @param string $section
     *
     * @return array
     */
    public function selectNamesForSection(string $section): array
    {
        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:Translations')->getNamesForSection($section);
    }

    /**
     * @param string $section
     * @param string $name
     *
     * @return string
     */
    public function noCacheTrans(string $section, string $name): string
    {
        $translation = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Translations')->findOneBy(['section' => $section, 'name' => $name]);

        if (null === $translation) {
            throw new \InvalidArgumentException('There is not translation for section ' . $section . ' and name ' . $name);
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
            ->setTranslation($text);

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
        $translation = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Translations')->findOneBy(['section' => $section, 'name' => $name]);

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
        $translation = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Translations')->findOneBy(['section' => $section, 'name' => $name]);

        if (null === $translation) {
            throw new \InvalidArgumentException('There is not translation for section ' . $section . ' and name ' . $name);
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
        $length                 = strlen($section);

        foreach ($allTranslation as $domain => $translations) {
            foreach ($translations as $label => $translation) {
                if (substr($label, 0, $length) === $section) {
                    $translationLabelWithoutSection                          = substr($label, $length);
                    $translationsForSection[$translationLabelWithoutSection] = $translation;
                }
            }
        }

        return $translationsForSection;
    }

    public function flush()
    {
        $this->symfonyCache->flush(['translations']);
    }
}
