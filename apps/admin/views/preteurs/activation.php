<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>

        <?php if (isset($_SESSION['freeow'])) : ?>
            var title = "<?= $_SESSION['freeow']['title'] ?>",
                message = "<?= $_SESSION['freeow']['message'] ?>",
                opts = {};

            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
            <?php unset($_SESSION['freeow']); ?>
        <?php endif; ?>
    });
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <h1>Activation prêteurs</h1>
    <?php if (count($this->lPreteurs) > 0) : ?>
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
                <?php $iRow = 1; ?>
                <?php foreach ($this->lPreteurs as $c) : ?>
                    <?php
                        $sGreenPointStatus = '';
                        $sWaitingForGP     = '';
                        $sBGColor          = '';

                        if (isset($this->aGreenPointStatus[$c['id_client']])) {
                            $sWaitingForGP     = '';
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
                        } elseif (in_array($c['status_client'], array(clients_status::TO_BE_CHECKED, clients_status::COMPLETENESS_REPLY, clients_status::MODIFICATION))) {
                            $sWaitingForGP = '&nbsp;<span style="font-weight: bold; color: #f79232;">Attente Green Point</span>';
                            $sBGColor      = '';
                        }

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
                    <tr class="<?= ($iRow % 2 == 1 ? '' : 'odd') ?> ">
                        <td align="center" <?php if (false === empty($sBGColor)) : ?>style="border-radius: 7px; color: #ffffff; font-weight: bold; font-size: 14px; background-color: <?= $sBGColor ?>" <?php endif; ?> title="Statut Green Point : <?= $sGreenPointStatus ?>"><?= $c['id_client'] ?></td>
                        <td><?= $nom ?></td>
                        <td><?= $prenom ?></td>
                        <td align="center"><?= date('d/m/Y', strtotime($c['added'])) ?></td>
                        <td align="center"><?= $val ?></td>
                        <td align="center"><?= $c['label_status'] . $sWaitingForGP ?></td>
                        <td align="center">
                            <?php if (in_array($c['status_client'], array(clients_status::TO_BE_CHECKED, clients_status::COMPLETENESS_REPLY, clients_status::MODIFICATION))) : ?>
                                <a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $c['id_lender'] ?>" class="btn_link" style="padding: 3px;">Contrôler</a>
                            <?php else : ?>
                                <a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $c['id_lender'] ?>">Détails</a>
                            <?php endif; ?>

                        </td>
                    </tr>
                    <?php $iRow++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($this->nb_lignes != '') : ?>
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
        <?php endif; ?>
    <?php else : ?>
        <?php if (isset($_POST['form_search_client'])) : ?>
            <p>Il n'y a aucun prêteur pour cette recherche.</p>
        <?php else : ?>
            <p>Il n'y a aucun prêteur pour le moment.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
