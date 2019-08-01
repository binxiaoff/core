<div id="contenu">
    <form method="post" name="add_tree" id="add_tree" enctype="multipart/form-data">
        <input type="hidden" name="lng_encours" id="lng_encours" value="fr"/>
        <input type="hidden" name="id_parent" id="id_parent" value="<?= (isset($this->params[0]) ? $this->params[0] : '') ?>"/>
        <div id="langue_fr">
            <fieldset>
                <div class="gauche">
                    <h1>Ajout d'une page</h1>
                    <table class="form">
                        <tr>
                            <th><label for="id_parent_fr">Rubrique parente :</label></th>
                            <td>
                                <select name="id_parent_fr" id="id_parent_fr" onchange="setNewIdParent(this.value);" class="select">
                                    <option value="0">Choisir une rubrique</option>
                                    <?php foreach ($this->lTree as $tree) : ?>
                                        <option value="<?= $tree['id_tree'] ?>"<?= (isset($this->params[0]) && $this->params[0] == $tree['id_tree'] ? ' selected="selected"' : '') ?>><?= $tree['title'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="id_template_fr">Template :</label></th>
                            <td>
                                <select name="id_template_fr" id="id_template_fr" class="select">
                                    <option value="0">Choisir un template</option>
                                    <?php foreach ($this->lTemplate as $template) : ?>
                                        <option value="<?= $template['id_template'] ?>"><?= $template['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="title_fr">Titre de la page :</label></th>
                            <td>
                                <input type="text" name="title_fr" id="title_fr" class="input_large"/>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="menu_title_fr">Titre du menu :</label></th>
                            <td>
                                <input type="text" name="menu_title_fr" id="menu_title_fr" class="input_large"/>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="slug_fr">Lien permanent :</label></th>
                            <td>
                                <input type="text" name="slug_fr" id="slug_fr" class="input_large"/>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="img_menu_fr">Image menu :</label></th>
                            <td><input type="file" name="img_menu_fr" id="img_menu_fr"/></td>
                        </tr>
                    </table>
                    <br/><br/>
                    <h1>Statuts de la page</h1>
                    <table class="form">
                        <tr>
                            <th><label>Statut de la page :</label></th>
                            <td>
                                <input type="radio" value="1" id="status1_fr" class="radio" name="status_fr" checked="checked"/>
                                <label for="status1_fr" class="label_radio">En ligne</label>
                            </td>
                            <td>
                                <input type="radio" value="0" id="status0_fr" class="radio" name="status_fr" checked="checked"/>
                                <label for="status0_fr" class="label_radio">Hors ligne</label>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Statut dans la navigation :</label></th>
                            <td>
                                <input type="radio" value="1" id="status_menu1_fr" name="status_menu_fr" class="radio" checked="checked"/>
                                <label for="status_menu1_fr" class="label_radio">Visible</label>
                            </td>
                            <td>
                                <input type="radio" value="0" id="status_menu0_fr" name="status_menu_fr" class="radio"/>
                                <label for="status_menu0_fr" class="label_radio">Invisible</label>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Visibilité de la page :</label></th>
                            <td width="80px">
                                <input type="radio" value="1" id="prive1_fr" name="prive_fr" class="radio"/>
                                <label for="prive1_fr" class="label_radio">Privée</label>
                            </td>
                            <td width="170px">
                                <input type="radio" value="0" id="prive0_fr" name="prive_fr" class="radio" checked="checked"/>
                                <label for="prive0_fr" class="label_radio">Publique</label>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="droite">
                    <h1>Eléments de référencement</h1>
                    <table class="form">
                        <tr>
                            <th><label for="meta_title_fr">Titre de la page :</label></th>
                            <td>
                                <input type="text" name="meta_title_fr" id="meta_title_fr" class="input_large"/>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="meta_description_fr">Description :</label></th>
                            <td>
                                <textarea name="meta_description_fr" id="meta_description_fr" class="textarea"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="meta_keywords_fr">Mots clés :</label></th>
                            <td>
                                <textarea name="meta_keywords_fr" id="meta_keywords_fr" class="textarea"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Indexation de la page :</label></th>
                            <td>
                                <input type="radio" value="1" id="indexation1_fr" name="indexation_fr" class="radio" checked="checked"/>
                                <label for="indexation1_fr" class="label_radio">Oui</label>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                <input type="radio" value="0" id="indexation0_fr" name="indexation_fr" class="radio"/>
                                <label for="indexation0_fr" class="label_radio">Non</label>
                            </td>
                        </tr>
                    </table>
                </div>
            </fieldset>
        </div>
        <table class="large">
            <tr>
                <td>
                    <input type="hidden" name="form_add_tree" id="form_add_tree"/>
                    <button type="submit" class="btn-primary">Valider l'ajout de la page</button>
                </td>
            </tr>
        </table>
    </form>
</div>
