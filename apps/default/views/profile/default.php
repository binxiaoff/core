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
                    <li class="active">
                        <a id="notification" href="#"><?= $this->lng['profile']['titre-4'] ?></a>
                    </li>
                    <li id="securite">
                        <a id="title_2" href="#"><?= $this->lng['profile']['titre-3'] ?></a>
                    </li>
                    <li id="info_perso">
                        <a id="title_3" href="#"><?= $this->lng['profile']['titre-1'] ?></a>
                    </li>
                    <?php if ($this->bIsAllowedToSeeAutobid ) : ?>
                    <li id="autolend">
                        <a href="#"><?= $this->lng['profile']['title-tab-autobid'] ?></a>
                    </li>
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
    $(function() {
        $('#notification').click(function() {
            location.hash = '';
            history.pushState('', '', location.pathname);
            $("#notification").scrollTop();
        });
        $('#securite').click(function() {
            location.hash = "securite";
            $("#securite").scrollTop();
        });
        $('#info_perso').click(function() {
            location.hash = "info_perso";
            $("#info_perso").scrollTop();
        });
        $('#autolend').click(function() {
            window.location.replace("<?= $this->lurl ?>/profile/autolend");
        });
    });
</script>
