<?php
// **************************************************************************************************** //
// ***************************************    ASPARTAM    ********************************************* //
// **************************************************************************************************** //
//
// Copyright (c) 2008-2011, equinoa
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
// associated documentation files (the "Software"), to deal in the Software without restriction,
// including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
// subject to the following conditions:
// The above copyright notice and this permission notice shall be included in all copies
// or substantial portions of the Software.
// The Software is provided "as is", without warranty of any kind, express or implied, including but
// not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.
// In no event shall the authors or copyright holders equinoa be liable for any claim,
// damages or other liability, whether in an action of contract, tort or otherwise, arising from,
// out of or in connection with the software or the use or other dealings in the Software.
// Except as contained in this notice, the name of equinoa shall not be used in advertising
// or otherwise to promote the sale, use or other dealings in this Software without
// prior written authorization from equinoa.
//
//  Version : 2.4.0
//  Date : 21/03/2011
//  Coupable : CM
//
// **************************************************************************************************** //

class tree extends tree_crud
{
    /**
     * Constant for sort press article in descendant order
     * This constant is arbo id in BDD
     */
    const PRESS_SPEAKS = 101;

    public function __construct($bdd, $params = '')
    {
        parent::tree($bdd, $params);
    }

    public function create($list_field_value = array())
    {
        parent::create($list_field_value);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM tree' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $result   = array();
        $resultat = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $result = $this->bdd->query('SELECT COUNT(*) FROM tree ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($list_field_value)
    {
        $list = '';
        foreach ($list_field_value as $champ => $valeur) {
            $list .= ' AND ' . $champ . ' = "' . $valeur . '" ';
        }

        $result = $this->bdd->query('SELECT * FROM tree WHERE 1 = 1 ' . $list);
        return ($this->bdd->fetch_array($result) > 0);
    }

    //******************************************************************************************//
    //**************************************** AJOUTS ******************************************//
    //******************************************************************************************//
    // Definition des types d'éléments
    public $typesElements = array('Texte', 'Textearea', 'Texteditor', 'Lien Interne', 'Lien Externe', 'Image', 'Fichier', 'Fichier Protected', 'Date', 'Checkbox', 'Boolean', 'SVG');

    // Affichage des elements de formulaire en fonction du type d'élément
    public function displayFormElement($id_tree, $element, $type = 'tree', $langue = 'fr')
    {
        if ($type == 'tree') {
            $this->params['tree_elements']->unsetData();
            $this->params['tree_elements']->get($element['id_element'], 'id_tree = ' . $id_tree . ' AND id_langue = "' . $langue . '" AND id_element');

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
                            <input class="input_big" type="text" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '" value="' . $this->params['tree_elements']->value . '" />
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
                            <textarea class="textarea_large" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '">' . $this->params['tree_elements']->value . '</textarea>
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
                            <textarea class="textarea_large" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '">' . $this->params['tree_elements']->value . '</textarea>
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
                        echo '<option value="' . $tree['id_tree'] . '"' . ($this->params['tree_elements']->value == $tree['id_tree'] ? ' selected="selected"' : '') . '>' . $tree['title'] . '</option>';
                    }
                    echo '
                            </select>
                        </th>
                    </tr>';
                    break;

                case 'Lien Externe':
                    echo '
                    <tr>
                        <th>
                            <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' :</label>
                        </th>
                    </tr>
                    <tr>
                        <td>
                            <input class="input_big" type="text" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '" value="' . $this->params['tree_elements']->value . '" />
                        </td>
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
                            <input type="hidden" name="' . $element['slug'] . '_' . $langue . '-old" id="' . $element['slug'] . '_' . $langue . '-old" value="' . $this->params['tree_elements']->value . '" />
                            &nbsp;&nbsp;<label for="nom_' . $element['slug'] . '_' . $langue . '">Nom du fichier image :</label>
                            <input class="input_large" type="text" name="nom_' . $element['slug'] . '_' . $langue . '" id="nom_' . $element['slug'] . '_' . $langue . '" value="' . $this->params['tree_elements']->complement . '" />
                        </th>
                    </tr>
                    <tr id="deleteImageElement' . $this->params['tree_elements']->id . '">';
                    if ($this->params['tree_elements']->value != '') {
                        if (substr(strtolower(strrchr(basename($this->params['tree_elements']->value), '.')), 1) == 'swf') {
                            echo '
                                <th class="bas">
                                    <object type="application/x-shockwave-flash" data="' . $this->params['surl'] . '/var/images/' . $this->params['tree_elements']->value . '" width="400" height="180" style="vertical-align:middle;">
                                        <param name="src" value="' . $this->params['surl'] . '/var/images/' . $this->params['tree_elements']->value . '" />
                                        <param name="movie" value="' . $this->params['surl'] . '/var/images/' . $this->params['tree_elements']->value . '" />
                                        <param name="quality" value="high" />
                                        <param name="bgcolor" value="#fff" />
                                        <param name="play" value="true" />
                                        <param name="loop" value="true" />
                                        <param name="scale" value="showall" />
                                        <param name="menu" value="true" />
                                        <param name="align" value="middle" />
                                        <param name="wmode" value="transparent" />
                                        <param name="pluginspage" value="http://www.macromedia.com/go/getflashplayer" />
                                        <param name="type" value="application/x-shockwave-flash" />
                                    </object>
                                    &nbsp;&nbsp; Supprimer le flash&nbsp;&nbsp;
                                    <a onclick="if(confirm(\'Etes vous sur de vouloir supprimer ce flash ?\')){deleteImageElement(' . $this->params['tree_elements']->id . ',\'' . $element['slug'] . '_' . $langue . '\');return false;}">
                                        <img src="' . $this->params['surl'] . '/images/admin/delete.png" alt="Supprimer" style="vertical-align:middle;" />
                                    </a>
                                </th>';
                        } else {
                            list($width, $height) = @getimagesize($this->params['spath'] . 'images/' . $this->params['tree_elements']->value);
                            echo '
                                <th class="bas">
                                    <a href="' . $this->params['surl'] . '/var/images/' . $this->params['tree_elements']->value . '" class="thickbox">
                                        <img src="' . $this->params['surl'] . '/var/images/' . $this->params['tree_elements']->value . '" alt="' . $element['name'] . '"' . ($height > 180 ? ' height="180"' : ($width > 400 ? ' width="400"' : '')) . ' style="vertical-align:middle;" />
                                    </a>
                                    &nbsp;&nbsp; Supprimer l\'image&nbsp;&nbsp;
                                    <a onclick="if(confirm(\'Etes vous sur de vouloir supprimer cette image ?\')){deleteImageElement(' . $this->params['tree_elements']->id . ',\'' . $element['slug'] . '_' . $langue . '\');return false;}">
                                        <img src="' . $this->params['surl'] . '/images/admin/delete.png" alt="Supprimer" style="vertical-align:middle;" />
                                    </a>
                                </th>';
                        }
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
                        <th colspan="2" class="bas">
                            <input type="file" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '" />
                            <input type="hidden" name="' . $element['slug'] . '_' . $langue . '-old" id="' . $element['slug'] . '_' . $langue . '-old" value="' . $this->params['tree_elements']->value . '" />
                            &nbsp;&nbsp;<label for="nom_' . $element['slug'] . '_' . $langue . '">Nom du fichier :</label>
                            <input class="input_large" type="text" name="nom_' . $element['slug'] . '_' . $langue . '" id="nom_' . $element['slug'] . '_' . $langue . '" value="' . $this->params['tree_elements']->complement . '" />
                        </th>
                    </tr>
                    <tr id="deleteFichierElement' . $this->params['tree_elements']->id . '">';
                    if ($this->params['tree_elements']->value != '') {
                        echo '
                            <th class="bas">
                                <label>Fichier actuel</label> :
                                <a href="' . $this->params['surl'] . '/var/fichiers/' . $this->params['tree_elements']->value . '" target="blank">' . $this->params['surl'] . '/var/fichiers/' . $this->params['tree_elements']->value . '</a>
                                &nbsp;&nbsp;
                                <a onclick="if(confirm(\'Etes vous sur de vouloir supprimer ce fichier ?\')){deleteFichierElement(' . $this->params['tree_elements']->id . ',\'' . $element['slug'] . '_' . $langue . '\');return false;}">
                                    <img src="' . $this->params['surl'] . '/images/admin/delete.png" alt="Supprimer" />
                                </a>
                            </th>';
                    } else {
                        echo '
                            <td>&nbsp;</td>';
                    }
                    echo '
                    </tr>';
                    break;

                case 'Fichier Protected':
                    echo '
                    <tr>
                        <th>
                            <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' :</label>
                        </th>
                    </tr>
                    <tr>
                        <th class="bas">
                            <input type="file" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '" />
                            <input type="hidden" name="' . $element['slug'] . '_' . $langue . '-old" id="' . $element['slug'] . '_' . $langue . '-old" value="' . $this->params['tree_elements']->value . '" />
                            &nbsp;&nbsp;<label for="nom_' . $element['slug'] . '_' . $langue . '">Nom du fichier :</label>
                            <input class="input_large" type="text" name="nom_' . $element['slug'] . '_' . $langue . '" id="nom_' . $element['slug'] . '_' . $langue . '" value="' . $this->params['tree_elements']->complement . '" />
                        </th>
                    </tr>
                    <tr id="deleteFichierProtectedElement' . $this->params['tree_elements']->id . '">';
                    if ($this->params['tree_elements']->value != '') {
                        echo '
                            <th class="bas">
                                <label>Fichier actuel</label> :
                                <a href="' . $this->params['url'] . '/protected/templates/' . $this->params['tree_elements']->value . '" target="blank">' . $this->params['tree_elements']->value . '</a>
                                &nbsp;&nbsp;
                                <a onclick="if(confirm(\'Etes vous sur de vouloir supprimer ce fichier ?\')){deleteFichierProtectedElement(' . $this->params['tree_elements']->id . ',\'' . $element['slug'] . '_' . $langue . '\');return false;}">
                                    <img src="' . $this->params['surl'] . '/images/admin/delete.png" alt="Supprimer" />
                                </a>
                            </th>';
                    } else {
                        echo '
                            <td>&nbsp;</td>';
                    }
                    echo '
                    </tr>';
                    break;

                case 'Date':
                    echo '
                    <tr>
                        <th>
                            <label for="datepik_' . $langue . '">' . $element['name'] . ' :</label>
                        </th>
                    </tr>
                    <tr>
                        <th class="bas">
                            <input class="input_dp" type="text" name="' . $element['slug'] . '_' . $langue . '" id="datepik_' . $langue . '" value="' . $this->params['tree_elements']->value . '" />
                        </th>
                    </tr>';
                    break;

                case 'Checkbox':
                    echo '
                    <tr>
                        <th class="bas">
                            <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . '</label> :
                            <input type="checkbox" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '" value="1"' . ($this->params['tree_elements']->value == 1 ? ' checked="checked"' : '') . ' />
                        </th>
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
                                    echo '<option value="' . $icon . '"' . ($this->params['tree_elements']->value === $icon ? ' selected' : '') . '>' . $icon . '</option>';
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
                        <th>
                            <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' : </label>
                            <select name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '">
                                <option value="0"' . ($this->params['tree_elements']->value == 0 ? ' selected' : '') . '>Non</option>
                                <option value="1"' . ($this->params['tree_elements']->value == 1 ? ' selected' : '') . '>Oui</option>
                            </select>
                        </th>
                    </tr>';
                    break;

                default:
                    echo '
                    <tr>
                        <th>
                            <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' :</label>
                        </th>
                    </tr>
                    <tr>
                        <td>
                            <input class="input_big" type="text" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '" value="' . $this->params['tree_elements']->value . '" />
                        </td>
                    </tr>';
                    break;
            }
        } else {
            $this->params['blocs_elements']->unsetData();
            $this->params['blocs_elements']->get($element['id_element'], 'id_bloc = ' . $id_tree . ' AND id_langue = "' . $langue . '" AND id_element');

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
                            <input class="input_big" type="text" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '" value="' . $this->params['blocs_elements']->value . '" />
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
                            <textarea class="textarea_large" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '">' . $this->params['blocs_elements']->value . '</textarea>
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
                            <textarea class="textarea_large" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '">' . $this->params['blocs_elements']->value . '</textarea>
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
                        echo '<option value="' . $tree['id_tree'] . '"' . ($this->params['blocs_elements']->value == $tree['id_tree'] ? ' selected="selected"' : '') . '>' . $tree['title'] . '</option>';
                    }
                    echo '
                            </select>
                        </th>
                    </tr>';
                    break;

                case 'Lien Externe':
                    echo '
                    <tr>
                        <th>
                            <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' :</label>
                        </th>
                    </tr>
                    <tr>
                        <td>
                            <input class="input_big" type="text" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '" value="' . $this->params['blocs_elements']->value . '" />
                        </td>
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
                            <input type="hidden" name="' . $element['slug'] . '_' . $langue . '-old" id="' . $element['slug'] . '_' . $langue . '-old" value="' . $this->params['blocs_elements']->value . '" />
                            &nbsp;&nbsp;<label for="nom_' . $element['slug'] . '_' . $langue . '">Nom du fichier image :</label>
                            <input class="input_large" type="text" name="nom_' . $element['slug'] . '_' . $langue . '" id="nom_' . $element['slug'] . '_' . $langue . '" value="' . $this->params['blocs_elements']->complement . '" />
                        </th>
                    </tr>
                    <tr id="deleteImageElementBloc' . $this->params['blocs_elements']->id . '">';
                    if ($this->params['blocs_elements']->value != '') {
                        if (substr(strtolower(strrchr(basename($this->params['blocs_elements']->value), '.')), 1) == 'swf') {
                            echo '
                                <th class="bas">
                                    <object type="application/x-shockwave-flash" data="' . $this->params['surl'] . '/var/images/' . $this->params['blocs_elements']->value . '" width="400" height="180" style="vertical-align:middle;">
                                        <param name="src" value="' . $this->params['surl'] . '/var/images/' . $this->params['blocs_elements']->value . '" />
                                        <param name="movie" value="' . $this->params['surl'] . '/var/images/' . $this->params['blocs_elements']->value . '" />
                                        <param name="quality" value="high" />
                                        <param name="bgcolor" value="#fff" />
                                        <param name="play" value="true" />
                                        <param name="loop" value="true" />
                                        <param name="scale" value="showall" />
                                        <param name="menu" value="true" />
                                        <param name="align" value="middle" />
                                        <param name="wmode" value="transparent" />
                                        <param name="pluginspage" value="http://www.macromedia.com/go/getflashplayer" />
                                        <param name="type" value="application/x-shockwave-flash" />
                                    </object>
                                    &nbsp;&nbsp; Supprimer le flash&nbsp;&nbsp;
                                    <a onclick="if(confirm(\'Etes vous sur de vouloir supprimer ce flash ?\')){deleteImageElementBloc(' . $this->params['blocs_elements']->id . ',\'' . $element['slug'] . '_' . $langue . '\');return false;}">
                                        <img src="' . $this->params['surl'] . '/images/admin/delete.png" alt="Supprimer" style="vertical-align:middle;" />
                                    </a>
                                </th>';
                        } else {
                            list($width, $height) = @getimagesize($this->params['surl'] . '/var/images/' . $this->params['blocs_elements']->value);
                            echo '
                                <th class="bas">
                                    <a href="' . $this->params['surl'] . '/var/images/' . $this->params['blocs_elements']->value . '" class="thickbox">
                                        <img src="' . $this->params['surl'] . '/var/images/' . $this->params['blocs_elements']->value . '" alt="' . $element['name'] . '"' . ($height > 180 ? ' height="180"' : ($width > 400 ? ' width="400"' : '')) . ' style="vertical-align:middle;" />
                                    </a>
                                    &nbsp;&nbsp; Supprimer l\'image&nbsp;&nbsp;
                                    <a onclick="if(confirm(\'Etes vous sur de vouloir supprimer cette image ?\')){deleteImageElementBloc(' . $this->params['blocs_elements']->id . ',\'' . $element['slug'] . '_' . $langue . '\');return false;}">
                                        <img src="' . $this->params['surl'] . '/images/admin/delete.png" alt="Supprimer" style="vertical-align:middle;" />
                                    </a>
                                </th>';
                        }
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
                            <input type="hidden" name="' . $element['slug'] . '_' . $langue . '-old" id="' . $element['slug'] . '_' . $langue . '-old" value="' . $this->params['blocs_elements']->value . '" />
                            &nbsp;&nbsp;<label for="nom_' . $element['slug'] . '_' . $langue . '">Nom du fichier :</label>
                            <input class="input_large" type="text" name="nom_' . $element['slug'] . '_' . $langue . '" id="nom_' . $element['slug'] . '_' . $langue . '" value="' . $this->params['blocs_elements']->complement . '" />
                        </th>
                    </tr>
                    <tr id="deleteFichierElementBloc' . $this->params['blocs_elements']->id . '">';
                    if ($this->params['blocs_elements']->value != '') {
                        echo '
                            <th class="bas">
                                <label>Fichier actuel</label> :
                                <a href="' . $this->params['surl'] . '/var/fichiers/' . $this->params['blocs_elements']->value . '" target="blank">' . $this->params['surl'] . '/var/fichiers/' . $this->params['blocs_elements']->value . '</a>
                                &nbsp;&nbsp;
                                <a onclick="if(confirm(\'Etes vous sur de vouloir supprimer ce fichier ?\')){deleteFichierElementBloc(' . $this->params['blocs_elements']->id . ',\'' . $element['slug'] . '_' . $langue . '\');return false;}">
                                    <img src="' . $this->params['surl'] . '/images/admin/delete.png" alt="Supprimer" />
                                </a>
                            </th>';
                    } else {
                        echo '
                            <td>&nbsp;</td>';
                    }
                    echo '
                    </tr>';
                    break;

                case 'Fichier Protected':
                    echo '
                    <tr>
                        <th>
                            <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' :</label>
                        </th>
                    </tr>
                    <tr>
                        <th class="bas">
                            <input type="file" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '" />
                            <input type="hidden" name="' . $element['slug'] . '_' . $langue . '-old" id="' . $element['slug'] . '_' . $langue . '-old" value="' . $this->params['blocs_elements']->value . '" />
                            &nbsp;&nbsp;<label for="nom_' . $element['slug'] . '_' . $langue . '">Nom du fichier :</label>
                            <input class="input_large" type="text" name="nom_' . $element['slug'] . '_' . $langue . '" id="nom_' . $element['slug'] . '_' . $langue . '" value="' . $this->params['blocs_elements']->complement . '" />
                        </th>
                    </tr>
                    <tr id="deleteFichierProtectedElementBloc' . $this->params['blocs_elements']->id . '">';
                    if ($this->params['blocs_elements']->value != '') {
                        echo '
                            <th class="bas">
                                <label>Fichier actuel</label> :
                                <a href="' . $this->params['url'] . '/protected/templates/' . $this->params['blocs_elements']->value . '" target="blank">' . $this->params['blocs_elements']->value . '</a>
                                &nbsp;&nbsp;
                                <a onclick="if(confirm(\'Etes vous sur de vouloir supprimer ce fichier ?\')){deleteFichierProtectedElementBloc(' . $this->params['blocs_elements']->id . ',\'' . $element['slug'] . '_' . $langue . '\');return false;}">
                                    <img src="' . $this->params['surl'] . '/images/admin/delete.png" alt="Supprimer" />
                                </a>
                            </th>';
                    } else {
                        echo '
                            <td>&nbsp;</td>';
                    }
                    echo '
                    </tr>';
                    break;

                case 'Date':
                    echo '
                    <tr>
                        <th>
                            <label for="datepik_' . $langue . '">' . $element['name'] . ' :</label>
                        </th>
                    </tr>
                    <tr>
                        <th class="bas">
                            <input class="input_dp" type="text" name="' . $element['slug'] . '_' . $langue . '" id="datepik_' . $langue . '" value="' . $this->params['blocs_elements']->value . '" />
                        </th>
                    </tr>';
                    break;

                case 'Checkbox':
                    echo '
                    <tr>
                        <th class="bas">
                            <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . '</label> :
                            <input type="checkbox" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '" value="1"' . ($this->params['blocs_elements']->value == 1 ? ' checked="checked"' : '') . ' />
                        </th>
                    </tr>';
                    break;

                case 'Boolean':
                    echo '
                    <tr>
                        <th>
                            <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' : </label>
                            <select name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '">
                                <option value="0"' . ($this->params['blocs_elements']->value == 0 ? ' selected' : '') . '>Non</option>
                                <option value="1"' . ($this->params['blocs_elements']->value == 1 ? ' selected' : '') . '>Oui</option>
                            </select>
                        </th>
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
                        echo '<option value="' . $icon . '"' . ($this->params['blocs_elements']->value === $icon ? ' selected' : '') . '>' . $icon . '</option>';
                    }
                    echo '
                            </select>
                            <a href="https://unilend.atlassian.net/wiki/display/PROJ/UI+nouveau+site" target="_blank">Voir la liste</a>
                        </th>
                    </tr>';
                    break;

                default:
                    echo '
                    <tr>
                        <th>
                            <label for="' . $element['slug'] . '_' . $langue . '">' . $element['name'] . ' :</label>
                        </th>
                    </tr>
                    <tr>
                        <td>
                            <input class="input_big" type="text" name="' . $element['slug'] . '_' . $langue . '" id="' . $element['slug'] . '_' . $langue . '" value="' . $this->params['blocs_elements']->value . '" />
                        </td>
                    </tr>';
                    break;
            }
        }
    }

    // Traitement du formulaire des elements en fonction du type d'element
    public function handleFormElement($id_tree, $element, $type = 'tree', $langue = 'fr')
    {
        if ($type == 'tree') {
            // Traitement des differents elements
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
                            $this->params['tree_elements']->id_tree    = $id_tree;
                            $this->params['tree_elements']->id_element = $element['id_element'];
                            $this->params['tree_elements']->id_langue  = $langue;
                            $this->params['tree_elements']->value      = $_POST[$element['slug'] . '_' . $langue];
                            $this->params['tree_elements']->complement = $_POST['nom_' . $element['slug'] . '_' . $langue];
                            $this->params['tree_elements']->status     = 1;
                            $this->params['tree_elements']->create();
                        } else {
                            $this->params['tree_elements']->id_tree    = $id_tree;
                            $this->params['tree_elements']->id_element = $element['id_element'];
                            $this->params['tree_elements']->id_langue  = $langue;
                            $this->params['tree_elements']->value      = '';
                            $this->params['tree_elements']->complement = '';
                            $this->params['tree_elements']->status     = 1;
                            $this->params['tree_elements']->create();
                        }
                    } else {
                        $this->params['tree_elements']->id_tree    = $id_tree;
                        $this->params['tree_elements']->id_element = $element['id_element'];
                        $this->params['tree_elements']->id_langue  = $langue;
                        $this->params['tree_elements']->value      = $_POST[$element['slug'] . '_' . $langue . '-old'];
                        $this->params['tree_elements']->complement = $_POST['nom_' . $element['slug'] . '_' . $langue];
                        $this->params['tree_elements']->status     = 1;
                        $this->params['tree_elements']->create();
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
                            $this->params['tree_elements']->id_tree    = $id_tree;
                            $this->params['tree_elements']->id_element = $element['id_element'];
                            $this->params['tree_elements']->id_langue  = $langue;
                            $this->params['tree_elements']->value      = $_POST[$element['slug'] . '_' . $langue];
                            $this->params['tree_elements']->complement = $_POST['nom_' . $element['slug'] . '_' . $langue];
                            $this->params['tree_elements']->status     = 1;
                            $this->params['tree_elements']->create();
                        } else {
                            $this->params['tree_elements']->id_tree    = $id_tree;
                            $this->params['tree_elements']->id_element = $element['id_element'];
                            $this->params['tree_elements']->id_langue  = $langue;
                            $this->params['tree_elements']->value      = '';
                            $this->params['tree_elements']->complement = '';
                            $this->params['tree_elements']->status     = 1;
                            $this->params['tree_elements']->create();
                        }
                    } else {
                        $this->params['tree_elements']->id_tree    = $id_tree;
                        $this->params['tree_elements']->id_element = $element['id_element'];
                        $this->params['tree_elements']->id_langue  = $langue;
                        $this->params['tree_elements']->value      = $_POST[$element['slug'] . '_' . $langue . '-old'];
                        $this->params['tree_elements']->complement = $_POST['nom_' . $element['slug'] . '_' . $langue];
                        $this->params['tree_elements']->status     = 1;
                        $this->params['tree_elements']->create();
                    }
                    break;

                case 'Fichier Protected':
                    if (isset($_FILES[$element['slug'] . '_' . $langue]) && $_FILES[$element['slug'] . '_' . $langue]['name'] != '') {
                        if ($_POST['nom_' . $element['slug'] . '_' . $langue] != '') {
                            $this->nom_fichier = $this->bdd->generateSlug($_POST['nom_' . $element['slug'] . '_' . $langue]);
                        } else {
                            $this->nom_fichier = '';
                        }

                        $this->params['upload']->setUploadDir($this->params['path'], 'protected/templates/');

                        if ($this->params['upload']->doUpload($element['slug'] . '_' . $langue, $this->nom_fichier)) {
                            $_POST[$element['slug'] . '_' . $langue]   = $this->params['upload']->getName();
                            $this->params['tree_elements']->id_tree    = $id_tree;
                            $this->params['tree_elements']->id_element = $element['id_element'];
                            $this->params['tree_elements']->id_langue  = $langue;
                            $this->params['tree_elements']->value      = $_POST[$element['slug'] . '_' . $langue];
                            $this->params['tree_elements']->complement = $_POST['nom_' . $element['slug'] . '_' . $langue];
                            $this->params['tree_elements']->status     = 1;
                            $this->params['tree_elements']->create();
                        } else {
                            $this->params['tree_elements']->id_tree    = $id_tree;
                            $this->params['tree_elements']->id_element = $element['id_element'];
                            $this->params['tree_elements']->id_langue  = $langue;
                            $this->params['tree_elements']->value      = '';
                            $this->params['tree_elements']->complement = '';
                            $this->params['tree_elements']->status     = 1;
                            $this->params['tree_elements']->create();
                        }
                    } else {
                        $this->params['tree_elements']->id_tree    = $id_tree;
                        $this->params['tree_elements']->id_element = $element['id_element'];
                        $this->params['tree_elements']->id_langue  = $langue;
                        $this->params['tree_elements']->value      = $_POST[$element['slug'] . '_' . $langue . '-old'];
                        $this->params['tree_elements']->complement = $_POST['nom_' . $element['slug'] . '_' . $langue];
                        $this->params['tree_elements']->status     = 1;
                        $this->params['tree_elements']->create();
                    }
                    break;

                default:
                    $this->params['tree_elements']->id_tree    = $id_tree;
                    $this->params['tree_elements']->id_element = $element['id_element'];
                    $this->params['tree_elements']->id_langue  = $langue;
                    $this->params['tree_elements']->value      = $_POST[$element['slug'] . '_' . $langue];
                    $this->params['tree_elements']->complement = '';
                    $this->params['tree_elements']->status     = 1;
                    $this->params['tree_elements']->create();
                    break;
            }
        } else {
            // Traitement des differents elements
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
                            $_POST[$element['slug'] . '_' . $langue]    = $this->params['upload']->getName();
                            $this->params['blocs_elements']->id_bloc    = $id_tree;
                            $this->params['blocs_elements']->id_element = $element['id_element'];
                            $this->params['blocs_elements']->id_langue  = $langue;
                            $this->params['blocs_elements']->value      = $_POST[$element['slug'] . '_' . $langue];
                            $this->params['blocs_elements']->complement = $_POST['nom_' . $element['slug'] . '_' . $langue];
                            $this->params['blocs_elements']->status     = 1;
                            $this->params['blocs_elements']->create();
                        } else {
                            $this->params['blocs_elements']->id_bloc    = $id_tree;
                            $this->params['blocs_elements']->id_element = $element['id_element'];
                            $this->params['blocs_elements']->id_langue  = $langue;
                            $this->params['blocs_elements']->value      = '';
                            $this->params['blocs_elements']->complement = '';
                            $this->params['blocs_elements']->status     = 1;
                            $this->params['blocs_elements']->create();
                        }
                    } else {
                        $this->params['blocs_elements']->id_bloc    = $id_tree;
                        $this->params['blocs_elements']->id_element = $element['id_element'];
                        $this->params['blocs_elements']->id_langue  = $langue;
                        $this->params['blocs_elements']->value      = $_POST[$element['slug'] . '_' . $langue . '-old'];
                        $this->params['blocs_elements']->complement = $_POST['nom_' . $element['slug'] . '_' . $langue];
                        $this->params['blocs_elements']->status     = 1;
                        $this->params['blocs_elements']->create();
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
                            $_POST[$element['slug'] . '_' . $langue]    = $this->params['upload']->getName();
                            $this->params['blocs_elements']->id_bloc    = $id_tree;
                            $this->params['blocs_elements']->id_element = $element['id_element'];
                            $this->params['blocs_elements']->id_langue  = $langue;
                            $this->params['blocs_elements']->value      = $_POST[$element['slug'] . '_' . $langue];
                            $this->params['blocs_elements']->complement = $_POST['nom_' . $element['slug'] . '_' . $langue];
                            $this->params['blocs_elements']->status     = 1;
                            $this->params['blocs_elements']->create();
                        } else {
                            $this->params['blocs_elements']->id_bloc    = $id_tree;
                            $this->params['blocs_elements']->id_element = $element['id_element'];
                            $this->params['blocs_elements']->id_langue  = $langue;
                            $this->params['blocs_elements']->value      = '';
                            $this->params['blocs_elements']->complement = '';
                            $this->params['blocs_elements']->status     = 1;
                            $this->params['blocs_elements']->create();
                        }
                    } else {
                        $this->params['blocs_elements']->id_bloc    = $id_tree;
                        $this->params['blocs_elements']->id_element = $element['id_element'];
                        $this->params['blocs_elements']->id_langue  = $langue;
                        $this->params['blocs_elements']->value      = $_POST[$element['slug'] . '_' . $langue . '-old'];
                        $this->params['blocs_elements']->complement = $_POST['nom_' . $element['slug'] . '_' . $langue];
                        $this->params['blocs_elements']->status     = 1;
                        $this->params['blocs_elements']->create();
                    }
                    break;

                case 'Fichier Protected':
                    if (isset($_FILES[$element['slug'] . '_' . $langue]) && $_FILES[$element['slug'] . '_' . $langue]['name'] != '') {
                        if ($_POST['nom_' . $element['slug'] . '_' . $langue] != '') {
                            $this->nom_fichier = $this->bdd->generateSlug($_POST['nom_' . $element['slug'] . '_' . $langue]);
                        } else {
                            $this->nom_fichier = '';
                        }

                        $this->params['upload']->setUploadDir($this->params['path'], 'protected/templates/');

                        if ($this->params['upload']->doUpload($element['slug'] . '_' . $langue, $this->nom_fichier)) {
                            $_POST[$element['slug'] . '_' . $langue]    = $this->params['upload']->getName();
                            $this->params['blocs_elements']->id_bloc    = $id_tree;
                            $this->params['blocs_elements']->id_element = $element['id_element'];
                            $this->params['blocs_elements']->id_langue  = $langue;
                            $this->params['blocs_elements']->value      = $_POST[$element['slug'] . '_' . $langue];
                            $this->params['blocs_elements']->complement = $_POST['nom_' . $element['slug'] . '_' . $langue];
                            $this->params['blocs_elements']->status     = 1;
                            $this->params['blocs_elements']->create();
                        } else {
                            $this->params['blocs_elements']->id_bloc    = $id_tree;
                            $this->params['blocs_elements']->id_element = $element['id_element'];
                            $this->params['blocs_elements']->id_langue  = $langue;
                            $this->params['blocs_elements']->value      = '';
                            $this->params['blocs_elements']->complement = '';
                            $this->params['blocs_elements']->status     = 1;
                            $this->params['blocs_elements']->create();
                        }
                    } else {
                        $this->params['blocs_elements']->id_bloc    = $id_tree;
                        $this->params['blocs_elements']->id_element = $element['id_element'];
                        $this->params['blocs_elements']->id_langue  = $langue;
                        $this->params['blocs_elements']->value      = $_POST[$element['slug'] . '_' . $langue . '-old'];
                        $this->params['blocs_elements']->complement = $_POST['nom_' . $element['slug'] . '_' . $langue];
                        $this->params['blocs_elements']->status     = 1;
                        $this->params['blocs_elements']->create();
                    }
                    break;

                default:
                    $this->params['blocs_elements']->id_bloc    = $id_tree;
                    $this->params['blocs_elements']->id_element = $element['id_element'];
                    $this->params['blocs_elements']->id_langue  = $langue;
                    $this->params['blocs_elements']->value      = $_POST[$element['slug'] . '_' . $langue];
                    $this->params['blocs_elements']->complement = '';
                    $this->params['blocs_elements']->status     = 1;
                    $this->params['blocs_elements']->create();
                    break;
            }
        }
    }

    // Recuperation de l'id max pour la création d'une page (clé primaire multiple, pas d'auto incremente)
    public function getMaxId()
    {
        $sql    = 'SELECT MAX(id_tree) as id FROM tree';
        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    // Recuperation des enfants et construction html de l'arbo
    // $type  : Si 0 = Arbo principale
    //            Si 1 = Arbo preteur
    //            Si 2 = Arbo emprunteur
    public function getChilds($id_parent, $langue = 'fr', $arbre, $type = 0)
    {
        $sSense     = (self::PRESS_SPEAKS == (int) $id_parent) ? 'DESC' : 'ASC';
        $lRubriques = $this->select('id_parent = ' . $id_parent . ' AND id_langue = "' . $langue . '" AND arbo = ' . $type, 'ordre ' . $sSense);

        // Creation de l'arbo
        foreach ($lRubriques as $rub) {
            // On recupere la premiere position pour voir si on affiche la fleche up
            if ($rub['ordre'] == $this->getFirstPosition($rub['id_parent'], $type)) {
                $up = '';
            } else {
                $up = '<a href="' . $this->params['url'] . '/tree/up/' . $rub['id_tree'] . '" title="Up"><img src="' . $this->params['surl'] . '/images/admin/up.png" alt="Up" /></a>';
            }
            // On recupere la derniere position pour voir si on affiche la fleche down
            if ($rub['ordre'] == $this->getLastPosition($rub['id_parent'], $type)) {
                $down = '';
            } else {
                $down = '
                <a href="' . $this->params['url'] . '/tree/down/' . $rub['id_tree'] . '" title="Down"><img src="' . $this->params['surl'] . '/images/admin/down.png" alt="Down" /></a>';
            }

            // On tronque les noms trop longs pour l'affichage dans le menu
            if (strlen($rub['menu_title']) > 60) {
                $rub['menu_title'] = substr($rub['menu_title'], 0, 60) . '...';
            }

            // Mise en gras des principales rubriques (id_parent = 1)
            if ($rub['id_parent'] == 1) {
                $b  = '<strong>';
                $sb = '</strong>';
            } else {
                $b  = '';
                $sb = '';
            }

            // On check si ya encore un niveau en dessous pour afficher un icone de dossier ou de fichier
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

            // Constructions des edit,del et add
            $edit = '
            <a href="' . $this->params['url'] . '/tree/edit/' . $rub['id_tree'] . '" title="Edit"><img src="' . $this->params['surl'] . '/images/admin/edit.png" alt="Edit" /></a>';

            $add = '
            <a href="' . $this->params['url'] . '/tree/add/' . $rub['id_tree'] . '" title="Add"><img src="' . $this->params['surl'] . '/images/admin/add.png" alt="Add" /></a>';

            $del = '
            <a href="' . $this->params['url'] . '/tree/delete/' . $rub['id_tree'] . '" onclick="return confirm(\'Etes vous sur de vouloir supprimer cette page et toutes les pages qui en dépendent ?\')" title="Delete"><img src="' . $this->params['surl'] . '/images/admin/delete.png" alt="Delete" /></a>';

            // Construction de l'arbre
            $this->arbre .= '
            <li>
                <span class="' . $class . '">' . $b . '' . $rub['menu_title'] . '' . $sb . '' . $up . '' . $down . '' . $edit . '' . $add . '' . ($id_parent == 1 && $type != 0 ? '' : $del) . '</span>';

            if ($this->counter('id_parent = ' . $rub['id_tree']) > 0) {
                $this->arbre .= '
                <ul>';

                $this->getChilds($rub['id_tree'], $langue, $this->arbre, $type);

                $this->arbre .= '
                </ul>';
            }

            $this->arbre .= '
            </li>';
        }
    }

    // Recuperation et affichage de l'arbo du site
    public function getArbo($id = '1', $langue = 'fr', $typeArbo = 0)
    {
        //en fonction du type d'arbo demandé on appelle la fonction appropriée

        $edit = '
        <a href="' . $this->params['url'] . '/tree/edit/' . $id . '" title="Edit"><img src="' . $this->params['surl'] . '/images/admin/edit.png" alt="Edit" /></a>';

        $add = '
        <a href="' . $this->params['url'] . '/tree/add/' . $id . '" title="Add"><img src="' . $this->params['surl'] . '/images/admin/add.png" border="0" alt="Add" /></a>';


        $this->arbre = '<img src="' . $this->params['surl'] . '/images/admin/home.png" border="0" alt="Home" />';

        // Si il s'agit de l'arbo de izinoa on affiche les options "editer + ajouter" à la maison
        if ($typeArbo == 0) {
            $this->arbre .= $edit . '' . $add;
        }

        $this->arbre .= '<ul id="browser" class="filetree">';

        $this->getChilds($id, $langue, $this->arbre, $typeArbo);

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


    public function search($search, $includeProjects = false, $langue = 'fr')
    {
        $result = [];
        $search = $this->bdd->escape_string($search);
        $sql    = '
            SELECT t.slug AS slug,
              t.title AS title ,
              t.id_template AS id_template ,
              t.id_parent AS id_parent,
              te.value AS value
            FROM tree_elements te
            LEFT JOIN tree t ON t.id_tree = te.id_tree
            LEFT JOIN elements e ON e.id_element  = te.id_element
            WHERE t.status = 1
              AND t.id_langue = "' . $langue . '"
              AND lcase(te.value) LIKE "%' . strtolower($search) . '%"
              AND t.id_tree NOT IN(16, 130)
            GROUP BY t.slug
            ORDER BY t.ordre ASC';

        $resultat = $this->bdd->query($sql);

        if ($this->bdd->num_rows($resultat)) {
            $result['cms'] = [];

            while ($record = $this->bdd->fetch_assoc($resultat)) {
                $replace  = strip_tags($record['value']);
                $mystring = strtolower($replace);
                $findme   = strtolower($search);
                $pos      = strpos($mystring, $findme);

                if ($pos !== false) {
                    $result['cms'][] = [
                        'title' => $record['title'],
                        'slug'  => $record['slug']
                    ];
                }
            }

            usort($result['cms'], function($firstElement, $secondElement) {
                return strcmp($firstElement['title'], $secondElement['title']);
            });
        }

        if ($includeProjects) {
            $sql = '
                SELECT p.slug AS slug,
                  p.title AS title,
                  (SELECT ps.status FROM projects_status ps LEFT JOIN projects_status_history psh ON (ps.id_project_status = psh.id_project_status) WHERE psh.id_project = p.id_project ORDER BY psh.id_project_status_history DESC LIMIT 1) AS status
                FROM projects p
                WHERE p.display = 0
                  AND p.title LIKE "%' . $search . '%"
                HAVING status >= ' . \projects_status::EN_FUNDING . '
                ORDER BY p.title ASC';

            $resultatProjects = $this->bdd->query($sql);

            if ($this->bdd->num_rows($resultatProjects)) {
                $result['projects'] = [];

                while ($recordProjects = $this->bdd->fetch_assoc($resultatProjects)) {
                    $result['projects'][] = [
                        'title' => $recordProjects['title'],
                        'slug'  => 'projects/detail/' . $recordProjects['slug']
                    ];
                }

                usort($result['projects'], function($firstElement, $secondElement) {
                    return strcmp($firstElement['title'], $secondElement['title']);
                });
            }
        }

        return $result;
    }
}

