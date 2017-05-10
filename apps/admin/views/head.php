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

            // Ad custom header function if in dev mode
            var envRegex = /admin\.([a-z0-9]+)\..+/,
                found = window.location.hostname.match(envRegex),
                wiki = false,
                wikiUrl = ''

            if (found[1] !== 'unilend') {
                switch (found[1]) {
                    case 'demo01':
                    case 'demo02':
                    case 'preprod':
                        wiki = true
                        break
                }

                if (wiki) {
                    wikiUrl = '<div style="background-color:#b20066; padding:8px 10px; margin-left: 10px; display:inline; height:40px; text-align:center;"><a style="color:#f1f1f1;" href="https://unilend.atlassian.net/wiki/pages/viewpage.action?pageId=46694427">Wiki</a></div>'
                }

                var element = '<div style="position:fixed; top:0; width:100%; height:45px; background-color:#8acf00; z-index:999; font-size:12px; text-align:center; color:#fff; line-height:45px"><div id="dev-box" style="background-color:#b20066; padding:8px 10px; display:inline; height:40px; text-align:center; color:#f1f1f1;"></div>' + wikiUrl + '</div>'
                document.getElementById('contener').style.top = "45px"
                $('body').append(element)
                $('#dev-box').html('Environnement de ' + found[1].toUpperCase())
            }
        })
    </script>
</head>
<body>
<div id="contener">
