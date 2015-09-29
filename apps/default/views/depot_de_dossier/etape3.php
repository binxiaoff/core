<!--#include virtual="ssi-header.shtml"  -->
<div class="main">
    <div class="shell">

        <p><?php printf($this->lng['etape3']['contenu'], $this->mensualite_min_ttc, $this->mensualite_max_ttc) ?></p>

        <div class="register-form">
            <form action="" method="post" id="form_etape_3" enctype="multipart/form-data">
                <table class="form-table">
                    <tr>
                        <th colspan="2"
                            style="text-align: left;"><?= $this->lng['etape3']['ligne-derniere-liasse-fiscal'] ?></th>
                    </tr>
                    <tr>
                        <td>
                            <label class="inline-text"><?= $this->lng['etape3']['fonds-propres'] ?></label>
                        </td>
                        <td>
                            <div class="field-holder">
                                <input type="text"
                                       name="fonds_propres"
                                       id="fonds_propres"
                                       class="field field-large euro-field"
                                       data-validators="Numericality"
                                       value="<?= $this->iFondsPropres ?>"
                                       onkeyup="lisibilite_nombre(this.value,this.id);">
                            </div>
                            <!-- /.field-holder -->
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label class="inline-text"><?= $this->lng['etape3']['ca'] ?></label>
                        </td>
                        <td>
                            <div class="field-holder">
                                <input type="text"
                                       name="ca"
                                       id="ca"
                                       class="field field-large euro-field"
                                       data-validators="Numericality"
                                       value="<?= $this->iCa ?>"
                                       onkeyup="lisibilite_nombre(this.value,this.id);">
                            </div>
                            <!-- /.field-holder -->
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label class="inline-text">
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
                                       value="<?= $this->iRex ?>"
                                       onkeyup="lisibilite_nombre(this.value,this.id);">
                                <!-- /.field-holder -->
                            </div>
                        </td>
                    </tr>
                </table>


                <div class="row row-upload" style="padding-bottom: 5px;">
                    <label class="inline-text"
                           style="width: 330px; height: 35px; padding: 10px; "><?= $this->lng['etape3']['liasse-fiscal'] ?></label>

                    <div class="uploader">
                        <input id="liasse_fiscal"
                               type="text"
                               class="field required <?= ($this->error_liasse_fiscal == true ? 'LV_invalid_field' : '') ?>"
                               readonly="readonly"
                               placeholder="<?= $this->lng['etape3']['aucun-fichier-selectionne'] ?>"
                               style="width: 310px; margin-left: 10px;"/>

                        <div class="file-holder">
                                    <span class="btn btn-small" style=" float: left; margin: 5px;">
                                        <?= $this->lng['etape2']['parcourir'] ?>
                                        <span class="file-upload">
                                    <input type="file" class="file-field" name="liasse_fiscal">
                                </span>
                            </span>
                        </div>
                    </div>
                    <!-- /.uploader -->
                </div>
                <!-- /.row -->


                <div class="row row-upload" style="padding-bottom: 5px;">
                    <label style="width: 330px; height: 35px; padding: 10px;"
                           class="inline-text"><?= $this->lng['etape3']['autre'] ?></label>

                    <div class="uploader">
                        <input id="autre"
                               type="text"
                               class="field required <?= ($this->error_extrait_kbis == true ? 'LV_invalid_field' : '') ?>"
                               readonly="readonly"
                               placeholder="<?= $this->lng['etape3']['aucun-fichier-selectionne'] ?>"
                               style="width: 310px; margin-left: 10px;"/>

                        <div class="file-holder">
                                 <span class="btn btn-small" style=" float: left; margin: 5px;">
                                     <?= $this->lng['etape2']['parcourir'] ?>
                                     <span class="file-upload"><input type="file" class="file-field" name="autre">
                                </span>
                            </span>
                        </div>
                    </div>
                    <!-- /.uploader -->
                </div>
                <!-- /.row -->
                <div class="row">
                    <button class="btn" style="height: 70px; line-height: 1.2em; width: 300px; margin-right: 30px; "
                            type="submit">
                        <?= $this->lng['etape3']['deposer-demande-financement'] ?>
                        <i class="icon-arrow-next"></i>
                    </button>
                    <button class="btn" name="procedure_acceleree"
                            style="height: 70px; line-height: 1.2em; width: 400px; margin-left: 30px; margin-right: 20px; float: right;"
                            type="submit">
                        <?= $this->lng['etape3']['procedure-acceleree'] ?>
                        <i class="icon-arrow-next"></i>
                    </button>
                    <input type="hidden" name="send_form_etape_3"/>
                </div>
            </form>
        </div>
        <!-- /.register-form -->
    </div>
    <!-- /.shell -->
</div><!-- /.main -->
<!--#include virtual="ssi-footer.shtml"  -->
<script>
    $(document).on('change', 'input.file-field', function () {
        var $self = $(this);
        var val = $self.val();

        if (val.length != 0 || val != '') {
            val = val.replace(/\\/g, '/').replace(/.*\//, '');
            $self.closest('.uploader').find('input.field').val(val).addClass('LV_valid_field').addClass('file-uploaded');
        }
    });
</script>