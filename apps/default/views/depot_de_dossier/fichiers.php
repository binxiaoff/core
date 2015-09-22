<!--#include virtual="ssi-header.shtml"  -->
<div class="main">
    <div class="shell">

        <!--				--><? //=$this->fireView('../blocs/depot-de-dossier')?>

        <p><?php printf($this->lng['espace-emprunteur']['contenu'], $this->mensualite_min, $this->mensualite_max) ?></p>

        <div class="register-form">
            <?
            if (isset($_SESSION['confirmation']['valid']) && $_SESSION['confirmation']['valid'] != '') {
                echo '<p id="valid-stand-by" style="color: #3FBD5D;text-align:center;">' . $_SESSION['confirmation']['valid'] . '</p>';
                unset($_SESSION['confirmation']['valid']);

                ?>
                <script>
                    setTimeout(function () {
                        $("#valid-stand-by").slideUp();
                    }, 8000);
                </script><?
            }
            ?>

            <form action="" method="post" id="form_espace_emptunteur" enctype="multipart/form-data">
                <div class="row">
                    <p><?= $this->lng['etape1']['identite-de-la-societe'] ?></p>

                    <input type="text" name="raison-sociale" id="raison-sociale"
                           title="<?= $this->lng['etape2']['raison-sociale'] ?>"
                           value="<?= ($this->companies->name != '' ? $this->companies->name : $this->lng['etape2']['raison-sociale']) ?>"
                           class="field field-large required" data-validators="Presence">
                </div>
                <div class="row">
                    <p><?=$this->lng['espace-emprunteur']['vous-pouvez-nous-envoyer']?></p>
                </div>
                <div class="row">
                    <span class="btn btn-medium">Type de document</span>

                </div>



                    <!-- TODO modifier source des données pour les fichiers joins -->

                            <div class="row row-upload">
                                <label class="inline-text"><?=$this->lng['etape3']['liasse-fiscal']?></label>
                                <div class="uploader">
                                    <input id="liasse_fiscal"
                                           type="text"
                                           class="field required <?=($this->error_liasse_fiscal==true?'LV_invalid_field':'')?>"
                                           readonly="readonly"
                                           value="<?=$this->lng['etape3']['aucun-fichier-selectionne']?>" />
                                    <div class="file-holder">
                                    <span class="btn btn-small">
                                        <?=$this->lng['etape2']['parcourir']?>
                                        <span class="file-upload">
                                    <input type="file" class="file-field" name="liasse_fiscal">
                                </span>
                            </span>
                                    </div>
                                </div><!-- /.uploader -->
                            </div><!-- /.row -->

                            <div class="row row-upload">
                                <label class="inline-text"><?=$this->lng['etape3']['autre']?></label>

                                <div class="uploader">
                                    <input id="autre"
                                           type="text"
                                           class="field required <?=($this->error_extrait_kbis==true?'LV_invalid_field':'')?>"
                                           readonly="readonly"
                                           value="<?=$this->lng['etape3']['aucun-fichier-selectionne']?>" />
                                    <div class="file-holder">
                                    <span class="btn btn-small">
                                        <?=$this->lng['etape2']['parcourir']?>
                                        <span class="file-upload">
                                    <input type="file" class="file-field" name="autre">
                                </span>
                            </span>
                                    </div>
                                </div><!-- /.uploader -->
                            </div><!-- /.row -->


                <input type="hidden" name="submit_files" />
                <button class="btn" type="submit"><?=$this->lng['espace-emprunteur']['valider']?><i class="icon-arrow-next"></i
            </form>
        </div>
        <!-- /.register-form -->
    </div>
    <!-- /.shell -->
</div><!-- /.main -->
<!--#include virtual="ssi-footer.shtml"  -->
<script>
    $(document).on('change', 'input.file-field', function(){
        var $self = $(this);
        var val = $self.val();

        if ( val.length != 0 || val != '' ) {
            val = val.replace(/\\/g, '/').replace(/.*\//, '');
            $self.closest('.uploader').find('input.field').val(val).addClass('LV_valid_field').addClass('file-uploaded');
        }
    });
</script>