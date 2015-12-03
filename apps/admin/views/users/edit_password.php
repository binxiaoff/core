    <script type="text/javascript">
        $(document).ready(function(){
            <?
            if($_SESSION['msgErreur'] != '')
            {
            ?>
            $.fn.colorbox({
                href:"<?=$this->lurl?>/thickbox/<?=$_SESSION['msgErreur']?>"
            });
            <?
            }
            ?>
        });
    </script>

    <style>
        .edit_pass{width:50% !important;}
        table.edit_pass td{ text-align:left;}
        /*.button_valid{float:left;}*/
        .large{width: 41% !important;}
        #contenu{margin-top:90px;}
    </style>

    <script type="text/javascript">
        <?
        if(isset($_SESSION['freeow']))
        {
        ?>
        $(document).ready(function(){
            var title, message, opts, container;
            title = "<?=$_SESSION['freeow']['title']?>";
            message = "<?=$_SESSION['freeow']['message']?>";
            opts = {};
            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
        });
        <?
        }
        ?>
    </script>
    <div id="freeow-tr" class="freeow freeow-top-right"></div>
    <div id="contenu">
        <center>
            <form method="post" name="edit_password" id="edit_password" enctype="multipart/form-data">
                <br /><br />
                <h1>Modification de votre mot de passe</h1>

                <?php
                if(isset($this->retour_pass) && $this->retour_pass != "")
                {
                    ?>
                    <br />
                    <div style="color:red; font-weight:bold;"><?=$this->retour_pass?></div>

                    <?php
                }
                ?>
                <br /><br />
                <table class="large edit_pass">
                    <tr>
                        <th><label for="old_pass">Ancien mot de passe* :</label></th>
                        <td><input type="password" name="old_pass" id="old_pass" value="" autocomplete="off" class="input_large" /></td>
                    </tr>

                    <tr>
                        <th><label for="new_pass">Nouveau mot de passe* :</label></th>
                        <td>
                            <input type="password" name="new_pass" id="new_pass" value="" onKeyUp="check_force_pass();" autocomplete="off" class="input_large" />
                            <div id="indicateur_force"></div>
                        </td>
                    </tr>


                    <tr>
                        <th><label for="new_pass2">Vérification du nouveau mot de passe* :</label></th>
                        <td><input type="password" name="new_pass2" id="new_pass2" value="" autocomplete="off" class="input_large" /></td>
                    </tr>

                </table>
                <br />
                <table class="large">
                    <tr>
                        <td colspan="2">
                            <input type="hidden" name="form_edit_pass_user" id="form_edit_pass_user" />
                            <input type="hidden" name="id_user" value="<?=$this->users->id_user?>" />
                            <input type="submit" value="Valider la modification du mot de passe" class="btn button_valid" />
                        </td>
                    </tr>
                </table>
            </form>
        </center>
    </div>
</div>
