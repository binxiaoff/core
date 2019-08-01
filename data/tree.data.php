<?php

use Unilend\Entity\Partner;

class tree extends tree_crud
{
    /**
     * Constant for sort press article in descendant order
     * This constant is arbo id in BDD
     */
    const PRESS_SPEAKS = 101;

    public static $keywordsPagesOutsideCMS = [
        'statistique', 'statistiques', 'contact'
    ];

    public function __construct($bdd, $params = '')
    {
        parent::__construct($bdd, $params);
    }

    function get($id, $field = 'id_tree')
    {
        return parent::get($id, $field);
    }

    function update($cs = '')
    {
        parent::update($cs);
    }

    function delete($id, $field = 'id_tree')
    {
        parent::delete($id, $field);
    }

    function create($cs = '')
    {
        $id = parent::create($cs);
        return $id;
    }

    function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `tree`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT count(*) FROM `tree` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    function exist($id, $field = 'id_tree')
    {
        $sql    = 'SELECT * FROM `tree` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    //******************************************************************************************//
    //**************************************** AJOUTS ******************************************//
    //******************************************************************************************//
    // Definition des types d'éléments
    public $typesElements = ['Texte', 'Textearea', 'Texteditor', 'Lien Interne', 'Image', 'Fichier', 'Boolean', 'SVG', 'Partner'];

    // Affichage des elements de formulaire en fonction du type d'élément
    public function displayFormElement($id_tree, $element, $type = 'tree', $langue = 'fr')
    {
        $elementType   = 'tree' === $type ? 'tree_elements' : 'blocs_elements';
        $jsFunction    = 'tree' === $type ? '' : 'Bloc';
        $parentIdField = 'tree' === $type ? 'id_tree' : 'id_bloc';

        $this->params[$elementType]->unsetData();
        $this->params[$elementType]->get($element['id_element'], $parentIdField . ' = ' . $id_tree . ' AND id_langue = "' . $langue . '" AND id_element');

        switch ($element['type_element']) {
            case 'Texte':
                echo '
                <tr>
                    <th>
                        <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' :</label>
                    </th>
                </tr>
                <tr>
                    <td>
                        <input class="input_big" type="text" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '" value="' . $this->params[$elementType]->value . '" />
                    </td>
                </tr>';
                break;

            case 'Textearea':
                echo '
                <tr>
                    <th>
                        <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' :</label>
                    </th>
                </tr>
                <tr>
                    <td>
                        <textarea class="textarea_large" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '">' . $this->params[$elementType]->value . '</textarea>
                    </td>
                </tr>';
                break;

            case 'Texteditor':
                echo '
                <tr>
                    <th>
                        <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' :</label>
                    </th>
                </tr>
                <tr>
                    <td>
                        <textarea class="textarea_large" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '">' . $this->params[$elementType]->value . '</textarea>
                        <script type="text/javascript">var cked = CKEDITOR.replace(\'' . $element['slug'] . '_' . $langue . '\');</script>
                    </td>
                </tr>';
                break;

            case 'Lien Interne':
                echo '
                <tr>
                    <th class="bas">
                        <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' :</label>
                        <select name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '" class="select">';
                foreach ($this->listChilds(0, array(), $langue) as $tree) {
                    echo '<option value="' . $tree['id_tree'] . '"' . ($this->params[$elementType]->value == $tree['id_tree'] ? ' selected' : '') . '>' . $tree['title'] . '</option>';
                }
                echo '
                        </select>
                    </th>
                </tr>';
                break;

            case 'Image':
                echo '
                <tr>
                    <th>
                        <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' :</label>
                    </th>
                </tr>
                <tr>
                    <th class="bas">
                        <input type="file" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '" />
                        <input type="hidden" name="' . $element['slug'] . '_' . $langue . '-old" id="' . $element['slug'] . '_' . $langue . '-old" value="' . $this->params[$elementType]->value . '" />
                        &nbsp;&nbsp;<label for="nom_' . $element['slug'] . '_' . $langue . '">Nom du fichier image :</label>
                        <input class="input_large" type="text" name="nom_' . $element['slug'] . '_' . $langue . '" id="nom_' . $element['slug'] . '_' . $langue . '" value="' . $this->params[$elementType]->complement . '" />
                    </th>
                </tr>
                <tr id="deleteImageElement' . $jsFunction . $this->params[$elementType]->id . '">';
                if ($this->params[$elementType]->value != '') {
                    $imagePath = 'tree' === $type ? $this->params['spath'] . 'images/' : $this->params['furl'] . '/var/images/';
                    list($width, $height) = @getimagesize($imagePath . $this->params[$elementType]->value);
                    echo '
                        <th class="bas">
                            <a href="' . $this->params['furl'] . '/var/images/' . $this->params[$elementType]->value . '" class="thickbox">
                                <img src="' . $this->params['furl'] . '/var/images/' . $this->params[$elementType]->value . '" alt="' . $element['name'] . '"' . ($height > 180 ? ' height="180"' : ($width > 400 ? ' width="400"' : '')) . ' style="vertical-align:middle;" />
                            </a>
                            &nbsp;&nbsp; Supprimer l\'image&nbsp;&nbsp;
                            <a onclick="if(confirm(\'Etes vous sur de vouloir supprimer cette image ?\')){deleteImageElement' . $jsFunction . '(' . $this->params[$elementType]->id . ',\'' . $element['slug'] . '_' . $langue . '\');return false;}">
                                <img src="' . $this->params['url'] . '/images/delete.png" alt="Supprimer" style="vertical-align:middle;" />
                            </a>
                        </th>';
                } else {
                    echo '
                        <td>&nbsp;</td>';
                }
                echo '
                </tr>';
                break;

            case 'Fichier':
                echo '
                <tr>
                    <th>
                        <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' :</label>
                    </th>
                </tr>
                <tr>
                    <th class="bas">
                        <input type="file" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '" />
                        <input type="hidden" name="' . $element['slug'] . '_' . $langue . '-old" id="' . $element['slug'] . '_' . $langue . '-old" value="' . $this->params[$elementType]->value . '" />
                        &nbsp;&nbsp;<label for="nom_' . $element['slug'] . '_' . $langue . '">Nom du fichier :</label>
                        <input class="input_large" type="text" name="nom_' . $element['slug'] . '_' . $langue . '" id="nom_' . $element['slug'] . '_' . $langue . '" value="' . $this->params[$elementType]->complement . '" />
                    </th>
                </tr>
                <tr id="deleteFichierElement' . $jsFunction . $this->params[$elementType]->id . '">';
                if ($this->params[$elementType]->value != '') {
                    echo '
                        <th class="bas">
                            <label>Fichier actuel</label> :
                            <a href="' . $this->params['furl'] . '/var/fichiers/' . $this->params[$elementType]->value . '" target="blank">' . $this->params['furl'] . '/var/fichiers/' . $this->params[$elementType]->value . '</a>
                            &nbsp;&nbsp;
                            <a onclick="if(confirm(\'Etes vous sur de vouloir supprimer ce fichier ?\')){deleteFichierElement' . $jsFunction . '(' . $this->params[$elementType]->id . ',\'' . $element['slug'] . '_' . $langue . '\');return false;}">
                                <img src="' . $this->params['url'] . '/images/delete.png" alt="Supprimer">
                            </a>
                        </th>';
                } else {
                    echo '
                        <td>&nbsp;</td>';
                }
                echo '
                </tr>';
                break;

            case 'SVG':
                $icons = ['header-contact', 'header-fiscalite', 'header-legal', 'header-plandusite', 'header-recrutement', 'header-securite', 'avatar-f-level-1', 'category-1', 'category-10', 'category-11', 'category-12', 'category-13', 'category-14', 'category-15', 'category-2', 'category-3', 'category-4', 'category-5', 'category-6', 'category-7', 'category-8', 'category-9', 'promo-balance', 'promo-barchart', 'promo-calendarweek', 'promo-clock', 'promo-francemap', 'promo-handshake', 'promo-info', 'promo-linechart', 'promo-linechart2', 'promo-money', 'promo-pagestack', 'promo-people', 'promo-piggybank', 'promo-profile', 'promo-projects', 'promo-protection', 'promo-saving', 'promo-transparancy', 'promo-verified'];
                sort($icons);
                echo '
                <tr>
                    <th>
                        <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' : </label>
                    </th>
                </tr>
                <tr>
                    <th class="bas">
                        <select name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '">
                            <option value=""></option>';
                            foreach ($icons as $icon) {
                                echo '<option value="' . $icon . '"' . ($this->params[$elementType]->value === $icon ? ' selected' : '') . '>' . $icon . '</option>';
                            }
                            echo '
                        </select>
                        <a href="https://unilend.atlassian.net/wiki/display/PROJ/UI+nouveau+site" target="_blank">Voir la liste</a>
                    </th>
                </tr>';
                break;

            case 'Boolean':
                echo '
                <tr>
                    <th style="padding-top:10px">
                        <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' : </label>
                        <select name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '">
                            <option value="0"' . ($this->params[$elementType]->value == 0 ? ' selected' : '') . '>Non</option>
                            <option value="1"' . ($this->params[$elementType]->value == 1 ? ' selected' : '') . '>Oui</option>
                        </select>
                    </th>
                </tr>';
                break;

            case 'Partner':
                $partner  = new \partner($this->bdd);
                $partners = $partner->select('status = ' . Partner::STATUS_VALIDATED, 'label ASC');

                echo '
                <tr>
                    <th style="padding-top:10px">
                        <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' : </label>
                        <select name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '">
                            <option value=""></option>';

                foreach ($partners as $partner) {
                    echo '<option value="' . $partner['id'] . '"' . ($this->params[$elementType]->value == $partner['id'] ? ' selected' : '') . '>' . $partner['label'] . '</option>';
                }

                echo '
                        </select>
                    </th>
                </tr>';
                break;

            default:
                trigger_error('Unknown element type: ' . $element['type_element'], E_USER_ERROR);
        }
    }

    // Traitement du formulaire des elements en fonction du type d'element
    public function handleFormElement($id_tree, $element, $type = 'tree', $langue = 'fr')
    {
        $elementType = 'tree' === $type ? 'tree_elements' : 'blocs_elements';
        $idFieldName = 'tree' === $type ? 'id_tree' : 'id_bloc';

        switch ($element['type_element']) {
            case 'Image':
                if (isset($_FILES[$element['slug'] . '_' . $langue]) && $_FILES[$element['slug'] . '_' . $langue]['name'] != '') {
                    if ($_POST['nom_' . $element['slug'] . '_' . $langue] != '') {
                        $this->nom_fichier = $this->bdd->generateSlug($_POST['nom_' . $element['slug'] . '_' . $langue]);
                    } else {
                        $this->nom_fichier = '';
                    }

                    $this->params['upload']->setUploadDir($this->params['spath'], 'images/');

                    if ($this->params['upload']->doUpload($element['slug'] . '_' . $langue, $this->nom_fichier)) {
                        $_POST[$element['slug'] . '_' . $langue]   = $this->params['upload']->getName();
                        $this->params[$elementType]->{$idFieldName} = $id_tree;
                        $this->params[$elementType]->id_element     = $element['id_element'];
                        $this->params[$elementType]->id_langue      = $langue;
                        $this->params[$elementType]->value          = $_POST[$element['slug'] . '_' . $langue];
                        $this->params[$elementType]->complement     = $_POST['nom_' . $element['slug'] . '_' . $langue];
                        $this->params[$elementType]->status         = 1;
                        $this->params[$elementType]->create();
                    } else {
                        $this->params[$elementType]->{$idFieldName} = $id_tree;
                        $this->params[$elementType]->id_element     = $element['id_element'];
                        $this->params[$elementType]->id_langue      = $langue;
                        $this->params[$elementType]->value          = '';
                        $this->params[$elementType]->complement     = '';
                        $this->params[$elementType]->status         = 1;
                        $this->params[$elementType]->create();
                    }
                } else {
                    $this->params[$elementType]->{$idFieldName} = $id_tree;
                    $this->params[$elementType]->id_element     = $element['id_element'];
                    $this->params[$elementType]->id_langue      = $langue;
                    $this->params[$elementType]->value          = $_POST[$element['slug'] . '_' . $langue . '-old'];
                    $this->params[$elementType]->complement     = $_POST['nom_' . $element['slug'] . '_' . $langue];
                    $this->params[$elementType]->status         = 1;
                    $this->params[$elementType]->create();
                }
                break;

            case 'Fichier':
                if (isset($_FILES[$element['slug'] . '_' . $langue]) && $_FILES[$element['slug'] . '_' . $langue]['name'] != '') {
                    if ($_POST['nom_' . $element['slug'] . '_' . $langue] != '') {
                        $this->nom_fichier = $this->bdd->generateSlug($_POST['nom_' . $element['slug'] . '_' . $langue]);
                    } else {
                        $this->nom_fichier = '';
                    }

                    $this->params['upload']->setUploadDir($this->params['spath'], 'fichiers/');

                    if ($this->params['upload']->doUpload($element['slug'] . '_' . $langue, $this->nom_fichier)) {
                        $_POST[$element['slug'] . '_' . $langue]   = $this->params['upload']->getName();
                        $this->params[$elementType]->{$idFieldName} = $id_tree;
                        $this->params[$elementType]->id_element     = $element['id_element'];
                        $this->params[$elementType]->id_langue      = $langue;
                        $this->params[$elementType]->value          = $_POST[$element['slug'] . '_' . $langue];
                        $this->params[$elementType]->complement     = $_POST['nom_' . $element['slug'] . '_' . $langue];
                        $this->params[$elementType]->status         = 1;
                        $this->params[$elementType]->create();
                    } else {
                        $this->params[$elementType]->{$idFieldName} = $id_tree;
                        $this->params[$elementType]->id_element     = $element['id_element'];
                        $this->params[$elementType]->id_langue      = $langue;
                        $this->params[$elementType]->value          = '';
                        $this->params[$elementType]->complement     = '';
                        $this->params[$elementType]->status         = 1;
                        $this->params[$elementType]->create();
                    }
                } else {
                    $this->params[$elementType]->{$idFieldName} = $id_tree;
                    $this->params[$elementType]->id_element     = $element['id_element'];
                    $this->params[$elementType]->id_langue      = $langue;
                    $this->params[$elementType]->value          = $_POST[$element['slug'] . '_' . $langue . '-old'];
                    $this->params[$elementType]->complement     = $_POST['nom_' . $element['slug'] . '_' . $langue];
                    $this->params[$elementType]->status         = 1;
                    $this->params[$elementType]->create();
                }
                break;

            default:
                $this->params[$elementType]->{$idFieldName} = $id_tree;
                $this->params[$elementType]->id_element     = $element['id_element'];
                $this->params[$elementType]->id_langue      = $langue;
                $this->params[$elementType]->value          = $_POST[$element['slug'] . '_' . $langue];
                $this->params[$elementType]->complement     = '';
                $this->params[$elementType]->status         = 1;
                $this->params[$elementType]->create();
                break;
        }
    }

    // Recuperation de l'id max pour la création d'une page (clé primaire multiple, pas d'auto incremente)
    public function getMaxId()
    {
        $sql    = 'SELECT MAX(id_tree) as id FROM tree';
        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    public function getChilds($id_parent, $langue = 'fr')
    {
        $sSense     = (self::PRESS_SPEAKS == (int) $id_parent) ? 'DESC' : 'ASC';
        $lRubriques = $this->select('id_parent = ' . $id_parent . ' AND id_langue = "' . $langue . '"', 'ordre ' . $sSense);

        foreach ($lRubriques as $rub) {
            if ($rub['ordre'] == $this->getFirstPosition($rub['id_parent'])) {
                $up = '';
            } else {
                $up = '<a href="' . $this->params['url'] . '/tree/up/' . $rub['id_tree'] . '" title="Up"><img src="' . $this->params['surl'] . '/images/admin/up.png" alt="Up" /></a>';
            }
            if ($rub['ordre'] == $this->getLastPosition($rub['id_parent'])) {
                $down = '';
            } else {
                $down = '<a href="' . $this->params['url'] . '/tree/down/' . $rub['id_tree'] . '" title="Down"><img src="' . $this->params['surl'] . '/images/admin/down.png" alt="Down" /></a>';
            }

            if (strlen($rub['menu_title']) > 60) {
                $rub['menu_title'] = substr($rub['menu_title'], 0, 60) . '...';
            }

            if ($rub['id_parent'] == 1) {
                $b  = '<strong>';
                $sb = '</strong>';
            } else {
                $b  = '';
                $sb = '';
            }

            if ($this->counter('id_parent = ' . $rub['id_tree']) > 0) {
                if ($rub['status'] == 0) {
                    $class = 'folder hl';
                } else {
                    $class = 'folder';
                }
            } else {
                if ($rub['status'] == 0) {
                    $class = 'file hl';
                } else {
                    $class = 'file';
                }
            }

            $edit = '<a href="' . $this->params['url'] . '/tree/edit/' . $rub['id_tree'] . '" title="Edit"><img src="' . $this->params['url'] . '/images/edit.png" alt="Edit" /></a>';
            $add  = '<a href="' . $this->params['url'] . '/tree/add/' . $rub['id_tree'] . '" title="Add"><img src="' . $this->params['url'] . '/images/add.png" alt="Add" /></a>';
            $del  = '<a href="' . $this->params['url'] . '/tree/delete/' . $rub['id_tree'] . '" onclick="return confirm(\'Etes vous sur de vouloir supprimer cette page et toutes les pages qui en dépendent ?\')" title="Delete"><img src="' . $this->params['url'] . '/images/delete.png" alt="Delete" /></a>';

            $this->arbre .= '
            <li>
                <span class="' . $class . '">' . $b . '' . $rub['menu_title'] . '' . $sb . '' . $up . '' . $down . '' . $edit . '' . $add . '' . $del . '</span>';

            if ($this->counter('id_parent = ' . $rub['id_tree']) > 0) {
                $this->arbre .= '
                <ul>';

                $this->getChilds($rub['id_tree'], $langue, $this->arbre);

                $this->arbre .= '
                </ul>';
            }

            $this->arbre .= '
            </li>';
        }
    }

    // Recuperation et affichage de l'arbo du site
    public function getArbo($id = '1', $langue = 'fr')
    {
        $edit = '<a href="' . $this->params['url'] . '/tree/edit/' . $id . '" title="Edit"><img src="' . $this->params['url'] . '/images/edit.png" alt="Edit" /></a>';
        $add  = '<a href="' . $this->params['url'] . '/tree/add/' . $id . '" title="Add"><img src="' . $this->params['url'] . '/images/add.png" border="0" alt="Add" /></a>';

        $this->arbre = '<img src="' . $this->params['url'] . '/images/home.png" border="0" alt="Home" />';
        $this->arbre .= $edit . '' . $add;
        $this->arbre .= '<ul id="browser" class="filetree">';

        $this->getChilds($id, $langue);

        $this->arbre .= '</ul>';

        return $this->arbre;
    }

    // Construction de l'arbo pour un select
    public function listChilds($id_parent, $tableau, $id_langue = 'fr', $indent = '')
    {
        $sql    = 'SELECT * FROM tree WHERE id_parent = ' . $id_parent . ' AND id_langue = "' . $id_langue . '" ORDER BY ordre ASC ';
        $result = $this->bdd->query($sql);

        while ($record = $this->bdd->fetch_assoc($result)) {
            $tableau[] = array('id_tree' => $record['id_tree'], 'title' => $indent . $record['menu_title'], 'id_parent' => $id_parent, 'slug' => $record['slug']);
            $tableau   = $this->listChilds($record['id_tree'], $tableau, $id_langue, $indent . '&nbsp;&nbsp;&nbsp;');
        }

        return $tableau;
    }

    // Récupération de la premiere position des pages d'une rubrique
    public function getFirstPosition($id_parent)
    {
        $sql    = 'SELECT ordre FROM tree WHERE id_parent = ' . $id_parent . ' ORDER BY ordre ASC LIMIT 1';
        $result = $this->bdd->query($sql);

        return (int) ($this->bdd->result($result, 0, 0));
    }

    // Récupération de la derniere position des pages d'une rubrique
    public function getLastPosition($id_parent)
    {
        $sql    = 'SELECT ordre FROM tree WHERE id_parent = ' . $id_parent . ' ORDER BY ordre DESC LIMIT 1';
        $result = $this->bdd->query($sql);

        return (int) ($this->bdd->result($result, 0, 0));
    }

    // Monter une page dans l'arborescence
    // Si pb cf tree_menu
    public function moveUp($id)
    {
        $id_parent = $this->getParent($id);
        $position  = $this->getPosition($id);

        $sql = 'UPDATE tree SET ordre = ordre + 1 WHERE id_parent = ' . $id_parent . ' AND ordre < ' . $position . ' ORDER BY ordre DESC LIMIT 1';
        $this->bdd->query($sql);

        $sql = 'UPDATE tree SET ordre = ordre - 1 WHERE id_tree = ' . $id;
        $this->bdd->query($sql);
        $this->reordre($id_parent);
    }

    // Descendre une page dans l'arborescence
    // Si pb cf tree_menu
    public function moveDown($id)
    {
        $id_parent = $this->getParent($id);
        $position  = $this->getPosition($id);

        $sql = 'UPDATE tree SET ordre = ordre - 1 WHERE id_parent = ' . $id_parent . ' AND ordre > ' . $position . ' ORDER BY ordre ASC LIMIT 1';
        $this->bdd->query($sql);

        $sql = 'UPDATE tree SET ordre = ordre + 1 WHERE id_tree = ' . $id;
        $this->bdd->query($sql);
        $this->reordre($id_parent);
    }

    // Récupération de l'ID parent de la rubrique
    public function getParent($id)
    {
        $sql    = 'SELECT id_parent FROM tree WHERE id_tree = ' . $id;
        $result = $this->bdd->query($sql);

        return (int) ($this->bdd->result($result, 0, 0));
    }

    // Récupération de la position de la page
    public function getPosition($id)
    {
        $sql    = 'SELECT ordre FROM tree WHERE id_tree = ' . $id;
        $result = $this->bdd->query($sql);

        return (int) ($this->bdd->result($result, 0, 0));
    }

    // Reordonner une rubrique
    public function reordre($id_parent)
    {
        $sql    = 'SELECT DISTINCT(id_tree) FROM tree WHERE id_parent=' . $id_parent . ' ORDER BY ordre ASC ';
        $result = $this->bdd->query($sql);

        $i = 0;
        while ($record = $this->bdd->fetch_array($result)) {
            $sql1 = 'UPDATE tree SET ordre = ' . $i . ' WHERE id_tree = ' . $record['id_tree'];
            $this->bdd->query($sql1);
            $i++;
        }
    }

    // Suppression en cascade des pages d'un parent
    public function deleteCascade($id_parent)
    {
        $id_grand_parent = $this->getParent($id_parent);

        $final  = [];
        $sql    = 'SELECT id_tree FROM tree WHERE id_parent = ' . $id_parent;
        $result = $this->bdd->query($sql);

        while ($record = $this->bdd->fetch_array($result)) {
            $final[] = $record['id_tree'];
            $this->params['tree_elements']->delete($record['id_tree'], 'id_tree');
            $this->deleteCascade($record['id_tree']);
        }

        foreach ($final as $f) {
            if (! is_null($f)) {
                $this->delete(array('id_tree' => $f));
            }
        }

        $this->delete(array('id_tree' => $id_parent));
        $this->params['tree_elements']->delete($id_parent, 'id_tree');
        $this->reordre($id_grand_parent);
    }

    // On rement le champ template des page à 0 dans la table tree
    public function deleteTemplate($id_template)
    {
        $sql = 'UPDATE tree SET id_template = 0 WHERE id_template = "' . $id_template . '"';
        $this->bdd->query($sql);
    }

    // Récupération du slug de la page
    public function getSlug($id, $langue = 'fr')
    {
        $sql    = 'SELECT slug FROM tree WHERE id_langue = "' . $langue . '" AND id_tree = ' . $id;
        $result = $this->bdd->query($sql);

        return $this->bdd->result($result, 0, 0);
    }

    // Status à 0 en cascade pour les enfants d'une page que l'on passe à 0
    public function statusCascade($id_parent, $id_langue = 'fr')
    {
        $final  = [];
        $sql    = 'SELECT id_tree FROM tree WHERE id_parent = ' . $id_parent . ' AND id_langue = "' . $id_langue . '"';
        $result = $this->bdd->query($sql);

        while ($record = $this->bdd->fetch_assoc($result)) {
            $final[] = $record['id_tree'];
            $this->statusCascade($record['id_tree'], $id_langue);
        }

        foreach ($final as $f) {
            if (! is_null($f)) {
                $this->get(array('id_tree' => $f, 'id_langue' => $id_langue));
                $this->status = 0;
                $this->update(array('id_tree' => $f, 'id_langue' => $id_langue));
            }
        }
    }

    /**
     * @param string $search
     * @param string $langue
     *
     * @return array
     */
    public function search($search, $langue = 'fr')
    {
        if (empty($search)) {
            return [];
        }

        $result = [];
        $search = $this->bdd->escape_string($search);
        $query  = '
            SELECT t.slug AS slug,
              t.title AS title ,
              t.id_template AS id_template ,
              t.id_parent AS id_parent,
              te.value AS value
            FROM tree t
              LEFT JOIN tree_elements te ON t.id_tree = te.id_tree
              LEFT JOIN elements e ON e.id_element  = te.id_element
            WHERE t.status = 1
              AND t.id_langue = :language
              AND (te.value LIKE :search OR t.title LIKE :search OR t.slug LIKE :search)
            GROUP BY t.slug
            ORDER BY t.ordre ASC';

        /** @var \Doctrine\DBAL\Statement $statement */
        $statement     = $this->bdd->executeQuery($query, ['language' => $langue, 'search' => '%' . $search . '%']);
        $searchResults = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (false === empty($searchResults)) {
            foreach ($searchResults as $record) {
                    $result[] = [
                        'title' => $record['title'],
                        'slug'  => $record['slug']
                    ];
            }

            usort($result, function($firstElement, $secondElement) {
                if (in_array($firstElement['slug'], $this::$keywordsPagesOutsideCMS)) {
                    return 0;
                } else {
                    return strcasecmp($firstElement['title'], $secondElement['title']);
                }
            });
        }

        return $result;
    }
}

