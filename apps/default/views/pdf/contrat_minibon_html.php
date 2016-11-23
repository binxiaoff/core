<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
    <title>SFF docs</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/pdf/style.css" type="text/css" media="all"/>
</head>
<body>
<!-- Doc Wrapper -->
<div class="doc-wrapper">
    <!-- Shell -->
    <div class="shell">
        <div>
            <h3>CERTIFICAT D’INSCRIPTION AU REGISTRE DES MINIBONS #<?= $this->oLoans->id_loan ?></h3>

            <h5>Désignation de l'Emprunteur</h5>
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
                        <div class="col-long">Activité</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->activite ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">Lieu d'exploitation</div>
                        <div class="col-small"><?= $this->companiesEmprunteur->lieu_exploi ?></div>
                        <div class="cl">&nbsp;</div>
                    </li>
                </ul>
            </div>
            <h5>Désignation du prêteur</h5>
            <div class="list">
                <ul>
                    <?php
                    if ($this->clients->type == 1) : // particulier
                        ?>
                        <li>Nom
                            <div class="col-small"><?= $this->clients->nom ?></div>
                        </li>
                        <li>Prénom
                            <div class="col-small"><?= $this->clients->prenom ?></div>
                        </li>
                        <li>Date de naissance
                            <div class="col-small"><?= date('d/m/Y', strtotime($this->clients->naissance)) ?></div>
                        </li>
                        <li>Adresse
                            <div class="col-small"><?= $this->clients_adresses->adresse1 ?></div>
                        </li>
                        <li>Code postal
                            <div class="col-small"><?= $this->clients_adresses->cp ?></div>
                        </li>
                        <li>Ville
                            <div class="col-small"><?= $this->clients_adresses->ville ?></div>
                        </li>
                        <?php
                    else : //morale
                        ?>
                        <li>Raison sociale
                            <div class="col-small"><?= $this->companiesPreteur->name ?></div>
                        </li>
                        <li>Forme juridique
                            <div class="col-small"><?= $this->companiesPreteur->forme ?></div>
                        </li>
                        <li>Capital social
                            <div class="col-small"><?= $this->ficelle->formatNumber($this->companiesPreteur->capital, 0) ?>&nbsp;&euro;</div>
                        </li>
                        <li>Adresse du siège social
                            <div class="col-small"><?= $this->companiesPreteur->adresse1 ?></div>
                        </li>
                        <li>Code postal
                            <div class="col-small"><?= $this->companiesPreteur->zip ?></div>
                        </li>
                        <li>Ville
                            <div class="col-small"><?= $this->companiesPreteur->city ?></div>
                        </li>
                        <li>Tribunal de commerce
                            <div class="col-small"><?= $this->companiesPreteur->tribunal_com ?></div>
                        </li>
                        <li>R.C.S.
                            <div class="col-small"><?= $this->companiesPreteur->siren ?></div>
                        </li>
                        <?php
                    endif;
                    ?>
                </ul>
            </div>
            <h5>Caractéristiques du minibon</h5>
            <div class="list">
                <ul>
                    <li>
                        <div class="col-long">
                            Montant total :
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->oLoans->amount / 100, 0) ?>&nbsp;&euro;
                        </div>
                        <div class="cl">&nbsp;</div>
                        <br/>
                    </li>
                    <li>
                        <div class="col-long">
                            Taux d'intérêt annuel :
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->oLoans->rate) ?>&nbsp;%
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Date de création :
                        </div>
                        <div class="col-small">
                            <?= $this->dateRemb ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            Date d'échéance :
                        </div>
                        <div class="col-small">
                            <?= date('d/m/Y', strtotime($this->dateLastEcheance)) ?>
                        </div>
                        <div class="cl">&nbsp;</div>
                        <br/>
                    </li>
                    <li>
                        Le Minibon émis est nominatif.<br>
                        L’Emetteur certifie avoir établi le bilan de son troisième exercice social.<br>
                        La signature des Minibons engage l’Emetteur, en contrepartie des sommes remises ce jour
                    </li>
                    <li>
                        <div class="col-long">
                            à rembourser aux Prêteurs la somme de
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->oLoans->amount / 100, 0) ?>&nbsp;&euro;
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            assortie des intérêts à
                        </div>
                        <div class="col-small">
                            <?= $this->ficelle->formatNumber($this->oLoans->rate) ?> %
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        <div class="col-long">
                            selon l'échéancier annexé aux présentes
                        </div>

                        <div class="cl">&nbsp;</div>
                        <br/>
                    </li>
                    <li>
                        <div class="col-long">
                            Fait à Paris, le  <?= $this->dateContrat ?><br/>
                            <strong>Signé par l’Emetteur</strong><br/>
                            <strong>Représenté par Unilend</strong>
                        </div>
                        <div class="col-small">
                            <div class="logo"></div>
                        </div>
                        <div class="cl">&nbsp;</div>
                    </li>
                    <li>
                        Le Minibon objet du présent certificat est émis selon les dispositions légales prévues aux articles L.226-3 à L.226-13 du code monétaire et financier.
                    </li>
                </ul>
            </div>
        </div>
        <div>
            <h3>DERNIER BILAN CERTIFIE SINCERE DE L’EMPRUNTEUR</h3>
            <div class="list">
                <ul>
                    <li>
                        Au <?= $this->dateDernierBilan ?>
                    </li>
                </ul>
            </div>
            <h5>Actif</h5>
            <div class="list">
                <ul>
                    <li>
                        Immobilisations corporelles :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['immobilisations_corporelles'], 0) ?>&nbsp;&euro;</div>
                    </li>
                    <li>
                        Immobilisations incorporelles :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['immobilisations_incorporelles'], 0) ?>&nbsp;&euro;</div>
                    </li>
                    <li>
                        Immobilisations financières :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['immobilisations_financieres'], 0) ?>&nbsp;&euro;</div>
                    </li>
                    <li>
                        Stocks :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['stocks'], 0) ?>&nbsp;&euro;</div>
                    </li>
                    <li>
                        Créances clients :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['creances_clients'], 0) ?>&nbsp;&euro;</div>
                    </li>
                    <li>
                        Disponibilités :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['disponibilites'], 0) ?>&nbsp;&euro;</div>
                    </li>
                    <li>
                        Valeurs mobilières de placement :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['valeurs_mobilieres_de_placement'], 0) ?>&nbsp;&euro;</div>
                    </li>
                    <?php if ($this->l_AP[0]['comptes_regularisation_actif'] != 0) : ?>
                        <li>
                            Comptes de régularisation
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['comptes_regularisation_actif'], 0) ?>&nbsp;&euro;</div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="total-row" style="white-space:nowrap; text-align:left;">
                Total actif :
                <div style="display:inline;float: right;"><?= $this->ficelle->formatNumber($this->totalActif, 0) ?>&nbsp;&euro;</div>
            </div>
            <br>
            <h5>Passif</h5>
            <div class="list">
                <ul>
                    <li>
                        Capitaux propres :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['capitaux_propres'], 0) ?>&nbsp;&euro;</div>
                    </li>
                    <li>
                        Provisions pour risques et charges :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['provisions_pour_risques_et_charges'], 0) ?>&nbsp;&euro;</div>
                    </li>
                    <li>
                        Amortissements sur immobilisations  :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['amortissement_sur_immo'], 0) ?>&nbsp;&euro;</div>
                    </li>

                    <li>
                        Dettes financières :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['dettes_financieres'], 0) ?>&nbsp;&euro;</div>
                    </li>
                    <li>
                        Dettes fournisseurs :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['dettes_fournisseurs'], 0) ?>&nbsp;&euro;</div>
                    </li>
                    <li>
                        Autres dettes :
                        <div class="col-small"><?= $this->ficelle->formatNumber($this->l_AP[0]['autres_dettes'], 0) ?>&nbsp;&euro;</div>
                    </li>
                    <?php if ($this->l_AP[0]['comptes_regularisation_passif'] != 0) : ?>
                        <li>
                            Comptes de régularisation
                            <div class="col-small nowrap"><?= $this->ficelle->formatNumber($this->l_AP[0]['comptes_regularisation_passif'], 0) ?>&nbsp;&euro;</div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="total-row" style="white-space:nowrap; text-align:left;">
                Total passif :
                <div style="display:inline;float: right;"><?= $this->ficelle->formatNumber($this->totalPassif, 0) ?>&nbsp;&euro;</div>
            </div>
            <div class="center-text">
                Certifié sincère par l’Emetteur
            </div>
            <h3>CONDITIONS D’EXIGIBILITE ANTICIPEE</h3>
            <p>
                L’Emetteur sera tenu de rembourser immédiatement au Prêteur l’ensemble des sommes dues au titre du Minibon en cas de survenance de l’un quelconque des événements prévus ci-dessous.
            </p>
            <p>Constitue un Cas d’Exigibilité Anticipée :</p>
            <ol>
                <li>le non-paiement par l’Emetteur de six remboursements mensuels dus au titre du Minibon ;</li>
                <li>le non-respect par l’Emetteur de l’utilisation des fonds au projet de financement telle que prévue dans la présentation du projet au prêteur ;</li>
                <li>l’inexactitude de l’une quelconque des informations et déclarations relatives notamment à la situation financière de l’Emetteur ;</li>
                <li>la non-certification des comptes de l’Emetteur par le Commissaire aux Comptes ;</li>
                <li>le non-respect par l’Emetteur de l’un quelconque de ses engagements au titre des Conditions Générales de Vente  Bénéficiaire.</li>
            </ol>
            <p>
                L’Emprunteur  est tenu dans les 8 jours de la survenance d’un Cas d’Exigibilité Anticipée d’en informer Unilend, lequel a reçu mandat du Prêteur en cas de défaut de paiement comme en cas de survenance d’un Cas d’Exigibilité Anticipée de mandater tout Recouvreur.
            </p>
            <p>
                En cas de survenance d’un Cas d’Exigibilité Anticipé, toutes les sommes dues par l’Emetteur en exécution du Minibon deviendront exigibles de plein droit sans qu’il soit besoin de mise en demeure préalable de quelque nature que ce soit. L’Emetteur devra alors payer immédiatement tous montants dus (principal et intérêt courus) au titre du Minibon sans préjudice de tout autre dommage et intérêt qui pourrait être dû au Prêteur.
            </p>
            <h3>REMBOURSEMENT ANTICIPE</h3>
            <p>
                A chaque date d'échéance, l'Emetteur pourra rembourser par anticipation, sans pénalité, l'intégralité des sommes restant dues au titre du Minibon. En cas de remboursement anticipé, l'Emetteur ne sera pas tenu au paiement non courus à la date de remboursement anticipé.
            </p>
        </div>
        <!-- End Page Break -->

        <!-- Page Break -->
        <div class="pageBreakBefore">
            <h3>ECHEANCIER DES REMBOURSEMENTS</h3>

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
                    <?php
                    endforeach;
                    ?>

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