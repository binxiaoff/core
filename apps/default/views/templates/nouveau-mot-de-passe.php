<style type="text/css">
    .register-form .pass-field-holder em {
        font-size: 14px;
        line-height: 14px;
        margin-top: 0;
        width: 472px;
    }
</style>


<!--#include virtual="ssi-header.shtml"  -->
<div class="main">
    <div class="shell">

        <?=$this->fireView('../blocs/breadcrumb')?>
        <h1><?=$this->tree->title?></h1>
        <?php if ($this->reponse == false) {?>
            <div class="register-form">
                <form action="" method="post" id="form_mdp">
                    <div class="row">
                        <span class="pass-field-holder">
                            <input type="password" name="pass" id="pass" title="<?=$this->lng['etape1']['mot-de-passe']?>" value="" class="field field-large required">
                            <em><?=$this->lng['etape1']['info-mdp']?></em>
                        </span>

                        <span class="pass-field-holder">
                            <input type="password" name="pass2" id="pass2" title="<?=$this->lng['etape1']['confirmation-de-mot-de-passe']?>" value="" class="field field-large " data-validators="Confirmation,{ match: 'pass' }">
                        </span>
                    </div><!-- /.row -->

                    <div class="row">
                        <?=$this->clients->secrete_question?>
                    </div><!-- /.row -->

                    <div class="row">
                        <input type="text" id="secret-response" name="secret-response" title="<?=$this->lng['etape1']['response']?>" value="<?=$this->lng['etape1']['response']?>" class="field field-mega required <?=($this->erreur_reponse_secrete==true?'LV_invalid_field':'')?>" data-validators="Presence">
                    </div><!-- /.row -->

                    <div class="form-foot row row-cols centered">
                        <input type="hidden" name="send_form_new_mdp" id="send_form_new_mdp">
                        <button class="btn" type="submit"><?=$this->lng['etape1']['valider']?><i class="icon-arrow-next"></i></button>
                    </div>
                </form>
            </div>


            <script type="text/javascript">
            $(document).ready(function () {
                // mdp controle particulier
                $('#pass').keyup(function() {
                    controleMdp($(this).val(), 'pass');
                });
                // mdp controle particulier
                $('#pass').blur(function() {
                    controleMdp($(this).val(), 'pass');
                });
            });
            // Submit formulaire inscription preteur particulier
            $( "#form_mdp" ).submit(function( event ) {
                var radio = true;

                // controle mdp
                if(controleMdp($('#pass').val(), 'pass', false) == false) {
                    radio = false
                }

                if(radio == false){
                    event.preventDefault();
                }
            });
            </script>

            <?php
        } else {
            echo $this->content['reponse'];
        }
        ?>

    </div><!-- /.shell -->
</div><!-- /.main -->
<!--#include virtual="ssi-footer.shtml"  -->

