<?php

use Unilend\Entity\Zones;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;

class traductionsController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_EDITION);

        $this->menu_admin = 'edition';
    }

    public function _default()
    {
        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');

        if (isset($_POST['form_add_traduction'])) {
            $sSection     = $this->bdd->generateSlug($_POST['section']);
            $sName        = $this->bdd->generateSlug($_POST['name']);
            $sTranslation = $_POST['translation'];

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
            $sTranslation = $_POST['translation'];
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
            $this->section     = $this->params[0];
            $this->nom         = $this->params[1];
            $this->translation = $translationManager->noCacheTrans($this->params[0], $this->params[1]);
        }
    }

    public function _add()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->url;
    }

    public function _export()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $_SESSION['request_url'] = $this->url;

        /** @var \Unilend\Repository\TranslationsRepository $translationRepository */
        $translationRepository = $this->get('doctrine.orm.entity_manager')->getRepository(Translations::class);
        $locale                = $this->getParameter('kernel.default_locale');
        $allTranslations       = $translationRepository->findBy(['locale' => $locale]);

        header("Pragma: no-cache");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public");
        header("Expires: 0");
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=traductions.csv");

        $handle = fopen('php://output', 'w+');
        fputs($handle, "\xEF\xBB\xBF"); // add UTF-8 BOM in order to be compatible to Excel
        fputcsv($handle, ['Id_translation', 'locale', 'Section', 'Nom', 'Traduction', 'Date d\'ajout', 'Date de mise à jour'], ';');

        /** @var \Unilend\Entity\Translations$translation */
        foreach ($allTranslations as $translation) {
            $singleTranslation = [
                $translation->getIdTranslation(),
                $translation->getLocale(),
                $translation->getSection(),
                $translation->getName(),
                $translation->getTranslation(),
                $translation->getAdded()->format('d-m-Y'),
                $translation->getUpdated() ? $translation->getUpdated()->format('d-m-Y') : ''
            ];
            fputcsv($handle, $singleTranslation, ';');
        }

        fclose($handle);
    }

    public function _regenerateTranslationCache()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var TranslationManager $translationManager */
        $translationManager = $this->get('unilend.service.translation_manager');
        $translationManager->flush();

        header('Location: ' . $this->lurl . '/traductions');
        die;
    }
}
