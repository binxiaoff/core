<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;

class settingsController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_CONFIGURATION);

        $this->menu_admin = 'configuration';
    }

    public function _default()
    {
        if (isset($_POST['form_add_settings'])) {
            $this->settings->type   = $_POST['type'];
            $this->settings->value  = $_POST['value'];
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
            $this->settings->update();

            $_SESSION['freeow']['title']   = 'Modification d\'un paramètre';
            $_SESSION['freeow']['message'] = 'Le paramètre a bien été modifié !';

            header('Location:' . $this->lurl . '/settings');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            $this->settings->get($this->params[1], 'id_setting');
            $this->settings->delete($this->params[1], 'id_setting');

            $_SESSION['freeow']['title']   = 'Suppression d\'un paramètre';
            $_SESSION['freeow']['message'] = 'Le paramètre a bien été supprimé';

            header('Location:' . $this->lurl . '/settings');
            die;
        }

        $this->lSettings = $this->settings->select('', 'type ASC');
    }

    public function _edit()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->url;

        $this->settings->get($this->params[0], 'id_setting');
    }

    public function _add()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->url;
    }
}
