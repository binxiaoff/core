<?php

namespace Unilend\Bundle\TranslationBundle\Service;

use Sonata\CacheBundle\Adapter\SymfonyCache;
use Symfony\Component\Translation\Translator;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

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
    /** @var string */
    private $cacheDirectory;

    /**
     * @param EntityManager $entityManager
     * @param Translator    $translator
     * @param SymfonyCache  $symfonyCache
     * @param string        $defaultLocale
     * @param string        $cacheDirectory
     */
    public function __construct(
        EntityManager $entityManager,
        Translator $translator,
        SymfonyCache $symfonyCache,
        $defaultLocale,
        $cacheDirectory
    )
    {
        $this->entityManager  = $entityManager;
        $this->translator     = $translator;
        $this->symfonyCache   = $symfonyCache;
        $this->defaultLocale  = $defaultLocale;
        $this->cacheDirectory = $cacheDirectory;
    }

    /**
     * @param string $sLocale
     *
     * @return array
     */
    public function selectSections($sLocale)
    {
        /** @var \translations $translations */
        $translations = $this->entityManager->getRepository('translations');
        return $translations->selectSections($sLocale);
    }

    /**
     * @param string $sSection
     *
     * @return array
     */
    public function selectNamesForSection($sSection)
    {
        /** @var \translations $translations */
        $translations = $this->entityManager->getRepository('translations');
        return $translations->selectNamesForSection($sSection);
    }

    /**
     * @param string $sSection
     * @param string $sName
     *
     * @return bool|string
     */
    public function noCacheTrans($sSection, $sName)
    {
        /** @var \translations $translations */
        $translations = $this->entityManager->getRepository('translations');
        $sTranslation = $translations->getTranslation($sSection, $sName);

        return stripcslashes($sTranslation);
    }

    /**
     * @param string $sSection
     * @param string $sName
     * @param string $sTranslation
     */
    public function addTranslation($sSection, $sName, $sTranslation)
    {
        /** @var \translations $translations */
        $translations              = $this->entityManager->getRepository('translations');
        $translations->locale      = $this->defaultLocale;
        $translations->section     = $sSection;
        $translations->name        = $sName;
        $translations->translation = $sTranslation;
        $translations->create();
    }

    /**
     * @param string $sSection
     * @param string $sName
     */
    public function deleteTranslation($sSection, $sName)
    {
        /** @var \translations $translations */
        $translations = $this->entityManager->getRepository('translations');
        $translations->get($sName, ' section = "' . $sSection . '" AND name ');
        $translations->delete($translations->id_translation);
    }

    /**
     * @param string $sSection
     * @param string $sName
     * @param string $sTranslation
     */
    public function modifyTranslation($sSection, $sName, $sTranslation)
    {
        /** @var \translations $translations */
        $translations = $this->entityManager->getRepository('translations');
        $translations->get($sName, ' section = "' . $sSection . '" AND name ');
        $translations->translation = $sTranslation;
        $translations->update();
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
                    $translationLabelWithoutSection = substr($label, $length);
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
