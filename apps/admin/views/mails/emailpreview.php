<div id="popup" style="width:800px;">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <table class="formMail">
        <tr>
            <th>Date : <?= $this->aEmail['date'] ?></th>
        </tr>
        <tr>
            <th>From : <?=  $this->aEmail['from']?></th>
        </tr>
        <tr>
            <th>Destinataire : <?= $this->aEmail['to'] ?></th>
        </tr>
        <tr>
            <th>Sujet : <?= $this->aEmail['subject'] ?></th>
        </tr>
        <tr>
            <td>
                <div style="width:760px; height:400px; overflow: auto;">
                    <?= $this->aEmail['body'] ?>
                </div>
            </td>
        </tr>
    </table>
</div>
