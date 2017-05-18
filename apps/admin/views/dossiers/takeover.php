<div id="popup" class="takeover-popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
    <?php if (isset($this->companies)) : ?>
        <?php if (empty($this->companies)) : ?>
            <h1>Aucun emprunteur trouvé</h1>
            <p>SIREN&nbsp;: <?= $this->siren ?></p>
            <form method="post" action="<?= $this->lurl ?>/dossiers/takeover/<?= $this->projects->id_project ?>/create">
                <input type="hidden" name="siren" value="<?= $this->siren ?>">
                <div style="text-align: right">
                    <a href="javascript:parent.$.fn.colorbox.close()" class="btn-default">Annuler</a>
                    <button type="submit" class="btn-primary">Créer l'emprunteur</button>
                </div>
            </form>
        <?php else : ?>
            <h1>Sélectionnez l'emprunteur</h1>
            <form method="post" action="<?= $this->lurl ?>/dossiers/takeover/<?= $this->projects->id_project ?>/select">
                <fieldset>
                    <?php foreach ($this->companies as $company) : ?>
                        <label>
                            <input type="radio" name="id_target_company" value="<?= $company['id_company'] ?>" required>
                            <?= $company['name'] ?>
                        </label>
                        (<a href="<?= $this->lurl ?>/emprunteurs/edit/<?= $company['id_client_owner'] ?>" target="_blank"><?= $company['id_client_owner'] ?></a>)
                        <br>
                    <?php endforeach; ?>
                    <div style="margin-top: 15px; text-align: right">
                        <a href="javascript:parent.$.fn.colorbox.close()" class="btn btn_link btnDisabled">Annuler</a>
                        <input type="submit" value="Sélectionner" class="btn">
                    </div>
                </fieldset>
            </form>
        <?php endif; ?>
    <?php else : ?>
        <h1>Société existante</h1>
        <form method="post" id="takeover-search-form" action="<?= $this->lurl ?>/dossiers/takeover/<?= $this->projects->id_project ?>/search">
            <fieldset>
                <table class="form">
                    <tr>
                        <th><label for="search-siren"></label></th>
                        <td><input type="text" name="siren" id="search-siren" class="input_moy" placeholder="SIREN" pattern="[0-9]{9}" required></td>
                        <td style="text-align: right">
                            <a href="javascript:parent.$.fn.colorbox.close()" class="btn-default">Annuler</a>
                            <button type="submit" class="btn-primary">Rechercher</button>
                        </td>
                    </tr>
                </table>
            </fieldset>
        </form>
        <hr>
        <h1>Nouvelle société</h1>
        <form method="post" action="<?= $this->lurl ?>/dossiers/takeover/<?= $this->projects->id_project ?>/create">
            <fieldset>
                <table class="form">
                    <tr>
                        <th><label for="create-siren"></label></th>
                        <td><input type="text" name="siren" id="create-siren" class="input_moy" placeholder="SIREN" pattern="[0-9]{9}" required></td>
                        <td style="text-align: right">
                            <a href="javascript:parent.$.fn.colorbox.close()" class="btn btn_link btnDisabled">Annuler</a>
                            <input type="submit" value="Créer" class="btn">
                        </td>
                    </tr>
                </table>
            </fieldset>
        </form>
    <?php endif; ?>
</div>
<script>
    $(function () {
        $('#takeover-search-form').submit(function(event) {
            event.preventDefault()
            $.colorbox({href: '/dossiers/takeover/<?= $this->projects->id_project ?>/search/' + $('#search-siren').val()})
        })
    })
</script>
