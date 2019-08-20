<div id="contenu">
    <h1>Liste des traductions</h1>
    <p>
        <a href="<?= $this->url ?>/traductions/regenerateTranslationCache" class="btn-primary">
            Appliquer les modifications
        </a>
        <a href="<?= $this->url ?>/traductions/add" class="btn-primary thickbox" style="margin-left: 10px;">Ajouter une traduction</a>&nbsp;
        <a href="<?= $this->url ?>/traductions/export" class="btn-default" style="margin-left: 10px;">Export</a>&nbsp;&nbsp;
    </p>
    <?php if (count($this->lSections) > 0) : ?>
        <table class="large">
            <tr>
                <th>
                    <label for="section">Section : </label>
                    <select name="section" id="section" onchange="loadNomTexte(this.value)" class="select">
                        <option value="">Sélectionner</option>
                        <?php foreach ($this->lSections as $section) : ?>
                            <option value="<?= $section['section'] ?>"<?= (isset($this->params[0]) && $this->params[0] == $section['section'] ? ' selected="selected"' : '') ?>>
                                <?= $section['section'] ?>(<?= $section['count'] ?>)
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
                    <a href="<?= $this->url ?>/traductions/add/<?= ((isset($this->params[0]) && $this->params[0] != '') ? $this->params[0] : '') ?>" id="btnAjouterTraduction"
                       class="btn-primary thickbox"<?= ((isset($this->params[0]) && $this->params[0] != '') ? ' style="display:block;"' : ' style="display:none;"') ?>>
                        Ajouter une traduction pour la section
                    </a>
                </th>
            </tr>
        </table>
        <div id="elementTraduction">
            <?php if (false === empty($this->params[1])) : ?>
                <?php $this->fireView('edit'); ?>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <p>Il n'y a aucune section pour le moment.</p>
    <?php endif; ?>
</div>
