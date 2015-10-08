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
                    <li <?= (! isset($this->params[0]) || $this->params[0] == 1 ? 'class="active"' : '') ?>>
                        <a id="title_1" href="#"><?= $this->lng['profile']['titre-4'] ?></a>
                    </li>
                    <li id='title_2_tab' <?= (isset($this->params[0]) && $this->params[0] == 2 ? 'class="active"' : '') ?> >
                        <a id="title_2" href="#"><?= $this->lng['profile']['titre-3'] ?></a>
                    </li>
                    <li id='title_3_tab' <?= (isset($this->params[0]) && $this->params[0] == 3 ? 'class="active"' : '') ?> >
                        <a id="title_3" href="#"><?= $this->lng['profile']['titre-1'] ?></a>
                    </li>
                </ul>
            </nav>
            <div class="tabs">
                <div class="tab page1 tab-manage">
                    <?= $this->fireView('/gestion_alertes') ?>
                </div>
                <div class="tab page2">
                    <?= $this->fireView('/secu_new') ?>
                </div>
                <div class="tab page3">
                <?php
                    if ($this->Command->Function == 'societe') {
                        echo $this->fireView('/societe_perso_new');
                        echo $this->fireView('/societe_bank_new');
                    } else {
                        echo $this->fireView('/particulier_perso_new');
                        echo $this->fireView('/particulier_bank_new');
                    }
                ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    <?php if (isset($this->params[0]) && $this->params[0] == 2) { ?>
        setTimeout(function () {
            $("#title_2_tab").click();
        }, 0);
    <?php } elseif (isset($this->params[0]) && $this->params[0] == 3) { ?>
        setTimeout(function () {
            $("#title_3_tab").click();
        }, 0);
    <?php } ?>

    $(window).load(function () {
    <?php
        if (isset($this->params[0]) && $this->params[0] > 1 && $this->params[0] <= 3) {
            for ($i = 1; $i <= 3; $i++){
                if ($this->params[0] != $i) {
                ?>
            $(".page<?=$i?>").hide();
    <?php
                }
            }
        } else{
    ?>
        $(".page2").hide();
        $(".page3").hide();
    <?php } ?>
    });
</script>
