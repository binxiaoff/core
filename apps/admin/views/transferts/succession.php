<div id="contenu">
    <?php if (false === empty($_SESSION['succession']['error'])) : ?>
        <div class="attention">
            <?= $_SESSION['succession']['error'] ?>
            <?php unset($_SESSION['succession']['error']); ?>
        </div>
    <?php endif; ?>
    <form action="<?= $this->lurl ?>/transferts/succession" method="post" enctype="multipart/form-data">
        <table style="width:500px; margin:auto;text-align:left;margin-bottom:10px; border:2px solid;padding:10px;">
            <tr>
                <th style="padding:15px;width: 30%;"><label for="id_client_to_transfer">ID client du défunt</label></th>
                <td>
                    <input id="id_client_to_transfer" class="input_moy" type="text" name="id_client_to_transfer">
                </td>
            </tr>
        </table>
        <table style="width:500px; margin:auto;text-align:left;margin-bottom:10px; border:2px solid;padding:10px;">
            <tr>
                <th style="padding:15px;width: 30%;"><label for="id_client_receiver">ID client de l'héritier</label></th>
                <td>
                    <input id="id_client_receiver" class="input_moy" type="text" name="id_client_receiver">
                </td>
            </tr>
        </table>
        <div style="text-align: center;">
            <button type="submit" class="btn-primary">Vérifier</button>
            <input type="hidden" name="succession_check">
        </div>
    </form>

    <?php if (false === empty($_SESSION['succession']['check'])) : ?>
        <div id="resultat" style="margin-top: 25px; text-align: center;">
            <h2>Prêts et solde à transférer</h2>
            <table style="width:600px; margin:auto;text-align:left;margin-bottom:10px; border:2px solid;padding:10px;background-color: lightgrey;">
                <tr>
                    <th style="padding:15px; width: 40%">Défunt :</th>
                    <td><?= $_SESSION['succession']['check']['formerClient']['prenom'] ?> <?= $_SESSION['succession']['check']['formerClient']['nom'] ?></td>
                </tr>
                <tr>
                    <th style="padding:15px; width: 40%">Héritier :</th>
                    <td><?= $_SESSION['succession']['check']['newOwner']['prenom'] ?> <?= $_SESSION['succession']['check']['newOwner']['nom'] ?></td>
                </tr>
                <tr>
                    <th style="padding:15px; width: 40%">Solde à transférer :</th>
                    <td><?= $this->ficelle->formatNumber($_SESSION['succession']['check']['accountBalance'])?> €</td>
                </tr>
                <tr>
                    <th style="padding:15px; width: 40%">Nombre de contrats<br>de financement à transférer :</th>
                    <td><?= $_SESSION['succession']['check']['numberLoans'] ?></td>
                </tr>
            </table>
            <form action="<?= $this->lurl ?>/transferts/succession" method="post" enctype="multipart/form-data">
                <table style="width:500px; margin:auto;text-align:left;margin-bottom:10px; border:2px solid;padding:10px;">
                    <tr class="row row-upload">
                        <th style="padding:15px;width: 30%;"><label for="document">Document justificatif</label></th>
                        <td>
                            <input type="hidden" name="id_client_to_transfer" value="<?= $_SESSION['succession']['check']['formerClient']['id_client'] ?>">
                            <input type="hidden" name="id_client_receiver" value="<?= $_SESSION['succession']['check']['newOwner']['id_client'] ?>">
                            <input type="file" name="transfer_document">
                        </td>
                    </tr>
                </table>
                <div style="text-align: center;">
                    <input type="hidden" name="succession_validate">
                    <button type="submit" class="btn-primary">Valider le transfert</button>
                </div>
            </form>
        </div>
        <?php unset($_SESSION['succession']['check']) ?>
    <?php endif; ?>

    <?php if (false === empty($_SESSION['succession']['success'])) : ?>
        <div id="resultat" style="margin-top: 25px; text-align: center;">
            <h2>Prêts et solde transférés avec succès</h2>
            <table style="width:600px; margin:auto;text-align:left;margin-bottom:10px; border:2px solid;padding:10px;background-color :#B8CEB9;">
                <tr>
                    <th style="padding:15px; width: 40%">Défunt :</th>
                    <td><?= $_SESSION['succession']['success']['formerClient']['prenom'] ?> <?= $_SESSION['succession']['success']['formerClient']['nom'] ?></td>
                </tr>
                <tr>
                    <th style="padding:15px; width: 40%">Héritier :</th>
                    <td><?= $_SESSION['succession']['success']['newOwner']['prenom'] ?> <?= $_SESSION['succession']['success']['newOwner']['nom'] ?></td>
                </tr>
                <tr>
                    <th style="padding:15px; width: 40%">Solde transféré :</th>
                    <td><?= $this->ficelle->formatNumber($_SESSION['succession']['success']['accountBalance'])?> €</td>
                </tr>
                <tr>
                    <th style="padding:15px; width: 40%">Nombre de contrats<br>de financement transférés :</th>
                    <td><?= $_SESSION['succession']['success']['numberLoans'] ?></td>
                </tr>
            </table>
        </div>
        <?php unset($_SESSION['succession']['success']) ?>
    <?php endif; ?>
</div>
