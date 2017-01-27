<?php

class templatesController extends bootstrap
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

        $this->templates = $this->loadData('templates');
        $this->templates->get($this->params[0], 'id_template');
    }

    public function _default()
    {
        $this->templates       = $this->loadData('templates');
        $this->blocs_templates = $this->loadData('blocs_templates');
        $this->elements        = $this->loadData('elements');

        $this->lTemplate = $this->templates->select('type = 0', 'name ASC');

        if (isset($_POST['form_edit_template'])) {
            $this->templates->get($this->params[0], 'id_template');
            $this->templates->name   = $_POST['name'];
            $this->templates->status = $_POST['status'];
            $this->templates->type   = 0;
            $this->templates->update();

            $_SESSION['freeow']['title']   = 'Modification d\'un template';
            $_SESSION['freeow']['message'] = 'Le template a bien &eacute;t&eacute; modifi&eacute; !';

            header('Location: ' . $this->lurl . '/templates');
            die;
        }

        if (isset($_POST['form_add_template'])) {
            $this->templates->name   = $_POST['name'];
            $this->templates->slug   = ($_POST['slug'] != '' ? $this->bdd->generateSlug($_POST['slug']) : $this->bdd->generateSlug($_POST['name']));
            $this->templates->status = $_POST['status'];
            $this->templates->type   = 0;
            $this->templates->create();

            if (false === file_exists($this->path . 'apps/default/views/templates/' . $this->templates->slug . '.php')) {
                // Creation de la vue
                $modifs_elements = "";
                $modifs_elements .= "<strong>Nom du Template : " . $this->templates->name . "</strong><br /><br />\r\n\r\n";

                $fp = fopen($this->path . 'apps/default/views/templates/' . $this->templates->slug . '.php', "wb");
                fputs($fp, $modifs_elements);
                fclose($fp);

                chmod($this->path . 'apps/default/views/templates/' . $this->templates->slug . '.php', 0777);

                $modifs_elements = "";
                $modifs_elements .= "<?php\r\n";

                $fp = fopen($this->path . 'apps/default/controllers/templates/' . $this->templates->slug . '.php', "wb");
                fputs($fp, $modifs_elements);
                fclose($fp);

                chmod($this->path . 'apps/default/controllers/templates/' . $this->templates->slug . '.php', 0777);
            }

            $_SESSION['freeow']['title']   = 'Ajout d\'un template';
            $_SESSION['freeow']['message'] = 'Le template a bien &eacute;t&eacute; ajout&eacute; !';

            header('Location: ' . $this->lurl . '/templates/elements/' . $this->templates->id_template);
            die;
        }

        if (isset($this->params[0]) && $this->params[0] != '') {
            switch ($this->params[0]) {
                case 'status':
                    $this->templates->get($this->params[1], 'id_template');
                    $this->templates->status = ($this->params[2] == 0 ? 1 : 0);
                    $this->templates->update();

                    $_SESSION['freeow']['title']   = 'Statut d\'un template';
                    $_SESSION['freeow']['message'] = 'Le statut du template a bien &eacute;t&eacute; modifi&eacute; !';

                    header('Location: ' . $this->lurl . '/templates');
                    die;
                case 'affichage':
                    $this->templates->get($this->params[1], 'id_template');
                    $this->templates->affichage = ($this->params[2] == 0 ? 1 : 0);
                    $this->templates->update();

                    // Mise en session du message
                    $_SESSION['freeow']['title']   = 'Affichage d\'un template';
                    $_SESSION['freeow']['message'] = 'L\'affichage du template a bien &eacute;t&eacute; modifi&eacute; !';

                    header('Location: ' . $this->lurl . '/templates');
                    die;
                case 'delete':
                    $this->templates->get($this->params[1], 'id_template');

                    @unlink($this->path . 'apps/default/views/templates/' . $this->templates->slug . '.php');
                    @unlink($this->path . 'apps/default/controllers/templates/' . $this->templates->slug . '.php');

                    $this->blocs_templates->delete($this->params[1], 'id_template');
                    $this->elements->delete($this->params[1], 'id_template');
                    $this->templates->delete($this->params[1], 'id_template');
                    $this->tree->deleteTemplate($this->params[1]);

                    $_SESSION['freeow']['title']   = 'Suppression d\'un template';
                    $_SESSION['freeow']['message'] = 'Le template a bien &eacute;t&eacute; supprim&eacute; !';

                    header('Location: ' . $this->lurl . '/templates');
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
    }

    public function _addElement()
    {
        $this->hideDecoration();

        $_SESSION['request_url'] = $this->url;

        $this->templates = $this->loadData('templates');
        $this->templates->get($this->params[0], 'id_template');
    }

    public function _addBloc()
    {
        $this->hideDecoration();

        $this->blocs     = $this->loadData('blocs');
        $this->templates = $this->loadData('templates');

        $_SESSION['request_url'] = $this->url;

        $this->lBlocsOnline = $this->blocs->select('status = 1', 'name ASC');

        $this->templates->get($this->params[0], 'id_template');
    }

    public function _elements()
    {
        $this->templates       = $this->loadData('templates');
        $this->elements        = $this->loadData('elements');
        $this->blocs           = $this->loadData('blocs');
        $this->blocs_templates = $this->loadData('blocs_templates');

        $this->templates->get($this->params[0], 'id_template');

        $this->lElements  = $this->elements->select('id_template = "' . $this->params[0] . '" AND id_template != 0', 'ordre ASC');
        $this->lPositions = $this->bdd->getEnum('blocs_templates', 'position');

        if (isset($_POST['form_add_bloc'])) {
            $this->blocs_templates->id_bloc     = $_POST['id_bloc'];
            $this->blocs_templates->id_template = $_POST['id_template'];
            $this->blocs_templates->position    = $_POST['position'];
            $this->blocs_templates->ordre       = $this->blocs_templates->getLastPosition($this->blocs_templates->position, $_POST['id_template']);
            $this->blocs_templates->status      = $_POST['status'];
            $this->blocs_templates->create();

            $this->blocs_templates->reordre($_POST['id_template'], $this->blocs_templates->position);

            $_SESSION['freeow']['title']   = 'Ajout d\'un bloc';
            $_SESSION['freeow']['message'] = 'Le bloc a bien &eacute;t&eacute; ajout&eacute; !';

            header('Location: ' . $this->lurl . '/templates/elements/' . $_POST['id_template']);
            die;
        }

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

            header('Location: ' . $this->lurl . '/templates/elements/' . $_POST['id_template']);
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

            header('Location: ' . $this->lurl . '/templates/elements/' . $this->elements->id_template);
            die;
        }

        if (isset($this->params[1]) && $this->params[1] != '') {
            switch ($this->params[1]) {
                case 'up':
                    $this->elements->moveUp($this->params[2], $this->params[0], 'id_template');
                    header('Location: ' . $this->lurl . '/templates/elements/' . $this->params[0]);
                    die;
                case 'down':
                    $this->elements->moveDown($this->params[2], $this->params[0], 'id_template');
                    header('Location: ' . $this->lurl . '/templates/elements/' . $this->params[0]);
                    die;
                case 'status':
                    $this->elements->get($this->params[2], 'id_element');
                    $this->elements->status = ($this->params[3] == 0 ? 1 : 0);
                    $this->elements->update();

                    $_SESSION['freeow']['title']   = 'Statut d\'un &eacute;l&eacute;ment';
                    $_SESSION['freeow']['message'] = 'Le statut de l\'&eacute;l&eacute;ment a bien &eacute;t&eacute; modifi&eacute; !';

                    header('Location: ' . $this->lurl . '/templates/elements/' . $this->params[0]);
                    die;
                case 'delete':
                    $this->elements->delete($this->params[2], 'id_element');
                    $this->tree_elements->delete($this->params[2], 'id_element');
                    $this->blocs_elements->delete($this->params[2], 'id_element');
                    $this->elements->reordre($this->params[0], 'id_template');

                    $_SESSION['freeow']['title']   = 'Suppression d\'un &eacute;l&eacute;ment';
                    $_SESSION['freeow']['message'] = 'L\'&eacute;l&eacute;ment a bien &eacute;t&eacute; supprim&eacute; !';

                    header('Location: ' . $this->lurl . '/templates/elements/' . $this->params[0]);
                    die;
                case 'upBloc':
                    $this->blocs_templates->moveUp($this->params[3], $this->params[0], $this->params[2]);
                    header('Location: ' . $this->lurl . '/templates/elements/' . $this->params[0]);
                    die;
                case 'downBloc':
                    $this->blocs_templates->moveDown($this->params[3], $this->params[0], $this->params[2]);
                    header('Location: ' . $this->lurl . '/templates/elements/' . $this->params[0]);
                    die;
                case 'statusBloc':
                    $this->blocs_templates->get($this->params[2]);
                    $this->blocs_templates->status = ($this->params[3] == 0 ? 1 : 0);
                    $this->blocs_templates->update();

                    $_SESSION['freeow']['title']   = 'Statut d\'un bloc';
                    $_SESSION['freeow']['message'] = 'Le statut du bloc a bien &eacute;t&eacute; modifi&eacute; !';

                    header('Location: ' . $this->lurl . '/templates/elements/' . $this->params[0]);
                    die;
                case 'deleteBloc':
                    $this->blocs_templates->delete($this->params[2]);

                    $_SESSION['freeow']['title']   = 'Suppression d\'un bloc';
                    $_SESSION['freeow']['message'] = 'Le bloc a bien &eacute;t&eacute; supprim&eacute; !';

                    header('Location: ' . $this->lurl . '/templates/elements/' . $this->params[0]);
                    die;
            }
        }
    }
}
