<script type="text/javascript">
    $(function() {
        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
        <?php foreach ($this->lLangues as $key => $lng) : ?>
        $("#datepik_<?= $key ?>").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
        });
        <?php endforeach; ?>

        <?php if (isset($_SESSION['freeow'])) : ?>
            var title = "<?= $_SESSION['freeow']['title'] ?>",
                message = "<?= $_SESSION['freeow']['message'] ?>",
                opts = {};

            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
            <?php unset($_SESSION['freeow']); ?>
        <?php endif; ?>
    });
</script>
<script type="text/javascript" src="<?= $this->url ?>/ckeditor/ckeditor.js"></script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <?php if (count($this->lLangues) > 1) : ?>
        <div id="onglets">
            <?php foreach ($this->lLangues as $key => $lng) :?>
                <a onclick="changeOngletLangue('<?= $key ?>');" id="lien_<?= $key ?>" title="<?= $lng ?>" class="<?= ($key == $this->language ? 'active' : '') ?>"><?= $lng ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" name="edit_tree" id="edit_tree" enctype="multipart/form-data">
        <input type="hidden" name="lng_encours" id="lng_encours" value="<?= $this->dLanguage ?>" />
        <input type="hidden" name="id_parent" id="id_parent" value="<?= $this->tree->id_parent ?>" />
        <?php foreach ($this->lLangues as $key => $lng) :
            $this->tree->get(array('id_tree' => $this->params[0], 'id_langue' => $key));

            // Recuperation de l'id du template s'il est passé en param
            if (isset($this->params[2]) && $this->params[2] != '') {
                $this->tree->id_template = $this->params[2];
            }

            // Recuperation des elements du template
            $this->lElements = $this->elements->select('status > 0 AND id_template != 0 AND id_template = ' . $this->tree->id_template, 'ordre ASC');
        ?>
            <div id="langue_<?= $key ?>"<?= (isset($this->params[2]) && $this->params[2] != '' ? ($this->params[1] != $key ? ' style="display:none;"' : '') : ($key != $this->dLanguage ? ' style="display:none;"' : '')) ?>>
                <fieldset>
                    <input type="hidden" name="id_tree" id="id_tree" value="<?= $this->tree->id_tree ?>" />
                    <div class="gauche">
                        <h1>Modification d'une page</h1>
                        <table class="form">
                            <tr>
                                <th><label for="id_parent_<?= $key ?>">Rubrique parente :</label></th>
                                <td>
                                    <select name="id_parent_<?= $key ?>" id="id_parent_<?= $key ?>" onchange="setNewIdParent(this.value);" class="select">
                                        <option value="0">Choisir une rubrique</option>
                                        <?php foreach($this->lTree as $tree) : ?>
                                            <option value="<?= $tree['id_tree'] ?>"<?= ($this->tree->id_parent == $tree['id_tree'] ? ' selected="selected"' : '') ?>><?= $tree['title'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="id_template_<?= $key ?>">Template :</label></th>
                                <td>
                                    <select name="id_template_<?= $key ?>" id="id_template_<?= $key ?>" onchange="if(confirm('Voulez-vous vraiment changer ce template ?\nLe contenu existant de cette page sera d\351finitivement \351ffac\351.')){ window.location.href = '<?= $this->lurl ?>/tree/edit/<?=$this->tree->id_tree?>/<?= $key ?>/' + this.value; } else { this.value = '<?= $this->tree->id_template ?>'; }" class="select">
                                        <option value="0">Choisir un template</option>
                                        <?php foreach($this->lTemplate as $template) : ?>
                                            <option value="<?= $template['id_template'] ?>"<?= ($this->tree->id_template == $template['id_template'] ? ' selected="selected"' : '') ?>><?= $template['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="title_<?= $key ?>">Titre de la page :</label></th>
                                <td><input type="text" name="title_<?= $key ?>" id="title_<?= $key ?>" value="<?= $this->tree->title ?>" class="input_large" /></td>
                            </tr>
                            <tr>
                                <th><label for="menu_title_<?= $key ?>">Titre du menu :</label></th>
                                <td><input type="text" name="menu_title_<?= $key ?>" id="menu_title_<?= $key ?>" value="<?= $this->tree->menu_title ?>" class="input_large" /></td>
                            </tr>
                            <tr>
                                <th><label for="slug_<?= $key ?>">Lien permanent :</label></td>
                                <td><input type="text" name="slug_<?= $key ?>" id="slug_<?= $key ?>" value="<?= $this->tree->slug ?>" class="input_large" /></th>
                            </tr>
                            <tr>
                                <th><label for="img_menu_<?= $key ?>">Image menu :</label></th>
                                <td>
                                    <input type="file" name="img_menu_<?= $key ?>" id="img_menu_<?= $key ?>" />
                                    <input type="hidden" name="img_menu_<?= $key ?>-old" id="img_menu_<?= $key ?>-old" value="<?= $this->tree->img_menu ?>" />
                                </td>
                            </tr>
                            <?php if ($this->tree->img_menu != '') : ?>
                                <?php list($width,$height) = @getimagesize($this->surl.'/var/images/'.$this->tree->img_menu); ?>
                                <tr id="deleteImageTree_<?= $key ?>">
                                    <th>
                                        <label>
                                            Image actuelle&nbsp;&nbsp;
                                            <a onclick="if(confirm('Etes vous sur de vouloir supprimer cette image ?')){deleteImageTree(<?= $this->tree->id_tree ?>,'<?= $key ?>');return false;}">
                                                <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Supprimer" />
                                            </a>
                                        </label>
                                    </th>
                                    <td>
                                        <a href="<?= $this->surl ?>/var/images/<?= $this->tree->img_menu ?>" class="thickbox">
                                            <img src="<?= $this->surl ?>/var/images/<?= $this->tree->img_menu ?>" alt="<?= $this->tree->menu_title ?>"<?= ($height > 150 ? ' height="150"' : ($width > 200 ? ' width="200"' : '')) ?> />
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
                                    <input type="radio" value="1" id="status1_<?= $key ?>" class="radio" name="status_<?= $key ?>"<?= ($this->tree->status == 1 ? ' checked="checked"' : '') ?> />
                                    <label for="status1_<?= $key ?>" class="label_radio">En ligne</label>
                                </td>
                                <td>
                                    <input type="radio" value="0" id="status0_<?= $key ?>" class="radio" name="status_<?= $key ?>"<?= ($this->tree->status == 0 ? ' checked="checked"' : '') ?> />
                                    <label for="status0_<?= $key ?>" class="label_radio">Hors ligne</label>
                                </td>
                            </tr>
                            <tr>
                                <th><label>Visible dans le Sitemap du site :</label></th>
                                <td>
                                    <input type="radio" value="1" id="status_menu1_<?= $key ?>" name="status_menu_<?= $key ?>" class="radio"<?= ($this->tree->status_menu == 1 ? ' checked="checked"' : '') ?> />
                                    <label for="status_menu1_<?= $key ?>" class="label_radio">Visible</label>
                                </td>
                                <td>
                                    <input type="radio" value="0" id="status_menu0_<?= $key ?>" name="status_menu_<?= $key ?>" class="radio"<?= ($this->tree->status_menu == 0 ? ' checked="checked"' : '') ?> />
                                    <label for="status_menu0_<?= $key ?>" class="label_radio">Invisible</label>
                                </td>
                            </tr>
                            <tr>
                                <th><label>Visibilité de la page :</label></th>
                                <td width="80px">
                                    <input type="radio" value="1" id="prive1_<?= $key ?>" name="prive_<?= $key ?>" class="radio"<?= ($this->tree->prive == 1 ? ' checked="checked"' : '') ?> />
                                    <label for="prive1_<?= $key ?>" class="label_radio">Privée</label>
                                </td>
                                <td width="170px">
                                    <input type="radio" value="0" id="prive0_<?= $key ?>" name="prive_<?= $key ?>" class="radio"<?= ($this->tree->prive == 0 ? ' checked="checked"' : '') ?> />
                                    <label for="prive0_<?= $key ?>" class="label_radio">Publique</label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="droite">
                        <h1>Eléments de référencement</h1>
                        <table class="form">
                            <tr>
                                <th><label for="meta_title_<?= $key ?>">Titre de la page :</label></th>
                                <td><input type="text" name="meta_title_<?= $key ?>" id="meta_title_<?= $key ?>" value="<?= $this->tree->meta_title ?>" class="input_large" /></td>
                            </tr>
                            <tr>
                                <th><label for="meta_description_<?= $key ?>">Description :</label></th>
                                <td><textarea name="meta_description_<?= $key ?>" id="meta_description_<?= $key ?>" class="textarea"><?= $this->tree->meta_description ?></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="meta_keywords_<?= $key ?>">Mots clés :</label></th>
                                <td><textarea name="meta_keywords_<?= $key ?>" id="meta_keywords_<?= $key ?>" class="textarea"><?= $this->tree->meta_keywords ?></textarea></td>
                            </tr>
                            <tr>
                                <th><label>Indexation de la page :</label></th>
                                <td>
                                    <input type="radio" value="1" id="indexation1_<?= $key ?>" name="indexation_<?= $key ?>" class="radio"<?= ($this->tree->indexation == 1 ? ' checked="checked"' : '') ?> />
                                    <label for="indexation1_<?= $key ?>" class="label_radio">Oui</label>
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <input type="radio" value="0" id="indexation0_<?= $key ?>" name="indexation_<?= $key ?>" class="radio"<?= ($this->tree->indexation == 0 ? ' checked="checked"' : '') ?> />
                                    <label for="indexation0_<?= $key ?>" class="label_radio">Non</label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <table class="large">
                        <tr>
                            <td>
                                <input type="hidden" name="form_edit_tree" id="form_edit_tree" />
                                <input type="submit" value="Valider la modification de la page" name="send_tree" id="send_tree" class="btn" />
                            </td>
                        </tr>
                    </table>

                    <?php if (count($this->lElements) > 0) : ?>
                        <br />
                        <h1>Eléments du template</h1>
                        <table class="large">
                            <?php
                                foreach ($this->lElements as $element) {
                                    $this->tree->displayFormElement($this->tree->id_tree, $element, 'tree', $key);
                                }
                            ?>
                        </table>
                        <table class="large">
                            <tr>
                                <td colspan="2">
                                    <input type="hidden" name="form_edit_tree" id="form_edit_tree" />
                                    <input onclick="document.getElementById('edit_tree').action = '';document.getElementById('edit_tree').target = '_self';" type="submit" value="Valider la modification de la page" name="send_tree" id="send_tree" class="btn" />
                                </td>
                            </tr>
                        </table>
                    <?php endif; ?>
                </fieldset>
            </div>
        <?php endforeach; ?>
    </form>
</div>
