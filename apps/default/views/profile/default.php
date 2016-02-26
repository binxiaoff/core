<style type="text/css">
    .tabs .tab {
        display: block;
    }

    .field-large {
        width: 422px;
    }

    .tab .form-choose {
        margin-bottom: 0;
    }

    .form-page form .row .pass-field-holder {
        width: 460px;
    }
</style>

<div class="main form-page account-page account-page-personal">
    <div class="shell">
        <div class="section-c tabs-c">
            <nav class="tabs-nav">
                <ul class="navProfile">
                    <li id="noti">
                        <a id="noti" href="#"><?= $this->lng['profile']['titre-4'] ?></a>
                    </li>
                    <li id="secu">
                        <a id="title_2" href="#"><?= $this->lng['profile']['titre-3'] ?></a>
                    </li>
                    <li id="info">
                        <a id="title_3" href="#"><?= $this->lng['profile']['titre-1'] ?></a>
                    </li>
                    <li id="auto">
                        <a href="#"><?= $this->lng['profile']['title-tab-autobid'] ?></a>
                    </li>
                </ul>
            </nav>
            <div class="tabs">
                <div class="tab notification">
                    <?= $this->fireView('/gestion_alertes') ?>
                </div>
                <div class="tab securite" style="display: none;">
                    <?= $this->fireView('/secu_new') ?>
                </div>
                <div class="tab info_perso" style="display: none;">
                <?php
                if (in_array($this->clients->type, array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER))) {
                    $this->fireView('/particulier_perso_new');
                    $this->fireView('/particulier_bank_new');
                } elseif (in_array($this->clients->type, array(\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER))) {
                    $this->fireView('/societe_perso_new');
                    $this->fireView('/societe_bank_new');
                }
                ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

    $( window ).load(function() {
        $('#notif').click(function() {
            location.hash = "notification";
        });
        $('#secu').click(function() {
            location.hash = "securite";
        });
        $('#info').click(function() {
            location.hash = "info_perso";
        });
        $('#auto').click(function() {
            window.location.replace("<?= $this->lurl ?>/profile/autolend");
        });

        if (window.location.hash == "#notification" || window.location.hash == "") {
            history.pushState('', '', location.pathname);
            switchTab("notification", "info_perso", "securite");
        } else if (window.location.hash == "#info_perso") {
            switchTab("info_perso", "notification", "securite");
        } else if (window.location.hash == "#securite") {
            switchTab("securite", "notification", "info_perso");
        }

        function switchTab (selected, hidden, hidden2) {
            $('.' + hidden).hide();
            $('#' + hidden.substr(0, 4)).removeClass('active');
            $('.' + hidden2).hide();
            $('#' + hidden2.substr(0, 4)).removeClass('active');
            $('.' + selected).show();
            $('#' + selected.substr(0, 4)).addClass('active');
        }
    });
</script>
