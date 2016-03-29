<div class="main form-page account-page account-page-personal">
    <div class="shell">
        <nav class="tabs-nav">
            <ul class="navProfile">
                <li><a id="notification" href="#"><?= $this->lng['profile']['titre-4'] ?></a></li>
                <li><a id="securite" href="#"><?= $this->lng['profile']['titre-3'] ?></a></li>
                <li><a id="info_perso" href="#"><?= $this->lng['profile']['titre-1'] ?></a></li>
                <li class="active"><a id="autolend" href="#"><?= $this->lng['profile']['title-tab-autobid'] ?></a></li>
            </ul>
        </nav>
        <header class="header-autobid inner-nav">
            <ul>
                <li id="consult" class="active">
                    <a id="consult_link" href="#consultation" data-dest="1"><?= $this->lng['autobid']['title-tab-overview'] ?></a>
                </li>
                <li id="param" style="display:none;">
                    <a id="parameter" href="#parametrage" data-dest="2"><?= $this->lng['autobid']['title-tab-settings'] ?></a>
                </li>
            </ul>
        </header>
        <div class="autobid-tabs">
            <div class="autobid-tab" id="tab-1">
                <?php $this->fireview('autolend_overview'); ?>
            </div>
            <div class="autobid-tab" id="tab-2">
                <?php $this->fireview('autolend_settings'); ?>
            </div>
        </div>
    </div>
