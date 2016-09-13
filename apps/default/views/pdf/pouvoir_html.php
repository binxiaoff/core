<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
    <title>SFF docs</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/pdf/style.css" type="text/css" media="all"/>
</head>
<body>
<div class="doc-wrapper">
    <div class="shell">
        <div class="page-break">
            <h3 class="pink"><?= $this->bloc_pouvoir['pouvoir'] ?></h3>
            <h5><?= $this->bloc_pouvoir['je-soussigne'] ?></h5>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">
                            Raison sociale
                        </div>
                        <div class="col-small">
                            <?= $this->companies->name ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Adresse
                        </div>
                        <div class="col-small">
                            <?= $this->companies->adresse1 ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Code postal
                        </div>
                        <div class="col-small">
                            <?= $this->companies->zip ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Ville
                        </div>
                        <div class="col-small">
                            <?= $this->companies->city ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            SIREN
                        </div>
                        <div class="col-small">
                            <?= $this->companies->siren ?>
                        </div>
                    </li>
                </ul>
            </div>
            <h5><?= $this->bloc_pouvoir['represente-par-142'] ?></h5>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">
                            Nom
                        </div>
                        <div class="col-small">
                            <?= $this->clients->nom ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Prénom
                        </div>
                        <div class="col-small">
                            <?= $this->clients->prenom ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Fonction
                        </div>
                        <div class="col-small">
                            <?= $this->clients->fonction ?>
                        </div>
                    </li>
                </ul>
            </div>

            <h5><?= $this->bloc_pouvoir['donne-pouvoir-a'] ?></h5>
            <div class="list">
                <ul>
                    <li>
                        <?= $this->lng['pdf-pouvoir']['adresse'] ?>
                    </li>
                    <li>
                        <h5><?= $this->bloc_pouvoir['pour-signer-en-mon'] ?></h5>
                    </li>
                    <li>
                        <div class="col-long">
                            <?= $this->bloc_pouvoir['montant-total'] ?>
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->montantPrete, 0) ?> &euro;
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            <?= $this->bloc_pouvoir['taux-i-annuel'] ?>
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->taux) ?> %
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <?php if ($this->nbLoansBDC > 0) : ?>
                    <li>
                        <div class="col-long">
                            <?= $this->bloc_pouvoir['nombre-de-bons'] ?>
                        </div>
                        <div class="col-small">
                            <?= $this->nbLoansBDC ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <?php endif; ?>
                    <?php if ($this->nbLoansIFP > 0) : ?>
                    <li>
                        <div class="col-long">
                            <?= $this->bloc_pouvoir['nombre-de-contrat'] ?>
                        </div>
                        <div class="col-small">
                            <?= $this->nbLoansIFP ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <?php endif; ?>
                    <li>
                        <div class="col-long">
                            <?= $this->bloc_pouvoir['date-de-creation-148'] ?>
                        </div>
                        <div class="col-small">
                            <?= $this->dateRemb ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            <?= $this->bloc_pouvoir['date-decheance-149'] ?>
                        </div>
                        <div class="col-small">
                            <?= date('d/m/Y', strtotime($this->dateLastEcheance)) ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            <?= $this->bloc_pouvoir['nombre-mensualites'] ?>
                        </div>
                        <div class="col-small">
                            <?= $this->projects->period ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            <?= $this->bloc_pouvoir['montant-mensualites'] ?>
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->rembByMonth) ?> &euro;
                        </div>
                    </li>
                </ul>
            </div>
            <div class="list">
                <ul>
                    <li>
                        <h5><?= $this->bloc_pouvoir['les-bons-nominatifs'] ?></h5>
                    </li>
                    <li><?= $this->bloc_pouvoir['la-signature-engage'] ?></li>
                    <li>
                        <div class="col-long">
                            <?= $this->bloc_pouvoir['a-rembourser-153'] ?>
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->montantPrete, 0) ?> &euro;
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            <?= $this->bloc_pouvoir['assortie'] ?>
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->taux) ?> %
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <?= $this->bloc_pouvoir['selon-echeancier-156'] ?>
                    </li>
                </ul>
            </div>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">
                            <?= $this->bloc_pouvoir['signature-emetteur'] ?>
                        </div>
                        <div class="col-small">
                            <div style="background-color:white;border:1px solid #808080;height: 50px;width: 250px;"></div>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                </ul>
            </div>
            <br/>
            <div class="list">
                <ul>
                    <li><?= $this->bloc_pouvoir['les-bons-de-caisse'] ?></li>
                    <li><?= $this->bloc_pouvoir['legitimite-contrats-de-pret'] ?></li>
                </ul>
            </div>
        </div>
        <div class="pageBreakBefore" style="margin-top: 40px;padding-top: 20px;">
            <h3 class="pink">DERNIER BILAN CERTIFIE SINCERE DE L'EMETTEUR</h3>
            <div class="list">
                <ul>
                    <li>
                        Au <?= $this->dateDernierBilan ?>
                    </li>
                </ul>
                <h5>ACTIF</h5>
                <div class="list">
                    <ul>
                        <li>
                            Immobilisations corporelles
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['immobilisations_corporelles'], 0) ?> &euro;</div>
                        </li>
                        <li>
                            Immobilisations incorporelles
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['immobilisations_incorporelles'], 0) ?> &euro;</div>
                        </li>
                        <li>
                            Immobilisations financières
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['immobilisations_financieres'], 0) ?> &euro;</div>
                        </li>
                        <li>
                            Stocks
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['stocks'], 0) ?> &euro;</div>
                        </li>
                        <li>
                            Créances clients et autres
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['creances_clients'], 0) ?> &euro;</div>
                        </li>
                        <li>
                            Disponibilités
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['disponibilites'], 0) ?> &euro;</div>
                        </li>
                        <li>
                            Valeurs mobilières de placement
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['valeurs_mobilieres_de_placement'], 0) ?> &euro;</div>
                        </li>
                        <?php if ($this->l_AP[0]['comptes_regularisation_actif'] != 0) : ?>
                        <li>
                            Comptes de régularisation
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['comptes_regularisation_actif'], 0) ?> &euro;</div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="total-row">
                    Total actif : <?= $this->ficelle->formatNumber($this->totalActif, 0) ?> &euro;
                </div>
                <h5>PASSIF</h5>
                <div class="list">
                    <ul>
                        <li>
                            Capitaux propres
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['capitaux_propres'], 0) ?> &euro;</div>
                        </li>
                        <li>
                            Provisions pour risques et charges
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['provisions_pour_risques_et_charges'], 0) ?> &euro;</div>
                        </li>
                        <li>
                            Amortissements sur immobilisations
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['amortissement_sur_immo'], 0) ?> &euro;</div>
                        </li>
                        <li>
                            Dettes financières
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['dettes_financieres'], 0) ?> &euro;</div>
                        </li>
                        <li>
                            Dettes fournisseurs
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['dettes_fournisseurs'], 0) ?> &euro;</div>
                        </li>
                        <li>
                            Autres dettes
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['autres_dettes'], 0) ?> &euro;</div>
                        </li>
                        <?php if ($this->l_AP[0]['comptes_regularisation_passif'] != 0) : ?>
                        <li>
                            Comptes de régularisation
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['comptes_regularisation_passif'], 0) ?> &euro;</div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="total-row">
                    Total passif : <?= $this->ficelle->formatNumber($this->totalPassif, 0) ?> &euro;
                </div>
                <div class="center-text">
                    Certifié sincère par l'Emetteur
                </div>
            </div>
        </div>
        <?php if ($this->projects->period > 48): ?>
            <div class="pageBreakBefore" style="margin-top: 40px;padding-top: 20px;">
                <h3 class="pink">ECHEANCIER DES REMBOURSEMENTS</h3>
                <div class="dates-table">
                    <table width="100%" cellspacing="0" cellpadding="0" class="table-2">
                        <tr>
                            <th valign="bottom">DATE</th>
                            <th valign="bottom">CAPITAL</th>
                            <th valign="bottom">INTERETS</th>
                            <th valign="bottom"><?= $this->bloc_pouvoir['commission'] ?><br/> <?= $this->bloc_pouvoir['unilend'] ?></th>
                            <th valign="bottom"><?= $this->bloc_pouvoir['tva'] ?></th>
                            <th valign="bottom">TOTAL</th>
                            <th valign="bottom">CAPITAL RESTANT DÛ</th>
                        </tr>
                        <?php
                        $capRestant = $this->capital;
                        foreach ($this->lRemb as $r) {
                            if ($r['ordre'] <= 48) {
                                $montantEmprunteur = round($r['montant'] + $r['commission'] + $r['tva'], 2);

                                $capRestant -= $r['capital'];
                                if ($capRestant < 0) {
                                    $capRestant = 0;
                                }

                                ?>
                                <tr>
                                    <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->dates->formatDate($r['date_echeance_emprunteur'], 'd/m/Y') ?></td>
                                    <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['capital'] / 100) ?> &euro;</td>
                                    <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['interets'] / 100) ?> &euro;</td>
                                    <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['commission'] / 100) ?> &euro;</td>
                                    <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['tva'] / 100) ?> &euro;</td>
                                    <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($montantEmprunteur / 100) ?> &euro;</td>
                                    <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($capRestant / 100) ?> &euro;</td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </table>
                </div>
            </div>
            <div class="pageBreakBefore" style="margin-top: 40px;padding-top: 20px;">
                <div class="dates-table">
                    <table width="100%" cellspacing="0" cellpadding="0" class="table-2">
                        <?php
                        foreach ($this->lRemb as $r) {
                            if ($r['ordre'] > 48) {
                                $montantEmprunteur = round($r['montant'] + $r['commission'] + $r['tva'], 2);

                                $capRestant -= $r['capital'];
                                if ($capRestant < 0) {
                                    $capRestant = 0;
                                }

                                ?>
                                <tr>
                                    <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->dates->formatDate($r['date_echeance_emprunteur'], 'd/m/Y') ?></td>
                                    <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['capital'] / 100) ?> &euro;</td>
                                    <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['interets'] / 100) ?> &euro;</td>
                                    <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['commission'] / 100) ?> &euro;</td>
                                    <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['tva'] / 100) ?> &euro;</td>
                                    <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($montantEmprunteur / 100) ?> &euro;</td>
                                    <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($capRestant / 100) ?> &euro;</td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="page-break">
                <h3 class="pink">ECHEANCIER DES REMBOURSEMENTS</h3>
                <div class="dates-table">
                    <table width="100%" cellspacing="0" cellpadding="0" class="table-2">
                        <tr>
                            <th valign="bottom">DATE</th>
                            <th valign="bottom">CAPITAL</th>
                            <th valign="bottom">INTERETS</th>
                            <th valign="bottom"><?= $this->bloc_pouvoir['commission'] ?><br/> <?= $this->bloc_pouvoir['unilend'] ?></th>
                            <th valign="bottom"><?= $this->bloc_pouvoir['tva'] ?></th>
                            <th valign="bottom">TOTAL</th>
                            <th valign="bottom">CAPITAL RESTANT DÛ</th>
                        </tr>
                        <?php

                        $capRestant = $this->capital;
                        foreach ($this->lRemb as $r) {
                            $montantEmprunteur = round($r['montant'] + $r['commission'] + $r['tva'], 2);

                            $capRestant -= $r['capital'];
                            if ($capRestant < 0) {
                                $capRestant = 0;
                            }

                            ?>
                            <tr>
                                <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->dates->formatDate($r['date_echeance_emprunteur'], 'd/m/Y') ?></td>
                                <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['capital'] / 100) ?> &euro;</td>
                                <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['interets'] / 100) ?> &euro;</td>
                                <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['commission'] / 100) ?> &euro;</td>
                                <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['tva'] / 100) ?> &euro;</td>
                                <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($montantEmprunteur / 100) ?> &euro;</td>
                                <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($capRestant / 100) ?> &euro;</td>
                            </tr>
                            <?
                        }
                        ?>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        <?php
        $var = 0;
        $nb  = intval((count($this->lLenders) / 26));
        for ($a = 0; $a <= $nb; $a++) {
            if ($var == count($this->lLenders)) {
                break;
            }
            ?>
            <div class="pageBreakBefore" style="padding-top: 30px;">
                <?php if ($var == 0): ?>
                    <h3><?= $this->bloc_pouvoir['liste-caracteristiques'] ?></h3>
                <?php endif; ?>
                <div class="dates-table">
                    <table width="100%" cellspacing="0" cellpadding="0" class="table-3">
                        <?php if ($var == 0) : ?>
                            <tr>
                                <th><?= $this->bloc_pouvoir['nom'] ?><br/> <?= $this->bloc_pouvoir['raison-sociale'] ?></th>
                                <th><?= $this->bloc_pouvoir['prenom'] ?><br/> <?= $this->bloc_pouvoir['siren'] ?></th>
                                <th><?= $this->bloc_pouvoir['adresse'] ?></th>
                                <th><?= $this->bloc_pouvoir['code'] ?><br/> <?= $this->bloc_pouvoir['postal'] ?></th>
                                <th><?= $this->bloc_pouvoir['ville'] ?></th>
                                <th><?= $this->bloc_pouvoir['montant-172'] ?></th>
                                <th><?= $this->bloc_pouvoir['taux'] ?><br/> <?= $this->bloc_pouvoir['interet-174'] ?></th>
                            </tr>
                        <?php endif; ?>
                        <?php
                        $i = 0;
                        foreach ($this->lLenders as $key => $l) {
                            if ($var == $key) {
                                if ($i <= 26) {
                                    $this->oLendersAccounts->get($l['id_lender'], 'id_lender_account');
                                    $this->clients->get($this->oLendersAccounts->id_client_owner, 'id_client');
                                    $this->clients_adresses->get($this->clients->id_client, 'id_client');

                                    $nom    = $this->clients->nom;
                                    $prenom = $this->clients->prenom;

                                    if ($this->clients->type == 2) {
                                        $this->companies->get($this->clients->id_client, 'id_client_owner');

                                        $nom    = $this->companies->name;
                                        $prenom = $this->companies->siren;
                                    }

                                    ?>
                                    <tr>
                                        <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $nom ?></td>
                                        <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $prenom ?></td>
                                        <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->clients_adresses->adresse1 ?></td>
                                        <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->clients_adresses->cp ?></td>
                                        <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->clients_adresses->ville ?></td>
                                        <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($l['amount'] / 100, 0) ?> &euro;</td>
                                        <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($l['rate'], 1) ?> %</td>
                                    </tr>
                                    <?php
                                    $var++;
                                    $i++;
                                }
                            }
                        }
                        ?>
                    </table>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
</div>
</body>
</html>
