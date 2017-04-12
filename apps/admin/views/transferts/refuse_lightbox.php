<div id="popup">
    <h1>Refus du transfert</h1>
    <form method="post" enctype="multipart/form-data" action="/transferts/refuse_lightbox/<?= $this->wireTransferOut->getIdVirement() ?>">
        <div style="text-align: center">
            <input type="submit" class="btn" value="Valider">
            <a href="javascript:parent.$.fn.colorbox.close()" class="btn btn_link btnDisabled">Annuler</a>
        </div>
    </form>
</div>
