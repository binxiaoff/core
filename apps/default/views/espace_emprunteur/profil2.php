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
                    <form>
                        <div class="form account-data">
                            <h3><?= $this->lng['espace-emprunteur']['vos-informations-personnelles'] ?></h3>
                            <div class="row">
                                <div class="inline-text">
                                    <div class="field field-small">
                                        <?= empty($this->clients->prenom) === false ? $this->clients->prenom : $this->lng['espace-emprunteur']['prenom'] ?>
                                    </div>
                                </div>
                                <div class="inline-text">
                                    <div class="field field-small">
                                        <?= empty($this->clients->nom) === false ? $this->clients->nom : $this->lng['espace-emprunteur']['nom'] ?></div>
                                </div>
                                <div class="inline-text">
                                    <div class="field field-small ">
                                        <?= empty($this->clients->function) === false ? $this->clients->function : $this->lng['espace-emprunteur']['fonction'] ?></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="inline-text">
                                    <div class="field-small">
                                        <?= $this->lng['espace-emprunteur']['telephone-mobile'] ?></div>
                                </div>
                                <div class="inline-text">
                                    <div class="field field-medium">
                                        <?= empty($this->clients->mobile) === false ? $this->clients->mobile : $this->lng['espace-emprunteur']['telephone-mobile'] ?></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="inline-text">
                                    <div class="field-small">
                                        <?= $this->lng['espace-emprunteur']['email'] ?></div>
                                </div>
                                <div class="inline-text">
                                    <div class="field field-medium">
                                        <?= empty($this->clients->email) === false ? $this->clients->email : $this->lng['espace-emprunteur']['email'] ?></div>
                                </div>
                            </div>
                    </form>
                    <div class="row">
                            <span
                                style="background: #ae0364; width: 900px; height: 5px; margin: 12px; border-radius: 3px; float:left;"></span>
                    </div>

                    <form id="account-data-company">
                        <div class="row">
                            <h3><?= $this->lng['espace-emprunteur']['coordonnes-de-votre-entreprise'] ?></h3>

                            <div class="inline-text">
                                <div class="field-small"><?= $this->lng['espace-emprunteur']['siren'] ?></div>
                            </div>
                            <div class="inline-text">
                                <div
                                    class="field field-medium"><?= empty($this->companies->siren) === false ? $this->companies->siren : $this->lng['espace-emprunteur']['siren'] ?></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="inline-text">
                                <div
                                    class="field-small"><?= $this->lng['espace-emprunteur']['raison-sociale'] ?></div>
                            </div>
                            <div class="inline-text">
                                <div
                                    class="field field-medium"><?= empty($this->companies->name) === false ? $this->companies->name : $this->lng['espace-emprunteur']['raison-sociale'] ?></div>
                            </div>
                        </div>
                        <div class="row">
                            <h4><?= $this->lng['espace-emprunteur']['adresse'] ?></h4>
                            <div
                                class="field field-large"><?= empty($this->companies->adresse1) === false ? $this->companies->adresse1 : $this->lng['espace-emprunteur']['adresse'] ?></div>
                        </div>
                        <div class="row">
                            <div class="inline-text">
                                <div class="field field-tiny"><?= $this->companies->zip ?></div>
                            </div>
                            <div class="inline-text">
                                <div class="field field-medium"><?= $this->companies->city ?></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="inline-text">
                                <div class="field-small"><?= $this->lng['espace-emprunteur']['telephone-societe'] ?></div>
                            </div>
                            <div class="inline-text">
                                <div class="field field-medium"><?= $this->companies->phone ?></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="inline-text">
                                <div class="field-small"><?= $this->lng['espace-emprunteur']['email-facturation'] ?></div>
                            </div>
                            <div class="inline-text">
                                <div class="field field-medium"><?= $this->companies->email_facture ?></div>
                            </div>
                        </div>
                    </form>

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
                        <form>
                            <div class="row">
                                    <h3><?= $this->lng['espace-emprunteur']['votre-rib'] ?></h3>
                                    <div class="field field-medium"><?= $this->companies->bic ?></div>
                            </div>
                            <div class="row">
<?php if (empty($this->companies->iban) === false ) : ?>
    <div class="inline-text">
            <span class="field field-extra-tiny"><?= substr($this->companies->iban, 0, 4) ?></span></div>
            <div class="inline-text">
                <span class="field field-extra-tiny"><?= substr($this->companies->iban, 4, 4) ?></span></div>
        <div class="inline-text">
                    <span class="field field-extra-tiny"><?= substr($this->companies->iban, 8, 4) ?></span></div>
            <div class="inline-text">
                        <span class="field field-extra-tiny"><?= substr($this->companies->iban, 12, 4) ?></span></div>
                <div class="inline-text">
                            <span class="field field-extra-tiny"><?= substr($this->companies->iban, 16, 4) ?></span></div>
                    <div class="inline-text">
                                <span class="field field-extra-tiny"><?= substr($this->companies->iban, 20, 4) ?></span></div>
                        <div class="inline-text">
                                    <span class="field field-extra-tiny"><?= substr($this->companies->iban, 24, 3) ?></span></div>

                                    <?php else : ?>
                                        <div class="field field-large"></div>
                                    <?php endif; ?>
                                </div>
                        </form>

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


