<div class="main">
    <div class="shell">
        <p><?php printf($this->lng['etape3']['contenu'], $this->iMinimumMonthlyPayment, $this->iMaximumMonthlyPayment) ?></p>
        <div class="register-form">
            <form action="<?= $_SERVER['REQUEST_URI'] ?>" method="post" enctype="multipart/form-data">
                <table class="form-table">
                    <tr>
                        <th colspan="2" style="text-align: left;">
                            <?= $this->lng['etape3']['ligne-derniere-liasse-fiscal'] ?>
                        </th>
                    </tr>
                    <tr>
                        <td>
                            <label class="inline-text"><?= $this->lng['etape3']['fonds-propres-label'] ?></label>
                        </td>
                        <td>
                            <div class="field-holder">
                                <input type="text" name="fonds_propres" id="fonds_propres"
                                       placeholder="<?= $this->lng['etape3']['fonds-propres'] ?>"
                                       value="<?= empty($this->iCapitalStock) ? '' : $this->iCapitalStock ?>"
                                       class="field field-large euro-field"
                                       data-validators="Presence&amp;Numericality" onkeyup="lisibilite_nombre(this.value,this.id);">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label class="inline-text"><?= $this->lng['etape3']['ca-label'] ?></label>
                        </td>
                        <td>
                            <div class="field-holder">
                                <input type="text" name="ca" id="ca"
                                       placeholder="<?= $this->lng['etape3']['ca'] ?>"
                                       value="<?= empty($this->iRevenue) ? '' : $this->iRevenue ?>"
                                       class="field field-large euro-field"
                                       data-validators="Presence&amp;Numericality" onkeyup="lisibilite_nombre(this.value,this.id);">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label class="inline-text"><?= $this->lng['etape3']['rex-label'] ?></label>
                        </td>
                        <td>
                            <div class="field-holder">
                                <input type="text" name="resultat_brute_exploitation" id="rbe"
                                       placeholder="<?= $this->lng['etape3']['rex'] ?>"
                                       value="<?= empty($this->iOperatingIncomes) ? '' : $this->iOperatingIncomes ?>"
                                       class="field field-large euro-field"
                                       data-validators="Presence&amp;Numericality" onkeyup="lisibilite_nombre(this.value,this.id);">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label class="inline-text"><?= $this->lng['etape3']['liasse-fiscal'] ?></label>
                        </td>
                        <td class="uploader">
                            <input id="liasse_fiscal" type="text"
                                   placeholder="<?= $this->lng['etape3']['aucun-fichier-selectionne'] ?>"
                                   class="field required<?= isset($this->aErrors['liasse_fiscale']) && true === $this->aErrors['liasse_fiscale'] ? ' LV_invalid_field' : '' ?>"
                                   readonly="readonly">
                            <div class="file-holder">
                                <span class="btn btn-small" style="float: left; margin-left: 5px;">
                                    <?= $this->lng['etape2']['parcourir'] ?>
                                    <span class="file-upload">
                                        <input type="file" class="file-field" name="liasse_fiscal">
                                    </span>
                                </span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label class="inline-text"><?= $this->lng['etape3']['autre'] ?></label>
                        </td>
                        <td class="uploader">
                            <input id="autre" type="text"
                                   placeholder="<?= $this->lng['etape3']['aucun-fichier-selectionne'] ?>"
                                   class="field required<?= isset($this->aErrors['autre']) && true === $this->aErrors['autre'] ? ' LV_invalid_field' : '' ?>"
                                   readonly="readonly">
                            <div class="file-holder">
                                 <span class="btn btn-small" style="float: left; margin-left: 5px;">
                                     <?= $this->lng['etape2']['parcourir'] ?>
                                     <span class="file-upload"><input type="file" class="file-field" name="autre"></span>
                                </span>
                            </div>
                        </td>
                    </tr>
                </table>
                <div class="row row-btn">
                    <button class="btn" style="width: 300px; float: left;" type="submit">
                        <?= $this->lng['etape3']['deposer-demande-financement'] ?>
                        <i class="icon-arrow-next"></i>
                    </button>
                    <button class="btn" name="procedure_acceleree" style="width: 400px; float: right;" type="submit">
                        <?= $this->lng['etape3']['procedure-acceleree'] ?>
                        <i class="icon-arrow-next"></i>
                    </button>
                    <input type="hidden" name="send_form_etape_3">
                </div>
                <div class="clear" style="clear: both"></div>
            </form>
        </div>
    </div>
</div>

<style>
    .form-table .euro-sign {left: auto; right: 30px;}
    .register-form .btn {line-height: 38px;}
    .row-btn {margin-top: 35px;}
    .row-btn .btn {height: 70px; line-height: 1.2em;}
</style>

<script>
    $(function() {
        $('.field.euro-field').each(function() {
            lisibilite_nombre(this.value, this.id);
        })
    });

    $(document).on('change', 'input.file-field', function () {
        var $self = $(this);
        var val = $self.val();

        if (val.length != 0 || val != '') {
            val = val.replace(/\\/g, '/').replace(/.*\//, '');
            $self.closest('.uploader').find('input.field').val(val).removeClass('LV_invalid_field').addClass('LV_valid_field').addClass('file-uploaded');
        }
    });
</script>
