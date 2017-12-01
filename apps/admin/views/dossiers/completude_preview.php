<div id="popup" style="width:800px;">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer" /></a>
    <?php if (false === empty($this->error)) : ?>
        <div class="error"><?= $this->error ?></div>
    <?php else : ?>
        <div id="send_completude_content">
            <form id="send_completude_form">
                <table class="formMail">
                    <tr>
                        <th>From : "<?= $this->mailTemplate->getSenderName() ?>" <?= $this->mailTemplate->getSenderEmail() ?></th>
                    </tr>
                    <tr>
                        <th>Destinataire : <?= $this->sRecipient ?></th>
                    </tr>
                    <tr>
                        <th>Sujet : <?= $this->mailTemplate->getSubject() ?></th>
                    </tr>
                    <tr>
                        <td><iframe src="<?= $this->lurl ?>/dossiers/completude_preview_iframe/<?= $this->iProjectId ?>/<?= $this->iClientId ?>" width="760px" height="400px"></iframe></td>
                    </tr>
                    <tr>
                        <td style="text-align:center;">
                            <input type="hidden" name="id_client" value="<?= $this->iClientId ?>">
                            <input type="hidden" name="id_project" value="<?= $this->iProjectId ?>">
                            <button type="submit" id="send_completude" class="btn-primary">Envoyer l'email</button>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
    $('#send_completude').click(function(e){
        e.preventDefault();
        // get all the inputs into an array.
        var inputs = $('#send_completude_form :input');

        // not sure if you wanted this, but I thought I'd add it.
        // get an associative array of just the values.
        var values = {};
        inputs.each(function() {
            values[this.name] = $(this).val();
        });
        $.ajax({
            url: "<?= $this->lurl ?>/dossiers/send_completude",
            type: 'POST',
            data: values,
            error: function() {
                alert('An error has occurred');
            },
            success: function(data) {
                $('#send_completude_content').html('<h2>Envoi d\'email Complétude</h2><p>' + data + '</p>');
            }
        });
    });
</script>
