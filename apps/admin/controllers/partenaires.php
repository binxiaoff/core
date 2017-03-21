<?php

class partenairesController extends bootstrap
{
    /** @var \partenaires */
    public $partenaires;

    /** @var \partenaires_types */
    public $partenaires_types;

    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;

        $this->users->checkAccess('configuration');

        $this->menu_admin = 'configuration';
    }

    public function _types()
    {
        $partnerType = $this->loadData('partenaires_types');

        if (isset($_POST['form_add_type'])) {
            $partnerType->nom     = $_POST['nom'];
            $partnerType->status  = $_POST['status'];
            $partnerType->create();

            $_SESSION['freeow']['title']   = 'Ajout d\'un type de campagne';
            $_SESSION['freeow']['message'] = 'Le type a bien &eacute;t&eacute; ajout&eacute; !';

            header('Location:' . $this->lurl . '/partenaires/types');
            die;
        }

        if (isset($_POST['form_edit_type'])) {
            $partnerType->get($this->params[0], 'id_type');
            $partnerType->nom    = $_POST['nom'];
            $partnerType->status = $_POST['status'];
            $partnerType->update();

            $_SESSION['freeow']['title']   = 'Modification d\'un type de campagne';
            $_SESSION['freeow']['message'] = 'Le type a bien &eacute;t&eacute; modifi&eacute; !';

            header('Location:' . $this->lurl . '/partenaires/types');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            $partnerType->delete($this->params[1], 'id_type');

            $_SESSION['freeow']['title']   = 'Suppression d\'un type de campagne';
            $_SESSION['freeow']['message'] = 'Le type a bien &eacute;t&eacute; supprim&eacute; !';

            header('Location:' . $this->lurl . '/partenaires/types');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'status') {
            $partnerType->get($this->params[1], 'id_type');
            $partnerType->status = ($this->params[2] == 1 ? 0 : 1);
            $partnerType->update();

            $_SESSION['freeow']['title']   = 'Statut d\'un type de campagne';
            $_SESSION['freeow']['message'] = 'Le statut a bien &eacute;t&eacute; modifi&eacute; !';

            header('Location:' . $this->lurl . '/partenaires/types');
            die;
        }

        $this->lTypes = $partnerType->select('', 'nom ASC');
    }

    public function _editType()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->url;

        $this->partenaires_types = $this->loadData('partenaires_types');
        $this->partenaires_types->get($this->params[0], 'id_type');
    }

    public function _addType()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->url;
    }

    public function _default()
    {
        $this->partenaires       = $this->loadData('partenaires');
        $this->partenaires_types = $this->loadData('partenaires_types');

        if (isset($_POST['form_add_part'])) {
            $this->partenaires->nom     = $_POST['nom'];
            $this->partenaires->slug    = $this->bdd->generateSlug($_POST['nom']);
            $this->partenaires->id_type = $_POST['id_type'];
            $this->partenaires->status  = $_POST['status'];
            $this->partenaires->id_user = $_SESSION['user']['id_user'];
            $this->partenaires->create();

            $_SESSION['freeow']['title']   = 'Ajout d\'une campagne';
            $_SESSION['freeow']['message'] = 'La campagne a bien &eacute;t&eacute; ajout&eacute;e !';

            header('Location:' . $this->lurl . '/partenaires');
            die;
        }

        if (isset($_POST['form_edit_part'])) {
            $this->partenaires->get($this->params[0], 'id_partenaire');
            $this->partenaires->nom     = $_POST['nom'];
            $this->partenaires->slug    = $this->bdd->generateSlug($_POST['nom']);
            $this->partenaires->id_type = $_POST['id_type'];
            $this->partenaires->status  = $_POST['status'];
            $this->partenaires->id_user = $_SESSION['user']['id_user'];
            $this->partenaires->update();

            $_SESSION['freeow']['title']   = 'Modification d\'une campagne';
            $_SESSION['freeow']['message'] = 'La campagne a bien &eacute;t&eacute; modifi&eacute;e !';

            header('Location:' . $this->lurl . '/partenaires');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            $this->partenaires->delete($this->params[1], 'id_partenaire');

            $_SESSION['freeow']['title']   = 'Suppression d\'une campagne';
            $_SESSION['freeow']['message'] = 'La campagne a bien &eacute;t&eacute; supprim&eacute;e !';

            header('Location:' . $this->lurl . '/partenaires');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'status') {
            $this->partenaires->get($this->params[1], 'id_partenaire');
            $this->partenaires->status  = ($this->params[2] == 1 ? 0 : 1);
            $this->partenaires->id_user = $_SESSION['user']['id_user'];
            $this->partenaires->update();

            $_SESSION['freeow']['title']   = 'Statut d\'une campagne';
            $_SESSION['freeow']['message'] = 'Le statut a bien &eacute;t&eacute; modifi&eacute; !';

            header('Location:' . $this->lurl . '/partenaires');
            die;
        }

        $this->lPartenaires = $this->partenaires->select('', 'nom ASC');
    }

    public function _edit()
    {
        $this->hideDecoration();

        /** @var \partenaires_types $partnerType */
        $partnerType             = $this->loadData('partenaires_types');
        $_SESSION['request_url'] = $this->url;
        $this->partenaires       = $this->loadData('partenaires');

        $this->partenaires->get($this->params[0], 'id_partenaire');
        $this->lTypes = $partnerType->select('status = 1', 'nom ASC');
    }

    public function _add()
    {
        $this->hideDecoration();

        /** @var \partenaires_types $partnerType */
        $partnerType             = $this->loadData('partenaires_types');
        $_SESSION['request_url'] = $this->url;
        $this->lTypes            = $partnerType->select('status = 1', 'nom ASC');
    }
}
