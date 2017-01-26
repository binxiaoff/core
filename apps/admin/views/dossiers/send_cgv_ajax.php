<?php if (empty($this->params[0])) : ?>
    <script type="text/javascript">
      parent.$.fn.colorbox.close();
    </script>
<?php endif; ?>

<div id="popup">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer" /></a>
    <h1>Resultat d'envoi des CGV</h1>
    <p><?= $this->result ?></p>
    <div class="clear"></div>
</div>
