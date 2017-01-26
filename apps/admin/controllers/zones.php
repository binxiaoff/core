<?php

class zonesController extends bootstrap
{
    var $Command;

    public function initialize()
    {
        parent::initialize();

        $this->catchAll   = true;

        $this->users->checkAccess('admin');
        $this->menu_admin = 'admin';
    }

    function _default()
    {
        $this->zones = $this->loadData('zones');

        if (isset($_POST['form_add_zones']))
        {
            $this->zones->name    = $_POST['name'];
            $this->zones->slug    = $_POST['slug'] != '' ? $this->bdd->generateSlug($_POST['slug']) : $this->bdd->generateSlug($_POST['name']);
            $this->zones->status  = $_POST['status'];
            $this->zones->id_zone = $this->zones->create();

            $_SESSION['freeow']['title']   = 'Ajout d\'une zone';
            $_SESSION['freeow']['message'] = 'La zone a bien &eacute;t&eacute; ajout&eacute;e !';

            header('Location: ' . $this->lurl . '/zones');
            die;
        }

        if (isset($_POST['form_mod_zones']))
        {
            $this->zones->get($this->params[0], 'id_zone');
            $this->zones->name   = $_POST['name'];
            $this->zones->status = $_POST['status'];
            $this->zones->update();

            $_SESSION['freeow']['title']   = 'Modification d\'une zone';
            $_SESSION['freeow']['message'] = 'La zone a bien &eacute;t&eacute; modifi&eacute;e !';

            header('Location: ' . $this->lurl . '/zones');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'delete')
        {
            $this->zones->delete($this->params[1], 'id_zone');
            $this->users_zones->delete($this->params[1], 'id_zone');

            $_SESSION['freeow']['title']   = 'Suppression d\'une zone';
            $_SESSION['freeow']['message'] = 'La zone a bien &eacute;t&eacute; supprim&eacute;e !';

            header('Location: ' . $this->lurl . '/zones');
            die;
        }

        $this->lUsers = $this->users->select('id_user != 1', 'name ASC');
        $this->lZones = $this->zones->select('', 'name ASC');
    }

    function _edit()
    {
        $this->hidedecoration();

        $_SESSION['request_url'] = $this->url;
        $this->zones->get($this->params[0], 'id_zone');
    }

    function _add()
    {
        $this->hidedecoration();

        $_SESSION['request_url'] = $this->url;
    }
}