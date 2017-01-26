<div id="popup" style="width:800px;">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <form action="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->lenders_accounts->id_lender_account ?>" method="post">
        <table class="formMail">
            <tr>
                <th>From : "<?= $this->mail_template->sender_name ?>" <?= $this->mail_template->sender_email ?></th>
            </tr>
            <tr>
                <th>Destinataire : <?= $this->clients->email ?></th>
            </tr>
            <tr>
                <th>Sujet : <?= $this->mail_template->subject ?></th>
            </tr>
            <tr>
                <td>
                    <iframe src="<?= $this->lurl ?>/preteurs/completude_preview_iframe/<?= $this->params[0] ?>" width="760px" height="400px"></iframe>
                </td>
            </tr>
            <tr>
                <td style="text-align:center;">
                    <input type="hidden" name="send_completude" id="send_completude">
                    <input type="submit" value="Envoyer l'email" title="Envoyer l'email" name="envoyer" id="envoyer" class="btn"/>
                </td>
            </tr>
        </table>
    </form>
</div>
