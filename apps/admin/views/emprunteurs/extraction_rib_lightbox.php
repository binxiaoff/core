<div id="popup">
    <h1>Extraire RIB</h1>
    <?php if (false === empty($this->attachment)) :
        $link = $this->url . '/attachment/download/id/' . $this->attachment->getId() . '/file/' . urlencode($this->attachment->getPath());
    ?>
    <div style="width:auto;background-color: white;border: 1px solid #A1A5A7;border-radius: 10px 10px 10px 10px;margin: 0 auto 20px;padding:5px;">
    <h2>Document</h2>
        <a href="<?= $link ?>" target="_blank">
        <?php if ($this->isImage) :?>
            <img src="<?= $link ?>" width="500px">
        <?php else : ?>
            <?= $this->attachment->getPath() ?>
        <?php endif; ?>
        </a>
        <br>
        <br>
    </div>
    <h2>Saisir le RIB</h2>
    <form method="post" enctype="multipart/form-data" action="/emprunteurs/extraction_rib_lightbox/<?= $this->attachment->getId() ?>">
        <table class="formColor" style="width: 775px;margin:auto;">
            <tr>
                <th><label for="iban1">IBAN</label></th>
                <td colspan="3">
                    <input type="text" name="iban1" id="iban1" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big">
                    <input type="text" name="iban2" id="iban2" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big">
                    <input type="text" name="iban3" id="iban3" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big">
                    <input type="text" name="iban4" id="iban4" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big">
                    <input type="text" name="iban5" id="iban5" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big">
                    <input type="text" name="iban6" id="iban6" onkeyup="jumpIBAN(this)" style="width: 78px;" size="4" class="input_big">
                    <input type="text" name="iban7" id="iban7" onkeyup="jumpIBAN(this)" style="width: 53px;" size="3" class="input_big">
                </td>
            </tr>
            <tr>
                <th><label for="bic">BIC</label></th>
                <td >
                    <input type="text" name="bic" id="bic" style="width: 100px;" class="input_big">
                </td>
            </tr>
        </table>
        <center>
            <input type="submit" class="btn" value="Valider">
            <button onclick="parent.$.fn.colorbox.close();" class='btn' style="margin-left:15px;">Fermer</button>
        </center>
    </form>
    <?php endif; ?>
</div>
<script type="text/javascript">
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
