<input type="hidden" name="section" id="section" value="<?=$this->params[0]?>" />
<label for="nom">Nom : </label>
<select name="nom" id="nom" onchange="loadTradTexte(this.value,document.getElementById('section').value)" class="select">
    <option value="">Sélectionner</option>
    <?
    foreach($this->lNoms as $nom)
    {
    ?>
        <option value="<?=$nom[0]?>"><?=$nom[0]?></option>
    <?
    }
    ?>
</select>