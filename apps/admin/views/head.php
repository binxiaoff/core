<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Administration <?=$this->cms?></title>
    <link rel="shortcut icon" href="<?=$this->surl?>/images/admin/favicon.png" type="image/x-icon" />
    <script type="text/javascript">
        var add_surl = '<?=$this->surl?>';
        var add_url = '<?=$this->lurl?>';
    </script>
    <?php $this->callCss();?>
    <?php $this->callJs();?>
    <script>
        // Ad custom header function if in dev mode
        $(document).ready(function() {
            (function() {
                var current = window.location.hostname,
                    backgroundcolor,
                    infoText,
                    isdev = false;
                switch (current) {
                    case "admin.demo01.corp.unilend.fr" :
                        isdev = true;
                        backgroundcolor = "#ff0000";
                        stripes = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAAoCAIAAAADnC86AAAArUlEQVRYhcXWuw2AUBQCUHQm95/AnbQy0ejT+wGkJyd0TBsaWevV+RcVSxnuqSgubqslmKHmYZKahHlqBqaqYZitxmCBGoA16hcsU19hpTqGxeoA1qtPsEW9wS71ChvVE+xVD9iuAph/UdH6XA0VaxnuqSgubqslmKHmYZKahHlqBqaqYZitxmCBGoA16hcsU19hpTqGxeoA1qtPsEW9wS71ChvVE+xVD9iuAtgBO70pG+yZxycAAAAASUVORK5CYII=";
                        infoText  = "Environnement de demo : demo01";
                        break;
                    case "admin.demo02.corp.unilend.fr" :
                        isdev = true;
                        backgroundcolor = "#0066ff";
                        stripes = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAAoCAIAAAADnC86AAAAtklEQVRYhcXWwQ2FMAwDUOcP26GY6e9ULkgIVGiTOMZ368k3G1pHNL1ZuPv7RLWtB+GkitjivBqBKaobZqk+mKg6YK66CtPVJbhCncNF6gSuU9/gUvURrlbHsEAdwBr1DsvUC6xUT1isHrBeBWD9H0bjKjKfK6P2ZkE4qSK2OK9GYIrqhlmqDyaqDpirrsJ0dQmuUOdwkTqB69Q3uFR9hKvVMSxQB7BGvcMy9QIr1RMWqwesVwHscK94fxGNJaQAAAAASUVORK5CYII=";
                        infoText = "Environnement de demo : demo02";
                        break;
                    case "admin.preprod.corp.unilend.fr" :
                        isdev = true;
                        backgroundcolor = "#21800e";
                        stripes = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAAoCAIAAAADnC86AAAAwElEQVRYhcXWSw2EQBgD4IIAPCx6EIWolbEaEIGB5UJCIAMz/6Ol9+ZLb+0+8wBvlml1d/tX1PE7OOGgCt/iuOqBU1QznKXa4ETVAOeqrXC62gQz1DpMUiswT32CqeotzFbLsEAtwBr1CsvUE6xUD1is7rBeBdD9f27UryLyuSLqMq1OOKjCtziueuAU1QxnqTY4UTXAuWornK42wQy1DpPUCsxTn2Cqeguz1TIsUAuwRr3CMvUEK9UDFqs7rFcBbLpMd8nASyknAAAAAElFTkSuQmCC";
                        infoText = "Environnement de preprod";
                        break;
                    case "admin.local.unilend.fr" :
                        isdev = true;
                        backgroundcolor = "#803F0E";
                        stripes = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAAoCAIAAAADnC86AAAAv0lEQVRYhcXWSw2EQBgD4IIALK0oBMAKwNmqwcByISGQgZn/0dJ786W3dvNngDfTsrq7/SvqdxyccFCFb3Fc9cApqhnOUm1womqAc9VWOF1tghlqHSapFZinPsFU9RZmq2VYoBZgjXqFZeoJVqoHLFZ3WK8C6P4/N+pXEflcEXVaViccVOFbHFc9cIpqhrNUG5yoGuBctRVOV5tghlqHSWoF5qlPMFW9hdlqGRaoBVijXmGZeoKV6gGL1R3WqwA21hZ359IqC54AAAAASUVORK5CYII=";
                        infoText = "Environnement de dev local";
                        break;
                    default :
                        break;
                }
                if(isdev) {
                    var element = "<div style='border-bottom:2px solid #000;position:fixed;top:0;width:100%;height:70px;background:url(\""+stripes+"\");z-index:999;font-size:19px;text-align:center;color:#fff;line-height:70px;'><div id='dev-box' style='background-color:"+backgroundcolor+";padding:15px 35px;display:inline;height:40px;margin:15px auto 0 auto;text-align:center;line-height:40px;color:#f1f1f1;'></div></div>"
                    document.getElementById('contener').style.top = "70px";
                    $('body').append(element);
                    $('#dev-box').html(infoText);
                }
            })();
        })
    </script>
</head>
<body>
<div id="contener">
