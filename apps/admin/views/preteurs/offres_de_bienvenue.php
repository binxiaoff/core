<script type="text/javascript">
    $(function() {
        jQuery.tablesorter.addParser({
            id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"
        });

        $(".tablesorter").tablesorter();

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
        $("#datepik_1").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 10)?>:<?=(date('Y') + 10)?>'
        });
        $("#datepik_2").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 10)?>:<?=(date('Y') + 10)?>'
        });

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>

        <?php if (isset($_SESSION['freeow'])) : ?>
            var title = "<?= $_SESSION['freeow']['title'] ?>",
                message = "<?= $_SESSION['freeow']['message'] ?>",
                opts = {},
                container;

            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
            <?php unset($_SESSION['freeow']); ?>
        <?php endif; ?>
    });
</script>

<style type="text/css">
    table.formColor {
        width: 697px;
    }

    .select {
        width: 251px;
    }

    .fenetre_offres_de_bienvenues {
        width: 697px;
        background-color: white;
        border: 1px solid #A1A5A7;
        border-radius: 10px 10px 10px 10px;
        padding: 5px;
    }
</style>

<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <h1>Gestion offre de bienvenue</h1>
    <div class="fenetre_offres_de_bienvenues">
        <form method="post" name="form_offres" id="form_offres" enctype="multipart/form-data" action="" target="_parent">
            <fieldset>
                <table class="formColor">
                    <tr>
                        <th><label for="datepik_1">Debut de l'offre :</label></th>
                        <td>
                            <input type="text" name="debut" id="datepik_1" class="input_dp" value="<?= $this->debut ?>"/>
                        </td>
                        <th><label for="datepik_2">Fin de l'offre :</label></th>
                        <td><input type="text" name="fin" id="datepik_2" class="input_dp" value="<?= $this->fin ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="montant">Montant de l'offre :</label></th>
                        <td>
                            <input type="text" name="montant" id="montant" class="input_moy" value="<?= $this->montant ?>"/> €
                        </td>
                        <th><label for="montant">Dépenses max :</label></th>
                        <td>
                            <input type="text" name="montant_limit" id="montant_limit" class="input_moy" value="<?= $this->montant_limit ?>"/> €
                        </td>
                    </tr>
                    <tr>
                        <th><label>Motif :</label></th>
                        <td><?= $this->motifOffreBienvenue ?></td>
                        <th><label for="montant">Solde Reel disponible :</label></th>
                        <td><?= $this->ficelle->formatNumber($this->sumDispoPourOffres / 100) ?> €</td>
                    </tr>

                    <tr>
                        <th colspan="4" style="text-align:center;">
                            <input type="hidden" name="form_send_offres" id="form_send_offres"/>
                            <input type="submit" value="Mettre à jour" title="Mettre à jour" name="send_offres" id="send_offres" class="btn"/>
                        </th>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
    <?php if (count($this->lOffres) > 0) : ?>
        <h2>Somme des offres de bienvenue déjà donnée : <?= $this->ficelle->formatNumber($this->sumOffres / 100) ?> €</h2>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Motif</th>
                    <th>Source3</th>
                    <th>Id client</th>
                    <th>Nom</th>
                    <th>Prenom</th>
                    <th>Email</th>
                    <th>Montant</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->lOffres as $o) : ?>
                    <?php $this->clients->get($o['id_client'], 'id_client'); ?>
                    <tr class="<?= ($i % 2 == 1 ? '' : 'odd') ?> ">
                        <td><?= $o['motif'] ?></td>
                        <td><?= $this->clients->slug_origine ?></td>
                        <td><?= $o['id_client'] ?></td>
                        <td><?= $this->clients->nom ?></td>
                        <td><?= $this->clients->prenom ?></td>
                        <td><?= $this->clients->email ?></td>
                        <td align="center"><?= $this->ficelle->formatNumber($o['montant'] / 100) ?> €</td>
                        <td align="center"><?= date('d/m/y H:i:s', strtotime($o['added'])) ?></td>
                    </tr>
                    <?php $i++; ?>
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
    <?php endif; ?>
</div>
