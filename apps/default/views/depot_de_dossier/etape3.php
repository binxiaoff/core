<!--#include virtual="ssi-header.shtml"  -->
<div class="main">
    <div class="shell">

        <!--				--><? //=$this->fireView('../blocs/depot-de-dossier')?>

        <p><?= printf($this->lng['etape3']['contenu'], $this->mensualite_min, $this->mensualite_max) ?></p>

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
            <!-- TODO modifier source des données, selon renseignement altares car seulement sur une année et plus dans une boucle foreach -->

            <form action="" method="post" id="form_etape_3" enctype="multipart/form-data">
                <table class="form-table">
                    <tr>
                        <td>
                            <label class="inline-text">
                                <i class="icon-help tooltip-anchor field-help-before" data-placement="right"
                                   title="<?= $this->lng['etape3']['info-fonds-propres'] ?>"></i>
                                <?= $this->lng['etape3']['fonds-propres'] ?>
                            </label>
                        </td>
                        <td>
                            <div class="field-holder">
                                <input type="text"
                                       name="fonds_propres"
                                       id="fonds_propres"
                                       class="field field-large euro-field"
                                       data-validators="Numericality"
                                       value="<?= ($this->lBilans[$i]['ca'] == 0 ? '' : number_format($this->lBilans[$i]['ca'], 0, '.', ' ')) ?>"
                                       onkeyup="lisibilite_nombre(this.value,this.id);">
                            </div>
                            <!-- /.field-holder -->
                        </td>

                    </tr>
                    <tr>
                        <td>
                            <label class="inline-text">
                                <i class="icon-help tooltip-anchor field-help-before" data-placement="right"
                                   title="<?= $this->lng['etape3']['info-chiffe-daffaires'] ?>"></i>
                                <?= $this->lng['etape3']['chiffe-daffaires'] ?>
                            </label>
                        </td>
                        <td>
                            <div class="field-holder">
                                <input type="text"
                                       name="ca"
                                       id="ca"
                                       class="field field-large euro-field"
                                       data-validators="Numericality"
                                       value="<?= ($this->lBilans[$i]['ca'] == 0 ? '' : number_format($this->lBilans[$i]['ca'], 0, '.', ' ')) ?>"
                                       onkeyup="lisibilite_nombre(this.value,this.id);">
                            </div>
                            <!-- /.field-holder -->
                        </td>
                    </tr>
                    <tr>

                        <td>
                            <label class="inline-text">
                                <i class="icon-help tooltip-anchor field-help-before" data-placement="right"
                                   title="<?= $this->lng['etape3']['info-resultat-brut-dexploitation'] ?>"></i>
                                <?= $this->lng['etape3']['resultat-brut-dexploitation'] ?>
                            </label>
                        </td>
                        <td>
                            <div class="field-holder">
                                <input type="text"
                                       name="resultat_brute_exploitation"
                                       id="rbe"
                                       class="field field-large euro-field"
                                       data-validators="Numericality"
                                       value="<?= ($this->lBilans[$i]['resultat_brute_exploitation'] == 0 ? '' : number_format($this->lBilans[$i]['resultat_brute_exploitation'], 0, '.', ' ')) ?>"
                                       onkeyup="lisibilite_nombre(this.value,this.id);">
                                <!-- /.field-holder -->
                            </div>
                        </td>


                    </tr>

                    <!-- TODO modifier source des données pour les fichiers joins -->
                    <tr>
                        <td colspan="2">
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
                    </tr>
                    </tr>
                    <tr>
                        <td colspan="2">
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
                    </tr>
                    </td>
                </table>

                <input type="hidden" name="send_form_etape_4" />
                <button class="btn" style="height: 70px; line-height: 1.2em; width: 300px" type="submit"><?=$this->lng['etape3']['deposer-demande-financement']?><i class="icon-arrow-next"></i></button>
                <span style="width: 100px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                <input type="hidden" name="send_form_etape_3" />
                <input type="hidden" name="procedure_acceleree" />
                <button class="btn"  style="height: 70px; line-height: 1.2em; width: 400px" type="submit"><?=$this->lng['etape3']['procedure-acceleree']?><i class="icon-arrow-next"></i
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