<style type="text/css">
    .error_montant_offre {
        font-size: 14px;
        line-height: 14px;
        color: #b10366;
        margin-top: 0px;
    }
</style>

<div class="main">
    <div class="shell">
        <div class="section-c tabs-c">
            <nav class="tabs-nav">
                <ul>
                    <li class="active"><a href="#"><?= $this->lng['preteur-alimentation']['ajouter-des-fonds'] ?></a>
                    </li>
                    <?php if ($this->retrait_ok) : ?>
                        <li><a href="#"><?= $this->lng['preteur-alimentation']['transferer-des-fonds'] ?></a></li>
                    <?php else : ?>
                        <li><a class="popup-link" href="<?= $this->lurl ?>/thickbox/pop_up_alerte_retrait"><?= $this->lng['preteur-alimentation']['transferer-des-fonds'] ?></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="tabs">
                <div class="tab">
                    <h2><?= $this->lng['preteur-alimentation']['ajouter-des-fonds'] ?></h2>
                    <p><?= $this->lng['preteur-alimentation']['contenu-ajouter-des-fonds'] ?></p>
                    <div class="info-table">
                        <table>
                            <tr>
                                <th style="width: 375px"><?= $this->lng['preteur-alimentation']['type-de-transfert-de-fonds'] ?></th>
                                <th>
                                    <div class="radio-holder ali">
                                        <label for="virement1"><?= $this->lng['preteur-alimentation']['virement'] ?></label>
                                        <input id="virement1" type="radio" class="custom-input" name="alimentation" value="1" checked="checked">
                                    </div>
                                    <br/>
                                    <div class="radio-holder ali">
                                        <label for="cb"><?= $this->lng['preteur-alimentation']['cb'] ?></label>
                                        <input id="cb" type="radio" class="custom-input" name="alimentation" value="2">
                                    </div>
                                    <br/>
                                </th>
                            </tr>
                        </table>
                        <div id="contenuVirement">
                            <form action="" method="post">
                                <br/>
                                <div class="bank-transfer">
                                    <div class="bank-data">
                                        <p class="line-content">
                                            <span class="label"><b><?= $this->lng['preteur-alimentation']['titulaire-du-compte'] ?></b></span>
                                            <span><?= $this->titulaire ?></span>
                                        </p>
                                        <p class="line-content">
                                            <span class="label"><b><?= $this->lng['preteur-alimentation']['domiciliation'] ?></b></span>
                                            <span><?= $this->domiciliation ?></span>
                                        </p>
                                        <br/>
                                        <p><b><?= $this->lng['preteur-alimentation']['ref-bancaires'] ?></b></p>
                                        <div class="cols">
                                            <div class="col">
                                                <span class="label" style="width:175px;display:inline-block;"><b><?= $this->lng['preteur-alimentation']['code-banque'] ?></b></span>
                                                <span><?= $this->etablissement ?></span>
                                                <br/>
                                                <span class="label" style="width:175px;display:inline-block;"><b><?= $this->lng['preteur-alimentation']['numero-de-compte'] ?></b></span>
                                                <span><?= $this->compte ?></span>
                                            </div><!-- /.col -->
                                            <div class="col">
                                                <span class="label" style="width:175px;display:inline-block;"><b><?= $this->lng['preteur-alimentation']['code-guichet'] ?></b></span> <?= $this->guichet ?></span>
                                                <br/>
                                                <span class="label" style="width:175px;display:inline-block;"><b><?= $this->lng['preteur-alimentation']['cle-rib'] ?></b></span>
                                                <span><?= $this->cle ?></span>
                                            </div><!-- /.col -->
                                        </div><!-- /.cols -->
                                        <br/>
                                        <span class="label" style="width:175px;display:inline-block;"><b><?= $this->lng['preteur-alimentation']['bic'] ?></b></span>
                                        <span><?= strtoupper($this->bic) ?></span>
                                        <p class="line-content">
                                            <span class="label"><b><?= $this->lng['preteur-alimentation']['iban'] ?></b></span>
                                            <?php for ($i = 1; $i <= 7; $i++) : ?>
                                                <span><?= strtoupper($this->iban[$i]) ?></span>
                                            <?php endfor; ?>
                                        </p>
                                        <p class="line-content">
                                            <span class="label"><b><?= $this->lng['preteur-alimentation']['motif'] ?></b> <i class="icon-help tooltip-anchor" data-placement="right" title="<?= $this->lng['preteur-alimentation']['motif-description'] ?>"></i></span>
                                            <span><b style="color: #B10366;"><?= $this->motif ?></b></span>
                                        </p>
                                        <p>
                                            <i style="color: #B10366;font-size:12px;"><?= $this->lng['preteur-alimentation']['contenu-motif'] ?></i>
                                        </p>
                                    </div><!-- /.bank-data -->
                                </div>
                                <br/>
                                <input type="hidden" name="sendVirement"/>
                                <button class="btn btnAlimentation" type="submit"><?= $this->lng['preteur-alimentation']['valider'] ?></button>
                            </form>
                        </div>

                        <div id="contenuCb" style="display:none;">
                            <br/>
                            <form action="" method="post" id="form_sendPaymentCb" name="form_sendPaymentCb">
                                <div class="row">
                                    <div class="form-choose">
                                        <span class="title"><b><?= $this->lng['preteur-alimentation']['fonds'] ?></b></span>
                                        <input type="text" class="field field-small required" value="" name="amount" id="amount" onkeyup="lisibilite_nombre(this.value,this.id);"/>
                                    </div><!-- /.form-choose -->
                                </div><!-- /.row -->
                                <div class="row">
                                    <div class="cards">
                                        <span class="inline-text"><b><?= $this->lng['preteur-alimentation']['carte-de-credit'] ?></b></span>
                                        <img src="<?= $this->surl ?>/styles/default/images/mastercard.png" alt="mc" width="96" height="60">
                                        <img src="<?= $this->surl ?>/styles/default/images/ob.png" alt="cb" width="92" height="60">
                                        <img src="<?= $this->surl ?>/styles/default/images/visa.png" alt="visa" width="97" height="60">
                                    </div><!-- /.cards -->
                                </div><!-- /.row -->
                                <br/>
                                <input type="hidden" name="sendPaymentCb"/>
                                <button class="btn btnAlimentation" type="submit"><?= $this->lng['preteur-alimentation']['valider'] ?></button>
                            </form>
                        </div><!-- /.card-payment -->
                    </div>
                </div><!-- /.tab -->

                <div class="tab">
                    <h2><?= $this->lng['preteur-alimentation']['transferer-des-fonds'] ?></h2>
                    <p><?= $this->lng['preteur-alimentation']['contenu-transferer-des-fonds'] ?></p>
                    <p>
                        <?= $this->lng['preteur-alimentation']['vous-avez-actuellement'] ?>
                        <span><?= $this->ficelle->formatNumber($this->solde) ?> €</span> <?= $this->lng['preteur-alimentation']['de-disponible-sr-votre-compte-unilend.'] ?>
                    </p>
                    <p class="reponse" style="text-align:center;color:green;display:none;"><?= $this->lng['preteur-alimentation']['demande-de-transfert-de-fonds-en-cours'] ?></p>
                    <p class="noBicIban" style="text-align:center;color:#C84747;display:none;"><?= $this->lng['preteur-alimentation']['erreur-bic-iban'] ?></p>
                    <div class="tab-form">
                        <form action="#" method="post">
                            <div class="row clearfix">
                                <p class="left"><?= $this->lng['preteur-alimentation']['nom-du-titulaire-du-compte'] ?></p>
                                <p class="right"><?= $this->clients->prenom ?> <?= $this->clients->nom ?></p>
                            </div>
                            <div class="row clearfix">
                                <p class="left"><?= $this->lng['preteur-alimentation']['numero-de-compte-2'] ?></p>
                                <p class="right"><?= $this->clients->id_client ?></p>
                            </div>
                            <?php if ($this->retrait_ok) : ?>
                                <input type="password" style="display:none;"/>
                                <input type="text" style="display:none;"/>
                                <div class="row">
                                    <span class="pass-field-holder">
                                        <input name="password-2" type="password" id="mot-de-passe" placeholder="<?= $this->lng['preteur-alimentation']['mot-de-passe'] ?>" value="" class="field field-large required" data-validators="Presence" autocomplete="off">
                                    </span>
                                </div>
                                <div class="row">
                                    <input type="text" id="montant" title="<?= $this->lng['preteur-alimentation']['montant'] ?>" value="<?= $this->lng['preteur-alimentation']['montant'] ?>" class="field field-large required" data-validators="Presence" autocomplete="off">
                                    <em class="error_montant_offre" style="display:none;"><?= $this->lng['preteur-alimentation']['le-montant-offert-par-unilend-ne-peut-pas-etre-retire'] ?></em>
                                </div>

                                <em><?= $this->lng['preteur-alimentation']['champs-obligatoires'] ?></em>
                                <button class="btn" type="button" onclick="transfert('<?= $this->clients->id_client ?>');"><?= $this->lng['preteur-alimentation']['valider'] ?></button>
                            <?php else : ?>
                                <div class="row">
                                     <span class="pass-field-holder">
                                        <input disabled="disabled" type="text" title="<?= $this->lng['preteur-alimentation']['mot-de-passe'] ?>" value="<?= $this->lng['preteur-alimentation']['mot-de-passe'] ?>" class="field field-large">
                                    </span>
                                </div>
                                <div class="row">
                                    <input disabled="disabled" type="text" title="<?= $this->lng['preteur-alimentation']['montant'] ?>" value="<?= $this->lng['preteur-alimentation']['montant'] ?>" class="field field-large">
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div><!-- /.tab -->
            </div>
        </div><!-- /.tabs-c -->
    </div>
