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
        height: 18px;
    }

    .medium {
        height: 18px;
    }

    .large {
        height: 18px;
    }

    .row-btn {
        margin-top: 35px;
    }

    .row-btn .btn {
        line-height: 1.2em;
    }

    .info th {
        width: 25%;
    }

    #account-data-person tr, #account-data-company tr {
        height: 100%;
    }

    #account-data-person table, #account-data-company table {
        table-layout: fixed;
        width: 100%;
    }

    #account-data-company .td_small, #account-data-person .td_small {
        width: 25%;
    }

    #account-data-company .td_medium, #account-data-person .td_medium {
        width: 50%;
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
                        <table id="account-data-person">
                            <tr>
                                <th rowspan="3" class="td_small small info">
                                    <div
                                        class="small info"><?= $this->lng['espace-emprunteur']['vos-informations-personnelles'] ?></div>
                                </th>
                                <td class="td_small small">
                                    <div
                                        class="small content"><?= empty($this->clients->prenom) === false ? $this->clients->prenom : $this->lng['espace-emprunteur']['prenom'] ?></div>
                                </td>
                                <td class="td_small small">
                                    <div
                                        class="small content"><?= empty($this->clients->nom) === false ? $this->clients->nom : $this->lng['espace-emprunteur']['nom'] ?></div>
                                </td>
                                <td class="td_small small">
                                    <div
                                        class="small content"><?= empty($this->clients->function) === false ? $this->clients->function : $this->lng['espace-emprunteur']['fonction'] ?></div>
                                </td>
                            </tr>
                            <tr>
                                <td class="td_small"><div
                                        class="small info"><?= $this->lng['espace-emprunteur']['telephone-mobile'] ?></div>
                                </td>
                                <td colspan="2"><div
                                        class="medium content"><?= empty($this->clients->mobile) === false ? $this->clients->mobile : $this->lng['espace-emprunteur']['telephone-mobile'] ?></div>
                                </td>
                            </tr>
                            <tr>
                                <td class="td_small"><div
                                        class="small info"><?= $this->lng['espace-emprunteur']['email'] ?></div></td>
                                <td colspan="2"><div
                                        class="medium content"><?= empty($this->clients->email) === false ? $this->clients->email : $this->lng['espace-emprunteur']['email'] ?></div>
                                </td>
                            </tr>
                        </table>

                        <div class="row">
                            <span
                                style="background: #ae0364; width: 900px; height: 5px; margin: 12px; border-radius: 3px; float:left;"></span>
                        </div>

                        <table id="account-data-company">
                            <tr>
                                <th rowspan="8" class="td_small"><div
                                        class="small info"><?= $this->lng['espace-emprunteur']['coordonnes-de-votre-entreprise'] ?></div>
                                </th>
                                <td>
                                    <div class="info small"><?= $this->lng['espace-emprunteur']['siren'] ?></div>
                                </td>
                                <td class="td_medium">
                                    <div
                                        class="medium content"><?= empty($this->companies->siren) === false ? $this->companies->siren : $this->lng['espace-emprunteur']['siren'] ?></div>
                                </td>
                            </tr>
                            <tr>
                                <td class="td_small"><div class="info small"><?= $this->lng['espace-emprunteur']['raison-sociale'] ?></div></td>
                                <td><div
                                        class="large content"><?= empty($this->companies->name) === false ? $this->companies->name : $this->lng['espace-emprunteur']['raison-sociale'] ?></div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3"><h4><?= $this->lng['espace-emprunteur']['adresse'] ?></h4></td>
                            </tr>
                            <tr>
                                <td colspan="3"><div
                                        class="large content"><?= empty($this->companies->adresse1) === false ? $this->companies->adresse1 : $this->lng['espace-emprunteur']['adresse'] ?></div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div class="content">
                                        <span class="small"><?= $this->companies->zip ?></span>
                                        <span class="medium"><?= $this->companies->city ?></span>
                                    </div>
                                </td>
                            </tr>
                            <tr></tr>
                            <tr>
                                <td>
                                    <div class="info"><?= $this->lng['espace-emprunteur']['telephone-societe'] ?></div>
                                </td>
                                <td>
                                    <div class="medium content"><?= $this->companies->phone ?></div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="info"><?= $this->lng['espace-emprunteur']['email-facturation'] ?></div>
                                </td>
                                <td>
                                    <div class="medium content"><?= $this->companies->email_facture ?></div>
                                </td>
                            </tr>
                        </table>

                    </div>
                    <div class="clear" style="clear: both"></div>
                    <div class="row row-btn">
                        <a href="contact">
                            <button class="btn" style="margin-left: 389px;">
                                <?= $this->lng['espace-emprunteur']['demande-modification'] ?>
                                <i class="icon-arrow-next"></i>
                            </button>
                        </a>
                    </div>
                </div>
                <div class="tab page2" style="display: none;">
                    <div class="row row-bank">
                        <table>
                            <tr>
                                <th rowspan="3">
                                    <div class="small info"><?= $this->lng['espace-emprunteur']['votre-rib'] ?></div></th>
                                <td>
                                    <label for="bic"><?=$this->lng['espace-emprunteur']['bic']?></label>
                                </td>
                                <td>
                                    <div class="content field-small"><?= $this->companies->bic ?></div>
                                </td>
                            </tr>
                            <tr><td colspan="3"><div class="info"></div></td></tr>
                            <tr><td><label class="inline-text"><?=$this->lng['espace-emprunteur']['iban']?></label></td>
                                <td colspan="2">
                                    <?php if (empty($this->companies->iban) === false ) : ?>
                                    <span class="content field-extra-tiny"><?= substr($this->companies->iban, 0, 4) ?></span>
                                    <span class="content field-extra-tiny"><?= substr($this->companies->iban, 4, 4) ?></span>
                                    <span class="content field-extra-tiny"><?= substr($this->companies->iban, 8, 4) ?></span>
                                    <span class="content field-extra-tiny"><?= substr($this->companies->iban, 12, 4) ?></span>
                                    <span class="content field-extra-tiny"><?= substr($this->companies->iban, 16, 4) ?></span>
                                    <span class="content field-extra-tiny"><?= substr($this->companies->iban, 20, 4) ?></span>
                                    <span class="content field-extra-tiny"><?= substr($this->companies->iban, 24, 3) ?></span>
                                    <?php else : ?>
                                    <div class="content"></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr><td colspan="2"><div class="info"></div></td></tr>
                        </table>

                        <div class="row">
                            <p><?= $this->lng['espace-emprunteur']['explication-changement-rib'] ?></p>
                        </div>
                        <div class="clear" style="clear: both"></div>
                        <div class="row row-btn">
                            <a href="contact">
                                <button class="btn" style="margin-left: 389px;">
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


