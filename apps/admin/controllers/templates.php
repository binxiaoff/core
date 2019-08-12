<?php

use Unilend\Entity\Zones;

class templatesController extends bootstrap
{
    /** @var \templates */
    public $templates;

    public function initialize()
    {
        parent::initialize();

        $this->menu_admin = 'edition';
        $this->templates  = $this->loadData('templates');
    }

    public function _default()
    {
        $this->elements  = $this->loadData('elements');
        $this->lTemplate = $this->templates->select('', 'name ASC');
    }

    public function _editElement()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->url;

        $this->elements = $this->loadData('elements');
        $this->elements->get($this->params[0], 'id_element');
    }

    public function _addElement()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->url;

        $this->templates->get($this->params[0], 'id_template');
    }

    public function _elements()
    {
        $this->templates->get($this->params[0], 'id_template');

        $this->elements  = $this->loadData('elements');
        $this->blocs     = $this->loadData('blocs');
        $this->lElements = $this->elements->select('id_template = "' . $this->params[0] . '" AND id_template != 0', 'ordre ASC');

        if (isset($_POST['form_add_element'])) {
            $this->elements->id_template  = $_POST['id_template'];
            $this->elements->name         = $_POST['name'];
            $this->elements->slug         = ($_POST['slug'] != '' ? $this->bdd->generateSlug($_POST['slug']) : $this->bdd->generateSlug($_POST['name']));
            $this->elements->ordre        = $this->elements->getLastPosition($_POST['id_template'], 'id_template');
            $this->elements->type_element = $_POST['type_element'];
            $this->elements->status       = $_POST['status'];
            $this->elements->create();

            $this->elements->reordre($_POST['id_template'], 'id_template');

            $_SESSION['freeow']['title']   = 'Ajout d\'un &eacute;l&eacute;ment';
            $_SESSION['freeow']['message'] = 'L\'&eacute;l&eacute;ment a bien &eacute;t&eacute; ajout&eacute; !';

            header('Location: ' . $this->url . '/templates/elements/' . $_POST['id_template']);
            die;
        }

        if (isset($_POST['form_edit_element'])) {
            $this->elements->get($_POST['id_element'], 'id_element');
            $this->elements->name         = $_POST['name'];
            $this->elements->type_element = $_POST['type_element'];
            $this->elements->status       = $_POST['status'];
            $this->elements->update();

            $_SESSION['freeow']['title']   = 'Modification d\'un &eacute;l&eacute;ment';
            $_SESSION['freeow']['message'] = 'L\'&eacute;l&eacute;ment a bien &eacute;t&eacute; modifi&eacute; !';

            header('Location: ' . $this->url . '/templates/elements/' . $this->elements->id_template);
            die;
        }

        if (isset($this->params[1]) && $this->params[1] != '') {
            switch ($this->params[1]) {
                case 'up':
                    $this->elements->moveUp($this->params[2], $this->params[0], 'id_template');
                    header('Location: ' . $this->url . '/templates/elements/' . $this->params[0]);
                    die;
                case 'down':
                    $this->elements->moveDown($this->params[2], $this->params[0], 'id_template');
                    header('Location: ' . $this->url . '/templates/elements/' . $this->params[0]);
                    die;
                case 'status':
                    $this->elements->get($this->params[2], 'id_element');
                    $this->elements->status = ($this->params[3] == 0 ? 1 : 0);
                    $this->elements->update();

                    $_SESSION['freeow']['title']   = 'Statut d\'un &eacute;l&eacute;ment';
                    $_SESSION['freeow']['message'] = 'Le statut de l\'&eacute;l&eacute;ment a bien &eacute;t&eacute; modifi&eacute; !';

                    header('Location: ' . $this->url . '/templates/elements/' . $this->params[0]);
                    die;
                case 'delete':
                    $this->elements->delete($this->params[2], 'id_element');
                    $this->tree_elements->delete($this->params[2], 'id_element');
                    $this->blocs_elements->delete($this->params[2], 'id_element');
                    $this->elements->reordre($this->params[0], 'id_template');

                    $_SESSION['freeow']['title']   = 'Suppression d\'un &eacute;l&eacute;ment';
                    $_SESSION['freeow']['message'] = 'L\'&eacute;l&eacute;ment a bien &eacute;t&eacute; supprim&eacute; !';

                    header('Location: ' . $this->url . '/templates/elements/' . $this->params[0]);
                    die;
            }
        }
    }
}
