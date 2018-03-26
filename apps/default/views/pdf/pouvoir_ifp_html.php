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
<div class="doc-wrapper">
    <div class="shell">
        <div class="page-break">
            <h3 class="pink">POUVOIR</h3>
            <h5>Je soussigné</h5>
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
                            <?= $this->companyAddress->getAddress() ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Code postal
                        </div>
                        <div class="col-small">
                            <?= $this->companyAddress->getZip() ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Ville
                        </div>
                        <div class="col-small">
                            <?= $this->companyAddress->getCity() ?>
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
            <h5>représenté par</h5>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">
                            Nom
                        </div>
                        <div class="col-small">
                            <?= $this->pdfClient->nom ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Prénom
                        </div>
                        <div class="col-small">
                            <?= $this->pdfClient->prenom ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Fonction
                        </div>
                        <div class="col-small">
                            <?= $this->pdfClient->fonction ?>
                        </div>
                    </li>
                </ul>
            </div>
            <h5>donne pouvoir à</h5>
            <div class="list">
                <ul>
                    <li>
                        <?= $this->lng['pdf-pouvoir']['adresse'] ?>
                    </li>
                </ul>
            </div>
            <h5>pour signer en mon nom et pour mon compte l'intégralité des contrats de prêt récapitulés ci-après et correspondant au total du financement recueilli sur Unilend.fr dont les caractéristiques sont les suivantes :</h5>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">
                            Montant total
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->montantPrete, 0) ?>&nbsp;€
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Commission de financement HT</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->projects->commission_rate_funds, 2) ?>&nbsp;%</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Montant net versé</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->releasedNetAmount, 0) ?>&nbsp;€</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Taux d'intérêt annuel moyen
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->taux) ?>&nbsp;%
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <?php if ($this->nbLoansIFP > 0) : ?>
                    <li>
                        <div class="col-long">
                            Nombre de contrats de prêt
                        </div>
                        <div class="col-small">
                            <?= $this->nbLoansIFP ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <?php endif; ?>
                    <li>
                        <div class="col-long">
                            Date de création
                        </div>
                        <div class="col-small">
                            <?= $this->dateRemb ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Date d'échéance
                        </div>
                        <div class="col-small">
                            <?= date('d/m/Y', strtotime($this->dateLastEcheance)) ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Nombre de mensualités
                        </div>
                        <div class="col-small">
                            <?= $this->projects->period ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Montant des mensualités (principal et intérêts compris)
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->rembByMonth) ?>&nbsp;€
                        </div>
                    </li>
                </ul>
            </div>
            <br>
            <div class="list">
                <ul>
                    <li>La signature des contrats de prêt par Unilend engage l'Emetteur, en contrepartie des sommes remises ce jour </li>
                    <li>
                        <div class="col-long">
                            à rembourser aux Prêteurs la somme de
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->montantPrete, 0) ?>&nbsp;€
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            assortie des intérêts à
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->taux) ?>&nbsp;%
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        selon l'échéancier annexé aux présentes.
                    </li>
                </ul>
            </div>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">
                            Signature de l'Emetteur
                        </div>
                        <div class="col-small">
                            <div style="background-color:white;height: 22mm;"></div>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                </ul>
            </div>
            <br>
            <br>
            <br>
            <div class="list" style="margin-top: 40px;">
                <ul>
                    <li>Les contrats de prêt sont réalisés selon les dispositions légales prévues aux articles R548-6 et R558-8 du code monétaire et financier.</li>
                </ul>
            </div>
        </div>
        <?php $this->numberOfRowsPerPage = 27; ?>
        <?= $this->fireView('proxy_schedule_table') ?>
        <?php $this->sectionTitle = 'LISTE ET CARACTERISTIQUES DES CONTRATS DE PRET'; ?>
        <?= $this->fireView('proxy_loans_table') ?>
    </div>
</div>
</body>
</html>
