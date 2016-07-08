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
        // On remonte la page dans l'arborescence
        if (isset($this->params[0]) && $this->params[0] == 'up') {
            $this->tree->moveUp($this->params[1]);

            // on v�rifie si la page �diter est dans l'arbo du preteur,emprunteur ou izinoa
            $this->tree->get(['id_tree' => $this->params[1], 'id_langue' => $this->language]);

            switch ($this->tree->arbo) {
                case 1:
                    // si arbo du test
                    header('Location:' . $this->lurl . '/preteur');
                    die;
                    break;
                case 2:
                    // si arbo des questions
                    header('Location:' . $this->lurl . '/emprunteur');
                    die;
                    break;
                default:
                    //pour arbo izinoa
                    header('Location:' . $this->lurl . '/tree');
                    die;
                    break;
            }
        }

        // On descend la page dans l'arborescence
        if (isset($this->params[0]) && $this->params[0] == 'down') {
            $this->tree->moveDown($this->params[1]);

            // on v�rifie si la page �diter est dans l'arbo du preteur,emprunteur ou izinoa
            $this->tree->get(['id_tree' => $this->params[1], 'id_langue' => $this->language]);

            switch ($this->tree->arbo) {
                case 1:
                    // si arbo du test
                    header('Location:' . $this->lurl . '/preteur');
                    die;
                    break;
                case 2:
                    // si arbo des questions
                    header('Location:' . $this->lurl . '/emprunteur');
                    die;
                    break;
                default:
                    //pour arbo izinoa
                    header('Location:' . $this->lurl . '/tree');
                    die;
                    break;
            }
        }

        // On supprime la page et ses dependances
        if (isset($this->params[0]) && $this->params[0] == 'delete') {
            $this->tree->get(['id_tree' => $this->params[1], 'id_langue' => $this->language]);
            $arbo = $this->tree->arbo;

            $this->tree->deleteCascade($this->params[1]);

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Suppression d\'une page';
            $_SESSION['freeow']['message'] = 'La page et ses enfants ont bien &eacute;t&eacute; supprim&eacute;s !';

            // on v�rifie si la page �diter est dans l'arbo du preteur,emprunteur ou izinoa
            $this->tree->get(['id_tree' => $this->params[1], 'id_langue' => $this->language]);

            switch ($arbo) {
                case 1:
                    // si arbo du test
                    header('Location:' . $this->lurl . '/preteur');
                    die;
                    break;
                case 2:
                    // si arbo des questions
                    header('Location:' . $this->lurl . '/emprunteur');
                    die;
                    break;
                default:
                    //pour arbo izinoa
                    header('Location:' . $this->lurl . '/tree');
                    die;
                    break;
            }
        }
    }

    public function _add()
    {
        $this->templates = $this->loadData('templates');

        // Formulaire d'ajout d'une page
        if (isset($_POST['form_add_tree'])) {
            // On enregistre les donn�es pour toutes les langues
            foreach ($this->lLangues as $key => $lng) {
                $this->tree->id_langue   = $key;
                $this->tree->id_parent   = $_POST['id_parent'];
                $this->tree->id_template = $_POST['id_template_' . $key];
                $this->tree->id_user     = $_SESSION['user']['id_user'];
                $this->tree->title       = trim($_POST['title_' . $key]);

                // On v�rifie si le p�re fait parti de l'arbo preteur,emprunteur ou d'izinoa
                // si le pere fait partie de larbo preteur ou emprunteur l'enfant que l'on cr�e ici sera aussi marqu� dans le champs "arbo"
                $this->treepere = $this->loadData('tree');
                $this->treepere->get(['id_tree' => $_POST['id_parent'], 'id_langue' => $key]);
                // L'enfant prend l'arbo de son pere
                $this->tree->arbo = $this->treepere->arbo;

                if ($_POST['id_parent_' . $key] == 0) {
                    $this->tree->slug = '';
                } else {
                    if (trim($_POST['slug_' . $key]) != '') {
                        $this->tree->slug = $this->bdd->generateSlug(trim($_POST['slug_' . $key]));
                    } else {
                        $this->tree->slug = $this->bdd->generateSlug(trim($_POST['title_' . $key]));
                    }
                }

                // Upload de l'image du menu
                if (isset($_FILES['img_menu_' . $key]) && $_FILES['img_menu_' . $key]['name'] != '') {
                    $this->upload->setUploadDir($this->spath, 'images/');

                    if ($this->upload->doUpload('img_menu_' . $key)) {
                        $this->tree->img_menu = $this->upload->getName();
                    } else {
                        $this->tree->img_menu = '';
                    }
                } else {
                    $this->tree->img_menu = '';
                }

                $this->tree->menu_title       = (trim($_POST['menu_title_' . $key]) != '' ? trim($_POST['menu_title_' . $key]) : $_POST['title_' . $key]);
                $this->tree->meta_title       = (trim($_POST['meta_title_' . $key]) != '' ? trim($_POST['meta_title_' . $key]) : $_POST['title_' . $key]);
                $this->tree->meta_description = ($_POST['meta_description_' . $key] != '' ? $_POST['meta_description_' . $key] : $_POST['title_' . $key]);
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

            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Ajout d\'une page';
            $_SESSION['freeow']['message'] = 'La page a bien &eacute;t&eacute; enregistr&eacute;e !';

            // Renvoi sur l'edition pour remplir le contenu des templates
            header('Location:' . $this->lurl . '/tree/edit/' . $this->tree->id_tree);
            die;
        }

        // Recuperation de l'arbo pour select
        $this->lTree = $this->tree->listChilds(0, [], $this->dLanguage);

        // Recuperation de la liste des templates
        $this->lTemplate = $this->templates->select('status > 0 AND type = 0', 'name ASC');
    }

    public function _edit()
    {
        $this->templates    = $this->loadData('templates');
        $this->elements     = $this->loadData('elements');
        $this->redirections = $this->loadData('redirections');
        $this->controlTree  = $this->loadData('tree');

        if (isset($this->params[0]) && $this->params[0] != '') {
            // Modification du tree
            if (isset($_POST['form_edit_tree'])) {
                // On enregistre les donn�es pour toutes les langues
                foreach ($this->lLangues as $key => $lng) {
                    // Recuperation des infos de la page
                    if ($this->tree->get(['id_tree' => $this->params[0], 'id_langue' => $key])) {
                        $create = false;
                    } else {
                        $create = true;
                    }

                    // Gestion du slug
                    if ($_POST['id_parent_' . $key] == 0) {
                        $this->tree->slug = '';
                    } else {
                        // On regarde si le slug est different du precedent pour le mettre � jour et cr�er la redirection
                        if (trim($_POST['slug_' . $key]) != $this->tree->slug) {
                            // On recupere le nouveau slug
                            if (trim($_POST['slug_' . $key]) != '') {
                                $slug_temp = $this->bdd->generateSlug(trim($_POST['slug_' . $key]));
                            } else {
                                $slug_temp = $this->bdd->generateSlug(trim($_POST['title_' . $key]));
                            }

                            // On regarde s'il existe pas deja sinon on le change
                            if ($this->controlTree->get(['slug' => $slug_temp, 'id_langue' => $key])) {
                                $slug_temp = $slug_temp . '-' . $key . '-' . $this->params[0];
                            }

                            // On regarde s'il est different de l'ancien et si oui on le place ds les redir
                            if ($slug_temp != $this->tree->slug) {
                                // On regarde si une redirection existe deja pour l'ancien slug
                                if ($this->redirections->get(['from_slug' => $this->tree->slug, 'id_langue' => $key])) {
                                    $createRedir = false;
                                } else {
                                    $createRedir = true;
                                }

                                // On rempli la base
                                $this->redirections->id_langue = $key;
                                $this->redirections->from_slug = $this->tree->slug;
                                $this->redirections->to_slug   = $slug_temp;
                                $this->redirections->type      = 301;
                                $this->redirections->status    = 1;

                                // On enregistre
                                if (! $createRedir) {
                                    $this->redirections->update(['from_slug' => $this->tree->slug, 'id_langue' => $key]);
                                } else {
                                    $this->redirections->create(['from_slug' => $this->tree->slug, 'id_langue' => $key]);
                                }
                            }

                            // On attribue le slug
                            $this->tree->slug = $slug_temp;
                        }
                    }

                    $this->tree->id_langue   = $key;
                    $this->tree->id_parent   = $_POST['id_parent'];
                    $this->tree->id_template = $_POST['id_template_' . $key];
                    $this->tree->id_user     = $_SESSION['user']['id_user'];
                    $this->tree->title       = trim($_POST['title_' . $key]);

                    // Upload de l'image du menu
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

                    // On modifie ou on cr�� si la page n"existe pas
                    if (! $create) {
                        $this->tree->update(['id_tree' => $this->params[0], 'id_langue' => $this->tree->id_langue]);
                    } else {
                        $this->tree->create(['id_tree' => $this->params[0], 'id_langue' => $this->tree->id_langue]);
                    }

                    // Modification du statu de tous les enfants si on passe le parent � 0
                    if ($_POST['status_' . $key] == 0) {
                        $this->tree->statusCascade($this->params[0], $key);
                    }
                    $this->tree->get(['id_tree' => $this->params[0], 'id_langue' => $key]);

                    // Enregistrement des values des elements du template
                    $this->tree_elements->delete($this->tree->id_tree, 'id_langue = "' . $key . '" AND id_tree');

                    // Recuperation des elements du template
                    $this->lElements = $this->elements->select('status > 0 AND id_template != 0 AND id_template = ' . $this->tree->id_template, 'ordre ASC');

                    foreach ($this->lElements as $element) {
                        $this->tree->handleFormElement($this->tree->id_tree, $element, 'tree', $key);
                    }
                }

                // Mise en session du message
                $_SESSION['freeow']['title']   = 'Modification d\'une page';
                $_SESSION['freeow']['message'] = 'La page a bien &eacute;t&eacute; modifi&eacute;e !';

                // Renvoi sur l'edition pour remplir le contenu des templates

                // on v�rifie si la page �diter est dans l'arbo preteur, emprunteur ou izinoa
                switch ($this->tree->arbo) {
                    case 1:
                        // si arbo preteur
                        header('Location:' . $this->lurl . '/preteur');
                        die;
                        break;
                    case 2:
                        // si arbo emprunteur
                        header('Location:' . $this->lurl . '/emprunteur');
                        die;
                        break;
                    default:
                        //pour arbo izinoa
                        header('Location:' . $this->lurl . '/tree');
                        die;
                        break;
                }
            }

            // Recuperation de l'arbo pour select
            $this->lTree = $this->tree->listChilds(0, [], $this->dLanguage);

            // Recuperation de la liste des templates
            $this->lTemplate = $this->templates->select('status > 0 AND type = 0', 'name ASC');

            // Recuperation des infos de la page pour la langue pardefaut
            $this->tree->get(['id_tree' => $this->params[0], 'id_langue' => $this->dLanguage]);
        } else {
            // Mise en session du message
            $_SESSION['freeow']['title']   = 'Modification d\'une page';
            $_SESSION['freeow']['message'] = 'Aucune page &agrave; modifier !';

            // on v�rifie si la page �diter est dans l'arbo preteur, emprunteur ou izinoa
            switch ($this->tree->arbo) {
                case 1:
                    header('Location:' . $this->lurl . '/preteur');
                    die;
                case 2:
                    header('Location:' . $this->lurl . '/emprunteur');
                    die;
                default:
                    header('Location:' . $this->lurl . '/tree');
                    die;
            }
        }
    }
}
