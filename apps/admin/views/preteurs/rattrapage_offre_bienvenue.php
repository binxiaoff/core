<style>
    .search_fields td {
        padding-top: 23px;
        padding-left: 10px;
    }
    .th-id, .th-nom, .th-prenom, .th-email, .th-dc, .th-dv {
        width: 149px;
    }
    .th-action {
        width: 207px;
    }
</style>
<div id="offer-search" class="block block-bordered">
    <div class="block-header">
        <h3 class="block-title">Rattrapage</h3>
    </div>
    <div class="block-content">
        <div class="row">
            <div class="col-md-12">
                <form method="post" class="form-inline" style="margin-bottom: 20px">
                    <input type="hidden" name="spy_search" id="spy_search">
                    <label>ID ou liste d'IDs (séparés par virgule)</label><br>
                    <div class="form-group">
                        <input type="text" name="id_client" class="form-control" value="<?= (empty($_POST['dateStart']) && empty($_POST['dateEnd']) && false === empty($_POST['id_client'])) ? $_POST['id_client'] : '' ?>">
                    </div>
                    <button type="submit" class="btn-primary">Rechercher</button>
                    <?php if (false === empty($this->clientsWithoutWelcomeOffer)) : ?>
                        <a href="#toggle-target" id="toggle-trigger" style="display: block; margin: 10px 0 -10px;">Hide table [x]</a>
                    <?php endif; ?>
                </form>
                <?php if (empty($this->clientsWithoutWelcomeOffer)) : ?>
                    <p style="margin-top: 20px">Il n'y a aucun utilisateur pour le moment.</p>
                <?php else : ?>
                    <div id="toggle-target">
                        <div style="height: 34px; overflow-y: scroll; overflow-x: hidden;">
                            <table id="offer-search-table-header" class="table tablesorter" style="margin: 0">
                                <thead>
                                <tr>
                                    <th class="th-id header">Id Client</th>
                                    <th class="th-nom header">Nom</th>
                                    <th class="th-prenom header">Prénom</th>
                                    <th class="th-email header">Email</th>
                                    <th class="th-dc header">Date de création</th>
                                    <th class="th-dv header">Date de validation</th>
                                    <th class="th-action">Action</th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                        <div style="height: 250px; overflow-y: scroll; overflow-x: hidden;">
                            <table id="offer-search-table" class="tablesorter table table-hover table-striped">
                                <thead style="display: none;">
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
                                        <td class="th-id"><?= $client['id_client'] ?></td>
                                        <td class="th-nom"><?= empty($client['company']) ? $client['nom'] : $client['company'] ?></td>
                                        <td class="th-prenom"><?= empty($client['company']) ? $client['prenom'] : '' ?></td>
                                        <td class="th-email"><?= $client['email'] ?></td>
                                        <td class="th-dc"><?= \DateTime::createFromFormat('Y-m-d', $client['date_creation'])->format('d/m/Y') ?></td>
                                        <td class="th-dv"><?= (false === empty($client['date_validation'])) ? \DateTime::createFromFormat('Y-m-d H:i:s', $client['date_validation'])->format('d/m/Y') : '' ?></td>
                                        <td class="th-action">
                                            <?php if (false === empty($client['date_validation'])) : ?>
                                                <a href="<?= $this->lurl ?>/preteurs/affect_welcome_offer/<?= $client['id_client'] ?>" class="btn-primary btn-sm thickbox">Attribuer</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
