<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
    <title>Unilend</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/pdf/style.css" type="text/css" media="all"/>
</head>
<body>
<!-- Doc Wrapper -->
<div class="doc-wrapper">
    <!-- Shell -->
    <div class="shell">
        <div>
            <h1>Certificat d’inscription au registre des Bons de caisse au sens de l’article L 223-6 du Code monétaire et financier</h1>
            <hr/>
            <h3>CERTIFICAT D’INSCRIPTION AU REGISTRE DES BONS DE CAISSE #<?= $this->oLoans->id_loan ?></h3>
            <h5>Désignation de l’Emprunteur</h5>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">Raison sociale</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->name ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Forme juridique</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->forme ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Capital social</div>
                        <div class="col-small"><?= (0 < $this->companiesEmprunteur->capital) ? $this->ficelle->formatNumber($this->companiesEmprunteur->capital, 0) : 0 ?>&nbsp;&euro;</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Adresse du siège social</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->adresse1 ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Code postal</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->zip ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Ville</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->city ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Tribunal de commerce</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->tribunal_com ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">R.C.S.</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->siren ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Objet social</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->activite ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Lieu d’exploitation</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->city ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                </ul>
            </div>
            <h5>Désignation du prêteur</h5>
            <div class="list">
                <ul>
                    <?php if (in_array($this->clients->type, [
                        \Unilend\Bundle\CoreBusinessBundle\Entity\Clients::TYPE_LEGAL_ENTITY,
                        \Unilend\Bundle\CoreBusinessBundle\Entity\Clients::TYPE_LEGAL_ENTITY_FOREIGNER
                    ])) : ?>
                        <li>
                            <div class="col-long">Raison sociale</div>
                            <div class="col-small"><?= $this->companiesPreteur->name ?></div>
                            <div class="cl">&nbsp;</div>
                        </li>
                        <li>
                            <div class="col-long">Forme juridique</div>
                            <div class="col-small"><?= $this->companiesPreteur->forme ?></div>
                            <div class="cl">&nbsp;</div>
                        </li>
                        <li>
                            <div class="col-long">Adresse du siège social</div>
                            <div class="col-small"><?= $this->companiesPreteur->adresse1 ?></div>
                            <div class="cl">&nbsp;</div>
                        </li>
                        <li>
                            <div class="col-long">Code postal</div>
                            <div class="col-small"><?= $this->companiesPreteur->zip ?></div>
                            <div class="cl">&nbsp;</div>
                        </li>
                        <li>
                            <div class="col-long">Ville</div>
                            <div class="col-small"><?= $this->companiesPreteur->city ?></div>
                            <div class="cl">&nbsp;</div>
                        </li>
                        <li>
                            <div class="col-long">Tribunal de commerce</div>
                            <div class="col-small"><?= $this->companiesPreteur->tribunal_com ?></div>
                            <div class="cl">&nbsp;</div>
                        </li>
                        <li>
                            <div class="col-long">R.C.S.</div>
                            <div class="col-small"><?= $this->companiesPreteur->siren ?></div>
                            <div class="cl">&nbsp;</div>
                        </li>
                    <?php else : ?>
                        <li>
                            <div class="col-long">Nom</div>
                            <div class="col-small"><?= $this->clients->nom ?></div>
                            <div class="cl">&nbsp;</div>
                        </li>
                        <li>
                            <div class="col-long">Prénom</div>
                            <div class="col-small"><?= $this->clients->prenom ?></div>
                            <div class="cl">&nbsp;</div>
                        </li>
                        <li>
                            <div class="col-long">Date de naissance</div>
                            <div class="col-small"><?= \DateTime::createFromFormat('Y-m-d', $this->clients->naissance)->format('d/m/Y') ?></div>
                            <div class="cl">&nbsp;</div>
                        </li>
                        <li>
                            <div class="col-long">Adresse</div>
                            <div class="col-small"><?= $this->clients_adresses->adresse1 ?></div>
                            <div class="cl">&nbsp;</div>
                        </li>
                        <li>
                            <div class="col-long">Code postal</div>
                            <div class="col-small"><?= $this->clients_adresses->cp ?></div>
                            <div class="cl">&nbsp;</div>
                        </li>
                        <li>
                            <div class="col-long">Ville</div>
                            <div class="col-small"><?= $this->clients_adresses->ville ?></div>
                            <div class="cl">&nbsp;</div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <h5>Caractéristiques du Bon de caisse</h5>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">Montant total</div>
                        <div class="col-small"> <?= $this->ficelle->formatNumber($this->oLoans->amount / 100, 0) ?>&nbsp;&euro; </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>&nbsp;</li>
                    <li>
                        <div class="col-long">Taux d’intérêt</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->oLoans->rate) ?>&nbsp;%</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Date de création</div>
                        <div class="col-small"><?= $this->dateRemb ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Date d’échéance</div>
                        <div class="col-small"><?= \DateTime::createFromFormat('Y-m-d H:i:s', $this->dateLastEcheance)->format('d/m/Y') ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Montant total des intérêts</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->fInterestTotal) ?>&nbsp;&euro;</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Frais totaux pour l’emprunteur</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->fCommissionRepayment + $this->fCommissionProject) ?>&nbsp;&euro;</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Coût total du prêt</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->fCommissionRepayment + $this->fCommissionProject + $this->fInterestTotal) ?>&nbsp;&euro;</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Amortissement</div>
                        <div class="col-small">Constant</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>&nbsp;</li>
                    <li>Le Bon de caisse émis est nominatif.</li>
                    <li>Le Bon de caisse est cessible à la condition pour le cessionnaire d’avoir préalablement ouvert un compte Unilend prêteur </li>
                    <li>L’Emetteur certifie avoir établi le bilan de son troisième exercice social.</li>
                    <li>La signature des Bons de Caisse engage l’Emetteur, en contrepartie des sommes remises ce jour</li>
                    <li>
                        <div class="col-long">à rembourser aux Prêteurs la somme de</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->oLoans->amount / 100, 0) ?>&nbsp;&euro;</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">assortie des intérêts à</div>
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->oLoans->rate) ?> %</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">selon l’échéancier annexé aux présentes.</div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>&nbsp;</li>
                    <li>
                        <div class="col-long">
                            Fait à Paris, le <?= $this->dateContrat ?><br/>
                            <strong>Signé par l’Emetteur</strong><br/>
                            <strong>Représenté par Unilend</strong>
                        </div>
                        <div class="col-small">
                            <div class="logo"></div>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Page Break -->
        <div class="pageBreakBefore">
            <p>Le Bon de caisse objet du présent certificat est émis selon les dispositions légales prévues aux articles L.226-3 à L.226-13 et D 223-1 à D 223-4 du code monétaire et financier. Unilend est conseiller en investissements participatifs, enregistré à l’ORIAS sous le numéro 15006955, dont le siège est au 6, rue du général Clergerie, 75116 Paris, dont le téléphone est le 01.82.28.51.20. et dont le site Internet est accessible à l’adresse www.unilend.fr</p>
            <h3>CONDITIONS D’EXIGIBILITE ANTICIPEE</h3>
            <p>L’Emetteur sera tenu de rembourser immédiatement au Prêteur l’ensemble des sommes dues au titre du Bon de caisse en cas de survenance de l’un quelconque des événements prévus ci-dessous.</p>
            <p>Constitue un Cas d’Exigibilité Anticipée :</p>
            <ol type="a">
                <li>l’exercice par le Bénéficiaire de son droit de rétractation ;</li>
                <li>le non-paiement par l’Emetteur de six remboursements mensuels dus au titre du Bon de caisse ;</li>
                <li>le non-respect par l’Emetteur de l’utilisation des fonds au projet de financement telle que prévue dans la présentation du projet au prêteur ;</li>
                <li>l’inexactitude de l’une quelconque des informations et déclarations relatives notamment à la situation financière de l’Emetteur ;</li>
                <li>la non-certification des comptes de l’Emetteur par le Commissaire aux Comptes ;</li>
                <li>le non-respect par l’Emetteur de l’un quelconque de ses engagements au titre des Conditions Générales de Vente  Bénéficiaire</li>
            </ol>
            <p>L’Emprunteur est tenu dans les 8 jours de la survenance d’un Cas d’Exigibilité Anticipée d’en informer Unilend, lequel a reçu mandat du Prêteur en cas de défaut de paiement comme en cas de survenance d’un Cas d’Exigibilité Anticipée de mandater tout Recouvreur.</p>
            <p>En cas de survenance d’un Cas d’Exigibilité Anticipé, toutes les sommes dues par l’Emetteur en exécution du Bon de caisse deviendront exigibles de plein droit sans qu’il soit besoin de mise en demeure préalable de quelque nature que ce soit. L’Emetteur devra alors payer immédiatement tous montants dus (principal et intérêt courus) au titre du Bon de caisse sans préjudice de tout autre dommage et intérêt qui pourrait être dû au Prêteur.</p>
            <h3>REMBOURSEMENT ANTICIPE</h3>
            <p>A chaque date d’échéance, l’Emetteur pourra rembourser par anticipation, sans pénalité, l’intégralité des sommes restant dues au titre du Bon de caisse. En cas de remboursement anticipé, l’Emetteur ne sera pas tenu au paiement non courus à la date de remboursement anticipé.</p>
        </div>
        <!-- End Page Break -->

        <!-- Page Break -->
        <div class="pageBreakBefore">
            <h3>ECHEANCIER DES REMBOURSEMENTS DU BON DE CAISSE</h3>

            <div class="dates-table">
                <table width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <th valign="bottom">DATE</th>
                        <th valign="bottom">CAPITAL</th>
                        <th valign="bottom">INTERETS</th>
                        <th valign="bottom">TOTAL</th>
                        <th valign="bottom">CAPITAL RESTANT DÛ</th>
                    </tr>
                    <?php
                    $capRestant = $this->capital;
                    foreach ($this->lRemb as $r) :
                        $capRestant -= $r['capital'];
                        if ($capRestant < 0) {
                            $capRestant = 0;
                        }
                    ?>
                        <tr>
                            <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->dates->formatDate($r['date_echeance'], 'd/m/Y') ?></td>
                            <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->ficelle->formatNumber($r['capital'] / 100) ?>&nbsp;&euro;</td>
                            <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->ficelle->formatNumber($r['interets'] / 100) ?>&nbsp;&euro;</td>
                            <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->ficelle->formatNumber($r['montant'] / 100) ?>&nbsp;&euro;</td>
                            <td style="border-bottom: dotted 1px #c0c0c0;border-right: solid 1px #c0c0c0;"><?= $this->ficelle->formatNumber($capRestant / 100) ?>&nbsp;&euro;</td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <!-- End Page Break -->
    </div>
    <!-- End Shell -->
</div>
<!-- End Doc Wrapper -->
</body>
</html>
