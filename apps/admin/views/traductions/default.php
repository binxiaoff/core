<?php if (isset($_SESSION['freeow'])) : ?>
    <script type="text/javascript">
        $(function() {
            var title = "<?= $_SESSION['freeow']['title'] ?>",
                message = "<?= $_SESSION['freeow']['message'] ?>",
                opts = {},
                container;

            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
        });
    </script>
    <?php unset($_SESSION['freeow']); ?>
<?php endif; ?>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <h1>Liste des traductions</h1>
    <div class="btnGauche">
        <a href="<?= $this->lurl ?>/traductions/regenerateTranslationCache" class="btn_link" style="background-color: slateblue;">
            Appliquer les modifications des trads
        </a>
    </div>
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/traductions/add" class="btn_link thickbox">Ajouter une traduction</a>&nbsp;&nbsp;
        <a href="<?= $this->lurl ?>/traductions/export" class="btn_link">Export</a>&nbsp;&nbsp;
        <a href="<?= $this->lurl ?>/traductions/import" class="btn_link thickbox">Import</a>
    </div>
    <?php if (count($this->lSections) > 0) : ?>
        <table class="large">
            <tr>
                <th>
                    <label for="section">Section : </label>
                    <select name="section" id="section" onchange="loadNomTexte(this.value)" class="select">
                        <option value="">Sélectionner</option>
                        <?php foreach ($this->lSections as $section) : ?>
                            <option value="<?= $section['section'] ?>"<?= (isset($this->params[0]) && $this->params[0] == $section['section'] ? ' selected="selected"' : '') ?>>
                                <?= $section['section'] ?>(<?= $section['COUNT(translation)'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <th>
                    <div id="listeNomTraduction">
                        <?php if (isset($this->params[0]) && $this->params[0] != '') : ?>
                            <label for="nom">Nom : </label>
                            <select name="name" id="name" onchange="loadTradTexte(this.value,document.getElementById('section').value)" class="select">
                                <option value="">Sélectionner</option>
                                <?php foreach ($this->lNoms as $nom) : ?>
                                    <option value="<?= $nom['name'] ?>"
                                        <?= (isset($this->params[1]) && $this->params[1] == $nom['name'] ? ' selected="selected"' : '') ?>><?= $nom['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                </th>
                <td>&nbsp;</td>
                <th>
                    <a href="<?= $this->lurl ?>/traductions/add/<?= ((isset($this->params[0]) && $this->params[0] != '') ? $this->params[0] : '') ?>" id="btnAjouterTraduction"
                       class="btn_link thickbox"<?= ((isset($this->params[0]) && $this->params[0] != '') ? ' style="display:block;"' : ' style="display:none;"') ?>>
                        Ajouter une traduction pour la section
                    </a>
                </th>
            </tr>
        </table>
        <div id="elementTraduction">
            <?php if (false === empty($this->params[1])) : ?>
                <form method="post" name="mod_traduction" id="mod_traduction" enctype="multipart/form-data" action="<?= $this->lurl ?>/traductions">
                    <input type="hidden" name="section" id="section" value="<?= $this->params[0] ?>"/>
                    <input type="hidden" name="nom" id="nom" value="<?= $this->params[1] ?>"/>
                    <table class="lng">
                        <tr>
                            <td>
                                <img src="<?= $this->surl ?>/images/admin/langues/fr.png" alt="fr"/>
                            </td>
                            <td>
                                <textarea class="textarea_lng" style="background-image: url('<?= $this->surl ?>/images/admin/langues/flag_fr.png'); background-position:center; background-repeat:no-repeat;" name="translation" id="translation"><?= $this->lTranslations ?></textarea>
                            </td>
                        <tr>
                        <tr>
                            <th colspan="2">
                                <input type="hidden" name="form_mod_traduction" id="form_mod_traduction" value="0"/>
                                <input type="submit" value="Modifier" name="send_traduction" id="send_traduction" class="btn"/>
                                &nbsp;&nbsp;&nbsp;&nbsp;
                                <input type="submit" value="Supprimer" name="del_traduction" id="del_traduction" class="btnRouge" onClick="if(confirm('Êtes vous certain ?')){ document.getElementById('form_mod_traduction').value = 'delete'; } else { return false; }"/>
                            </th>
                        </tr>
                    </table>
                </form>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <p>Il n'y a aucune section pour le moment.</p>
    <?php endif; ?>
</div>
