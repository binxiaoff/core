<?php

use \Unilend\Bundle\TranslationBundle\Service\TranslationManager;

class traductionsController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;

        // Controle d'acces � la rubrique
        $this->users->checkAccess('edition');

        // Activation du menu
        $this->menu_admin = 'edition';
    }

    public function _default()
    {
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translations');

        if (isset($_POST['form_add_traduction'])) {
            $sSection     = $this->bdd->generateSlug($_POST['section']);
            $sName        = $this->bdd->generateSlug($_POST['name']);
            $sTranslation = addslashes($_POST['translation']);

            $translationManager->addTranslation($sSection, $sName, $sTranslation);

            $_SESSION['freeow']['title']   = 'Ajout d\'une traduction';
            $_SESSION['freeow']['message'] = 'La traduction a bien &eacute;t&eacute; ajout&eacute;e !';

            header('Location:' . $this->lurl . '/traductions/' . $sSection);
            die;
        }

        if (isset($_POST['form_mod_traduction']) && isset($_POST['del_traduction']) && $_POST['del_traduction'] == 'Supprimer') {
            $translationManager->deleteTranslation($_POST['section'],$_POST['nom']);

            $_SESSION['freeow']['title']   = 'Suppression d\'une traduction';
            $_SESSION['freeow']['message'] = 'La traduction a bien &eacute;t&eacute; supprim&eacute;e !';
            header('Location:' . $this->lurl . '/traductions/' . $_POST['section']);
            die;
        }

        if (isset($_POST['form_mod_traduction']) && isset($_POST['send_traduction']) && $_POST['send_traduction'] == 'Modifier') {
            $sTranslation = addslashes($_POST['translation']);
            $translationManager->modifyTranslation($_POST['section'], $_POST['nom'], $sTranslation);

            $_SESSION['freeow']['title']   = 'Modification d\'une traduction';
            $_SESSION['freeow']['message'] = 'La traduction a bien &eacute;t&eacute; modifi&eacute;e !';
            header('Location:' . $this->lurl . '/traductions/' . $_POST['section'] . '/' . $_POST['nom']);
            die;
        }

        $this->lSections = $translationManager->selectSections('fr_FR');

        if (isset($this->params[0])) {
            $this->lNoms = $translationManager->selectNamesForSection($this->params[0]);
        }

        if (isset($this->params[1])) {
            $this->lTranslations = $translationManager->selectTranslation($this->params[0], $this->params[1]);
        }
    }

    public function _add()
    {
        $this->hideDecoration();
        $_SESSION['request_url'] = $this->url;
    }

    public function _export()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        // On place le redirect sur la home
        $_SESSION['request_url'] = $this->url;

        // Requete de l'export des traductions
        $this->requete        = 'SELECT * FROM textes ORDER BY section ASC';
        $this->requete_result = $this->bdd->query($this->requete);
    }

}