</div>

<script type="text/javascript">
    $('#amount').change(function () {
        var amount = $("#amount").val().replace(',', '.');
        amount = amount.replace(' ', '');

        var val_amount = true;
        if (isNaN(amount) == true) {
            val_amount = false
        }
        else if (amount > 10000 || amount < 20) {
            val_amount = false
        }

        if (val_amount == false) {
            $(this).addClass('LV_invalid_field');
            $(this).removeClass('LV_valid_field');
        }
        else {
            $(this).addClass('LV_valid_field');
            $(this).removeClass('LV_invalid_field');
        }
    });

    $(".ali").click(function () {
        var val = $('input[type=radio][name=alimentation]:checked').attr('value');
        if (val == 1) {
            $('#contenuVirement').show();
            $('#contenuCb').hide();
        }
        else if (val == 2) {
            $('#contenuVirement').hide();
            $('#contenuCb').show();
        }
    });

    $("#mot-de-passe").change(function () {
        if ($("#mot-de-passe").val() == '') {
            $(this).addClass('LV_invalid_field');
            $(this).removeClass('LV_valid_field');
        }
        else {
            $(this).addClass('LV_valid_field');
            $(this).removeClass('LV_invalid_field');
        }

    });

    $("#montant").change(function () {
        if ($("#montant").val() == '') {
            $(this).addClass('LV_invalid_field');
            $(this).removeClass('LV_valid_field');
        }
        else {
            $(this).addClass('LV_valid_field');
            $(this).removeClass('LV_invalid_field');
        }
    });


    $("#form_sendPaymentCb").submit(function (event) {
        var amount = $("#amount").val().replace(',', '.');
        amount = amount.replace(' ', '');

        var form_ok = true;

        var val_amount = true;
        if (isNaN(amount) == true) {
            val_amount = false
        }
        else if (amount > 10000 || amount < 20) {
            val_amount = false
        }

        if (val_amount == false) {
            form_ok = false;
            $("#amount").addClass('LV_invalid_field');
            $("#amount").removeClass('LV_valid_field');
        }
        else {
            $("#amount").addClass('LV_valid_field');
            $("#amount").removeClass('LV_invalid_field');
        }

        if (form_ok == false) {
            event.preventDefault();
        }
    });
</script>
