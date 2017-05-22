<div id="popup">
    <h1>Extraire RIB</h1>
    <?php if (false === empty($this->attachment)) :
        $link = $this->url . '/attachment/download/id/' . $this->attachment->getId() . '/file/' . urlencode($this->attachment->getPath());
    ?>
    <a href="<?= $link ?>" target="_blank" style="display: block;">
    <?php if ($this->isImage) :?>
        <img src="<?= $link ?>" style="display: block; max-width: 100%; height: auto; margin: 0 auto 15px;">
    <?php else : ?>
        <?= $this->attachment->getPath() ?>
    <?php endif; ?>
    </a>

    <h2>Saisir le RIB</h2>
    <form method="post" enctype="multipart/form-data" action="/emprunteurs/extraction_rib_lightbox/<?= $this->attachment->getId() ?>">
        <table style="margin: 20px auto;">
            <tr>
                <td>
                    <b style="padding: 0 15px">IBAN</b>
                </td>
                <td>
                    <input type="text" name="iban1" id="iban1" onfocus="clearField(this)" onkeydown="jumpIBAN(this)" size="4" class="input_big" style="width: 40px; margin-right: 10px;">
                    <input type="text" name="iban2" id="iban2" onfocus="clearField(this)" onkeydown="jumpIBAN(this)" size="4" class="input_big" style="width: 40px; margin-right: 10px;">
                    <input type="text" name="iban3" id="iban3" onfocus="clearField(this)" onkeydown="jumpIBAN(this)" size="4" class="input_big" style="width: 40px; margin-right: 10px;">
                    <input type="text" name="iban4" id="iban4" onfocus="clearField(this)" onkeydown="jumpIBAN(this)" size="4" class="input_big" style="width: 40px; margin-right: 10px;">
                    <input type="text" name="iban5" id="iban5" onfocus="clearField(this)" onkeydown="jumpIBAN(this)" size="4" class="input_big" style="width: 40px; margin-right: 10px;">
                    <input type="text" name="iban6" id="iban6" onfocus="clearField(this)" onkeydown="jumpIBAN(this)" size="4" class="input_big" style="width: 40px; margin-right: 10px;">
                    <input type="text" name="iban7" id="iban7" onfocus="clearField(this)" onkeydown="jumpIBAN(this)" size="3" class="input_big" style="width: 40px; margin-right: 10px;">
                </td>
            </tr>
            <tr>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
                <td>
                    <b style="padding: 0 15px">BIC</b>
                </td>
                <td>
                    <input type="text" name="bic" id="bic" style="width: 100px;" class="input_big">
                </td>
            </tr>
        </table>
        <div style="text-align: center">
            <a href="javascript:parent.$.fn.colorbox.close()" class="btn-default">Annuler</a>
            <button type="submit" class="btn-primary">Valider</button>
        </div>
    </form>
    <?php endif; ?>
</div>
<script type="text/javascript">
    function clearField(field) {
        field.value = '';
    }
    function jumpIBAN(field) {
        if (field.id == 'iban7') {
            field.value = field.value.substring(0, 3);
        }
        if (field.value.length == 4) {
            field.nextElementSibling.value = '';
            field.nextElementSibling.focus();
        }
    }
</script>
