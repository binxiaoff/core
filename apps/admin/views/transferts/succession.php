<div id="freeow-tr" class="freeow freeow-top-right"></div>


<div id="contenu">
    <div id="errors" style="background-color: #fadfe4; font-size: 14px; line-height: 1.3em; text-align: center; margin-top: 20px; margin-bottom: 20px; ">
        <?php if (false === empty($_SESSION['succession']['error'])) : ?>
            <?= $_SESSION['succession']['error'] ?>
        <?php endif; ?>
        <?php unset($_SESSION['succession']['error']); ?>
    </div>
    <form action="<?= $this->lurl ?>/transferts/succession" method="post" enctype="multipart/form-data">
    <table style="width:600px; margin:auto;text-align:left;margin-bottom:10px; border:2px solid;padding:10px;">
        <tr>
            <th style="padding:15px;"><label for="id_client_to_transfer">Id client à transférer</label></th>
            <td>
                <input id="id_client_to_transfer" class="input_moy" type="text" name="id_client_to_transfer">
            </td>
        </tr>
    </table>
    <table style="width:600px; margin:auto;text-align:left;margin-bottom:10px; border:2px solid;padding:10px;">
        <tr>
            <th style="padding:15px;"><label for="id_client_receiver">Id client destinataire</label></th>
            <td>
                <input id="id_client_receiver" class="input_moy" type="text" name="id_client_receiver">
            </td>
        </tr>
    </table>
    <table style="width:600px; margin:auto;text-align:left;margin-bottom:10px; border:2px solid;padding:10px;">
        <tr class="row row-upload">
            <th style="padding:15px;"><label for="document">Document justificatif</label></th>
            <td>
                <input type="file" class="file-field" name="transfer_document">
            </td>
        </tr>
    </table>
        <input type="hidden" name="succession_form">
    <div style="text-align: center;"><input type="submit" class="btn" value="Valider"></div>
</div>
