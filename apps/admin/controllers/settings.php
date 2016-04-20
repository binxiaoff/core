<?php

class settingsController extends bootstrap
{
    public function settingsController(&$command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;
    }

    public function _default()
    {
        $this->users->checkAccess('configuration');

        $this->menu_admin = 'configuration';

        $this->templates = $this->loadData('templates');

        if (isset($_POST['form_add_settings'])) {
            $this->settings->type        = $_POST['type'];
            $this->settings->value       = $_POST['value'];
            $this->settings->id_template = $_POST['id_template'];
            $this->settings->status      = $_POST['status'];
            $this->settings->id_setting  = $this->settings->create();

            $_SESSION['freeow']['title']   = 'Ajout d\'un param&egrave;tre';
            $_SESSION['freeow']['message'] = 'Le param&egrave;tre a bien &eacute;t&eacute; ajout&eacute; !';

            header('Location: ' . $this->lurl . '/settings');
            die;
        }

        if (isset($_POST['form_edit_settings'])) {
            $this->settings->get($this->params[0], 'id_setting');
            $this->settings->type        = $_POST['type'];
            $this->settings->value       = $_POST['value'];
            $this->settings->id_template = $_POST['id_template'];
            $this->settings->status      = ($this->settings->status == 2 ? 2 : $_POST['status']);
            $this->settings->update();

            // En cas de changement de tva qui passe a 20%
            if ($this->settings->id_setting == 9) {
                $echeanciers            = $this->loadData('echeanciers');
                $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');

                $echeanciers->onMetAjourTVA($this->settings->value);
                $echeanciers_emprunteur->onMetAjourTVA($this->settings->value);
            }

            // EQ-CRDS
            if ($this->settings->id_setting == 62) {
                $echeanciers = $this->loadData('echeanciers');
                $preteur     = $this->loadData('clients');
                $lender      = $this->loadData('lenders_accounts');

                foreach ($echeanciers->select() as $e) {
                    $lender->get($e['id_lender'], 'id_lender_account');
                    $preteur->get($lender->id_client_owner, 'id_client');

                    if ($preteur->type == 1) {
                        $echeanciers->get($e['id_echeancier'], 'id_echeancier');
                        $echeanciers->crds = round($e['interets'] / 100 * $this->settings->value, 2);
                        $echeanciers->update();
                    }
                }
            }

            $_SESSION['freeow']['title']   = 'Modification d\'un param&egrave;tre';
            $_SESSION['freeow']['message'] = 'Le param&egrave;tre a bien &eacute;t&eacute; modifi&eacute; !';

            header('Location: ' . $this->lurl . '/settings');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            $this->settings->get($this->params[1], 'id_setting');

            if ($this->settings->status != 2) {
                $this->settings->delete($this->params[1], 'id_setting');
            }

            $_SESSION['freeow']['title']   = 'Suppression d\'un param&egrave;tre';
            $_SESSION['freeow']['message'] = 'Le param&egrave;tre a bien &eacute;t&eacute; supprim&eacute; !';

            header('Location: ' . $this->lurl . '/settings');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'status') {
            $this->settings->get($this->params[1], 'id_setting');

            if ($this->settings->status != 2) {
                $this->settings->status = ($this->params[2] == 1 ? 0 : 1);
                $this->settings->update();
            }

            $_SESSION['freeow']['title']   = 'Statut d\'un param&egrave;tre';
            $_SESSION['freeow']['message'] = 'Le statut a bien &eacute;t&eacute; modifi&eacute; !';

            header('Location: ' . $this->lurl . '/settings');
            die;
        }

        $this->lSettings = $this->settings->select(($this->cms == 'iZinoa' ? 'cms = "iZinoa" || cms = ""' : ''), 'type ASC');
    }

    public function _edit()
    {
        $this->hideDecoration();

        $this->users->checkAccess('configuration');

        $this->menu_admin = 'configuration';

        $this->templates  = $this->loadData('templates');
        $this->lTemplates = $this->templates->select('status = 1', 'name ASC');

        $_SESSION['request_url'] = $this->url;

        $this->settings->get($this->params[0], 'id_setting');
    }

    public function _add()
    {
        $this->hideDecoration();

        $this->users->checkAccess('configuration');

        $this->menu_admin = 'configuration';

        $this->templates  = $this->loadData('templates');
        $this->lTemplates = $this->templates->select('status = 1', 'name ASC');

        $_SESSION['request_url'] = $this->url;
    }

    public function _crud()
    {
        $handle = opendir($this->path . 'data/crud/');

        while (false !== ($fichier = readdir($handle))) {
            if ($fichier != '.' && $fichier != '..') {
                unlink($this->path . 'data/crud/' . $fichier);
            }
        }

        $_SESSION['freeow']['title']   = 'Vider le CRUD';
        $_SESSION['freeow']['message'] = 'Le CRUD a bien &eacute;t&eacute; vid&eacute; !';

        header('Location: ' . $this->lurl . '/tree');
        die;
    }
}
