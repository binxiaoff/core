<?php

class settingsController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;
    }

    public function _default()
    {
        $this->users->checkAccess('configuration');

        $this->menu_admin = 'configuration';

        if (isset($_POST['form_add_settings'])) {
            $this->settings->type   = $_POST['type'];
            $this->settings->value  = $_POST['value'];
            $this->settings->status = $_POST['status'];
            $this->settings->create();

            $_SESSION['freeow']['title']   = 'Ajout d\'un paramètre';
            $_SESSION['freeow']['message'] = 'Le paramètre a bien été ajouté';

            header('Location:' . $this->lurl . '/settings');
            die;
        }

        if (isset($_POST['form_edit_settings'])) {
            $this->settings->get($this->params[0], 'id_setting');
            $this->settings->type   = $_POST['type'];
            $this->settings->value  = $_POST['value'];
            $this->settings->status = ($this->settings->status == 2 ? 2 : $_POST['status']);
            $this->settings->update();

            if ($this->settings->id_setting == 9) {
                $echeanciers_emprunteur = $this->loadData('echeanciers_emprunteur');
                $echeanciers_emprunteur->onMetAjourTVA($this->settings->value);
            }

            $_SESSION['freeow']['title']   = 'Modification d\'un paramètre';
            $_SESSION['freeow']['message'] = 'Le paramètre a bien été modifié !';

            header('Location:' . $this->lurl . '/settings');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            $this->settings->get($this->params[1], 'id_setting');

            if ($this->settings->status != \settings::STATUS_BLOCKED) {
                $this->settings->delete($this->params[1], 'id_setting');
            }

            $_SESSION['freeow']['title']   = 'Suppression d\'un paramètre';
            $_SESSION['freeow']['message'] = 'Le paramètre a bien été supprimé';

            header('Location:' . $this->lurl . '/settings');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'status') {
            $this->settings->get($this->params[1], 'id_setting');

            if ($this->settings->status != \settings::STATUS_BLOCKED) {
                $this->settings->status = ($this->params[2] == \settings::STATUS_ACTIVE ? \settings::STATUS_INACTIVE : \settings::STATUS_ACTIVE);
                $this->settings->update();
            }

            $_SESSION['freeow']['title']   = 'Statut d\'un paramètre';
            $_SESSION['freeow']['message'] = 'Le statut a bien été modifié';

            header('Location:' . $this->lurl . '/settings');
            die;
        }

        $this->lSettings = $this->settings->select('', 'type ASC');
    }

    public function _edit()
    {
        $this->hideDecoration();

        $this->users->checkAccess('configuration');

        $this->menu_admin = 'configuration';

        $_SESSION['request_url'] = $this->url;

        $this->settings->get($this->params[0], 'id_setting');
    }

    public function _add()
    {
        $this->hideDecoration();

        $this->users->checkAccess('configuration');

        $this->menu_admin = 'configuration';

        $_SESSION['request_url'] = $this->url;
    }
}
