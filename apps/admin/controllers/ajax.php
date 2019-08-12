<?php

use Unilend\Entity\Zones;
use Unilend\Service\Translation\TranslationManager;

class ajaxController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $_SESSION['request_url'] = $this->url;

        $this->autoFireView = false;
        $this->hideDecoration();
    }

    // Fonction AJAX delete image ELEMENT
    public function _deleteImageElement()
    {
        if (isset($this->params[0]) && '' != $this->params[0]) {
            $this->tree_elements->get($this->params[0], 'id');

            @unlink($this->spath . 'images/' . $this->tree_elements->value);

            $this->tree_elements->value      = '';
            $this->tree_elements->complement = '';
            $this->tree_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    // Fonction AJAX delete fichier ELEMENT
    public function _deleteFichierElement()
    {
        if (isset($this->params[0]) && '' != $this->params[0]) {
            $this->tree_elements->get($this->params[0], 'id');

            @unlink($this->spath . 'fichiers/' . $this->tree_elements->value);

            $this->tree_elements->value      = '';
            $this->tree_elements->complement = '';
            $this->tree_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    // Fonction AJAX delete image ELEMENT BLOC
    public function _deleteImageElementBloc()
    {
        if (isset($this->params[0]) && '' != $this->params[0]) {
            $this->blocs_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'images/' . $this->blocs_elements->value);

            // On supprime le fichier de la base
            $this->blocs_elements->value      = '';
            $this->blocs_elements->complement = '';
            $this->blocs_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    // Fonction AJAX delete fichier ELEMENT BLOC
    public function _deleteFichierElementBloc()
    {
        if (isset($this->params[0]) && '' != $this->params[0]) {
            $this->blocs_elements->get($this->params[0], 'id');

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'fichiers/' . $this->blocs_elements->value);

            // On supprime le fichier de la base
            $this->blocs_elements->value      = '';
            $this->blocs_elements->complement = '';
            $this->blocs_elements->update();

            echo '<td>&nbsp;</td>';
        }
    }

    // Fonction AJAX delete image TREE
    public function _deleteImageTree()
    {
        if (isset($this->params[0]) && '' != $this->params[0]) {
            $this->tree->get($this->params[0]);

            // On supprime le fichier sur le serveur
            @unlink($this->spath . 'images/' . $this->tree->img_menu);

            // On supprime le fichier de la base
            $this->tree->img_menu = '';
            $this->tree->update();
        }
    }

    // Fonction AJAX chargement des noms de la section de traduction
    public function _loadNomTexte()
    {
        $this->autoFireView = true;
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        if (isset($this->params[0]) && '' != $this->params[0]) {
            $this->lNoms = $translationManager->selectNamesForSection($this->params[0]);
        }
    }

    // Fonction AJAX chargement des traductions de la section de traduction
    public function _loadTradTexte()
    {
        $this->autoFireView = true;
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        if (isset($this->params[0]) && '' != $this->params[0]) {
            $this->section     = $this->params[1];
            $this->nom         = $this->params[0];
            $this->translation = $translationManager->noCacheTrans($this->params[1], $this->params[0]);
        }

        $this->setView('../traductions/edit');
    }
}