</div>
<script>
    $(window).load(function () {
        $('#notification').click(function () {
            window.location.replace("<?= $this->lurl ?>/profile");
        });
        $('#securite').click(function () {
            window.location.replace("<?= $this->lurl ?>/profile#securite");
        });
        $('#info_perso').click(function () {
            window.location.replace("<?= $this->lurl ?>/profile#info_perso");
        });
        $('#consult_link').click(function () {
            location.hash = "consultation";
        });
        $('#parameter').click(function () {
            location.hash = "parametrage";
        });

        // Autobid inner nav
        $('.header-autobid a').on('click', function (e) {
            e.preventDefault();
            $('.header-autobid li').removeClass('active');
            $(this).parent().addClass('active');
            $(this).parent().show();
            $('.autobid-tab').removeClass('visible');
            $('.autobid-tab').hide();
            $('#tab-' + $(this).attr('data-dest')).addClass('visible');
            $('#tab-' + $(this).attr('data-dest')).show();

        });

        var tab;
        if (window.location.hash == "#consultation" || window.location.hash == "") {
            tab = $('#consult_link');
        } else if (window.location.hash == "#parametrage") {
            tab = $('#parameter');
        }
        tab.trigger("click");

        // Switch On/Off handler
        $('.switch-input').on('change', function () {
            var Settings = {
                setting: $('#autobid-switch-1').val(),
                id_lender: "<?= $this->oLendersAccounts->id_lender_account ?>"
            };
            if ($('#autobid-switch-1').val() == <?= \client_settings::AUTO_BID_ON ?>) {
                $.post(add_url + "/profile/AutoBidSettingOff", Settings).done(function (data) {
                    if (data == "update_off_success") {
                        autoBidTabsOff();
                        autoBidSwitchOff();
                        $('#switch-notice-nth-active').show();
                    }
                })
            } else {
                autoBidTabsOn();
                $('#parameter').trigger("click");
                if ($('#settings-mode').val() === 'novice') {
                    noviceModification();
                } else {
                    expertModification();
                }
            }
        });

        $.ajax({
            url: "<?= $this->lurl ?>/profile/autobidDetails/<?= $this->oLendersAccounts->id_lender_account ?>",
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if(data.success === true) {
                    if (data.info.autobid_on) {
                        autoBidSwitchOn();
                        autoBidTabsOn();
                    } else {
                        autoBidTabsOff();
                        if (data.info.never_activated){
                            $('#switch-notice-first-active').show();
                        } else {
                            $('#switch-notice-nth-active').show();
                        }
                    }

                    if (!data.info.lender_active) {
                        $('#switch-notice-lender-inactive').show();
                        $('.switch-input').off();
                    }

                    if (data.info.is_qualified) {
                        if (data.info.never_activated) {
                            $('#settings_modifications_novice').html('<?= $this->lng['autobid']['settings-button-define-parameters'] ?>');
                            $('#settings_modifications_expert').html('<?= $this->lng['autobid']['settings-button-define-parameters'] ?>');
                        } else {
                            if (data.info.autobid_on) {
                                autoBidSwitchOn();
                                autoBidTabsOn();
                            }
                            $('#last-upadated-date').show();
                            var sValidationDate = '<?= $this->lng['autobid']['settings-page-date-last-update'] ?>'
                                .replace('[#DATE#]', data.info.validation_date);
                            $('#last-upadated-date').html(sValidationDate);
                            $('#settings_modifications_novice').html('<?= $this->lng['autobid']['settings-button-modify-parameters'] ?>');
                            $('.link-more').show();
                            $('#settings_modifications_expert').html('<?= $this->lng['autobid']['settings-button-modify-parameters'] ?>');
                            }

                        if (data.info.is_novice) {
                            $('#settings-mode').val('novice');
                            noviceConsultation();
                        } else {
                            $('#settings-mode').val('expert');
                            expertConsultation();
                        }
                    }
                }
            }
        });

        $('#settings_modifications_novice').click(function () {
            noviceModification();
        });

        $('.link-less').click(function () {
            noviceModification();
        });

        $('#settings_modifications_expert').click(function () {
            expertModification();
        });

        $('.link-more').click(function () {
            expertModification();
        });

        $('#validate_settings_expert').click(function () {
            var Settings = {
                id_client: "<?= $this->clients->id_client ?>"
            };
            $(':input').each(function(){
                Settings[$(this).attr('id')] = $(this).val();
            });

            $.post(add_url + "/profile/autoBidExpertForm", Settings).done(function (data) {
                if (data == 'settings_saved') {
                    expertConsultation();
                    autoBidSwitchOn();
                    $('#switch-notice-nth-active').hide();
                    $('#switch-notice-lender-inactive').hide();
                }
            })
        });

        function noviceConsultation(){
            $('#settings_instructions_novice').hide();
            $('#settings_instructions_expert').hide();

            $('#settings_modifications_novice').show();
            $('#settings_modifications_expert').hide();

            $('#novice-settings').show();
            $('#expert-settings').hide();

            $('#autobid-amount').prop('disabled', true);

            $('#autobid-param-simple-taux-min-field-no-input').show();
            $('#select-autobid-taux').hide();
            $('#rate-settings-novice').show();

            $('.link-more').hide();

            $('#validate_settings_novice').hide();
            $('#cancel_modification_settings_novice').hide();
        }

        function noviceModification() {
            $('#settings_instructions_novice').show();
            $('#settings_instructions_expert').hide();

            $('#novice-settings').show();
            $('#expert-settings').hide();

            $('#settings_modifications_novice').hide();
            $('#settings_modifications_expert').hide();

            $('#autobid-amount').prop('disabled', false);
            $('#autobid-amount').show();
            $('#autobid-amount-no-input').hide();

            $('#rate-settings-novice').show();
            $('#select-autobid-taux').show();
            $('#autobid-param-simple-taux-min-field-no-input').hide();

            if ($('#autobid-amount').val()) {
                $('.link-more').show();
            }

            $('#validate_settings_novice').show();
            $('#cancel_modification_settings_novice').show();

            $('.unit').removeClass('.unit');
        }

        function expertConsultation() {
            $('#settings_modifications_novice').hide();
            $('#settings_modifications_expert').show();

            $('#expert-settings').show();
            $('#novice-settings').show();

            $('#autobid-amount').prop('disabled', true);

            $('#autobid-param-simple-taux-min-field').hide();
            $('#select-autobid-taux').hide();
            $('#rate-settings-novice').hide();

            $('.link-more').hide();
            $('.link-less').hide();

            $('#cancel_modification_settings_expert').hide();

            $('#autobid-block').addClass('autobid-param-advanced-locked');

            $('#table-infos_right').hide();

            $('.param-advanced-switch').hide();
            $('.param-advanced-buttons').hide();
            $('#settings_instructions_expert').hide();
            $('.apply-global-medium-rate').hide();
            $('#validate_settings_expert').hide();
        }

        function expertModification() {
            $('#settings_instructions_novice').hide();
            $('#settings_instructions_expert').show();

            $('#novice-settings').hide();
            $('#expert-settings').show();

            $('#settings_modifications_novice').hide();
            $('#settings_modifications_expert').hide();

            $('#autobid-amount').prop('disabled', false);
            $('#autobid-amount').show();
            $('#autobid-amount-no-input').hide();

            $('#autobid-param-simple-taux-min-field').hide();
            $('#select-autobid-taux').hide();

            $('.link-more').hide();
            $('.link-less').show();

            $('#validate_settings_novice').hide();
            $('#cancel_modification_settings_novice').hide();

            $('#autobid-block').removeClass('autobid-param-advanced-locked');

            $('.param-advanced-switch').show();
            $('.param-advanced-buttons').show();
            $('.apply-global-medium-rate').show();
            $('#validate_settings_expert').show();
            $('#cancel_modification_settings_expert').show();
        }

        function autoBidSwitchOn() {
            $('.switch-container').addClass('checked');
            $('#autobid-switch-1').val('<?= \client_settings::AUTO_BID_ON ?>');
            $('#switch-notice-active').show();
            $('#tab-1').addClass('visible');
        }

        function autoBidSwitchOff() {
            $('.switch-container').removeClass('checked');
            $('#autobid-switch-1').val('<?= \client_settings::AUTO_BID_OFF ?>');
            $('#switch-notice-active').hide();
            $('#tab-1').addClass('visible');
        }

        function autoBidTabsOn() {
            $('#tab-2').addClass('visible');
            $('#param').show();
        }

        function autoBidTabsOff() {
            $('#consult').addClass('active');
            $('#param').hide();
            $('#tab-2').hide();
            $('#tab-1').addClass('visible');
            $('#tab-1').show();
        }
    });
</script>

