<div id="contenu">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1>Recherche emprunteurs</h1>
            </div>
        </div>

        <form method="post" action="<?= $this->lurl ?>/sfpmei/emprunteurs" role="search">
            <div class="form-group row">
                <label for="siren" class="col-md-2 col-form-label">SIREN</label>
                <div class="col-md-4">
                    <input id="siren" name="siren" type="text" class="form-control">
                </div>
                <label for="company" class="col-md-2 col-form-label">Raison sociale</label>
                <div class="col-md-4">
                    <input id="company" name="company" type="text" class="form-control">
                </div>
            </div>
            <div class="form-group row">
                <label for="lastname" class="col-md-2 col-form-label">Nom dirigeant</label>
                <div class="col-md-4">
                    <input id="lastname" name="lastname" type="text" class="form-control">
                </div>
                <label for="email" class="col-md-2 col-form-label">Email</label>
                <div class="col-md-4">
                    <input id="email" name="email" type="text" class="form-control">
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-12">
                    <button type="submit" class="btn col-md-2 pull-right">Chercher</button>
                </div>
            </div>
        </form>
    </div>

    <?php if (false === empty($_SESSION['error_search'])) : ?>
        <div class="attention">
            <?= implode('<br>', $_SESSION['error_search']) ?>
            <?php unset($_SESSION['error_search']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($this->borrowers)) : ?>
        <div class="container-fluid">
            <?php if (count($this->borrowers) > 0) : ?>
                <div class="row">
                    <div class="col-md-12">
                        <h1><?= count($this->borrowers) ?> emprunteur<?= count($this->borrowers) > 1 ? 's' : '' ?> trouvé<?= count($this->borrowers) > 1 ? 's' : '' ?></h1>
                    </div>
                </div>
                <table class="tablesorter table table-hover table-striped">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>SIREN</th>
                        <th>Raison sociale</th>
                        <th>Dirigeant</th>
                        <th>Email</th>
                        <th>Montant cumulé</th>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; ?>
                    <?php foreach ($this->borrowers as $borrower) : ?>
                        <tr class="<?= ($i++ % 2 == 1 ? '' : 'odd') ?>">
                            <td><?= $borrower['id_client'] ?></td>
                            <td><?= $borrower['siren'] ?></td>
                            <td><?= $borrower['name'] ?></td>
                            <td><?= $borrower['prenom'] ?> <?= $borrower['nom'] ?></td>
                            <td><?= $borrower['email'] ?></td>
                            <td><?= $this->ficelle->formatNumber($borrower['total_amount'], 0) ?> €</td>
                            <td align="center">
                                <a href="<?= $this->lurl ?>/emprunteur/<?= $borrower['id_client'] ?>">
                                    <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Voir la fiche de l'emprunteur">
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($this->borrowers) > $this->pagination) : ?>
                    <div id="pagination" class="row">
                        <div class="col-md-12 text-center">
                            <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first">
                            <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev">
                            <input type="text" class="pagedisplay input_court text-center" title="Page" disabled>
                            <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next">
                            <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last">
                            <select class="pagesize sr-only" title="Page">
                                <option value="<?= $this->pagination ?>" selected="selected"><?= $this->pagination ?></option>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else : ?>
                <h1>Aucun résultat trouvé pour cette recherche</h1>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    $(function() {
        jQuery.tablesorter.addParser({
            id: 'amount',
            type: 'numeric',
            is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            },
            format: function (s) {
                return s.replace(/,/g, '').replace('€', '').replace(' ', '');
            }
        });

        $('.tablesorter').tablesorter({headers: {5: {sorter: 'amount'}, 6: {sorter: false}}});

        <?php if (count($this->borrowers) > $this->pagination) : ?>
            $('.tablesorter').tablesorterPager({container: $('#pagination'), positionFixed: false, size: <?= $this->pagination ?>});
        <?php endif; ?>

        $('#company').autocomplete({
            source: '<?= $this->lurl ?>/sfpmei/autocompleteCompanyName/',
            minLength: 3,
            delay: 100
        });
    });
</script>
