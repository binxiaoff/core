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
                    <li><a id="notif" href="#notification"><?= $this->lng['profile']['titre-4'] ?></a></li>
                    <li><a id="secu" href="#securite"><?= $this->lng['profile']['titre-3'] ?></a></li>
                    <li><a id="info" href="#info_perso"><?= $this->lng['profile']['titre-1'] ?></a></li>
                    <?php if ($this->bIsAllowedToSeeAutobid ) : ?>
                    <li><a id="auto" href="#"><?= $this->lng['profile']['title-tab-autobid'] ?></a></li>
                    <?php endif; ?>
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

    $(document).ready(function() {
        $('#auto').click(function() {
            window.location.replace("<?= $this->lurl ?>/profile/autolend");
        });
    });

    $(window).load(function() {
        $('#notif').click(function() {
            location.hash = "notification";
        });
        $('#secu').click(function() {
            location.hash = "securite";
        });
        $('#info').click(function() {
            location.hash = "info_perso";
        });

        var tab;

        if (window.location.hash == "#notification" || window.location.hash == "") {
            tab = $('#notif');
        } else if (window.location.hash == "#securite") {
            tab = $('#secu');
        } else if (window.location.hash == "#info_perso") {
            tab = $('#info');
        }
        tab.trigger( "click" );
    });
</script>
