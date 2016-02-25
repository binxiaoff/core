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
                    <li id="notification">
                        <a id="notification" href="#"><?= $this->lng['profile']['titre-4'] ?></a>
                    </li>
                    <li id="securite">
                        <a id="title_2" href="#"><?= $this->lng['profile']['titre-3'] ?></a>
                    </li>
                    <li id="info_perso">
                        <a id="title_3" href="#"><?= $this->lng['profile']['titre-1'] ?></a>
                    </li>
                    <li id="autolend">
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
        $('#notification').click(function() {
            location.hash = "notification";
        });
        $('#securite').click(function() {
            location.hash = "securite";
        });
        $('#info_perso').click(function() {
            location.hash = "info_perso";
        });
        $('#autolend').click(function() {
            window.location.replace("<?= $this->lurl ?>/profile/autolend");
        });

        if (window.location.hash == "#notification" || window.location.hash == "") {
            history.pushState('', '', location.pathname);
            $(".notification").show();
            $(".autolend").hide();
            $(".info_perso").hide();
            $(".securite").hide();
            $("#notification").addClass('active');
            $("#autolend").removeClass('active');
            $("#info_perso").removeClass('active');
            $("#securite").removeClass('active');
        } else if (window.location.hash == "#info_perso") {
            $(".notification").hide();
            $(".autolend").hide();
            $(".info_perso").show();
            $(".securite").hide();
            $("#notification").removeClass('active');
            $("#autolend").removeClass('active');
            $("#info_perso").addClass('active');
            $("#securite").removeClass('active');
        } else if (window.location.hash == "#securite") {
            $(".notification").hide();
            $(".autolend").hide();
            $(".info_perso").hide();
            $(".securite").show();
            $("#notification").removeClass('active');
            $("#autolend").removeClass('active');
            $("#info_perso").removeClass('active');
            $("#securite").addClass('active');
        } else if (window.location.pathname == "/profile/autolend") {
            $(".notification").hide();
            $(".autolend").show();
            $(".info_perso").hide();
            $(".securite").hide();
            $("#notification").removeClass('active');
            $("#autolend").addClass('active');
            $("#info_perso").removeClass('active');
            $("#securite").removeClass('active');
        }
    });
</script>
