<script type="text/javascript">
    $(document).ready(function () {
        $(".tablesorter").tablesorter({headers: {7: {sorter: false}}});
        <?php
        if(empty($this->nb_lignes) === false) : ?>
        $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?=$this->nb_lignes?>});
        <?php endif; ?>
    });
    <?php
    if(isset($_SESSION['freeow'])) : ?>
    $(document).ready(function () {
        var title, message, opts, container;
        title = "<?=$_SESSION['freeow']['title']?>";
        message = "<?=$_SESSION['freeow']['message']?>";
        opts = {};
        opts.classes = ['smokey'];
        $('#freeow-tr').freeow(title, message, opts);
    });
    <?php
        unset($_SESSION['freeow']);
        endif;
    ?>
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/preteurs" title="Clients">Prêteurs</a> -</li>
        <li>Gestion des prêteurs</li>
    </ul>
    <?php
    if (isset($_POST['form_search_client'])) : ?>
        <h1>Résultats de la recherche prêteurs non
            inscrits <?= (count($this->lPreteurs) > 0 ? '(' . count($this->lPreteurs) . ')' : '') ?></h1>
    <?php else : ?>
        <h1>Liste des <?= count($this->lPreteurs) ?> prêteurs non inscrits</h1>
    <?php endif; ?>
    <div class="btnDroite"><a href="<?= $this->lurl ?>/preteurs/search_non_inscripts" class="btn_link thickbox">Rechercher
            un prêteur</a></div>
    <?php  if (count($this->lPreteurs) > 0) : ?>
    <table class="tablesorter">
        <thead>
        <tr>
            <th>ID</th>
            <th>Nom / Raison sociale</th>
            <th>Nom d'usage</th>
            <th>Prénom / Dirigeant</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Montant sur Unilend</th>
            <th>Nbre d'enchères en cours</th>
            <th>&nbsp;</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $i = 1;
        foreach ($this->lPreteurs as $c) : ?>

            <tr class="<?= ($i++ % 2 == 1 ? '' : 'odd') ?> ">


                <td class="leLender<?= $c['id_lender_account'] ?>"><?= $c['id_client'] ?></td>
                <td class="leLender<?= $c['id_lender_account'] ?>"><?= $c['nom_ou_societe'] ?></td>
                <td class="leLender<?= $c['id_lender_account'] ?>"><?= $c['nom_usage'] ?></td>
                <td class="leLender<?= $c['id_lender_account'] ?>"><?= $c['prenom_ou_dirigeant'] ?></td>
                <td class="leLender<?= $c['id_lender_account'] ?>"><?= $c['email'] ?></td>
                <td class="leLender<?= $c['id_lender_account'] ?>"><?= $c['telephone'] ?></td>
                <td class="leLender<?= $c['id_lender_account'] ?>"><?= $this->ficelle->formatNumber($c['solde']) ?>
                    €
                </td>
                <td class="leLender<?= $c['id_lender_account'] ?>"><?= $c['bids_encours'] ?></td>
                <td align="center">
                    <img
                        onclick="if(confirm('Voulez vous <?= ((int)$c['status'] ===  \clients::STATUS_ONLINE ? 'Passer hors ligne' : 'Passer en ligne') ?> ce preteur ?')){window.location = '<?= $this->lurl ?>/preteurs/liste_preteurs_non_inscrits/status/<?= $c['id_client'] ?>/<?= ((int)$c['status'] === \clients::STATUS_ONLINE ? \clients::STATUS_OFFLINE : \clients::STATUS_ONLINE ) ?>';}"
                        src="<?= $this->surl ?>/images/admin/<?= ((int)$c['status'] === \clients::STATUS_ONLINE ? 'offline' : 'online') ?>.png"
                        alt="<?= ((int)$c['status'] === \clients::STATUS_ONLINE ? 'Passer hors ligne' : 'Passer en ligne') ?>"/>

                    <a href="<?= $this->lurl ?>/preteurs/edit/<?= $c['id_lender_account'] ?>">
                        <img src="<?= $this->surl ?>/images/admin/edit.png"
                             alt="Modifier <?= $c['nom'] . ' ' . $c['prenom'] ?>"/>
                    </a>
                    <script>
                        $(".leLender<?=$c['id_lender_account']?>").click(function () {
                            $(location).attr('href', '<?=$this->lurl?>/preteurs/edit/<?=$c['id_lender_account']?>');
                        });
                    </script>
                </td>
            </tr>
            <?php
        endforeach; ?>
        </tbody>
    </table>
        <?php if (empty($this->nb_lignes) === false) : ?>
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
        <?php else : ?>
            <?php
            if (isset($_POST['form_search_client'])) : ?>
                <p>Il n'y a aucun prêteur non inscrit pour cette recherche.</p>
            <?php else: ?>
                <p>Il n'y a aucun prêteur non inscrit pour le moment.</p>
            <?php endif; ?>
        <?php endif;
    endif; ?>
</div>
<?php unset($_SESSION['freeow']); ?>