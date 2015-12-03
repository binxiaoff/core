
<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer" /></a>
    <h1>Rechercher un projet </h1>
    <p>Montant : <?= $this->ficelle->formatNumber($this->receptions->montant / 100) ?> €</p>
    <p style="text-align:center;color:green;display:none;" class="reponse_valid_vir">attribution effectuée</p>
    <div id="leformpreteur">
        <form method="post" name="search_projet" id="search_preteur" enctype="multipart/form-data" action="<?= $this->lurl ?>/transferts/virements_emprunteurs" target="_parent">

            <fieldset>
                <table class="formColor">
                    <tr>
                        <th><label for="id">ID projet :</label></th>
                        <td><input type="text" name="id" id="id" class="input_large" /></td>
                    </tr>
                    <tr>
                        <th><label for="montant_edite">Editer le montant :</label></th>
                        <td><input type="text" name="montant_edite" id="montant_edite" class="input_moy" value="<?= ($this->receptions->montant / 100) ?>"/> &euro;</td>
                    </tr>
                    <tr>
                        <th><label for="motif">Editer le motif:</label></th>
                        <td><textarea name="motif" id="motif" class="textarea"><?= $this->receptions->motif ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="motif">Type de remb :</label></th>
                        <td>
                            <input id="type_remb1" class="radio" type="radio" checked="checked" name="type_remb" value="1">
                            <label class="label_radio" for="type_remb1">Anticipé</label>
                            <input id="type_remb2" class="radio" type="radio" name="type_remb" value="2">
                            <label class="label_radio" for="type_remb2">Régularisation</label>
                            <input id="type_remb3" class="radio" type="radio" name="type_remb" value="3">
                            <label class="label_radio" for="type_remb3">Recouvrement</label>
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <th>
                            <input type="hidden" name="id_reception" id="id_reception" value ="<?= $this->receptions->id_reception ?>" />
                            <input type="submit" value="Valider" title="Valider" name="send_projet" id="send_projet" class="btn" />
                        </th>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
    <div id="reponse">

    </div>
</div>
<!--
<script type="text/javascript">



    $("#send_preteur").click(function () {

        var id = $("#id").val();

        var val = {
            id: id
        }

        $.post(add_url + '/ajax/attribution_emprunteur', val).done(function (data) {
            //alert(data);

            if (data != 'nok')
            {
                $("#leformpreteur").hide();
                $("#reponse").show();
                $("#reponse").html(data);

                /*setTimeout(function() {
                 $(".reponse").slideUp();
                 }, 3000);*/
            }
        });
    });

</script>-->
