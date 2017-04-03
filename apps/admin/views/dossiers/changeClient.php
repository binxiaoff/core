<?php if (empty($this->params[0])) : ?>
    <script>
        parent.$.colorbox.close();
    </script>
<?php else : ?>
    <div id="popup">
        <a onclick="parent.$.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
        <h1>Recherche : <?= $this->search ?></h1>
        <?php if (empty($this->clients)) : ?>
            <p>Aucun résultat</p>
        <?php else : ?>
            <table style="margin-bottom:15px;">
                <?php foreach ($this->clients as $client) : ?>
                    <tr>
                        <td>
                            <?= $client['id_client'] ?>
                            <input type="hidden" id="prenom_change_<?= $client['id_client'] ?>" value="<?= $client['prenom'] ?>">
                            <input type="hidden" id="nom_change_<?= $client['id_client'] ?>" value="<?= $client['nom'] ?>">
                            <input class="radio" type="radio" name="clients" id="client_<?= $client['id_client'] ?>" value="<?= $client['id_client'] ?>">
                            <label for="client_<?= $client['id_client'] ?>"><?= $client['prenom'] ?> <?= $client['nom'] ?></label>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <button id="valider_search" style="float:right" class="btn_link" onclick="parent.$.fn.colorbox.close();">Valider</button>
        <?php endif; ?>
        <div class="clear"></div>
    </div>

    <script>
        $("#valider_search").click(function () {
            var id = $('input[name=clients]:checked').val(),
                prenom = $("#prenom_change_" + id).val(),
                nom = $("#nom_change_" + id).val();

            $('#search_result').show();
            $("#search").val('');

            $("#id_client").val(id);
            $("#id_clientHtml").html(id);
            $("#prenom").val(prenom);
            $("#prenomHtml").html(prenom);
            $("#nom").val(nom);
            $("#nomHtml").html(nom);
        });
    </script>
<?php endif; ?>

