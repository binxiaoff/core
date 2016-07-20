<style type="text/css">
    .message-valid {
        background: #D4FDD2;
        color: #0DB200;
    }
    .message-error {
        background: #ffe8e8;
        color: #c84747;
    }
    .message-box {
        font-weight: bold;
        text-align: center;
        padding: 10px;
        width: 100%;
        box-sizing: border-box;
        border-radius: 5px;
        border: 1px;
    }
</style>

<?php if ('success' === $this->status): ?>
    <div class="message-valid message-box">
        <?= $this->lng['lender-dashboard']['validation-success'] ?>
    </div>
<?php else: ?>
    <div class="message-error message-box">
        <?= $this->lng['lender-dashboard']['validation-error'] ?>
    </div>
<?php endif; ?>