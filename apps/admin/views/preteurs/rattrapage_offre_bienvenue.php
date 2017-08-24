<style>
    .datepicker_table {
        width: 650px;
        margin: 0 auto 20px;
        background-color: white;
        border: 1px solid #A1A5A7;
        border-radius: 10px 10px 10px 10px;
        padding: 5px;
        padding-bottom: 20px;
    }

    .csv {
        margin-bottom: 20px;
        float: right;
    }

    .search_fields td {
        padding-top: 23px;
        padding-left: 10px;
    }
</style>
<section class="row">
    <div class="col-md-12">
        <h3>Rattrapage offre de bienvenue</h3> <br>
        <form method="post" name="date_select" class="form-inline">
            <input type="hidden" name="spy_search" id="spy_search">
            <label>ID ou liste d'IDs (séparés par virgule)</label><br>
            <div class="form-group">
                <input type="text" name="id_client" class="form-control" value="<?= (empty($_POST['dateStart']) && empty($_POST['dateEnd']) && false === empty($_POST['id_client'])) ? $_POST['id_client'] : '' ?>">
            </div>
            <button type="submit" class="btn-primary">Rechercher</button>
        </form>
        <!-- TODO add hide table button -->
        <?php if (empty($this->clientsWithoutWelcomeOffer)) : ?>
            <p style="margin-top: 20px">Il n'y a aucun utilisateur pour le moment.</p>
        <?php else : ?>
            <div class="table"> <!-- limiter le tableau en lignes et paginer -->
                <table class="tablesorter">
                    <thead>
                    <tr>
                        <th>Id Client</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Date de création</th>
                        <th>Date de validation</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->clientsWithoutWelcomeOffer as $client) : ?>
                        <tr>
                            <td><?= $client['id_client'] ?></td>
                            <td><?= empty($client['company']) ? $client['nom'] : $client['company'] ?></td>
                            <td><?= empty($client['company']) ? $client['prenom'] : '' ?></td>
                            <td><?= $client['email'] ?></td>
                            <td><?= \DateTime::createFromFormat('Y-m-d', $client['date_creation'])->format('d/m/Y') ?></td>
                            <td><?= (false === empty($client['date_validation'])) ? \DateTime::createFromFormat('Y-m-d H:i:s', $client['date_validation'])->format('d/m/Y') : '' ?></td>
                            <td>
                                <?php if (false === empty($client['date_validation'])) : ?>
                                    <a href="<?= $this->lurl ?>/preteurs/affect_welcome_offer/<?= $client['id_client'] ?>" class="link thickbox">Attribuer</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
