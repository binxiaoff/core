<div id="popup" style="width:800px;">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <?php if (false === empty($this->email)) : ?>
        <div class="formMail">
            <div>
                <p>Date : <?= $this->email['date'] ?></p>
            </div>
            <div>
                <p>From : <?= $this->email['from'] ?></p>
            </div>
            <div>
                <p>Destinataire : <?= $this->email['to'] ?></p>
            </div>
            <div>
                <p>Sujet : <?= $this->email['subject'] ?></p>
            </div>
            <div>
                <div style="width:760px; height:400px; overflow: auto;">
                    <?= $this->email['body'] ?>
                </div>
            </div>
        </div>
    <?php elseif (false === empty($this->errorMessage)) : ?>
        <div style="text-align: center">
            <p><?= $this->errorMessage ?></p>
        </div>
    <?php endif; ?>
</div>
