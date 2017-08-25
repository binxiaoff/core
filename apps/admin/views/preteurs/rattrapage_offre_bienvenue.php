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
<div id="offer-search" class="block block-bordered">
    <div class="block-header">
        <h3 class="block-title">Rattrapage</h3>
    </div>
    <div class="block-content">
        <div class="row">
            <div class="col-md-12">
                <form method="post" name="date_select" class="form-inline" style="margin-bottom: 20px">
                    <input type="hidden" name="spy_search" id="spy_search">
                    <label>ID ou liste d'IDs (séparés par virgule)</label><br>
                    <div class="form-group">
                        <input type="text" name="id_client" class="form-control" value="<?= (empty($_POST['dateStart']) && empty($_POST['dateEnd']) && false === empty($_POST['id_client'])) ? $_POST['id_client'] : '' ?>">
                    </div>
                    <button type="submit" class="btn-primary">Rechercher</button>
                </form>
                <?php if (empty($this->clientsWithoutWelcomeOffer)) : ?>
                    <p style="margin-top: 20px">Il n'y a aucun utilisateur pour le moment.</p>
                <?php else : ?>
                    <table class="tablesorter table table-hover table-striped">
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
