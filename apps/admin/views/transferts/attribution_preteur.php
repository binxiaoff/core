<script type="text/javascript">
    $(function() {
        $('.tablePreteur').tablesorter({headers: {4: {sorter: false}}});
        $('.tablePreteur').tablesorterPager({container: $('#search-pager'), positionFixed: false, size: 5});

        $('#newRecherche').click(function() {
            document.getElementById('search-lender').reset();
            $('#response').hide(0);
            $('#lender-form-container').show(0, function() {
                $.colorbox.resize();
            });
        });
    });

    function attribuer_preteur(id_client, id_reception) {
        if (confirm('Voulez vous vraiment attribuer la somme à ce prêteur ?')) {
            var val = {
                id_client: id_client,
                id_reception: id_reception
            };

            $.post(add_url + '/transferts/attribuer_preteur', val).done(function(data) {
                if (data != 'nok') {
                    $(".attrib").html('');
                    $(".num_client_" + id_reception).html(data);
                    $(".statut_operation_" + id_reception).html('Attribué manu');
                    $(".ajouter_" + id_reception).hide();
                    $(".annuler_" + id_reception).show();

                    $(".reponse_valid_vir").show();

                    setTimeout(function() {
                        $.colorbox.close();
                    }, 1000);
                }
            });
        }
    }
</script>
<br/><br/><br/>
<div class="btnDroite"><a href="#" id="newRecherche" class="btn_link">Nouvelle recherche</a></div>
<?php if (count($this->lPreteurs) > 0) : ?>
    <table class="tablesorter tablePreteur">
        <thead>
        <tr>
            <th>ID</th>
            <th>Nom / Raison sociale</th>
            <th>Prénom / Dirigeant</th>
            <th>Téléphone</th>
            <th>Email</th>
            <th>&nbsp;</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->lPreteurs as $c) : ?>
            <?php
                $i = 1;
                $companies = false;
                if ($this->companies->get($c['id_client'], 'id_client_owner')) {
                    $companies = true;

                    if ($this->companies->status_client == 1) {
                        $this->clients->get($this->companies->id_client_owner, 'id_client');
                        $dirigeant = $this->clients->prenom . ' ' . $this->clients->nom;
                    } else {
                        $dirigeant = $this->companies->prenom_dirigeant . ' ' . $this->companies->nom_dirigeant;
                    }
                }
            ?>
            <tr class="<?= ($i++ % 2 == 1 ? '' : 'odd') ?> leLender<?= $c['id_lender_account'] ?>">
                <td><?= $c['id_client'] ?></td>
                <td><?= $c['nom_ou_societe'] ?></td>
                <td><?= $c['prenom_ou_dirigeant'] ?></td>
                <td><?= $c['telephone'] ?></td>
                <td><?= $c['email'] ?></td>
                <td class="attrib" align="center">
                    <a onclick="attribuer_preteur(<?= $c['id_client'] ?>, <?= $this->id_reception ?>);" title="Attribuer client <?= $c['id_client'] ?>">Attribuer</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div id="search-pager" style="text-align:center;">
        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
        <input type="hidden" class="pagesize" value="5"/>
        <input type="text" class="pagedisplay" disabled/>
        <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
        <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
    </div>
<?php else : ?>
    <p>Il n'y a aucun prêteur pour le moment.</p>
<?php endif; ?>
