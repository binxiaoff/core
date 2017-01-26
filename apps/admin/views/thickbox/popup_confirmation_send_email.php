<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer" /></a>
    <form method="post" name="form_date_retrait" id="form_date_retrait" enctype="multipart/form-data" action="" target="_parent">
        <h1>Confirmation</h1>
        <fieldset>
            <table class="form">
                <tr>
                    <td colspan="2" style="text-align: center;" >
                        <p>Etes vous sûr de vouloir envoyer l'email d'information aux prêteurs ?</p>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: center;">
                        <input type="button" value="Oui" title="Oui" name="oui" id="oui" class="btn" />
                    </td >
                    <td style="text-align: center;">
                        <input type="button" value="Non" title="Non" name="non" id="non" class="btn" />
                    </td>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
<script type="text/javascript">
    $("#oui").click(function () {
        $('#check_confirmation_send_email').val('1');
        $("#dossier_resume").submit();
    });
    $("#non").click(function () {
        $('.closeBtn').click();
    });
</script>
