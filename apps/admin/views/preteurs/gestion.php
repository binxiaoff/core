<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {8: {sorter: false}}});

        <?php if ($this->nb_lignes != ''): ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?=$this->nb_lignes?>});
        <?php endif; ?>

        <?php if (isset($_SESSION['freeow'])): ?>
            var title = "<?=$_SESSION['freeow']['title']?>",
                message = "<?=$_SESSION['freeow']['message']?>",
                opts = {},
                container;

            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
            <?php unset($_SESSION['freeow']); ?>
        <?php endif; ?>
    });
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/preteurs" title="Clients">Prêteurs</a> -</li>
        <li>Gestion des prêteurs</li>
    </ul>

    <?php if (isset($_POST['form_search_client'])): ?>
        <h1>Résultats de la recherche prêteurs <?= (count($this->lPreteurs) > 0 ? '(' . count($this->lPreteurs) . ')' : '') ?></h1>
    <?php else: ?>
        <h1>Gestion des prêteurs</h1>
    <?php endif; ?>

    <?php if (count($this->lPreteurs) > 0): ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nom / Raison sociale</th>
                <th>Nom d'usage</th>
                <th>Prénom / Dirigeant</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Status</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody>
            <?php $i = 1; ?>
            <?php foreach ($this->lPreteurs as $c): ?>
                <tr class="<?= ($i++ % 2 == 1 ? '' : 'odd') ?> ">
                    <td class="leLender<?= $c['id_lender_account'] ?>"><?= $c['id_client'] ?></td>
                    <td class="leLender<?= $c['id_lender_account'] ?>"><?= $c['nom_ou_societe'] ?></td>
                    <td class="leLender<?= $c['id_lender_account'] ?>"><?= $c['nom_usage'] ?></td>
                    <td class="leLender<?= $c['id_lender_account'] ?>"><?= $c['prenom_ou_dirigeant'] ?></td>
                    <td class="leLender<?= $c['id_lender_account'] ?>"><?= $c['email'] ?></td>
                    <td class="leLender<?= $c['id_lender_account'] ?>"><?= $c['telephone'] ?></td>
                    <td class="leLender<?= $c['id_lender_account'] ?>"><?= $c['status'] == 1 ? 'en ligne' : 'hors ligne' ?></td>
                    <td align="center">
                        <?php if (1 == $c['novalid']): ?>
                            <a href="<?= $this->lurl ?>/preteurs/edit/<?= $c['id_lender_account'] ?>">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $c['nom_ou_societe'] . ' ' . $c['prenom_ou_dirigeant'] ?>"/>
                            </a>
                        <?php else: ?>
                            <a href="<?= $this->lurl ?>/preteurs/edit/<?= $c['id_lender_account'] ?>">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier <?= $c['nom_ou_societe'] . ' ' . $c['prenom_ou_dirigeant'] ?>"/>
                            </a>
                            <script>
                                $(".leLender<?= $c['id_lender_account'] ?>").click(function() {
                                    $(location).attr('href', '<?= $this->lurl ?>/preteurs/edit/<?= $c['id_lender_account'] ?>');
                                });
                            </script>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($this->nb_lignes != ''): ?>
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
    <?php elseif (isset($_POST['form_search_client'])): ?>
        <p>Il n'y a aucun prêteur pour cette recherche.</p>
    <?php endif; ?>
</div>
