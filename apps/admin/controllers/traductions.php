<?php

class traductionsController extends bootstrap
{

    public function __construct(&$command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        // Controle d'acces � la rubrique
        $this->users->checkAccess('edition');

        // Activation du menu
        $this->menu_admin = 'edition';
    }

    public function _default()
    {
        // Formulaire d'ajout d'un texte de traduction
        if (isset($_POST['form_import_traduction'])) {
            if (isset($_FILES['csv']) && $_FILES['csv']['name'] != '') {
                $this->upload->setExtValide(array('csv'));

                $this->upload->setUploadDir($this->path, 'protected/imports/');

                if ($this->upload->doUpload('csv', 'traductions')) {
                    // On purge les traductions en cours
                    $this->ln->purgeTrad();

                    // Initialisation de la 1�re ligne du csv
                    $row = 1;

                    // Ouverture du fichier en lecture seule
                    $fp = fopen($this->path . 'protected/imports/traductions.csv', 'r');

                    // Traitement du csv
                    while ($data = fgetcsv($fp, 1000, ";")) {
                        if ($row != 1) {
                            // Enregistrement des donnees
                            $this->ln->id_texte  = trim(utf8_encode($data[0]));
                            $this->ln->id_langue = trim(utf8_encode($data[1]));
                            $this->ln->section   = trim(utf8_encode($data[2]));
                            $this->ln->nom       = trim(utf8_encode($data[3]));
                            $this->ln->texte     = trim(utf8_encode($data[4]));
                            $this->ln->added     = trim(utf8_encode($data[5]));
                            $this->ln->updated   = trim(utf8_encode($data[6]));
                            $this->ln->create();
                        }
                        $row++;
                    }
                    // Mise en session du message
                    $_SESSION['freeow']['title']   = 'Import des traductions';
                    $_SESSION['freeow']['message'] = 'L\'import des traductions est termin&eacute; !';
                } else {
                    // Mise en session du message
                    $_SESSION['freeow']['title']   = 'Import des traductions';
                    $_SESSION['freeow']['message'] = $this->upload->getErrorType();
                }
            }
            // Renvoi sur la liste des traductions avec la section pr�charg�e
            header('Location:' . $this->lurl . '/traductions');
            die;
        }

        // Formulaire d'ajout d'un texte de traduction
        if (isset($_POST['form_add_traduction'])) {
            foreach ($this->lLangues as $key => $lng) {
                $this->ln->id_langue = $key;
                $this->ln->section   = $this->bdd->generateSlug($_POST['section']);
                $this->ln->nom       = $this->bdd->generateSlug($_POST['nom']);

                if ($_POST['id_langue'] == $key) {
                    $this->ln->texte = addslashes($_POST['texte']);
                } else {
                    $this->ln->texte = '';
                }
                $this->ln->create();
            }

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Ajout d\'une traduction';
            $_SESSION['freeow']['message'] = 'La traduction a bien &eacute;t&eacute; ajout&eacute;e !';

            // Renvoi sur la liste des traductions avec la section pr�charg�e
            header('Location:' . $this->lurl . '/traductions/' . $_POST['section']);
            die;
        }

        // Formulaire de modification d'un texte de traduction
        if (isset($_POST['form_mod_traduction'])) {
            // dans le cas d'un delete
            if ($_POST['form_mod_traduction'] == 1) {
                $this->ln->delete($_POST['section'], 'nom = "' . $_POST['nom'] . '" AND section');

                // Mise en session du message
                $_SESSION['freeow']['title']   = 'Suppression d\'une traduction';
                $_SESSION['freeow']['message'] = 'La traduction a bien &eacute;t&eacute; supprim&eacute;e !';

                // On renseigne les params pour les selects
                $this->params[0] = $_POST['section'];
                $this->params[1] = '';
            } else {
                foreach ($this->lLangues as $key => $lng) {
                    $values[$key] = addslashes($_POST[ 'texte-' . $key ]);
                }

                $this->ln->updateTextTranslations($_POST['section'], $_POST['nom'], $values);
                // Mise en session du message
                $_SESSION['freeow']['title']   = 'Modification d\'une traduction';
                $_SESSION['freeow']['message'] = 'La traduction a bien &eacute;t&eacute; modifi&eacute;e !';
                // On renseigne les params pour les selects
                $this->params[0] = $_POST['section'];
                $this->params[1] = $_POST['nom'];
            }
        }

        // Recuperation de la liste des sections
        $this->lSections = $this->ln->selectSections();

        // Si une section est pass�e on recupere la liste des nom
        if (isset($this->params[0]) && $this->params[0] != '') {
            $this->lNoms = $this->ln->selectTexts($this->params[0]);
        }

        // Si un nom est pass�e on recupere la liste des trads
        if (isset($this->params[1]) && $this->params[1] != '') {
            $this->lTranslations = $this->ln->selectTranslations($this->params[0], $this->params[1]);
        }
    }

    public function _add()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        // On place le redirect sur la home
        $_SESSION['request_url'] = $this->url;
    }

    public function _import()
    {
        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        // On place le redirect sur la home
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