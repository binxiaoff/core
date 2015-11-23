<style type="text/css">
    .tabs .tab {
        display: block;
    }

    .info {
        padding: 8px 12px 8px 12px;
        margin: 5px;
    }

    .content {
        padding: 8px 12px 8px 12px;
        margin: 5px;
        background: #f4f4f4;
        border: 1px solid #dbdbdb;
        border-radius: 3px;
        border-collapse: separate;
    }

    .small {
        width: 192px;
        height: 18px;
        float: left;
    }

    .medium {
        width: 306px;
        height: 18px;
        float: left;
    }

    .large {
        width: 646px;
        height: 18px;
        float: left;
    }

    .row-btn {
        margin-top: 35px;
    }

    .row-btn .btn {
        line-height: 1.2em;
    }

    .info {
        height: 36px;
    }
</style>

<div class="main form-page account-page account-page-personal">
    <div class="shell">
        <div class="section-c tabs-c">
            <nav class="tabs-nav">
                <ul class="navProfile">
                    <li id='title_1_tab' class="active">
                        <a id="title_1" href="#"><?= $this->lng['espace-emprunteur']['titre-onglet-informations'] ?></a>
                    </li>
                    <li id='title_2_tab'>
                        <a id="title_2" href="#"><?= $this->lng['espace-emprunteur']['titre-onglet-banque'] ?></a>
                    </li>
                </ul>
            </nav>
            <div class="tabs">
                <div class="tab page1 tab-manage">
                    <div class="account-data">
                        <div class="row">
                            <span
                                class="small info"><?= $this->lng['espace-emprunteur']['vos-informations-personnelles'] ?></span>
                            <span class="small content"><?= $this->clients->prenom ?></span>
                            <span class="small content"><?= $this->clients->nom ?></span>
                            <span class="small content"><?= $this->clients->function ?></span>
                        </div>
                        <div class="row">
                            <span class="medium content"><?= $this->clients->mobile ?></span>
                            <span class="medium content"><?= $this->clients->email ?></span>
                        </div>
                        <div class="row">
                            <span
                                style="background: #ae0364; width: 900px; height: 5px; margin: 12px; border-radius: 3px; float:left;"></span>
                        </div>
                        <div class="row">
                            <span class="small info"
                                  style="height: 200px;"><?= $this->lng['espace-emprunteur']['coordonnes-de-votre-entreprise'] ?></span>
                            <span class="medium content"><?= $this->companies->siren ?></span>
                        </div>
                        <div class="row">
                            <span class="large content"><?= $this->companies->name ?></span>
                        </div>
                        <div class="row">
                            <span class="large content"><?= $this->companies->adresse1 ?></span>
                        </div>
                        <div class="row">
                            <span class="medium content"><?= $this->companies->zip ?></span>
                            <span class="medium content"><?= $this->companies->city ?></span>
                        </div>
                        <div class="row">
                            <span class="medium content"><?= $this->companies->phone ?></span>
                            <span class="medium content"><?= $this->companies->email_facture ?></span>
                        </div>
                    </div>
                    <div class="clear" style="clear: both"></div>
                    <div class="row row-btn">
                        <a href="contact">
                            <button class="btn" style="float: right;">
                                <?= $this->lng['espace-emprunteur']['demande-modification'] ?>
                                <i class="icon-arrow-next"></i>
                            </button>
                        </a>
                    </div>
                </div>
                <div class="tab page2" style="display: none;">
                    <div class="row row-bank">
                        <div class="row" style="float: left;">
                            <span class="small info"><?= $this->lng['espace-emprunteur']['votre-rib'] ?></span>
                            <span class="medium content"><?= $this->companies->bic ?></span>
                        </div>
                        <div class="row" style="float: left;">
                            <span class="small info">&nbsp;</span>
                            <span class="large content"><?= $this->companies->iban ?></span>
                        </div>
                        <div class="row" style="float: left;">
                            &nbsp;
                            <p><?= $this->lng['espace-emprunteur']['explication-changement-rib'] ?></p>
                        </div>
                        <div class="clear" style="clear: both"></div>
                        <div class="row row-btn">
                            <a href="contact">
                                <button class="btn" style="float: right;">
                                    <?= $this->lng['espace-emprunteur']['demande-modification'] ?>
                                    <i class="icon-arrow-next"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script>
    $(function () {
        $("#tabs").tabs();
    });
</script>


