<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Unilend\Bundle\TranslationBundle\Service\TranslationManager;
use Unilend\Service\Simulator\EntityManager;

class TestimonialManager
{
    /** @var  EntityManager */
    private $entityManager;

    /** @var TranslationManager  */
    private $translationManager;

    public function __construct(EntityManager $entityManager, TranslationManager $translationManager)
    {
        $this->entityManager = $entityManager;
        $this->translationManager = $translationManager;
    }

    /**
     * @return array
     */
    public function getActiveBattenbergTestimonials()
    {
        /** @var \testimonial $testimonial */
        $testimonial             = $this->entityManager->getRepository('testimonial');
        $aActiveBattenbergPeople = $testimonial->getActiveBattenbergTestimonials();
        $aActiveBattenbergPeople = $this->addCtaElements($aActiveBattenbergPeople);

        return  $aActiveBattenbergPeople;
    }

    private function addCtaElements(array $aActiveBattenbergPeople)
    {
        foreach ($aActiveBattenbergPeople as $iKey => $aBattenbergPerson) {
            if ('emprunter' === $aBattenbergPerson['type']){
                $aActiveBattenbergPeople[$iKey]['labelCta'] = $this->translationManager->selectTranslation('site-general', 'testimonial-section-link-on-borrower');
                $aActiveBattenbergPeople[$iKey]['urlCta']   = 'projects/new'; //TODO add real URL once routes are finished
            }

            if ('preter' === $aBattenbergPerson['type']){
                $aActiveBattenbergPeople[$iKey]['labelCta'] = $this->translationManager->selectTranslation('site-general', 'testimonial-section-link-on-lender');
                $aActiveBattenbergPeople[$iKey]['urlCta']   = 'preter'; //TODO add real URL once routes are finishedd
            }
        }

        return $aActiveBattenbergPeople;
    }

    /**
     * @param string $sTypeClient
     * @return array
     */
    public function getActiveVideoHeroes($sTypeClient)
    {
        /** @var \testimonial $testimonial */
        $testimonial  = $this->entityManager->getRepository('testimonial');
        $aVideoHeroes = $testimonial->getVideoHeroes($sTypeClient);

        return $aVideoHeroes;
    }

    /**
     * @return array
     */
    public function getAllActiveTestimonials()
    {
        /** @var \testimonial $testimonial */
        $testimonial = $this->entityManager->getRepository('testimonial');
        $aActiveTestimonials = $testimonial->getActiveTestimonials();

        return  $aActiveTestimonials;
    }

}