<?php

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Entity\{Redirections, Tree};

class treeController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->menu_admin = 'edition';
    }

    public function _default()
    {
        if (isset($this->params[0]) && 'up' == $this->params[0]) {
            $this->tree->moveUp($this->params[1]);

            header('Location: ' . $this->url . '/tree');
            die;
        }

        if (isset($this->params[0]) && 'down' == $this->params[0]) {
            $this->tree->moveDown($this->params[1]);

            header('Location: ' . $this->url . '/tree');
            die;
        }

        if (isset($this->params[0]) && 'delete' == $this->params[0]) {
            $this->tree->get($this->params[1]);
            $this->tree->deleteCascade($this->params[1]);

            $_SESSION['freeow']['title']   = 'Suppression d\'une page';
            $_SESSION['freeow']['message'] = 'La page et ses enfants ont bien été supprimés';

            header('Location: ' . $this->url . '/tree');
            die;
        }
    }

    public function _add()
    {
        $this->templates = $this->loadData('templates');

        if (isset($_POST['form_add_tree'])) {
            $this->tree->id_langue   = 'fr';
            $this->tree->id_parent   = $_POST['id_parent'];
            $this->tree->id_template = $_POST['id_template_fr'];
            $this->tree->id_user     = $this->getUser()->getIdClient();
            $this->tree->title       = trim($_POST['title_fr']);
            $this->tree->slug        = '';
            $this->tree->img_menu    = '';

            if (false === empty($_POST['id_parent_fr'])) {
                if (false === empty(trim($_POST['slug_fr']))) {
                    $this->tree->slug = $this->bdd->generateSlug(trim($_POST['slug_fr']));
                } else {
                    $this->tree->slug = $this->bdd->generateSlug(trim($_POST['title_fr']));
                }
            }

            if (false === empty($_FILES['img_menu_fr']['name'])) {
                $this->upload->setUploadDir($this->spath, 'images/');

                if ($this->upload->doUpload('img_menu_fr')) {
                    $this->tree->img_menu = $this->upload->getName();
                }
            }

            $this->tree->menu_title       = empty(trim($_POST['menu_title_fr'])) ? $_POST['title_fr'] : trim($_POST['menu_title_fr']);
            $this->tree->meta_title       = empty(trim($_POST['meta_title_fr'])) ? $_POST['title_fr'] : trim($_POST['meta_title_fr']);
            $this->tree->meta_description = empty($_POST['meta_description_fr']) ? $_POST['title_fr'] : $_POST['meta_description_fr'];
            $this->tree->meta_keywords    = $_POST['meta_keywords_fr'];
            $this->tree->ordre            = $this->tree->getLastPosition($_POST['id_parent_fr']) + 1;
            $this->tree->status           = $_POST['status_fr'];
            $this->tree->status_menu      = $_POST['status_menu_fr'];
            $this->tree->prive            = $_POST['prive_fr'];
            $this->tree->indexation       = $_POST['indexation_fr'];
            $this->tree->create();

            $_SESSION['freeow']['title']   = 'Ajout d\'une page';
            $_SESSION['freeow']['message'] = 'La page a bien été enregistrée';

            header('Location: ' . $this->url . '/tree/edit/' . $this->tree->id_tree);
            die;
        }

        $this->lTree     = $this->tree->listChilds(0, [], 'fr');
        $this->lTemplate = $this->templates->select('', 'name ASC');
    }

    public function _edit()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $this->templates       = $this->loadData('templates');
        $this->elements        = $this->loadData('elements');
        $treeRepository        = $entityManager->getRepository(Tree::class);
        $redirectionRepository = $entityManager->getRepository(Redirections::class);

        if (false === empty($this->params[0])) {
            if (isset($_POST['form_edit_tree']) && $this->tree->get($this->params[0])) {
                if (empty($_POST['id_parent_fr'])) {
                    $this->tree->slug = '';
                } elseif (trim($_POST['slug_fr']) !== $this->tree->slug) {
                    if (false === empty(trim($_POST['slug_fr']))) {
                        $slug_temp = $this->bdd->generateSlug(trim($_POST['slug_fr']));
                    } else {
                        $slug_temp = $this->bdd->generateSlug(trim($_POST['title_fr']));
                    }

                    if ($treeRepository->findOneBy(['slug' => $slug_temp])) {
                        $slug_temp = $slug_temp . '-fr' . '-' . $this->params[0];
                    }

                    if ($slug_temp !== $this->tree->slug) {
                        $redirection = $redirectionRepository->findOneBy(['fromSlug' => '/' . $this->tree->slug]);
                        if (null === $redirection) {
                            $redirection = new Redirections();
                        }
                        $redirection
                            ->setIdLangue('fr')
                            ->setFromSlug('/' . $this->tree->slug)
                            ->setToSlug('/' . $slug_temp)
                            ->setType(301)
                            ->setStatus(Redirections::STATUS_ENABLED)
                            ;
                        $entityManager->persist($redirection);
                        $entityManager->flush();
                    }

                    $this->tree->slug = $slug_temp;
                }

                $this->tree->id_langue   = 'fr';
                $this->tree->id_parent   = $_POST['id_parent'];
                $this->tree->id_template = $_POST['id_template_fr'];
                $this->tree->id_user     = $this->getUser()->getIdClient();
                $this->tree->title       = trim($_POST['title_fr']);

                if (false === empty($_FILES['img_menu_fr']['name'])) {
                    $this->upload->setUploadDir($this->spath, 'images/');

                    if ($this->upload->doUpload('img_menu_fr')) {
                        $this->tree->img_menu = $this->upload->getName();
                    } else {
                        $this->tree->img_menu = $_POST['img_menu_fr' . '-old'];
                    }
                } else {
                    $this->tree->img_menu = $_POST['img_menu_fr' . '-old'];
                }

                $this->tree->menu_title       = empty(trim($_POST['menu_title_fr'])) ? $_POST['title_fr'] : trim($_POST['menu_title_fr']);
                $this->tree->meta_title       = empty(trim($_POST['meta_title_fr'])) ? $_POST['title_fr'] : trim($_POST['meta_title_fr']);
                $this->tree->meta_description = empty($_POST['meta_description_fr']) ? $_POST['title_fr'] : $_POST['meta_description_fr'];
                $this->tree->meta_keywords    = $_POST['meta_keywords_fr'];
                $this->tree->status           = $_POST['status_fr'];
                $this->tree->status_menu      = $_POST['status_menu_fr'];
                $this->tree->prive            = $_POST['prive_fr'];
                $this->tree->indexation       = $_POST['indexation_fr'];
                $this->tree->update();

                if (empty($_POST['status_fr'])) {
                    $this->tree->statusCascade($this->params[0], 'fr');
                }

                $this->tree->get($this->params[0]);

                $this->tree_elements->delete($this->tree->id_tree, 'id_langue = "fr" AND id_tree');

                $elements = $this->elements->select('status > 0 AND id_template != 0 AND id_template = ' . $this->tree->id_template, 'ordre ASC');

                foreach ($elements as $element) {
                    $this->tree->handleFormElement($this->tree->id_tree, $element, 'tree', 'fr');
                }

                $_SESSION['freeow']['title']   = 'Modification d\'une page';
                $_SESSION['freeow']['message'] = 'La page a bien été modifiée';

                header('Location: ' . $this->url . '/tree');
                die;
            }

            $this->lTree     = $this->tree->listChilds(0, [], 'fr');
            $this->lTemplate = $this->templates->select('', 'name ASC');

            $this->tree->get($this->params[0]);
        } else {
            $_SESSION['freeow']['title']   = 'Modification d\'une page';
            $_SESSION['freeow']['message'] = 'Aucune page &agrave; modifier';

            header('Location: ' . $this->url . '/tree');
            die;
        }
    }
}
