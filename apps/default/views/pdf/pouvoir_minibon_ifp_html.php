<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
    <title>Unilend</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/pdf/style.css" type="text/css" media="all"/>
</head>
<body>
<div class="doc-wrapper">
    <div class="shell">
        <div class="page-break">
            <h3 class="pink">POUVOIR</h3>
            <h5>Je soussigné</h5>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">Raison sociale</div>
                        <div class="col-small"><?= $this->companies->name ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Adresse</div>
                        <div class="col-small"><?= $this->companies->adresse1 ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Code postal</div>
                        <div class="col-small"><?= $this->companies->zip ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Ville</div>
                        <div class="col-small"><?= $this->companies->city ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">SIREN</div>
                        <div class="col-small"><?= $this->companies->siren ?></div>
                    </li>
                </ul>
            </div>
            <h5>représenté par</h5>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">Nom</div>
                        <div class="col-small"><?= $this->pdfClient->nom ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Prénom</div>
                        <div class="col-small"><?= $this->pdfClient->prenom ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Fonction</div>
                        <div class="col-small"><?= $this->pdfClient->fonction ?></div>
                    </li>
                </ul>
            </div>
            <h5>donne pouvoir à</h5>
            <div class="list">
                <ul>
                    <li><?= $this->lng['pdf-pouvoir']['adresse'] ?></li>
                </ul>
            </div>
            <h5>pour signer en mon nom et pour mon compte l'intégralité des bons de caisse et contrats de prêt récapitulés ci-après et correspondant au total du financement recueilli sur Unilend.fr dont les caractéristiques sont les suivantes&nbsp;:</h5>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">Montant total</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->montantPrete, 0) ?> &euro;</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Taux d'intérêt annuel moyen</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->taux) ?> %</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <?php if ($this->nbLoansMinibon > 0) : ?>
                        <li>
                            <div class="col-long">Nombre de bons de caisse</div>
                            <div class="col-small"><?= $this->nbLoansMinibon ?></div>
                            <div class="cl">&nbsp;</div>
                        </li>
                    <?php endif; ?>
                    <?php if ($this->nbLoansIFP > 0) : ?>
                        <li>
                            <div class="col-long">Nombre de contrats de prêt</div>
                            <div class="col-small"><?= $this->nbLoansIFP ?></div>
                            <div class="cl">&nbsp;</div>
                        </li>
                    <?php endif; ?>
                    <li>
                        <div class="col-long">Date de création</div>
                        <div class="col-small"><?= $this->dateRemb ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Date d'échéance</div>
                        <div class="col-small"><?= date('d/m/Y', strtotime($this->dateLastEcheance)) ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Nombre de mensualités</div>
                        <div class="col-small"><?= $this->projects->period ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Montant des mensualités (principal et intérêts compris)</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->rembByMonth) ?> &euro;</div>
                    </li>
                </ul>
            </div>
            <h5>Les bons de caisse émis sont nominatifs. Le Bon de caisse est cessible à la condition pour le cessionnaire d’avoir préalablement ouvert un compte Unilend prêteur.</h5>
            <div class="list">
                <ul>
                    <li>Le Bénéficiaire certifie avoir inscrit la liste de prêteurs telle que récapitulée ci-dessous dans le Registre des Bons de caisse</li>
                    <li>La signature des bons de caisse et contrats de prêt par Unilend engage l'Emetteur, en contrepartie des sommes remises ce jour</li>
                    <li>
                        <div class="col-long">à rembourser aux Prêteurs la somme de</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->montantPrete, 0) ?> &euro;</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">assortie des intérêts à</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->taux) ?> %</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>selon l'échéancier annexé aux présentes.</li>
                </ul>
            </div>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">Signature de l'Emetteur</div>
                        <div class="col-small"><div style="background-color:white;border:1px solid #808080;height: 50px;width: 250px;"></div></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                </ul>
            </div>
            <br/>
            <div class="list">
                <ul>
                    <li>Les bons de caisse sont réalisés selon les dispositions légales prévues aux articles L226-3 à L226-13 du code monétaire et financier.</li>
                    <li>Les contrats de prêt sont réalisés selon les dispositions légales prévues aux articles R548-6 et R558-8 du code monétaire et financier.</li>
                </ul>
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
                            <th valign="bottom">COMMISSION<br/> UNILEND H.T.</th>
                            <th valign="bottom">TVA</th>
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
                            <th valign="bottom">COMMISSION<br/> UNILEND H.T.</th>
                            <th valign="bottom">TVA</th>
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
        for ($a = 0; $a <= $nb; $a++) :
            if ($var == count($this->lLenders)) :
                break;
            endif;
            ?>
            <div class="pageBreakBefore" style="padding-top: 30px;">
                <?php if ($var == 0): ?>
                    <h3>LISTE ET CARACTERISTIQUES DES BONS DE CAISSE ET DES CONTRATS DE PRET</h3>
                <?php endif; ?>
                <div class="dates-table">
                    <table width="100%" cellspacing="0" cellpadding="0" class="table-3">
                        <?php if ($var == 0) : ?>
                            <tr>
                                <th>NOM ou<br/>Raison sociale</th>
                                <th>PRENOM<br/>ou SIREN</th>
                                <th>ADRESSE</th>
                                <th>CODE<br/> POSTAL</th>
                                <th>VILLE</th>
                                <th>MONTANT</th>
                                <th>TAUX<br/> D'INTERET</th>
                            </tr>
                        <?php endif; ?>
                        <?php
                        $i = 0;
                        foreach ($this->lLenders as $key => $l) :
                            if ($var == $key) :
                                if ($i <= 26) :
                                    /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $wallet */
                                    $wallet = $this->walletRepository->find($l['id_lender']);
                                    $this->clients->get($wallet->getIdClient()->getIdClient(), 'id_client');
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
                                endif;
                            endif;
                        endforeach;
                        ?>
                    </table>
                </div>
            </div>
            <?php
        endfor;
        ?>
    </div>
</div>
</body>
</html>
