<?php

use Unilend\Service\Translation\TranslationManager;

class ajaxController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $_SESSION['request_url'] = $this->url;

        $this->hideDecoration();
    }

    /**
     * Fonction AJAX chargement des noms de la section de traduction
     */
    public function _loadNomTexte()
    {
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        if (false === empty($this->params[0])) {
            $this->lNoms = $translationManager->selectNamesForSection($this->params[0]);
        }

        $this->setView('../traductions/list');
    }

    /**
     * Fonction AJAX chargement des traductions de la section de traduction
     */
    public function _loadTradTexte()
    {
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        if (false === empty($this->params[0])) {
            $this->section     = $this->params[1];
            $this->nom         = $this->params[0];
            $this->translation = $translationManager->noCacheTrans($this->params[1], $this->params[0]);
        }

        $this->setView('../traductions/edit');
    }
}
