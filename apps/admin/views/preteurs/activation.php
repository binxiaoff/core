<script type="text/javascript">
    $(document).ready(function () {
        $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});
        <?
        if($this->nb_lignes != '')
        {
        ?>
        $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?=$this->nb_lignes?>});
        <?
        }
        ?>
    });
    <?
    if(isset($_SESSION['freeow']))
    {
    ?>
    $(document).ready(function () {
        var title, message, opts, container;
        title = "<?=$_SESSION['freeow']['title']?>";
        message = "<?=$_SESSION['freeow']['message']?>";
        opts = {};
        opts.classes = ['smokey'];
        $('#freeow-tr').freeow(title, message, opts);
    });
    <?
    unset($_SESSION['freeow']);
    }
    ?>
</script>


<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/preteurs" title="Clients">Prêteurs</a> -</li>
        <li>Activation prêteurs</li>
    </ul>
    <h1>Activation prêteurs</h1>

    <?
    if (count($this->lPreteurs) > 0) {
        ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nom / Raison sociale</th>
                <th>Prénom / Dirigeant</th>
                <th>Date création</th>
                <th>Montant</th>
                <th>Statut</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $i      = 1;
            foreach ($this->lPreteurs as $c) {
                if (isset($this->aGreenPointStatus[$c['id_client']])) {
                    $sGreenPointStatus = $this->aGreenPointStatus[$c['id_client']];
                    if (preg_match('/^[8-9]{3}$/', $sGreenPointStatus)) {
                        $sBGColor = '#14892c;';
                    } else {
                        if (preg_match('/[1-7]+/', $sGreenPointStatus)) {
                            $sBGColor = '#f79232;';
                        } else {
                            $sBGColor = '#ff0100;';
                        }
                    }
                } else {
                    $sGreenPointStatus = 'Non contrôlé';
                    $sBGColor = '#ff0100;';
                }

                // Solde du compte preteur
                $solde = $this->transactions->getSolde($c['id_client']);

                if ($this->companies->get($c['id_client'], 'id_client_owner')) {
                    if ($this->companies->status_client != 1) {
                        $prenom = $this->companies->prenom_dirigeant . ' ' . $this->companies->nom_dirigeant;
                    } else {
                        $prenom = $c['prenom'] . ' ' . $c['nom'];
                    }

                    $nom = $this->companies->name;
                } else {
                    $nom    = $c['nom'];
                    $prenom = $c['prenom'];
                }

                if ($c['type_transfert'] == 1) {
                    $val = 'Virement';
                } else {
                    $val = $this->ficelle->formatNumber($solde) . ' €';
                }
                ?>

                <tr class="<?= ($i % 2 == 1 ? '' : 'odd') ?> ">
                    <td align="center" style="border-radius: 7px; color: #ffffff; font-weight: bold; font-size: 14px; background-color: <?= $sBGColor ?>" title="Statut Green Point : <?= $sGreenPointStatus ?>"><?= $c['id_client'] ?></td>
                    <td><?= $nom ?></td>
                    <td><?= $prenom ?></td>
                    <td align="center"><?= date('d/m/Y', strtotime($c['added'])) ?></td>
                    <td align="center"><?= $val ?></td>
                    <td align="center"><?= $c['label_status'] ?></td>
                    <td align="center">
                        <?
                        if (in_array($c['status_client'], array(clients_status::TO_BE_CHECKED, clients_status::COMPLETENESS_REPLY, clients_status::MODIFICATION))) {
                            ?>
                            <a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $c['id_lender'] ?>" class="btn_link" style="padding: 3px;">Contrôler</a><?
                        } else {
                            ?><a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $c['id_lender'] ?>">Détails</a><?
                        }
                        ?>

                    </td>
                </tr>
                <?
                $i++;
            }
            ?>
            </tbody>
        </table>
        <?
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
            <?
        }
        ?>
        <?
    } else {
        ?>
        <?
        if (isset($_POST['form_search_client'])) {
            ?>
            <p>Il n'y a aucun prêteur pour cette recherche.</p>
            <?
        } else {
            ?>
            <p>Il n'y a aucun prêteur pour le moment.</p>
            <?
        }
        ?>
        <?
    }
    ?>
</div>
<?php unset($_SESSION['freeow']); ?>