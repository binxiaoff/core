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
                        <option value="<?= $this->projects->target_rate ?>"><?= $this->projects->target_rate ?></option>
                            <?
                            if ($this->soldeBid >= $this->projects->amount)
                            {
                                if (number_format($this->txLenderMax, 1, '.', ' ') > '10.0')
                                {
                                    ?><option <?= ($this->projects->target_rate == '10.0' ? 'selected' : '') ?> value="10.0">10,0%</option><?
                                }
                                for ($i = 9; $i >= 4; $i--)
                                {
                                    for ($a = 9; $a >= 0; $a--)
                                    {
                                        if (number_format($this->txLenderMax, 1, '.', ' ') > $i . '.' . $a)
                                        {
                                            ?><option <?= ($this->projects->target_rate == $i . '.' . $a ? 'selected' : '') ?> value="<?= $i . '.' . $a ?>"><?= $i . ',' . $a ?>%</option><?
                                        }
                                    }
                                }
                            }
                            else
                            {
                                ?><option <?= ($this->projects->target_rate == '10.0' ? 'selected' : '') ?> value="10.0">10,0%</option><?
                                for ($i = 9; $i >= 4; $i--)
                                {
                                    for ($a = 9; $a >= 0; $a--)
                                    {
                                        ?><option <?= ($this->projects->target_rate == $i . '.' . $a ? 'selected' : '') ?> value="<?= $i . '.' . $a ?>"><?= $i . ',' . $a ?>%</option><?
                                    }
                                }
                            }
                            ?>
                    </select>
                </div><!-- /.form-controls -->
            </div><!-- /.form-row -->

            <div class="form-row">
                    <label for="offer-sum" class="form-label"><?= $this->lng['preteur-projets']['soit-un-remboursement-mensuel-de'] ?></label>

                    <div class="form-controls">
                            <input type="text" id="montant_pM" class="field" value="<?= $this->lng['preteur-projets']['montant-exemple'] ?>" name="montant_p" title="<?= $this->lng['preteur-projets']['montant-exemple'] ?>" onkeyup="lisibilite_nombre(this.value, this.id);"/>

                            <span class="currency">€</span>
                    </div><!-- /.form-controls -->
            </div><!-- /.form-row -->
            

            <div class="form-actions">
                <p><?= $this->lng['preteur-projets']['soit-un-remboursement-mensuel-de'] ?></p>

                <div class="laMensualM" style="font-size:14px;width:245px;display:none;">
                    <div style="text-align:center;"><span id="mensualiteM">xx</span> €</div>
                </div>
                <br />
                
                <?php
                // on check si on a coché les cgv ou pas 
                // cgu societe
                if (in_array($this->clients->type, array(2, 4)))
                {
                    $this->settings->get('Lien conditions generales inscription preteur societe', 'type');
                    $this->lienConditionsGenerales_header = $this->settings->value;
                }
                // cgu particulier
                else
                {
                    $this->settings->get('Lien conditions generales inscription preteur particulier', 'type');
                    $this->lienConditionsGenerales_header = $this->settings->value;
                }

                // liste des cgv accpeté
                $listeAccept_header = $this->acceptations_legal_docs->selectAccepts('id_client = ' . $this->clients->id_client);
                //$listeAccept = array();
                // Initialisation de la variable
                $this->update_accept_header = false;

                // On cherche si on a déjà le cgv
                if (in_array($this->lienConditionsGenerales, $listeAccept_header))
                {
                    $this->accept_ok_header = true;
                }
                else
                {
                    $this->accept_ok_header = false;
                    // Si on a deja des cgv d'accepté
                    if ($listeAccept_header != false)
                    {
                        $this->update_accept_header = true;
                    }
                }                
                ?>
                
                
                <a style="width:76px; display:block;margin:auto;" href="<?= (!$this->accept_ok_header ? $this->lurl . '/thickbox/pop_up_cgv' : $this->lurl . '/thickbox/pop_valid_pret_mobile/' . $this->projects->id_project) ?>" class="btn btn-medium popup-linkM <?= (!$this->accept_ok_header ? 'thickbox' : '') ?>"><?= $this->lng['preteur-projets']['preter'] ?></a> 
            </div><!-- /.form-actions -->
        </form>
    </div><!-- /popup-cnt -->
</div>



<script type="text/javascript" >

    $('.popup-linkM').colorbox({      
        maxWidth:'95%',
        opacity: 0.5,
        scrolling: false,
        onComplete: function(){

                $('.popup .custom-select').c2Selectbox();

                $('input.file-field').on('change', function(){
                        var $self = $(this),
                                val = $self.val()
                        if( val.length != 0 || val != '' ){
                                $self.closest('.uploader').find('input.field').val(val);

                                var idx = $('#rule-selector').val();
                                $('.rules-list li[data-rule="'+idx+'"]').addClass('valid');

                        }
                })

                $('#rule-selector').on('change', function(){
                        var idx = $(this).val();
                        $('.uploader[data-file="'+idx+'"]').slideDown().siblings('.uploader:visible').slideUp();
                })
        }

    });


    $("#montant_pM").blur(function () {        
        var montant = $("#montant_pM").val();
        var tx = $("#tx_pM").val();
        var form_ok = true;


        if (tx == '-')
        {
            form_ok = false;
        }
        else if (montant < <?= $this->pretMin ?>)
        {
            form_ok = false;
        }

        if (form_ok == true)
        {
            var val = {
                montant: montant,
                tx: tx,
                nb_echeances: <?= $this->projects->period ?>
            }
            
            $.post(add_url + '/ajax/load_mensual', val).done(function (data) {

                if (data != 'nok')
                {

                    $(".laMensualM").slideDown();
                    $("#mensualiteM").html(data);
                }
            });
        }

    });
    $("#tx_pM").change(function () {
        var montant = $("#montant_pM").val();
        var tx = $("#tx_p").val();
        var form_ok = true;

        if (tx == '-')
        {
            form_ok = false;
        }
        else if (montant < <?= $this->pretMin ?>)
        {
            form_ok = false;
        }

        if (form_ok == true)
        {
            var val = {
                montant: montant,
                tx: tx,
                nb_echeances: <?= $this->projects->period ?>
            }
            $.post(add_url + '/ajax/load_mensual', val).done(function (data) {

                if (data != 'nok')
                {

                    $(".laMensualM").slideDown();
                    $("#mensualiteM").html(data);
                }
            });
        }

    });
</script>