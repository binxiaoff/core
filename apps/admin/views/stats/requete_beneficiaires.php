<script type="text/javascript">
    $(document).ready(function () {
        $(".tablesorter").tablesorter();
        <?php
        if ($this->nb_lignes != '') {
        ?>
        $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?=$this->nb_lignes?>});
        <?php
        }
        ?>

    });
    <?php
    if (isset($_SESSION['freeow'])) {
    ?>
    $(document).ready(function () {
        var title, message, opts, container;
        title = "<?=$_SESSION['freeow']['title']?>";
        message = "<?=$_SESSION['freeow']['message']?>";
        opts = {};
        opts.classes = ['smokey'];
        $('#freeow-tr').freeow(title, message, opts);
    });
    <?php
    }
    ?>


</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/settings" title="Configuration">Configuration</a> -</li>
        <li>Administrateurs</li>
    </ul>
    <h1>Requete Bénéficiaires</h1>

    <div style="margin-bottom:20px; float:right;"><a href="<?= $this->lurl ?>/stats/requete_beneficiaires_csv" class="btn_link">Recuperation du CSV</a></div>
    <?php
    if (count($this->lPre) > 0) {
    ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>Cbene</th>
                <th>Nom</th>
                <th>Qualité</th>
                <th>NomJFille</th>
                <th>Prénom</th>
                <th>DateNaissance</th>
                <th>DépNaissance</th>
                <th>ComNaissance</th>
                <th>LieuNaissance</th>
                <th>NomMari</th>
                <th>Siret</th>
                <th>AdISO</th>
                <th>Adresse</th>
                <th>Voie</th>
                <th>CodeCommune</th>
                <th>Commune</th>
                <th>CodePostal</th>
                <th>Ville / nom pays</th>
                <th>IdFiscal</th>
                <th>PaysISO</th>
                <th>Entité</th>
                <th>ToRS</th>
                <th>Plib</th>
                <th>Tél</th>
                <th>Banque</th>
                <th>IBAN</th>
                <th>BIC</th>
                <th>EMAIL</th>
                <th>Obs</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $i = 1;
            foreach ($this->lPre as $e) {
                $this->clients_adresses->get($e['id_client'], 'id_client');
                $this->lenders_accounts->get($e['id_client'], 'id_client_owner');
                $entreprise = false;
                if ($this->companies->get($e['id_client'], 'id_client_owner') && in_array($e['type'], array(2, 4))) {
                    $entreprise = true;
                    if ($this->companies->id_pays == 0) {
                        $this->companies->id_pays = 1;
                    }
                    $this->pays->get($this->companies->id_pays, 'id_pays');
                    $isoFiscal = $this->pays->iso;

                    $ville_paysFiscal = $this->companies->city;

                    $cp = substr($this->companies->zip, 0, 2);
                    if ($cp[0] == 0) {
                        $cp = substr($cp, 1);
                    }

                    // Code commune insee ville
                    $codeCom = $this->villes->getInseeCode($this->companies->zip, $this->companies->city);
                    $codeComNaissance = '';
                    $retenuesource = '';
                    $sLieuNaissance = '';
                } else {
                    $this->etranger = 0;

                    // fr/resident etranger
                    if ($e['id_nationalite'] <= 1 && $this->clients_adresses->id_pays_fiscal > 1) {
                        $this->etranger = 1;
                    } // no fr/resident etranger
                    elseif ($e['id_nationalite'] > 1 && $this->clients_adresses->id_pays_fiscal > 1) {
                        $this->etranger = 2;
                    }

                    // on veut adresse fiscal
                    if ($this->clients_adresses->meme_adresse_fiscal == 1) {
                        $adresse_fiscal = trim($this->clients_adresses->adresse1);
                        $cp_fiscal      = trim($this->clients_adresses->cp);
                        $ville_fiscal   = trim($this->clients_adresses->ville);
                        $id_pays_fiscal = ($this->clients_adresses->id_pays == 0 ? 1 : $this->clients_adresses->id_pays);
                    } else {
                        $adresse_fiscal = trim($this->clients_adresses->adresse_fiscal);
                        $cp_fiscal      = trim($this->clients_adresses->cp_fiscal);
                        $ville_fiscal   = trim($this->clients_adresses->ville_fiscal);
                        $id_pays_fiscal = ($this->clients_adresses->id_pays_fiscal == 0 ? 1 : $this->clients_adresses->id_pays_fiscal);
                    }

                    // date naissance
                    $nais      = explode('-', $e['naissance']);
                    $naissance = $nais[2] . '/' . $nais[1] . '/' . $nais[0];

                    // Iso fiscal
                    if ($this->clients_adresses->id_pays_fiscal == 0) {
                        $this->clients_adresses->id_pays_fiscal = 1;
                    }
                    $this->pays->get($this->clients_adresses->id_pays_fiscal, 'id_pays');
                    $isoFiscal = $this->pays->iso;

                    if ($e['id_pays_naissance'] == 0) {
                        $id_pays_naissance = 1;
                    } else {
                        $id_pays_naissance = $e['id_pays_naissance'];
                    }
                    $this->pays->get($id_pays_naissance, 'id_pays');
                    $isoNaissance = $this->pays->iso;


                    if ($this->etranger == 0) {
                        // Code commune insee ville
                        $codeCom = $this->villes->getInseeCode($cp_fiscal, $ville_fiscal);
                        $commune = '';
                        $cp      = $cp_fiscal;

                        $retenuesource = '';

                        $ville_paysFiscal = $ville_fiscal;
                    } else {
                        $codeCom = $cp_fiscal;
                        $commune = $ville_fiscal;

                        if ($id_pays_fiscal == 0) {
                            $id_pays = 1;
                        } else {
                            $id_pays = $id_pays_fiscal;
                        }
                        $this->pays->get($id_pays, 'id_pays');

                        $this->insee_pays->getByCountryIso(trim($this->pays->iso));
                        $cp = $this->insee_pays->COG;

                        $retenuesource = $this->ficelle->formatNumber($this->retenuesource * 100) . '%';

                        if ($id_pays_fiscal == 0) {
                            $id_pays = 1;
                        } else {
                            $id_pays = $id_pays_fiscal;
                        }
                        $this->pays->get($id_pays, 'id_pays');
                        $paysFiscal = $this->pays->fr;

                        $ville_paysFiscal = $paysFiscal;

                    }

                    if (1 >= $e['id_pays_naissance']) {
                        $sLieuNaissance = $e['ville_naissance'];
                    } else {
                        $this->pays->get($e['id_pays_naissance'], 'id_pays');
                        $sLieuNaissance = $this->pays->fr;
                    }

                    $this->clients->get($e['id_client'], 'id_client');
                    $codeComNaissance = $this->clients->insee_birth == '' ? '00000' : $this->clients->insee_birth;
                    $depNaiss = substr($codeComNaissance, 0, 2) !== '97' ? substr($codeComNaissance, 0, 2) : substr($codeComNaissance, 0, 3);
                } // fin particulier

                // Motif
                $p   = substr($this->ficelle->stripAccents(utf8_decode(trim($e['prenom']))), 0, 1);
                $nom = $this->ficelle->stripAccents(utf8_decode(trim($e['nom'])));
                //$id_client = str_pad($e['id_client'],6,0,STR_PAD_LEFT);
                $id_client = $e['id_client'];
                $motif     = mb_strtoupper($id_client . $p . $nom, 'UTF-8');
                //$motif = mb_strtoupper($id_client.$nom,'UTF-8');
                $motif = substr($motif, 0, 10);
                ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>

                    <?php
                    if ($entreprise == true) {
                        ?>
                        <td><?= $motif ?></td>
                        <td><?= $this->companies->name ?></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><?= $this->companies->siret ?></td>
                        <td><?= $isoFiscal ?></td>
                        <td></td>
                        <td><?= $this->companies->adresse1 ?></td>
                        <td><?= $codeCom ?></td> <!-- code commune inse ou CP si etranger-->
                        <td></td> <!-- commune ou ville si etranger-->
                        <td><?= $this->companies->zip ?></td><!-- cp ou code iso si etranger-->
                        <td><?= $ville_paysFiscal ?></td>
                        <td></td>
                        <td><?= $isoFiscal ?></td> <!-- iso pays naissance -->
                        <td>X</td>
                        <td><?= $retenuesource ?></td>
                        <td>N</td>
                        <td><?= $this->companies->phone ?></td>
                        <td></td>
                        <td><?= $this->lenders_accounts->iban ?></td>
                        <td><?= $this->lenders_accounts->bic ?></td>
                        <td><?= $e['email'] ?></td>
                        <td></td>
                        <?php
                    } else {
                        ?>
                        <td><?= $motif ?></td>
                        <td><?= $e['nom'] ?></td>
                        <td><?= $e['civilite'] ?></td>
                        <td><?= $e['nom'] ?></td>
                        <td><?= $e['prenom'] ?></td>
                        <td><?= $naissance ?></td>
                        <td><?= $depNaiss ?></td>
                        <td><?= $codeComNaissance ?></td>
                        <td><?= $sLieuNaissance ?></td>
                        <td></td>
                        <td></td>
                        <td><?= $isoFiscal ?></td>
                        <td></td>
                        <td><?= $adresse_fiscal ?></td>
                        <td><?= $codeCom ?></td><!-- code commune inse ou CP si etranger-->
                        <td><?= $commune ?></td><!-- commune ou ville si etranger-->
                        <td><?= $cp ?></td><!-- cp ou code iso si etranger-->
                        <td><?= $ville_paysFiscal ?></td>
                        <td></td>
                        <td><?= $isoNaissance ?></td> <!-- iso pays naissance -->
                        <td>X</td>
                        <td><?= $retenuesource ?></td>
                        <td>N</td>
                        <td><?= $e['telephone'] ?></td>
                        <td></td>
                        <td><?= $this->lenders_accounts->iban ?></td>
                        <td><?= $this->lenders_accounts->bic ?></td>
                        <td><?= $e['email'] ?></td>
                        <td></td>
                        <?php
                    }
                    ?>


                </tr>
                <?php
                $i++;
            }
            ?>
            </tbody>
        </table>
        <?php
        if ($this->nb_lignes != '') {
            ?>
            <table>
                <tr>
                    <td id="pager">
                        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay"/>
                        <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                        <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                        <select class="pagesize">
                            <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php
        }
        ?>
        <?php
    } else {
        ?>
        <p>Il n'y a aucun bénéficiaire pour le moment.</p>
        <?php
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>