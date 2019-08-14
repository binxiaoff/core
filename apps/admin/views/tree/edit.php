<script type="text/javascript">
    $(function() {
        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#datepik_fr").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->url ?>/images/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
        });
    });
</script>
<script type="text/javascript" src="<?= $this->url ?>/ckeditor/ckeditor.js"></script>
<div id="contenu">
    <form method="post" name="edit_tree" id="edit_tree" enctype="multipart/form-data">
        <input type="hidden" name="lng_encours" id="lng_encours" value="fr" />
        <input type="hidden" name="id_parent" id="id_parent" value="<?= $this->tree->id_parent ?>" />
        <?php
            $this->tree->get($this->params[0]);

            // Recuperation de l'id du template s'il est passé en param
            if (isset($this->params[2]) && $this->params[2] != '') {
                $this->tree->id_template = $this->params[2];
            }

            // Recuperation des elements du template
            $this->lElements = $this->elements->select('status > 0 AND id_template != 0 AND id_template = ' . $this->tree->id_template, 'ordre ASC');
        ?>
        <div id="langue_fr">
            <fieldset>
                <input type="hidden" name="id_tree" id="id_tree" value="<?= $this->tree->id_tree ?>" />
                <div class="gauche">
                    <h1>Modification d'une page</h1>
                    <table class="form">
                        <tr>
                            <th><label for="id_parent_fr">Rubrique parente :</label></th>
                            <td>
                                <select name="id_parent_fr" id="id_parent_fr" onchange="setNewIdParent(this.value);" class="select">
                                    <option value="0">Choisir une rubrique</option>
                                    <?php foreach($this->lTree as $tree) : ?>
                                        <option value="<?= $tree['id_tree'] ?>"<?= ($this->tree->id_parent == $tree['id_tree'] ? ' selected="selected"' : '') ?>><?= $tree['title'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="id_template_fr">Template :</label></th>
                            <td>
                                <select name="id_template_fr" id="id_template_fr" onchange="if(confirm('Voulez-vous vraiment changer ce template ?\nLe contenu existant de cette page sera d\351finitivement \351ffac\351.')){ window.location.href = '<?= $this->url ?>/tree/edit/<?=$this->tree->id_tree?>/fr/' + this.value; } else { this.value = '<?= $this->tree->id_template ?>'; }" class="select">
                                    <option value="0">Choisir un template</option>
                                    <?php foreach($this->lTemplate as $template) : ?>
                                        <option value="<?= $template['id_template'] ?>"<?= ($this->tree->id_template == $template['id_template'] ? ' selected="selected"' : '') ?>><?= $template['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="title_fr">Titre de la page :</label></th>
                            <td><input type="text" name="title_fr" id="title_fr" value="<?= $this->tree->title ?>" class="input_large" /></td>
                        </tr>
                        <tr>
                            <th><label for="menu_title_fr">Titre du menu :</label></th>
                            <td><input type="text" name="menu_title_fr" id="menu_title_fr" value="<?= $this->tree->menu_title ?>" class="input_large" /></td>
                        </tr>
                        <tr>
                            <th><label for="slug_fr">Lien permanent :</label></td>
                            <td><input type="text" name="slug_fr" id="slug_fr" value="<?= $this->tree->slug ?>" class="input_large" /></th>
                        </tr>
                        <tr>
                            <th><label for="img_menu_fr">Image menu :</label></th>
                            <td>
                                <input type="file" name="img_menu_fr" id="img_menu_fr" />
                                <input type="hidden" name="img_menu_fr-old" id="img_menu_fr-old" value="<?= $this->tree->img_menu ?>" />
                            </td>
                        </tr>
                        <?php if ($this->tree->img_menu != '') : ?>
                            <?php list($width,$height) = @getimagesize($this->furl.'/var/images/' . $this->tree->img_menu); ?>
                            <tr id="deleteImageTree_fr">
                                <th>
                                    <label>
                                        Image actuelle&nbsp;&nbsp;
                                        <a onclick="if(confirm('Etes vous sur de vouloir supprimer cette image ?')){deleteImageTree(<?= $this->tree->id_tree ?>,'fr');return false;}">
                                            <img src="<?= $this->url ?>/images/delete.png" alt="Supprimer" />
                                        </a>
                                    </label>
                                </th>
                                <td>
                                    <a href="<?= $this->furl ?>/var/images/<?= $this->tree->img_menu ?>" class="thickbox">
                                        <img src="<?= $this->furl ?>/var/images/<?= $this->tree->img_menu ?>" alt="<?= $this->tree->menu_title ?>"<?= ($height > 150 ? ' height="150"' : ($width > 200 ? ' width="200"' : '')) ?> />
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </table>
                    <br /><br />
                    <h1>Statuts de la page</h1>
                    <table class="form">
                        <tr>
                            <th><label>Statut de la page :</label></th>
                            <td>
                                <input type="radio" value="1" id="status1_fr" class="radio" name="status_fr"<?= ($this->tree->status == 1 ? ' checked="checked"' : '') ?> />
                                <label for="status1_fr" class="label_radio">En ligne</label>
                            </td>
                            <td>
                                <input type="radio" value="0" id="status0_fr" class="radio" name="status_fr"<?= ($this->tree->status == 0 ? ' checked="checked"' : '') ?> />
                                <label for="status0_fr" class="label_radio">Hors ligne</label>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Visible dans le Sitemap du site :</label></th>
                            <td>
                                <input type="radio" value="1" id="status_menu1_fr" name="status_menu_fr" class="radio"<?= ($this->tree->status_menu == 1 ? ' checked="checked"' : '') ?> />
                                <label for="status_menu1_fr" class="label_radio">Visible</label>
                            </td>
                            <td>
                                <input type="radio" value="0" id="status_menu0_fr" name="status_menu_fr" class="radio"<?= ($this->tree->status_menu == 0 ? ' checked="checked"' : '') ?> />
                                <label for="status_menu0_fr" class="label_radio">Invisible</label>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Visibilité de la page :</label></th>
                            <td width="80px">
                                <input type="radio" value="1" id="prive1_fr" name="prive_fr" class="radio"<?= ($this->tree->prive == 1 ? ' checked="checked"' : '') ?> />
                                <label for="prive1_fr" class="label_radio">Privée</label>
                            </td>
                            <td width="170px">
                                <input type="radio" value="0" id="prive0_fr" name="prive_fr" class="radio"<?= ($this->tree->prive == 0 ? ' checked="checked"' : '') ?> />
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
                            <td><input type="text" name="meta_title_fr" id="meta_title_fr" value="<?= $this->tree->meta_title ?>" class="input_large" /></td>
                        </tr>
                        <tr>
                            <th><label for="meta_description_fr">Description :</label></th>
                            <td><textarea name="meta_description_fr" id="meta_description_fr" class="textarea"><?= $this->tree->meta_description ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="meta_keywords_fr">Mots clés :</label></th>
                            <td><textarea name="meta_keywords_fr" id="meta_keywords_fr" class="textarea"><?= $this->tree->meta_keywords ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label>Indexation de la page :</label></th>
                            <td>
                                <input type="radio" value="1" id="indexation1_fr" name="indexation_fr" class="radio"<?= ($this->tree->indexation == 1 ? ' checked="checked"' : '') ?> />
                                <label for="indexation1_fr" class="label_radio">Oui</label>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                <input type="radio" value="0" id="indexation0_fr" name="indexation_fr" class="radio"<?= ($this->tree->indexation == 0 ? ' checked="checked"' : '') ?> />
                                <label for="indexation0_fr" class="label_radio">Non</label>
                            </td>
                        </tr>
                    </table>
                </div>
                <table class="large">
                    <tr>
                        <td>
                            <input type="hidden" name="form_edit_tree" id="form_edit_tree" />
                            <button type="submit" class="btn-primary">Valider la modification de la page</button>
                        </td>
                    </tr>
                </table>

                <?php if (count($this->lElements) > 0) : ?>
                    <br />
                    <h1>Eléments du template</h1>
                    <table class="large">
                        <?php
                            foreach ($this->lElements as $element) {
                                $this->tree->displayFormElement($this->tree->id_tree, $element, 'tree', 'fr');
                            }
                        ?>
                    </table>
                    <table class="large">
                        <tr>
                            <td colspan="2">
                                <input type="hidden" name="form_edit_tree" id="form_edit_tree" />
                                <button type="submit" onclick="document.getElementById('edit_tree').action = '';document.getElementById('edit_tree').target = '_self';">Valider la modification de la page</button>
                            </td>
                        </tr>
                    </table>
                <?php endif; ?>
            </fieldset>
        </div>
    </form>
</div>
