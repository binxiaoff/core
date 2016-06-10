<?php

namespace Unilend\Bundle\TranslationBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Symfony\Component\Finder\Finder;


class TranslationManager
{
    /** @var EntityManager  */
    private $entityManager;

    /** @var TranslationLoader  */
    private $translationLoader;

    private $defaultLocale;
    private $cacheDirectory;

    public function __construct(EntityManager $entityManager, TranslationLoader $translationLoader, $defaultLocale, $cacheDirectory)
    {
        $this->entityManager     = $entityManager;
        $this->translationLoader = $translationLoader;
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
     * @param string $sSection
     * @param string|null $sLocale
     * @return array
     */
    public function getAllTranslationsForSection($sSection, $sLocale = null)
    {
        if (is_null($sLocale)) {
            $sLocale = $this->defaultLocale;
        }

        /** @var \translations $translations */
        $translations            = $this->entityManager->getRepository('translations');
        $aTranslationsForSection = $translations->getAllTranslationsForSection($sSection, $sLocale);
        $aTranslations           = array();

        foreach($aTranslationsForSection as $key => $translation){
            $aTranslations[$translation['name']] = $translation['translation'];
        }

        return $aTranslations;
    }

    /**
     * @param string|null $sLocale
     * @return array
     */
    public function getTranslatedCompanySectorList($sLocale = null)
    {
        if (is_null($sLocale)) {
            $sLocale = $this->defaultLocale;
        }

        /** @var \company_sector $companySector */
        $companySector       = $this->entityManager->getRepository('company_sector');
        $aSectorTranslations = $this->getAllTranslationsForSection('company-sector', $sLocale);
        $aCompanySectors     = $companySector->select();
        $aTranslatedSectors  = array();

        foreach ($aCompanySectors as $aSector) {
            $aTranslatedSectors[$aSector['id_company_sector']] = $aSectorTranslations['sector-' . $aSector['id_company_sector']];
        }

        return $aTranslatedSectors;
    }

    /**
     * @param string|null $sLocale
     * @return array
     */
    public function getTranslatedLoanMotiveList($sLocale = null)
    {
        if (is_null($sLocale)) {
            $sLocale = $this->defaultLocale;
        }

        /** @var \loan_motive $loanMotive */
        $loanMotive          = $this->entityManager->getRepository('loan_motive');
        $aMotiveTranslations = $this->getAllTranslationsForSection('loan-motive', $sLocale);
        $aLoanMotives        = $loanMotive->select();
        $aTranslatedMotives  = array();

        foreach ($aLoanMotives  as $aMotive) {
            $aTranslatedMotives[$aMotive['id_motive']] = $aMotiveTranslations['motive-' . $aMotive['id_motive']];
        }

        return $aTranslatedMotives;
    }
}
