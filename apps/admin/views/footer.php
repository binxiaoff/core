<div id="footer">
    <?= $this->cms ?> 1.1 -
    <a href="http://www.equinoa.com" title="Agence Web Equinoa">Equinoa</a> &copy;<?= date('Y') ?> -
    <a href="<?= $this->urlfront ?>" title="Retourner sur le site" target="_blank">Retourner sur le site</a>
</div>
</div>
</body>
<?php if ($this->Config['env'] != 'prod'): ?>
<script type="text/javascript">
    window.ATL_JQ_PAGE_PROPS = $.extend(window.ATL_JQ_PAGE_PROPS, {
        // ==== default field values ====
        fieldValues: {
            email: '<?php echo $_SESSION['user']['email'] ?>'
        }
    });
</script>
<?php endif; ?>
</html>