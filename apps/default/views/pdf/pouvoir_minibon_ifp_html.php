<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
    <title>Unilend</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/pdf/style.css" type="text/css" media="all"/>
    <style type="text/css">
        @media print {
            table {
                page-break-after: auto
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto
            }

            td {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }

        .doc-wrapper .shell {
            width: 90%;
            margin: 0 auto;
            padding: 15px 0;
        }
    </style>
</head>
<body>
<?php $numberOfRowsPerPage = 27; ?>
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
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->montantPrete, 0) ?>&nbsp;€</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Taux d'intérêt annuel moyen</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->taux) ?>&nbsp;%</div>
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
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->rembByMonth) ?>&nbsp;€</div>
                    </li>
                </ul>
            </div>
            <br>
            <h5>Les bons de caisse émis sont nominatifs. Le Bon de caisse est cessible à la condition pour le cessionnaire d’avoir préalablement ouvert un compte Unilend prêteur.</h5>
            <div class="list">
                <ul>
                    <li>Le Bénéficiaire certifie avoir inscrit la liste de prêteurs telle que récapitulée ci-dessous dans le Registre des Bons de caisse</li>
                    <li>La signature des bons de caisse et contrats de prêt par Unilend engage l'Emetteur, en contrepartie des sommes remises ce jour</li>
                    <li>
                        <div class="col-long">à rembourser aux Prêteurs la somme de</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->montantPrete, 0) ?>&nbsp;€</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">assortie des intérêts à</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->taux) ?>&nbsp;%</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>selon l'échéancier annexé aux présentes.</li>
                </ul>
            </div>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">Signature de l'Emetteur</div>
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
                    <li>Les bons de caisse sont réalisés selon les dispositions légales prévues aux articles L226-3 à L226-13 du code monétaire et financier.</li>
                    <li>Les contrats de prêt sont réalisés selon les dispositions légales prévues aux articles R548-6 et R558-8 du code monétaire et financier.</li>
                </ul>
            </div>
            <div class="pageBreakBefore">
                <h3 class="pink">ECHEANCIER DES REMBOURSEMENTS</h3>
                <div class="dates-table">
                    <table width="100%" cellspacing="0" cellpadding="0" class="table-2">
                        <tr>
                            <th>DATE</th>
                            <th>CAPITAL</th>
                            <th>INTERETS</th>
                            <th>COMMISSION<br/>UNILEND H.T.</th>
                            <th>TVA</th>
                            <th>TOTAL</th>
                            <th>CAPITAL RESTANT DÛ</th>
                        </tr>
                        <?php $capRestant   = $this->capital; ?>
                        <?php $printedLines = 0; ?>
                        <?php foreach ($this->lRemb as $r) : ?>
                            <?php $montantEmprunteur = round($r['montant'] + $r['commission'] + $r['tva'], 2); ?>
                            <?php $capRestant        -= $r['capital']; ?>
                            <?php if ($capRestant < 0) : ?>
                                <?php $capRestant = 0; ?>
                            <?php endif; ?>
                            <tr>
                                <td height="35" style="width: 15%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->dates->formatDate($r['date_echeance_emprunteur'], 'd/m/Y') ?></td>
                                <td height="35" style="width: 15%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['capital'] / 100) ?>&nbsp;€</td>
                                <td height="35" style="width: 10%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['interets'] / 100) ?>&nbsp;€</td>
                                <td height="35" style="width: 15%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['commission'] / 100) ?>&nbsp;€</td>
                                <td height="35" style="width: 10%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($r['tva'] / 100) ?>&nbsp;€</td>
                                <td height="35" style="width: 15%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($montantEmprunteur / 100) ?>&nbsp;€</td>
                                <td height="35" style="width: 20%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($capRestant / 100) ?>&nbsp;€</td>
                            </tr>
                            <?php $printedLines++; ?>
                            <?php if (0 === $printedLines % $numberOfRowsPerPage || $printedLines === count($this->lRemb)) : ?>
                                        </table>
                                    </div> <!--dates-table-->
                                </div> <!--pageBreakBefore-->
                                <?php if ($printedLines < count($this->lRemb)): ?>
                                    <div class="pageBreakBefore">
                                        <div class="dates-table">
                                            <table width="100%" cellspacing="0" cellpadding="0" class="table-2">
                                                <tr>
                                                    <th>DATE</th>
                                                    <th>CAPITAL</th>
                                                    <th>INTERETS</th>
                                                    <th>COMMISSION<br/>UNILEND H.T.</th>
                                                    <th>TVA</th>
                                                    <th>TOTAL</th>
                                                    <th>CAPITAL RESTANT DÛ</th>
                                                </tr>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
            <div class="pageBreakBefore">
                <h3>LISTE ET CARACTERISTIQUES DES BONS DE CAISSE ET DES CONTRATS DE PRET</h3>
                <div class="dates-table">
                    <table width="100%" cellspacing="0" cellpadding="0" class="table-3">
                        <tr>
                            <th>NOM ou<br/>Raison sociale</th>
                            <th>PRENOM<br/>ou SIREN</th>
                            <th>ADRESSE</th>
                            <th>CODE<br/> POSTAL</th>
                            <th>VILLE</th>
                            <th>MONTANT</th>
                            <th>TAUX<br/> D'INTERET</th>
                        </tr>
                        <?php $printedLines = 0; ?>
                        <?php foreach ($this->lLenders as $l) : ?>
                            <?php $wallet = $this->walletRepository->find($l['id_lender']); ?>
                            <?php $this->clients_adresses->get($wallet->getIdClient()->getIdClient(), 'id_client'); ?>
                            <?php $nom    = $wallet->getIdClient()->getNom(); ?>
                            <?php $prenom = $wallet->getIdClient()->getPrenom(); ?>

                            <?php if ($wallet->getIdClient()->getType() == \Unilend\Bundle\CoreBusinessBundle\Entity\Clients::TYPE_LEGAL_ENTITY) : ?>
                                <?php $this->companies->get($wallet->getIdClient()->getIdClient(), 'id_client_owner'); ?>
                                <?php $nom    = $this->companies->name; ?>
                                <?php $prenom = $this->companies->siren; ?>
                            <?php endif; ?>
                            <tr>
                                <td height="35" style="width: 15%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $nom ?></td>
                                <td height="35" style="width: 10%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $prenom ?></td>
                                <td height="35" style="width: 27%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->clients_adresses->adresse1 ?></td>
                                <td height="35" style="width: 7%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->clients_adresses->cp ?></td>
                                <td height="35" style="width: 23%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->clients_adresses->ville ?></td>
                                <td height="35" style="width: 10%;border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($l['amount'] / 100, 0) ?>&nbsp;€</td>
                                <td height="35" style="width: 8%; border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;" class="nowrap"><?= $this->ficelle->formatNumber($l['rate'], 1) ?>&nbsp;%</td>
                            </tr>
                            <?php $printedLines++; ?>
                            <?php if (0 === $printedLines % $numberOfRowsPerPage || $printedLines === count($this->lLenders)) : ?>
                                        </table>
                                    </div> <!--dates-table-->
                                </div> <!--pageBreakBefore-->
                                <?php if ($printedLines < count($this->lLenders)): ?>
                                    <div class="pageBreakBefore">
                                        <div class="dates-table">
                                            <table width="100%" cellspacing="0" cellpadding="0" class="table-3">
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
                            <?php endif; ?>
                        <?php endforeach; ?>
        </div> <!-- page-break -->
    </div> <!-- shell -->
</div> <!-- doc-wrapper -->
</body>
</html>
