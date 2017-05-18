<?php if (false === isset($this->params[1]) || empty($this->params[1])) : ?>
    <script type="text/javascript">
        parent.$.fn.colorbox.close();
    </script>
<?php endif; ?>

<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn">
        <img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/>
    </a>
    <div id="popup-content">
        <h1>Recherche : <?= $this->params[1] ?></h1>

        <?php if (empty($this->aClients)) : ?>
            <p>Aucun résultat pour <?= $this->params[1] ?></p>
        <?php else : ?>
            <form name="select_prescripteur" id="select_prescripteur" action="#">
                <table style="margin-bottom:15px;">
                    <?php foreach ($this->aClients as $c) : ?>
                        <tr>
                            <td>
                                <input type="hidden" id="id_prescripteur_change_<?= $c['id_prescripteur'] ?>"/>
                                <input type="hidden" id="civilite_change_<?= $c['id_prescripteur'] ?>" value="<?= $c['civilite'] ?>"/>
                                <input type="hidden" id="prenom_change_<?= $c['id_prescripteur'] ?>" value="<?= $c['prenom'] ?>"/>
                                <input type="hidden" id="nom_change_<?= $c['id_prescripteur'] ?>" value="<?= $c['nom'] ?>"/>
                                <input type="hidden" id="email_change_<?= $c['id_prescripteur'] ?>" value="<?= $c['email'] ?>"/>
                                <input type="hidden" id="telephone_change_<?= $c['id_prescripteur'] ?>" value="<?= $c['telephone'] ?>"/>
                                <input type="hidden" id="company_change_<?= $c['id_prescripteur'] ?>" value="<?= $c['name'] ?>"/>
                                <input type="hidden" id="siren_change_<?= $c['id_prescripteur'] ?>" value="<?= $c['siren'] ?>"/>
                                <input class="radio" type="radio" name="prescripteur" id="prescripteur_<?= $c['id_prescripteur'] ?>" value="<?= $c['id_prescripteur'] ?>"/>
                                <label for="prescripteur_<?= $c['id_prescripteur'] ?>"><?= $c['prenom'] ?> <?= $c['nom'] ?></label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><input type="hidden" id="id_project" name="id_project" value="<?= $this->params[0] ?>"/></td>
                    </tr>
                </table>
                <button type="submit" class="btn-primary">Sélectionner</button>
            </form>
        <?php endif; ?>
        <div class="clear"></div>
    </div>
</div>
<script type="text/javascript">
    $(function() {
        $('#select_prescripteur').submit(function (e) {
            e.preventDefault();

            $.ajax({
                url: "<?= $this->lurl ?>/prescripteurs/search_ajax",
                type: 'POST',
                data: {
                    project: $("#id_project").val(),
                    prescripteur: $('input[name=prescripteur]:checked').val(),
                    valider_search_prescripteur: true
                },
                dataType: 'json',
                error: function () {
                    alert('An error has occurred');
                },
                success: function (data) {
                    if (data.result && data.result == 'OK') {
                        $("#id_prescripteur").val(data.id_prescripteur);
                        $("#civilite_prescripteur").html($("#civilite_change_" + data.id_prescripteur).val());
                        $("#prenom_prescripteur").html($("#prenom_change_" + data.id_prescripteur).val());
                        $("#nom_prescripteur").html($("#nom_change_" + data.id_prescripteur).val());
                        $("#email_prescripteur").html($("#email_change_" + data.id_prescripteur).val());
                        $("#telephone_prescripteur").html($("#telephone_change_" + data.id_prescripteur).val());
                        $("#company_prescripteur").html($("#company_change_" + data.id_prescripteur).val());
                        $("#siren_prescripteur").html($("#siren_change_" + data.id_prescripteur).val());
                        $('.identification_prescripteur').show('slow');
                        $("#popup-content").html('Le prescripteur a &eacute;t&eacute; rattach&eacute au projet!');
                    } else {
                        alert('An error has occurred');
                    }
                }
            });
        });
    });
</script>
