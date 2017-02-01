<?php

class blocsController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll   = true;
        $this->menu_admin = 'edition';

        $this->users->checkAccess('edition');
    }

    public function _add()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->url;
    }

    public function _edit()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->url;

        $this->blocs = $this->loadData('blocs');
        $this->blocs->get($this->params[0], 'id_bloc');
    }

    public function _default()
    {
        $this->blocs           = $this->loadData('blocs');
        $this->blocs_templates = $this->loadData('blocs_templates');
        $this->elements        = $this->loadData('elements');

        $this->lBlocs = $this->blocs->select('', 'name ASC');

        if (isset($_POST['form_edit_bloc'])) {
            $this->blocs->get($this->params[0], 'id_bloc');
            $this->blocs->name   = $_POST['name'];
            $this->blocs->status = $_POST['status'];
            $this->blocs->update();

            $_SESSION['freeow']['title']   = 'Modification d\'un bloc';
            $_SESSION['freeow']['message'] = 'Le bloc a bien &eacute;t&eacute; modifi&eacute; !';

            header('Location: ' . $this->lurl . '/blocs');
            die;
        }

        if (isset($_POST['form_add_bloc'])) {
            $this->blocs->name    = $_POST['name'];
            $this->blocs->slug    = ($_POST['slug'] != '' ? $this->bdd->generateSlug($_POST['slug']) : $this->bdd->generateSlug($_POST['name']));
            $this->blocs->status  = $_POST['status'];
            $this->blocs->create();

            if (false === file_exists($this->path . 'apps/default/views/blocs/' . $this->blocs->slug . '.php')) {
                $modifs_elements = "";
                $modifs_elements .= "<strong>Nom du Bloc : " . $this->blocs->name . "</strong><br /><br />\r\n\r\n";

                $fp = fopen($this->path . 'apps/default/views/blocs/' . $this->blocs->slug . '.php', "wb");
                fputs($fp, $modifs_elements);
                fclose($fp);

                chmod($this->path . 'apps/default/views/blocs/' . $this->blocs->slug . '.php', 0777);
            }

            $_SESSION['freeow']['title']   = 'Ajout d\'un bloc';
            $_SESSION['freeow']['message'] = 'Le bloc a bien &eacute;t&eacute; ajout&eacute; !';

            header('Location: ' . $this->lurl . '/blocs');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] != '') {
            switch ($this->params[0]) {
                case 'status':
                    $this->blocs->get($this->params[1], 'id_bloc');
                    $this->blocs->status = ($this->params[2] == 0 ? 1 : 0);
                    $this->blocs->update();

                    $_SESSION['freeow']['title']   = 'Statut d\'un bloc';
                    $_SESSION['freeow']['message'] = 'Le statut du bloc a bien &eacute;t&eacute; modifi&eacute; !';

                    header('Location: ' . $this->lurl . '/blocs');
                    die;
                case 'delete':
                    $this->blocs->get($this->params[1],'id_bloc');

                    @unlink($this->path . 'apps/default/views/blocs/' . $this->blocs->slug . '.php');

                    $this->blocs_templates->delete($this->params[1], 'id_bloc');
                    $this->blocs_elements->delete($this->params[1], 'id_bloc');
                    $this->elements->delete($this->params[1], 'id_bloc');
                    $this->blocs->delete($this->params[1], 'id_bloc');

                    $_SESSION['freeow']['title']   = 'Suppression d\'un bloc';
                    $_SESSION['freeow']['message'] = 'Le bloc a bien &eacute;t&eacute; supprim&eacute; !';

                    header('Location: ' . $this->lurl . '/blocs');
                    die;
            }
        }
    }

    public function _editElement()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->url;

        $this->elements = $this->loadData('elements');
        $this->elements->get($this->params[0], 'id_element');

        $this->blocs = $this->loadData('blocs');
        $this->blocs->get($this->params[1], 'id_bloc');
    }

    public function _addElement()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->url;

        $this->blocs = $this->loadData('blocs');
        $this->blocs->get($this->params[0], 'id_bloc');
    }

    public function _elements()
    {
        $this->blocs    = $this->loadData('blocs');
        $this->elements = $this->loadData('elements');

        $this->blocs->get($this->params[0], 'id_bloc');

        $this->lElements = $this->elements->select('id_bloc = "' . $this->params[0] . '" AND id_bloc != 0', 'ordre ASC');

        if (isset($_POST['form_add_element'])) {
            $this->elements->id_bloc      = $_POST['id_bloc'];
            $this->elements->name         = $_POST['name'];
            $this->elements->slug         = ($_POST['slug'] != '' ? $this->bdd->generateSlug($_POST['slug']) : $this->bdd->generateSlug($_POST['name']));
            $this->elements->ordre        = $this->elements->getLastPosition($_POST['id_bloc'], 'id_bloc') + 1;
            $this->elements->type_element = $_POST['type_element'];
            $this->elements->status       = $_POST['status'];
            $this->elements->create();

            $this->elements->reordre($_POST['id_bloc'], 'id_bloc');

            $_SESSION['freeow']['title']   = 'Ajout d\'un &eacute;l&eacute;ment';
            $_SESSION['freeow']['message'] = 'L\'&eacute;l&eacute;ment a bien &eacute;t&eacute; ajout&eacute; !';

            header('Location: ' . $this->lurl . '/blocs/elements/' . $_POST['id_bloc']);
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

            header('Location: ' . $this->lurl . '/blocs/elements/' . $this->elements->id_bloc);
            die;
        }

        if (isset($this->params[1]) && $this->params[1] != '') {
            switch ($this->params[1]) {
                case 'up':
                    $this->elements->moveUp($this->params[2], $this->params[0], 'id_bloc');
                    header('Location: ' . $this->lurl . '/blocs/elements/' . $this->params[0]);
                    die;
                case 'down':
                    $this->elements->moveDown($this->params[2], $this->params[0], 'id_bloc');
                    header('Location: ' . $this->lurl . '/blocs/elements/' . $this->params[0]);
                    die;
                case 'status':
                    $this->elements->get($this->params[2], 'id_element');
                    $this->elements->status = ($this->params[3] == 0 ? 1 : 0);
                    $this->elements->update();

                    $_SESSION['freeow']['title']   = 'Statut d\'un &eacute;l&eacute;ment';
                    $_SESSION['freeow']['message'] = 'Le statut de l\'&eacute;l&eacute;ment a bien &eacute;t&eacute; modifi&eacute; !';

                    header('Location: ' . $this->lurl . '/blocs/elements/' . $this->params[0]);
                    die;
                case 'delete':
                    $this->elements->delete($this->params[2], 'id_element');
                    $this->tree_elements->delete($this->params[2], 'id_element');
                    $this->blocs_elements->delete($this->params[2], 'id_element');
                    $this->elements->reordre($this->params[0], 'id_bloc');

                    $_SESSION['freeow']['title']   = 'Suppression d\'un &eacute;l&eacute;ment';
                    $_SESSION['freeow']['message'] = 'L\'&eacute;l&eacute;ment a bien &eacute;t&eacute; supprim&eacute; !';

                    header('Location: ' . $this->lurl . '/blocs/elements/' . $this->params[0]);
                    die;
            }
        }
    }

    public function _modifier()
    {
        $this->blocs    = $this->loadData('blocs');
        $this->elements = $this->loadData('elements');

        $this->blocs->get($this->params[0], 'id_bloc');

        $this->lTree = $this->tree->listChilds(0, [], $this->language);

        if (isset($_POST['form_edit_bloc'])) {
            foreach ($this->lLangues as $key => $lng) {
                $this->blocs_elements->delete($this->blocs->id_bloc, 'id_langue = "' . $key . '" AND id_bloc');

                $this->lElements = $this->elements->select('status = 1 AND id_bloc != 0 AND id_bloc = ' . $this->blocs->id_bloc, 'ordre ASC');

                foreach ($this->lElements as $element) {
                    $this->tree->handleFormElement($this->blocs->id_bloc, $element, 'bloc', $key);
                }
            }

            $_SESSION['freeow']['title']   = 'Modification d\'un bloc';
            $_SESSION['freeow']['message'] = 'Le bloc a bien &eacute;t&eacute; modifi&eacute; !';

            header('Location: ' . $this->lurl . '/blocs');
            die;
        }
    }
}
