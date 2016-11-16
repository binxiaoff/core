<?php

namespace Unilend\Bundle\TranslationBundle\Service;

use Symfony\Component\Translation\Translator;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Symfony\Component\Finder\Finder;


class TranslationManager
{
    /** @var EntityManager  */
    private $entityManager;

    /** @var Translator  */
    private $translator;

    private $defaultLocale;
    private $cacheDirectory;

    public function __construct(EntityManager $entityManager, Translator $translator, $defaultLocale, $cacheDirectory)
    {
        $this->entityManager     = $entityManager;
        $this->translator = $translator;
        $this->defaultLocale     = $defaultLocale;
        $this->cacheDirectory    = $cacheDirectory;
    }

    /**
     * Deletes the pho file with the translation array in the cache folder.
     * and if the folder does not exist (deleted manually) it creates the folder.
     */
    public function clearLanguageCache()
    {
        try {
            foreach(Finder::create()->in($this->cacheDirectory)->files() as $file) {
                unlink($file->getRealpath());
            }
        } catch (\InvalidArgumentException $noTranslationDirectoryException) {
            mkdir($this->cacheDirectory);
        }
    }

    /**
     * @param $sLocale
     * @return array
     */
    public function selectSections($sLocale)
    {
        /** @var \translations $translations */
        $translations = $this->entityManager->getRepository('translations');
        return $translations->selectSections($sLocale);
    }

    /**
     * @param $sSection
     * @return array
     */
    public function selectNamesForSection($sSection)
    {
        /** @var \translations $translations */
        $translations = $this->entityManager->getRepository('translations');
        return $translations->selectNamesForSection($sSection);
    }

    /**
     * @param $sSection
     * @param $sName
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
     * @param $sSection
     * @param $sName
     * @param $sTranslation
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
     * @param $sSection
     * @param $sName
     */
    public function deleteTranslation($sSection, $sName)
    {
        /** @var \translations $translations */
        $translations = $this->entityManager->getRepository('translations');
        $translations->get($sName, ' section = "' . $sSection . '" AND name ');
        $translations->delete($translations->id_translation);
    }

    /**
     * @param $sSection
     * @param $sName
     * @param $sTranslation
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
     * @param      $section
     * @param null $locale
     *
     * @return array
     */
    public function getAllTranslationsForSection($section, $locale = null)
    {
        $translationCatalogue = $this->translator->getCatalogue($locale);
        $allTranslation = $translationCatalogue->all();
        $section = $section . TranslationLoader::SECTION_SEPARATOR;
        $length = strlen($section);

        $translationsForSection = [];
        foreach($allTranslation as $domain => $translations){
            foreach ($translations as $label => $translation) {
                if (substr($label, 0, $length) === $section) {
                    $translationLabelWithoutSection = substr($label, $length);
                    $translationsForSection[$translationLabelWithoutSection] = $translation;
                }
            }
        }

        return $translationsForSection;
    }
}
