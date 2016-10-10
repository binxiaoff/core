<input type="hidden" name="section" id="section" value="<?= $this->params[0] ?>"/>
<label for="nom">Nom : </label>
<select name="nom" id="nom" onchange="loadTradTexte(this.value,document.getElementById('section').value)" class="select">
    <option value="">Sélectionner</option>
    <?php foreach ($this->lNoms as $nom) : ?>
        <option value="<?= $nom['name'] ?>"><?= $nom['name'] ?></option>
    <?php endforeach; ?>
</select>