<style type="text/css">
    .btn {
        margin: 20px;
    }
    .popup {
        background-color: transparent;
    }

</style>
<div class="popup">
    <a href="#" class="popup-close">close</a>

    <div class="popup-cnt">
        <a href="<?= $this->url?>/synthese" class="btn btn-medium"><?= $this->lng['header']['acceder-preteur'] ?></a>
        <a href="<?= $this->url?>/espace_emprunteur" class="btn btn-medium"><?= $this->lng['header']['acceder-emprunteur'] ?></a>

    </div>
</div>
<!-- /popup-cnt -->


<script type="text/javascript">
    $("#non").click(function () {
        $(".popup-close").click()
    });
</script>