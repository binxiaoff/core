<div id="select_account" style="display: none;">
    <div id="popup">
        <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
        <div id="popup-content">
            <h1>Choisissez le prêteur à valider</h1>
            <p>Des comptes avec le même nom, prénom et date de naissance ont été trouvés.</p>
            <p>Séléctionnez le compte à valider. Les autres comptes seront clôturés.</p>
            <hr>
            <div class="margin-10-l">
                <form method="post" action="<?= $this->lurl ?>/preteurs/valider_preteur" id="form-validate-lender">
                    <?php foreach ($this->duplicateAccounts as $client) : ?>
                        <p>
                            <input type="radio"
                                   name="id_client_to_validate"
                                   value="<?= $client['id_client'] ?>" <?php if ($this->client->getIdClient() == $client['id_client']) : ?> checked <?php endif; ?>
                                   style="margin-left: 15px;">&nbsp;
                            <a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $client['id_client'] ?>" target="_blank" title="Voir ce prêteur"><?= $client['id_client'] ?></a>&nbsp;
                            - Statut : <?= htmlentities($client['label']) ?>
                        </p>
                    <?php endforeach; ?>
                    <hr>
                    <div class="text-right">
                        <button type="button" class="btn-default" onclick="parent.$.fn.colorbox.close();">Annuler</button>
                        <button type="button" class="btn-primary" id="btn-validate-lender">Valider</button>
                    </div>
                </form>
            </div>
            <div class="has-error"></div>
        </div>
    </div>
</div>
<script>
    $(function () {
        var $cBoxContent = $('#popup'),
            $btnValidate = $('#btn-validate-lender')

        $('#show_duplicated').colorbox({
            inline: true,
            href: $cBoxContent,
            onClose: function () {
                // To avoid attaching multiple click event handlers, if the user opens and closes the popup several times
                $btnValidate.unbind('click')
            },
            onComplete: function () {
                $btnValidate.on('click', function () {
                    $('#form-validate-lender').submit()
                })
            }
        })
    })
</script>
