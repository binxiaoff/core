<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Administration du site</title>
    <link rel="shortcut icon" href="<?= $this->surl ?>/images/admin/favicon.png" type="image/x-icon" />
    <script type="text/javascript">
        var add_surl = '<?= $this->surl ?>'
        var add_url = '<?= $this->lurl ?>'
    </script>
    <?php $this->callCss(); ?>
    <?php $this->callJs(); ?>
    <script type="text/javascript">
        <?php if ($this->getParameter('kernel.environment') === 'prod') : ?>
            // Hotjar Tracking Code for https://admin.unilend.fr/
            (function(h,o,t,j,a,r){
                h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
                h._hjSettings={hjid:479632,hjsv:5};
                a=o.getElementsByTagName('head')[0];
                r=o.createElement('script');r.async=1;
                r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
                a.appendChild(r);
            })(window,document,'//static.hotjar.com/c/hotjar-','.js?sv=');
        <?php endif; ?>

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
                        buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
                        buttonImageOnly: true,
                        changeMonth: true,
                        changeYear: true,
                        yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
                    })

                    $('#datepik_to').datepicker({
                        showOn: 'both',
                        buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
                        buttonImageOnly: true,
                        changeMonth: true,
                        changeYear: true,
                        yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
                    })
                }
            })

            $('#quick_search').submit(function(event) {
                var form = $(this),
                    siren = form.children('[name=siren]').val(),
                    projectName = form.children('[name=projectName]').val(),
                    projectId = form.children('[name=projectId]').val(),
                    lender = form.children('[name=lender]').val()

                if ('' != projectName) {
                    form.attr('action', '/dossiers')
                    form.append('<input type="hidden" name="form_search_dossier" value="1" />')
                    form.append('<input type="hidden" name="raison-sociale" value="' + projectName + '" />')
                    return
                }

                if ('' != siren) {
                    form.attr('action', '/dossiers')
                    form.append('<input type="hidden" name="form_search_dossier" value="1" />')
                    form.append('<input type="hidden" name="siren" value="' + siren + '" />')
                    return
                }

                if ('' != projectId && projectId == parseInt(projectId)) {
                    event.preventDefault();
                    window.location.replace('/dossiers/edit/' + projectId)
                    return
                }

                if ('' != lender && lender == parseInt(lender)) {
                    form.attr('action', '/preteurs/gestion')
                    form.append('<input type="hidden" name="form_search_preteur" value="1" />')
                    form.append('<input type="hidden" name="id" value="' + lender + '" />')
                    return
                }

                event.preventDefault()
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
        <div class="wiki"><a href="https://unilend.atlassian.net/wiki/pages/viewpage.action?pageId=46694427">Wiki</a></div>
    </div>
<?php endif; ?>
<div id="contener" class="container<?php if ('prod' !== $this->getParameter('kernel.environment')) : ?> debug<?php endif; ?>">

