<?php

namespace Unilend\Service;

use Unilend\Bundle\TranslationBundle\Service\TranslationManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class CompanyManager
{
    /** @var EntityManager  */
    private $entityManager;

    /** @var TranslationManager */
    private $translationManager;

    public function __construct(EntityManager $entityManager, TranslationManager $translationManager)
    {
        $this->entityManager      = $entityManager;
        $this->translationManager = $translationManager;
    }

    /**
     * @param string|null $sLanguage
     * @return array
     */
    public function getTranslatedCompanySectorList($sLanguage = null)
    {
        /** @var \company_sector $companySector */
        $companySector       = $this->entityManager->getRepository('company_sector');
        $aSectorTranslations = $this->translationManager->getAllTranslationsForSection('company-sector', $sLanguage);
        $aCompanySectors     = $companySector->select();
        $aTranslatedSectors  = array();

        foreach ($aCompanySectors as $aSector) {
            $aTranslatedSectors[$aSector['id_company_sector']] = $aSectorTranslations['sector-' . $aSector['id_company_sector']];
        }

        return $aTranslatedSectors;
    }

}