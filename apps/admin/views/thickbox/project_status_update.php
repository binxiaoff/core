<div id="popup" style="width: 433px;">
    <a onclick="parent.$.fn.colorbox.close();" class="closeBtn" title="Fermer"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <h1>Passage en statut &laquo;&nbsp;<?= $this->projects_status->label ?>&nbsp;&raquo;</h1>
    <form id="problematic_status_form" method="post" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->projectId ?>">
        <em>Envoyer un email d'information aux prêteurs</em><br/><br/>
        <label><input type="radio" name="send_email" value="1"/> Oui</label>
        <label><input type="radio" name="send_email" value="0"/> Non</label><br/><br/>
        <div class="hidden-fields">
            <label for="mail_content"><em>Email d'information aux prêteurs</em></label><br/><br/>
            <textarea id="mail_content" name="mail_content" class="textarea_lng" style="height: 100px;width: 420px;"><?= isset($this->mailInfoStatusChange) ? $this->mailInfoStatusChange : ''; ?></textarea><br/><br/>
        </div>
        <label for="site_content"><em>Message d'information aux prêteurs (site)</em></label><br/><br/>
        <textarea id="site_content" name="site_content" class="textarea_lng" style="height:100px; width:420px;"><?= isset($this->sInfoStatusChange) ? $this->sInfoStatusChange : ''; ?></textarea>
        <br/><br/>

        <em>Envoyer un email d'information aux emprunteurs</em><br/><br/>
        <label><input type="radio" name="send_email_borrower" value="1"/> Oui</label>
        <label><input type="radio" name="send_email_borrower" value="0"/> Non</label><br/><br/>
        <div id="problematic_status_error">Vous devez saisir tous les champs<br/><br/></div>

        <div style="text-align:right">
            <input type="hidden" name="problematic_status" value="<?= $this->projects_status->status ?>"/>
            <button type="submit" class="btn-primary">Sauvegarder</button>
        </div>
    </form>
</div>
<script type="text/javascript">
    $('[name=send_email]').change(function () {
        if (1 == $(this).val()) {
            $('.hidden-fields').slideDown(function () {
                $.colorbox.resize();
            });
        } else {
            $('.hidden-fields').slideUp(function () {
                $.colorbox.resize();
            });
        }
    });

    $('#problematic_status_form').submit(function (e) {
        if (
            undefined == $('[name=send_email]:checked').val()
            || undefined == $('[name=send_email_borrower]:checked').val()
            || 1 == $('[name=send_email]:checked').val() && '' == $('#mail_content').val()
            || '' == $('#site_content').val()
        ) {
            e.preventDefault();

            $("#problematic_status_error").slideDown(function () {
                $.colorbox.resize();
            });

            setTimeout(function () {
                $("#problematic_status_error").slideUp(function () {
                    $.colorbox.resize();
                });
            }, 3000);
        }
    });
</script>
