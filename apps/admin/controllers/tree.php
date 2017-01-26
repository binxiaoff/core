<?php

class treeController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;

        $this->users->checkAccess('edition');

        $this->menu_admin = 'edition';
    }

    public function _default()
    {
        if (isset($this->params[0]) && $this->params[0] == 'up') {
            $this->tree->moveUp($this->params[1]);

            header('Location: ' . $this->lurl . '/tree');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'down') {
            $this->tree->moveDown($this->params[1]);

            header('Location: ' . $this->lurl . '/tree');
            die;
        }

        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            $this->tree->get(['id_tree' => $this->params[1], 'id_langue' => $this->language]);
            $this->tree->deleteCascade($this->params[1]);

            $_SESSION['freeow']['title']   = 'Suppression d\'une page';
            $_SESSION['freeow']['message'] = 'La page et ses enfants ont bien été supprimés';

            header('Location: ' . $this->lurl . '/tree');
            die;
        }
    }

    public function _add()
    {
        $this->templates = $this->loadData('templates');

        if (isset($_POST['form_add_tree'])) {
            foreach ($this->lLangues as $key => $lng) {
                $this->tree->id_langue   = $key;
                $this->tree->id_parent   = $_POST['id_parent'];
                $this->tree->id_template = $_POST['id_template_' . $key];
                $this->tree->id_user     = $_SESSION['user']['id_user'];
                $this->tree->title       = trim($_POST['title_' . $key]);
                $this->tree->slug        = '';
                $this->tree->img_menu    = '';

                if ($_POST['id_parent_' . $key] != 0) {
                    if (trim($_POST['slug_' . $key]) != '') {
                        $this->tree->slug = $this->bdd->generateSlug(trim($_POST['slug_' . $key]));
                    } else {
                        $this->tree->slug = $this->bdd->generateSlug(trim($_POST['title_' . $key]));
                    }
                }

                if (isset($_FILES['img_menu_' . $key]) && $_FILES['img_menu_' . $key]['name'] != '') {
                    $this->upload->setUploadDir($this->spath, 'images/');

                    if ($this->upload->doUpload('img_menu_' . $key)) {
                        $this->tree->img_menu = $this->upload->getName();
                    }
                }

                $this->tree->menu_title       = trim($_POST['menu_title_' . $key]) != '' ? trim($_POST['menu_title_' . $key]) : $_POST['title_' . $key];
                $this->tree->meta_title       = trim($_POST['meta_title_' . $key]) != '' ? trim($_POST['meta_title_' . $key]) : $_POST['title_' . $key];
                $this->tree->meta_description = $_POST['meta_description_' . $key] != '' ? $_POST['meta_description_' . $key] : $_POST['title_' . $key];
                $this->tree->meta_keywords    = $_POST['meta_keywords_' . $key];

                if ($key == $this->dLanguage) {
                    $this->current_ordre = $this->tree->getLastPosition($_POST['id_parent_' . $key]) + 1;
                }

                $this->tree->ordre       = $this->current_ordre;
                $this->tree->status      = $_POST['status_' . $key];
                $this->tree->status_menu = $_POST['status_menu_' . $key];
                $this->tree->prive       = $_POST['prive_' . $key];
                $this->tree->indexation  = $_POST['indexation_' . $key];

                if ($key == $this->dLanguage) {
                    $this->current_id = $this->tree->getMaxId() + 1;
                }

                $this->tree->id_tree = $this->current_id;
                $this->tree->create(['id_tree' => $this->tree->id_tree, 'id_langue' => $this->tree->id_langue]);
            }

            $_SESSION['freeow']['title']   = 'Ajout d\'une page';
            $_SESSION['freeow']['message'] = 'La page a bien été enregistrée';

            header('Location: ' . $this->lurl . '/tree/edit/' . $this->tree->id_tree);
            die;
        }

        $this->lTree     = $this->tree->listChilds(0, [], $this->dLanguage);
        $this->lTemplate = $this->templates->select('status > 0 AND type = 0', 'name ASC');
    }

    public function _edit()
    {
        $this->templates    = $this->loadData('templates');
        $this->elements     = $this->loadData('elements');
        $this->redirections = $this->loadData('redirections');
        $this->controlTree  = $this->loadData('tree');

        if (isset($this->params[0]) && $this->params[0] != '') {
            if (isset($_POST['form_edit_tree'])) {
                foreach ($this->lLangues as $key => $lng) {
                    if ($this->tree->get(['id_tree' => $this->params[0], 'id_langue' => $key])) {
                        $create = false;
                    } else {
                        $create = true;
                    }

                    if ($_POST['id_parent_' . $key] == 0) {
                        $this->tree->slug = '';
                    } else {
                        if (trim($_POST['slug_' . $key]) != $this->tree->slug) {
                            if (trim($_POST['slug_' . $key]) != '') {
                                $slug_temp = $this->bdd->generateSlug(trim($_POST['slug_' . $key]));
                            } else {
                                $slug_temp = $this->bdd->generateSlug(trim($_POST['title_' . $key]));
                            }

                            if ($this->controlTree->get(['slug' => $slug_temp, 'id_langue' => $key])) {
                                $slug_temp = $slug_temp . '-' . $key . '-' . $this->params[0];
                            }

                            if ($slug_temp != $this->tree->slug) {
                                if ($this->redirections->get(['from_slug' => $this->tree->slug, 'id_langue' => $key])) {
                                    $createRedir = false;
                                } else {
                                    $createRedir = true;
                                }

                                $this->redirections->id_langue = $key;
                                $this->redirections->from_slug = $this->tree->slug;
                                $this->redirections->to_slug   = $slug_temp;
                                $this->redirections->type      = 301;
                                $this->redirections->status    = 1;

                                if ($createRedir) {
                                    $this->redirections->create(['from_slug' => $this->tree->slug, 'id_langue' => $key]);
                                } else {
                                    $this->redirections->update(['from_slug' => $this->tree->slug, 'id_langue' => $key]);
                                }
                            }

                            $this->tree->slug = $slug_temp;
                        }
                    }

                    $this->tree->id_langue   = $key;
                    $this->tree->id_parent   = $_POST['id_parent'];
                    $this->tree->id_template = $_POST['id_template_' . $key];
                    $this->tree->id_user     = $_SESSION['user']['id_user'];
                    $this->tree->title       = trim($_POST['title_' . $key]);

                    if (isset($_FILES['img_menu_' . $key]) && $_FILES['img_menu_' . $key]['name'] != '') {
                        $this->upload->setUploadDir($this->spath, 'images/');

                        if ($this->upload->doUpload('img_menu_' . $key)) {
                            $this->tree->img_menu = $this->upload->getName();
                        } else {
                            $this->tree->img_menu = $_POST['img_menu_' . $key . '-old'];
                        }
                    } else {
                        $this->tree->img_menu = $_POST['img_menu_' . $key . '-old'];
                    }

                    $this->tree->menu_title       = (trim($_POST['menu_title_' . $key]) != '' ? trim($_POST['menu_title_' . $key]) : $_POST['title_' . $key]);
                    $this->tree->meta_title       = (trim($_POST['meta_title_' . $key]) != '' ? trim($_POST['meta_title_' . $key]) : $_POST['title_' . $key]);
                    $this->tree->meta_description = ($_POST['meta_description_' . $key] != '' ? $_POST['meta_description_' . $key] : $_POST['title_' . $key]);
                    $this->tree->meta_keywords    = $_POST['meta_keywords_' . $key];
                    $this->tree->status           = $_POST['status_' . $key];
                    $this->tree->status_menu      = $_POST['status_menu_' . $key];
                    $this->tree->prive            = $_POST['prive_' . $key];
                    $this->tree->indexation       = $_POST['indexation_' . $key];

                    if ($create) {
                        $this->tree->create(['id_tree' => $this->params[0], 'id_langue' => $this->tree->id_langue]);
                    } else {
                        $this->tree->update(['id_tree' => $this->params[0], 'id_langue' => $this->tree->id_langue]);
                    }

                    if ($_POST['status_' . $key] == 0) {
                        $this->tree->statusCascade($this->params[0], $key);
                    }

                    $this->tree->get(['id_tree' => $this->params[0], 'id_langue' => $key]);

                    $this->tree_elements->delete($this->tree->id_tree, 'id_langue = "' . $key . '" AND id_tree');

                    $this->lElements = $this->elements->select('status > 0 AND id_template != 0 AND id_template = ' . $this->tree->id_template, 'ordre ASC');

                    foreach ($this->lElements as $element) {
                        $this->tree->handleFormElement($this->tree->id_tree, $element, 'tree', $key);
                    }
                }

                $_SESSION['freeow']['title']   = 'Modification d\'une page';
                $_SESSION['freeow']['message'] = 'La page a bien été modifiée';

                header('Location: ' . $this->lurl . '/tree');
                die;
            }

            $this->lTree     = $this->tree->listChilds(0, [], $this->dLanguage);
            $this->lTemplate = $this->templates->select('status > 0 AND type = 0', 'name ASC');

            $this->tree->get(['id_tree' => $this->params[0], 'id_langue' => $this->dLanguage]);
        } else {
            $_SESSION['freeow']['title']   = 'Modification d\'une page';
            $_SESSION['freeow']['message'] = 'Aucune page &agrave; modifier';

            header('Location: ' . $this->lurl . '/tree');
            die;
        }
    }
}
