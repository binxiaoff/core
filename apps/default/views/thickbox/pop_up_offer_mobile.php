<div class="popup popup-offer">
    <a href="#" class="popup-close">close</a>

    <div class="popup-head">
        <h2><?= $this->lng['preteur-projets']['faire-une-offre'] ?></h2>
    </div>

    <div class="popup-cnt">
        <form action="?" method="post">
            <div class="form-row">
                <label for="offer-select" class="form-label"><?= $this->lng['preteur-projets']['je-prete-a'] ?></label>

                <div class="form-controls">
                    <select name="tx_p" id="tx_pM" class="select custom-select">
                        <?php foreach (range($this->rateRange['rate_max'], $this->rateRange['rate_min'], -0.1) as $fRate) { ?>
                            <?php if ($this->soldeBid < $this->projects->amount || round($fRate, 1) < round($this->txLenderMax, 1)) { ?>
                                <option value="<?= $fRate ?>"><?= $this->ficelle->formatNumber($fRate, 1) ?>&nbsp;%</option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <label for="offer-sum" class="form-label"><?= $this->lng['preteur-projets']['la-somme-de'] ?></label>

                <div class="form-controls">
                    <input type="text" id="montant_pM" class="field field-currency" value="<?= $this->lng['preteur-projets']['montant-exemple'] ?>" name="montant_p" title="<?= $this->lng['preteur-projets']['montant-exemple'] ?>" onkeyup="lisibilite_nombre(this.value, this.id);"/>
                    <span class="currency">€</span>
                </div>
            </div>
            <div class="form-actions">
                <p><?= $this->lng['preteur-projets']['soit-un-remboursement-mensuel-de'] ?></p>

                <div class="laMensualM" style="font-size:14px;width:245px;visibility: hidden;">
                    <div style="text-align:center;"><span id="mensualiteM">xx</span> €</div>
                </div>
                <br />

                <?php
                if (in_array($this->clients->type, array(\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER))) {
                    $this->settings->get('Lien conditions generales inscription preteur societe', 'type');
                    $this->lienConditionsGenerales_header = $this->settings->value;
                } else {
                    $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
                    $this->lienConditionsGenerales_header = $this->settings->value;
                }

                $listeAccept_header = $this->acceptations_legal_docs->selectAccepts('id_client = ' . $this->clients->id_client);
                $this->update_accept_header = false;

                if (in_array($this->lienConditionsGenerales, $listeAccept_header)) {
                    $this->accept_ok_header = true;
                } else {
                    $this->accept_ok_header = false;
                    if ($listeAccept_header != false) {
                        $this->update_accept_header = true;
                    }
                }
                ?>
                <a style="width:76px; display:block;margin:auto;" href="<?= (!$this->accept_ok_header ? $this->lurl . '/thickbox/pop_up_cgv' : $this->lurl . '/thickbox/pop_valid_pret_mobile/' . $this->projects->id_project) ?>" class="btn btn-medium popup-linkM <?= (!$this->accept_ok_header ? 'thickbox' : '') ?>"><?= $this->lng['preteur-projets']['preter'] ?></a>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    $('.popup-linkM').colorbox({
        maxWidth: '95%',
        opacity: 0.5,
        scrolling: false,
        onComplete: function () {
            $('.popup .custom-select').c2Selectbox();

            $('input.file-field').on('change', function () {
                var $self = $(this),
                        val = $self.val();
                if (val.length != 0 || val != '') {
                    $self.closest('.uploader').find('input.field').val(val);

                    var idx = $('#rule-selector').val();
                    $('.rules-list li[data-rule="' + idx + '"]').addClass('valid');
                }
            });

            $('#rule-selector').on('change', function () {
                var idx = $(this).val();
                $('.uploader[data-file="' + idx + '"]').slideDown().siblings('.uploader:visible').slideUp();
            });
        }
    });

    $("#montant_pM").blur(function () {
        var montant = $("#montant_pM").val(),
            tx = $("#tx_pM").val(),
            form_ok = true;

        if (tx == '-') {
            form_ok = false;
        } else if (montant < <?= $this->pretMin ?>) {
            form_ok = false;
        }

        if (form_ok == true) {
            var val = {
                montant: montant,
                tx: tx,
                nb_echeances: <?= $this->projects->period ?>
            };

            $.post(add_url + '/ajax/load_mensual', val).done(function (data) {
                if (data != 'nok') {
                    $(".laMensualM").css('visibility','visible');
                    $("#mensualiteM").html(data);
                }
            });
        }
    });

    $("#tx_pM").change(function () {
        var montant = $("#montant_pM").val(),
            tx = $("#tx_p").val(),
            form_ok = true;

        if (tx == '-') {
            form_ok = false;
        } else if (montant < <?= $this->pretMin ?>) {
            form_ok = false;
        }

        if (form_ok == true) {
            var val = {
                montant: montant,
                tx: tx,
                nb_echeances: <?= $this->projects->period ?>
            };
            $.post(add_url + '/ajax/load_mensual', val).done(function (data) {

                if (data != 'nok')
                {

                    $(".laMensualM").css('visibility','visible');
                    $("#mensualiteM").html(data);
                }
            });
        }
    });
</script>
