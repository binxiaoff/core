<?php if (! isset($this->params[0]) || $this->params[0] == '') : ?>
    <script>
        parent.$.colorbox.close();
    </script>
<?php endif; ?>

<div id="popup">
    <a onclick="parent.$.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <h1>Recherche : <?= $this->params['0'] ?></h1>
    <?php if ($this->lClients != false) : ?>
        <table style="margin-bottom:15px;">
            <?php foreach ($this->lClients as $c) : ?>
                <tr>
                    <td>
                        <input type="hidden" id="prenom_change_<?= $c['id_client'] ?>" value="<?= $c['prenom'] ?>">
                        <input type="hidden" id="nom_change_<?= $c['id_client'] ?>" value="<?= $c['nom'] ?>">
                        <input class="radio" type="radio" name="clients" id="client_<?= $c['id_client'] ?>" value="<?= $c['id_client'] ?>">
                        <label for="client_<?= $c['id_client'] ?>"><?= $c['prenom'] ?> <?= $c['nom'] ?></label>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <button id="valider_search" style="float:right" class="btn_link" onclick="parent.$.fn.colorbox.close();">Valider</button>
    <?php else : ?>
        <p>Aucun résultat pour <?= $this->params['0'] ?></p>
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
