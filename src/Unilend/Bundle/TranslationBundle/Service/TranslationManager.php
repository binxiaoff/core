<?php

namespace Unilend\Bundle\TranslationBundle\Service;

use Unilend\Bundle\TranslationBundle\Service\TranslationLoader;
use Unilend\Service\Simulator\EntityManager;


class TranslationManager
{
    /** @var EntityManager  */
    private $entityManager;

    /** @var TranslationLoader  */
    private $translationLoader;

    public function __construct(EntityManager $entityManager, TranslationLoader $translationLoader, $defaultLanguage, $rootDirectory)
    {
        $this->entityManager     = $entityManager;
        $this->translationLoader = $translationLoader;
        $this->defaultLanguage   = $defaultLanguage;
        $this->rootDirectory     = $rootDirectory;
    }

    public function clearLanguageCache()
    {
        $cacheDir = $this->rootDirectory . '/../var/cache';
        $finder   = new \Symfony\Component\Finder\Finder();

        $finder->in(array($cacheDir . '/dev/translations', $cacheDir . '/prod/translations'))->files();

        foreach($finder as $file) {
            unlink($file->getRealpath());
        }
    }


    public function selectSections($sLanguage)
    {
        /** @var \translations $translations */
        $translations = $this->entityManager->getRepository('translations');
        return $translations->selectSections($sLanguage);
    }

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
    public function selectTranslation($sSection, $sName)
    {
        /** @var \translations $translations */
        $translations = $this->entityManager->getRepository('translations');
        $sTranslation = $translations->selectTranslation($sSection, $sName);

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
        $translations->lang        = $this->defaultLanguage;
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
     * @param string $sSection
     * @param string|null $sLanguage
     * @return array
     */
    public function getAllTranslationsForSection($sSection, $sLanguage = null)
    {
        if (is_null($sLanguage)) {
            $sLanguage = $this->defaultLanguage;
        }

        /** @var \translations $translations */
        $translations            = $this->entityManager->getRepository('translations');
        $aTranslationsForSection = $translations->getAllTranslationsForSection($sSection, $sLanguage);
        $aTranslations           = array();

        foreach($aTranslationsForSection as $key => $translation){
            $aTranslations[$translation['name']] = $translation['translation'];
        }

        return $aTranslations;
    }

}
