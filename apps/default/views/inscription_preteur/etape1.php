<div class="main form-page">
    <div class="shell">
        <?= $this->fireView('../blocs/inscription-preteur') ?>
        <p><?= $this->lng['etape1']['contenu'] ?></p>
        <?php if ($this->emprunteurCreatePreteur == false) { ?>
            <div id="content_type_personne">
                <div class="row">
                    <div class="form-choose fixed">
                        <span class="title"><?= $this->lng['etape1']['vous-etes'] ?></span>

                        <div class="radio-holder" id="lab_radio1">
                            <label for="typePersonne-1">
                                <?= $this->lng['etape1']['particulier'] ?>
                            </label>
                            <input <?= ($this->modif == false ? 'checked' : ($this->modif == true && in_array($this->clients->type, array(1, 3)) ? 'checked' : '')) ?> type="radio" class="custom-input" name="typePersonne" id="typePersonne-1" value="1">
                        </div>
                        <div class="radio-holder" id="lab_radio2">
                            <label for="typePersonne-2">
                                <?= $this->lng['etape1']['societe'] ?>
                            </label>
                            <input <?= ($this->modif == true && in_array($this->clients->type, array(2, 4)) ? 'checked' : '') ?> type="radio" class="custom-input" name="typePersonne" id="typePersonne-2" value="2">
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        <span style="text-align:center; color:#C84747;"><?= $this->messageDeuxiemeCompte ?></span>
        <span style="text-align:center; color:#C84747;"><?= $this->reponse_email ?></span>
        <span style="text-align:center; color:#C84747;"><?= $this->reponse_age ?></span>

        <div class="register-form">
            <?php if ($this->emprunteurCreatePreteur == false) { ?>
                <div class="particulier">
                    <?= $this->fireView('particulier_etape_1') ?>
                </div>
            <?php } ?>
            <div class="societe">
                <?= $this->fireView('societe_etape_1') ?>
            </div>
            <?php
            // si modif et que c'est une societe
            if ($this->modif == true && in_array($this->clients->type, array(2, 4))) {
            ?>
            <script>
                $(".particulier").hide();
                $(".societe").show();
            </script>
            <?php } ?>
        </div>
    </div>
</div>
<script>
    <?php if ($this->emprunteurCreatePreteur == false && $this->modif == false || $this->modif == true && in_array($this->clients->type, array(1, 3))) { ?>
    $(window).load(function() {
        $(".societe").hide();
    });
    <?php } ?>

    $(document).ready(function () {
        // mdp controle particulier
        $('#pass').keyup(function () {
            controleMdp($(this).val(), 'pass');
        });
        // mdp controle particulier
        $('#pass').blur(function () {
            controleMdp($(this).val(), 'pass');
        });

        // mdp controle societe
        $('#passE').keyup(function () {
            controleMdp($(this).val(), 'passE');
        });
        // mdp controle societe
        $('#passE').blur(function () {
            controleMdp($(this).val(), 'passE');
        });

        // confirmation mdp particulier
        $('#pass2').bind('paste', function (e) {
            e.preventDefault();
        });
        // confirmation email preteur particulier
        $('#conf_email').bind('paste', function (e) {
            e.preventDefault();
        });
        $('#email').bind('paste', function (e) {
            e.preventDefault();
        });

        // confirmation email preteur societe
        $('#conf_email_inscription').bind('paste', function (e) {
            e.preventDefault();
        });
        $('#email_inscription').bind('paste', function (e) {
            e.preventDefault();
        });

        // confirmation mpd societe
        $('#passE2').bind('paste', function (e) {
            e.preventDefault();
        });

        $('select#external-consultant').on('change', function () {
            if ($('option:selected', this).val() == '3') {
                $('#autre_inscription').show();
            }
            else {
                $('#autre_inscription').hide();
            }
        });

        $("#jour_naissance, #mois_naissance, #annee_naissance").change(function () {
            var d = $('#jour_naissance').val();
            var m = $('#mois_naissance').val();
            var y = $('#annee_naissance').val();

            $.post(add_url + "/ajax/controleAge", {d: d, m: m, y: y}).done(function (data) {
                if (data == 'ok') {
                    $(".check_age").html('true');
                    $(".error_age").slideUp();
                }
                else {
                    $(".check_age").html('false');
                    $(".error_age").slideDown();
                }
            });
        });
    });

    // display particulier
    $("#lab_radio1").click(function () {
        $(".societe").hide();
        $(".particulier").show();
    });
    // display societe
    $("#lab_radio2").click(function () {
        $(".particulier").hide();
        $(".societe").show();

    });

    // particulier messagge check_etranger
    $("#check_etranger").change(function () {
        if ($(this).is(':checked') == true) {
            $(".message_check_etranger").slideUp();
        }
        else {
            $(".message_check_etranger").slideDown();
        }
    });

    // Submit formulaire inscription preteur particulier
    $("#form_inscription_preteur_particulier_etape_1").submit(function (event) {
        var radio = true;

        // controle cp ville
        if (controlePostCodeCity($('#postal'), $('#ville_inscription'), $('#pays1'), false) == false) {
            radio = false
        }

        if ($('#mon-addresse').is(':checked') == false) {
            // controle cp ville
            if (controlePostCodeCity($('#postal2'), $('#ville2'), $('#pays2'), false) == false) {
                radio = false
            }
        }

        // date de naissance
        if ($(".check_age").html() == 'false') {
            radio = false;
        }

        // Civilite
        if ($('input[type=radio][name=sex]:checked').length) {
            $('#radio_sex').css('color', '#727272');
            $('#female').removeClass('LV_invalid_field');
        } else {
            $('#radio_sex').css('color', '#C84747');
            radio = false;
            $('#female').addClass('LV_invalid_field');
        }

        //resident etranger
        var pays1 = $('#pays1').val();
        var nationalite = $('#nationalite').val();
        // check_etranger
        if ($('#check_etranger').is(':checked') == false) {
            $('.check_etranger').css('color', '#C84747');
            radio = false;
        } else {
            $('.check_etranger').css('color', '#727272');
        }

        // cgu
        if ($('#accept-cgu').is(':checked') == false) {
            $('.check').css('color', '#C84747');
            radio = false;
        } else {
            $('.check').css('color', '#727272');
        }

        // controle mdp
        if (controleMdp($('#pass').val(), 'pass', false) == false) {
            radio = false;
        }

        if ($('#jour_naissance').val() == '<?=$this->lng['etape1']['jour']?>') {
            $("#jour_naissance").removeClass("LV_valid_field");
            $("#jour_naissance").addClass("LV_invalid_field");
        } else {
            $("#jour_naissance").removeClass("LV_invalid_field");
            $("#jour_naissance").addClass("LV_valid_field");
        }

        if (('' == $('#insee_birth').val() && 1 == $('#pays3').val()) || controleCity($('#naissance'), $('#pays3'), false) == false) {
            $("#naissance").removeClass("LV_valid_field");
            $("#naissance").addClass("LV_invalid_field");
            radio = false;
        }

        if (radio == false) {
            event.preventDefault();
        }
    });

    // Submit formulaire inscription preteur societe
    $("#form_inscription_preteur_societe_etape_1").submit(function (event) {
        var radio = true;
        // controle cp
        if (controlePostCodeCity($('#postalE'), $('#ville_inscriptionE'), $('#pays1E'), false) == false) {
            radio = false
        }

        if ($('#mon-addresse').is(':checked') == false) {
            // controle cp
            if (controlePostCodeCity($('#postal2E'), $('#ville2E'), $('#pays2E'), false) == false) {
                radio = false
            }
        }
        // Civilite vos cordonnées
        if ($('input[type=radio][name=genre1]:checked').length) {
            $('#radio_genre1').css('color', '#727272');
        } else {
            $('#radio_genre1').css('color', '#C84747');
            radio = false
        }

        // type d'utilisateur
        var radio_enterprise = $('input[type=radio][name=enterprise]:checked').attr('value');

        if (radio_enterprise == 2 || radio_enterprise == 3) {
            if ($('input[type=radio][name=genre2]:checked').length) {
                $('#radio_genre2').css('color', '#727272');
            } else {
                $('#radio_genre2').css('color', '#C84747');
                radio = false
            }
        } else {
            $('#radio_genre2').css('color', '#727272');
        }

        // cgu
        if ($('#accept-cgu-societe').is(':checked') == false) {
            $('.check-societe').css('color', '#C84747');
            radio = false
        } else {
            $('.check-societe').css('color', '#727272');
        }

        <?php if ($this->emprunteurCreatePreteur == false) { ?>
        if (controleMdp($('#passE').val(), 'passE', false) == false) {
            radio = false
        }
        <?php } ?>

        if (radio == false) {
            event.preventDefault();
        }
    });
</script>
