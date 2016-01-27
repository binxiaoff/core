<div id="popup" style="min-width:500px;">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer"/></a>
    <h1>Attribution d'une opération</h1>
    <h2>Montant</h2>
    <?= $this->ficelle->formatNumber($this->receptions->montant / 100) ?> €
    <br/><br/>
    <h2>Motif</h2>
    <?= $this->receptions->motif ?>
    <br/><br/>
    <form id="switch-form">
        <div style="text-align:center;">
            <button type="button" id="switch-lender" class="btn">Prêteur</button>
            <button type="button" id="switch-project" class="btn">Emprunteur</button>
        </div>
    </form>
    <div id="lender-form-container" style="display:none;">
        <br/><hr/><br/>
        <form id="search-lender" name="search-lender">
            <fieldset>
                <table class="formColor">
                    <tr>
                        <th><label for="id">ID :</label></th>
                        <td><input type="text" name="id" id="id" class="input_large"/></td>
                    </tr>
                    <tr>
                        <th colspan="2" style="text-align:center;"><br/>Personne physique</th>
                    </tr>
                    <tr>
                        <th><label for="nom">Nom :</label></th>
                        <td><input type="text" name="nom" id="nom" class="input_large"/></td>
                    </tr>
                    <tr>
                        <th><label for="prenom">Prenom :</label></th>
                        <td><input type="text" name="prenom" id="prenom" class="input_large"/></td>
                    </tr>
                    <tr>
                        <th><label for="email">Email :</label></th>
                        <td><input type="text" name="email" id="email" class="input_large"/></td>
                    </tr>
                    <tr>
                        <th colspan="2" style="text-align:center;"><br/>Personne morale</th>
                    </tr>
                    <tr>
                        <th><label for="raison_sociale">Raison sociale :</label></th>
                        <td><input type="text" name="raison_sociale" id="raison_sociale" class="input_large"/></td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <th>
                            <input type="hidden" name="id_reception" value="<?= $this->receptions->id_reception ?>"/>
                            <input type="submit" value="Valider" title="Valider" name="send_preteur" class="btn"/>
                        </th>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
    <div id="project-form-container" style="display:none;">
        <br/><hr/><br/>
        <form method="post" name="project-form" id="project-form" enctype="multipart/form-data" action="<?= $this->lurl ?>/transferts/non_attribues">
            <fieldset>
                <table class="formColor">
                    <tr>
                        <th><label for="id">ID projet :</label></th>
                        <td><input type="text" id="id_project" name="id_project" class="input_large"/></td>
                    </tr>
                    <tr>
                        <th><label for="motif">Type de remboursement :</label></th>
                        <td>
                            <label class="label_radio">
                                <input class="radio" type="radio" name="type_remb" value="remboursement_anticipe">
                                Anticipé
                            </label>
                            <label class="label_radio">
                                <input class="radio" type="radio" name="type_remb" value="regularisation">
                                Régularisation
                            </label>
                            <!--<label class="label_radio">
                                <input class="radio" type="radio" name="type_remb" value="recouvrement">
                                Recouvrement
                            </label>-->
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <th>
                            <input type="hidden" name="id_reception" value ="<?= $this->receptions->id_reception ?>"/>
                            <input type="submit" value="Valider" title="Valider" name="send_projet" id="send_projet" class="btn"/>
                        </th>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
    <div id="response"></div>
    <p style="text-align:center;color:green;display:none;" class="reponse_valid_vir">Attribution effectuée</p>
</div>

<script type="text/javascript">
    $('#switch-lender').click(function() {
        $('#switch-lender').removeClass('btnDisabled');
        $('#switch-project').addClass('btnDisabled');
        $('#project-form-container').hide(0);
        $('#response').hide(0);
        $('#lender-form-container').show(0, function() {
            $.colorbox.resize();
        });
    });

    $('#switch-project').click(function() {
        $('#switch-lender').addClass('btnDisabled');
        $('#switch-project').removeClass('btnDisabled');
        $('#lender-form-container').hide(0);
        $('#response').hide(0);
        $('#project-form-container').show(0, function() {
            $.colorbox.resize();
        });
        $('#id_project').focus();
    });

    $('#search-lender').submit(function(e) {
        e.preventDefault();

        $.post(add_url + '/transferts/attribution_preteur', $(this).serialize()).done(function(data) {
            if (data != 'nok') {
                $('#lender-form-container').hide();
                $('#response').html(data).show(0, function() {
                    $.colorbox.resize();
                });
            }
        });
    });

    $('#project-form').submit(function(e) {
        if ($('[name=type_remb]:checked').val() == undefined) {
            e.preventDefault();
            alert('Vous devez renseigner le type de remboursement');
        }

        if ($('#id_project').val() == '') {
            e.preventDefault();
            alert('Vous devez renseigner le numéro de projet');
        }
    });
</script>
