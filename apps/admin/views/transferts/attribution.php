<style>
    #response .attention {
        width: 100%!important;
    }
</style>

<div id="popup" style="min-width:500px;">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
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
    <br/><hr/><br/>
    <div id="lender-form-container" style="display:none;">
        <form id="search-lender" name="search-lender">
            <fieldset style="background: #ECECEC; padding: 15px 15px 0; margin-bottom: 15px;">
                <div class="form-group">
                    <label for="id">ID Prêteur:</label>
                    <input class="form-control" type="text" id="id" name="id">
                </div>
                </fieldset>
            <fieldset style="background: #ECECEC; padding: 15px 15px 0; margin-bottom: 15px;">
                <h3>Personne physique</h3>
                <div class="form-group">
                    <label for="nom">Nom :</label>
                    <input class="form-control" type="text" id="nom" name="nom">
                </div>
                <div class="form-group">
                    <label for="prenom">Prenom :</label>
                    <input class="form-control" type="text" id="prenom" name="prenom">
                </div>
                <div class="form-group">
                    <label for="email">Email :</label>
                    <input class="form-control" type="text" id="email" name="email">
                </div>
            </fieldset>
            <fieldset style="background: #ECECEC; padding: 15px 15px 0; margin-bottom: 15px;">
                <h3>Personne morale</h3>
                <div class="form-group">
                    <label for="raison_sociale">Raison sociale :</label>
                    <input class="form-control" type="text" id="raison_sociale" name="raison_sociale">
                </div>
            </fieldset>
            <div class="text-right">
                <input type="hidden" name="id_reception" value="<?= $this->receptions->id_reception ?>"/>
                <button type="submit" class="btn-primary">Valider</button>
            </div>
        </form>
    </div>
    <div id="project-form-container" style="display:none;">
        <form method="post" name="project-form" id="project-form" enctype="multipart/form-data" action="<?= $this->lurl ?>/transferts/non_attribues">
            <fieldset style="background: #ECECEC; padding: 15px 15px 0; margin-bottom: 15px;">
                <div class="form-group">
                    <label for="id_project">ID projet:</label>
                    <input class="form-control" type="text" id="id_project" name="id_project">
                </div>

                <div class="form-group">
                    <p style="margin-bottom: 5px;">
                        Type de remboursement:
                    </p>
                    <label style="display: inline-block; margin-right: 10px;">
                        <input type="radio" name="type_remb" value="remboursement_anticipe">
                        Anticipé
                    </label>
                    <label>
                        <input type="radio" name="type_remb" value="regularisation">
                        Régularisation
                    </label>
                </div>
            </fieldset>
            <div class="text-right">
                <input type="hidden" name="id_reception" value ="<?= $this->receptions->id_reception ?>"/>
                <button type="submit" class="btn-primary">Valider</button>
            </div>
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
