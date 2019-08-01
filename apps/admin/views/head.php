<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Administration du site</title>
    <link rel="shortcut icon" href="<?= $this->furl ?>/favicon.ico" type="image/x-icon" />
    <script type="text/javascript">
        var add_url = '<?= $this->url ?>'
    </script>
    <?php $this->callCss(); ?>
    <?php $this->callJs(); ?>
    <script type="text/javascript">
        $(function() {
            $('body').keydown('textarea', function(event) {
                if (event.ctrlKey && event.keyCode == 13) {
                    var $form = $(event.target).parents('form')
                    if ($form.is('form')) {
                        $form.submit()
                    }
                }
            })

            $('.searchBox').colorbox({
                onComplete: function () {
                    $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']))

                    $('#datepik_from').datepicker({
                        showOn: 'both',
                        buttonImage: '<?= $this->url ?>/images/calendar.gif',
                        buttonImageOnly: true,
                        changeMonth: true,
                        changeYear: true,
                        yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
                    })

                    $('#datepik_to').datepicker({
                        showOn: 'both',
                        buttonImage: '<?= $this->url ?>/images/calendar.gif',
                        buttonImageOnly: true,
                        changeMonth: true,
                        changeYear: true,
                        yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
                    })
                }
            })

            <?php if (isset($_SESSION['freeow'])) : ?>
                $('#freeow-tr').freeow(
                    "<?= addslashes($_SESSION['freeow']['title']) ?>",
                    "<?= addslashes($_SESSION['freeow']['message']) ?>",
                    {classes: ['smokey']}
                )
                <?php unset($_SESSION['freeow']); ?>
            <?php endif; ?>
        })
    </script>
</head>
<body>

<?php if ('prod' !== $this->getParameter('kernel.environment')) : ?>
    <div class="debug-environment">
        <div class="environment">Environnement de <?= $this->getParameter('kernel.environment') ?></div>
    </div>
<?php endif; ?>

<div id="contener" class="container<?php if ('prod' !== $this->getParameter('kernel.environment')) : ?> debug<?php endif; ?>">
