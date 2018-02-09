<?php if (empty($this->search)) : ?>
    <script>
        parent.$.colorbox.close();
    </script>
<?php else : ?>
    <div id="popup" style="min-width: 300px; padding-bottom: 0;">
        <a onclick="parent.$.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
        <h1>Recherche : <?= $this->search ?></h1>
        <?php if (empty($this->clients)) : ?>
            <p>Aucun résultat</p>
        <?php else : ?>
            <form id="existing-client-form" action="<?= $this->lurl ?>/dossiers/add/client" method="post">
                <fieldset class="form-group">
                    <?php foreach ($this->clients as $client) : ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="id_client" id="client-<?= $client['id_client'] ?>" value="<?= $client['id_client'] ?>">
                            <label class="form-check-label" for="client-<?= $client['id_client'] ?>">
                                <?= $client['prenom'] ?> <?= $client['nom'] ?> (<a href="<?= $this->lurl ?>/emprunteurs/edit/<?= $client['id_client'] ?>" target="_blank"><?= $client['projets'] ?> projet<?= $client['projets'] > 1 ? 's' : '' ?></a>)
                            </label>
                        </div>
                    <?php endforeach; ?>
                </fieldset>
                <div class="form-group row text-right" style="margin-bottom: 0;">
                    <button type="submit" class="btn-primary">Valider</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        $(function () {
            $('#existing-client-form').on('submit', function (event) {
                var $form = $(this)
                var $button = $form.find('[type=submit]')

                if (! $('[name=id_client]:checked').val()) {
                    alert('Veuillez sélectionner un emprunteur')
                    return
                }

                $button.after('<img src="<?= $this->surl ?>/images/admin/ajax-loader.gif">')
                $button.hide()
            })
        })
    </script>
<?php endif; ?>
