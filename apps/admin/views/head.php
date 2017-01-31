<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Administration du site</title>
    <link rel="shortcut icon" href="<?= $this->surl ?>/images/admin/favicon.png" type="image/x-icon" />
    <script type="text/javascript">
        var add_surl = '<?= $this->surl ?>';
        var add_url = '<?= $this->lurl ?>';
    </script>
    <?php $this->callCss();?>
    <?php $this->callJs();?>
    <script>
        // Ad custom header function if in dev mode
        $(function() {
            (function() {
                var current = window.location.hostname,
                    envBackgroundColor,
                    backgroundColor,
                    infoText,
                    isdev = false,
                    envRegex = /admin\.([a-z0-9]+)\..+/,
                    found = current.match(envRegex),
                    wiki = false,
                    wikiUrl = '';

                if (found[1] !== 'unilend') {
                    isdev = true;
                    envBackgroundColor = "#b20066";
                    backgroundColor = "#8acf00";
                    infoText = 'Environnement de ' + found[1].toUpperCase();
                }

                switch (found[1]) {
                    case 'demo01':
                    case 'demo02':
                    case 'preprod':
                        wiki = true;
                        break;
                }

                if (isdev) {
                    if (wiki) { wikiUrl = '<div style="background-color:' + envBackgroundColor + '; padding:8px 10px; margin-left: 10px; display:inline; height:40px; text-align:center;"><a style="color:#f1f1f1;" href="https://unilend.atlassian.net/wiki/pages/viewpage.action?pageId=46694427">Wiki</a></div>'; }
                    var element = '<div style="position:fixed; top:0; width:100%; height:45px; background-color:' + backgroundColor + '; z-index:999; font-size:12px; text-align:center; color:#fff; line-height:45px"><div id="dev-box" style="background-color:' + envBackgroundColor + '; padding:8px 10px; display:inline; height:40px; text-align:center; color:#f1f1f1;"></div>' + wikiUrl + '</div>'
                    document.getElementById('contener').style.top = "45px";
                    $('body').append(element);
                    $('#dev-box').html(infoText);
                }
            })();
        })
    </script>
</head>
<body>
<div id="contener">
