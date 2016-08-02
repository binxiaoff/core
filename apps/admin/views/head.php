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
                var envRegex = /admin\.([a-z0-9]+)\..+/;
                var found = current.match(envRegex);
                if (found[1] !== 'unilend') {
                    isdev = true;
                    backgroundcolor = "#B20066";
                    stripes = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAAoCAIAAAADnC86AAAAtklEQVRYhcXWwQ2FMAwDUOcP26GY6e9ULkgIVGiTOMZ368k3G1pHNL1ZuPv7RLWtB+GkitjivBqBKaobZqk+mKg6YK66CtPVJbhCncNF6gSuU9/gUvURrlbHsEAdwBr1DsvUC6xUT1isHrBeBWD9H0bjKjKfK6P2ZkE4qSK2OK9GYIrqhlmqDyaqDpirrsJ0dQmuUOdwkTqB69Q3uFR9hKvVMSxQB7BGvcMy9QIr1RMWqwesVwHscK94fxGNJaQAAAAASUVORK5CYII=";
                    infoText  = 'Environnement de ' + found[1].toUpperCase();
                }

                if(isdev) {
                    var element = "<div style='border-bottom:1px solid #000;position:fixed;top:0;width:100%;height:45px;background:url(\""+stripes+"\");z-index:999;font-size:12px;text-align:center;color:#fff;line-height:45px;'><div id='dev-box' style='background-color:"+backgroundcolor+";padding:8px 10px;display:inline;height:40px;text-align:center;color:#f1f1f1;'></div></div>"
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
