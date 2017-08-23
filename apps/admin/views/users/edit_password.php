<script>
    $(function() {
        <?php if (false === empty($_SESSION['msgErreur'])) : ?>
            $.fn.colorbox({
                href: '<?= $this->lurl ?>/thickbox/<?= $_SESSION['msgErreur'] ?>'
            })
        <?php endif; ?>

        var passwordStrengthCheck, password

        $('#new_pass').on('keyup keydown change', function () {
            if (passwordStrengthCheck) {
                clearTimeout(passwordStrengthCheck)
            }

            if ($(this).val() && $(this).val() != password) {
                password              = $(this).val()
                passwordStrengthCheck = setTimeout(function () {
                    $.post('<?= $this->lurl ?>/ajax/check_force_pass', {
                        pass: password
                    }).done(function (response) {
                        $('#indicateur_force').html(response)
                    })
                }, 500)
            }
        })
    })
</script>
<style>
    .edit_pass{width:50% !important;}
    table.edit_pass td{ text-align:left;}
    .large{width: 41% !important;}
    #contenu{margin-top:90px;}
</style>
<div id="contenu">
    <form method="post" enctype="multipart/form-data">
        <br><br>
        <h1>Modification de votre mot de passe</h1>
        <?php if (false === empty($this->retour_pass)) : ?>
            <br>
            <div style="color:red; font-weight:bold;"><?= $this->retour_pass ?></div>
        <?php endif; ?>
        <br><br>
        <table class="large edit_pass">
            <tr>
                <th><label for="old_pass">Ancien mot de passe *</label></th>
                <td><input type="password" name="old_pass" id="old_pass" autocomplete="off" class="input_large"></td>
            </tr>
            <tr>
                <th><label for="new_pass">Nouveau mot de passe *</label></th>
                <td>
                    <input type="password" name="new_pass" id="new_pass" autocomplete="off" class="input_large">
                    <div id="indicateur_force"></div>
                </td>
            </tr>
            <tr>
                <th><label for="new_pass2">Vérification du nouveau mot de passe *</label></th>
                <td><input type="password" name="new_pass2" id="new_pass2" autocomplete="off" class="input_large"></td>
            </tr>
        </table>
        <br>
        <table class="large">
            <tr>
                <td colspan="2">
                    <input type="hidden" name="form_edit_pass_user" id="form_edit_pass_user">
                    <input type="hidden" name="id_user" value="<?= $this->users->id_user ?>">
                    <button type="submit" class="btn-primary">Valider la modification</button>
                </td>
            </tr>
        </table>
    </form>
</div>
