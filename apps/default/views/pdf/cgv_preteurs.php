<html>
<head>
    <title>CGV preteurs</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="<?=$this->surl?>/styles/default/pdf/style.css" type="text/css" media="all" />
</head>
<body>
<script>var dataLayer = [<?= json_encode($this->aDataLayer) ?>];</script>
<!-- Google Tag Manager -->
<noscript><iframe src="//www.googletagmanager.com/ns.html?id=GTM-MB66VL"
                  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        '//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-MB66VL');</script>
<!-- End Google Tag Manager -->
<div class="cgu-wrapper">
    <div class="main" style="padding-bottom: 0px;">
        <div class="shell" style="width:750px;">
                <div class="logo"></div>
                <?= $this->content['contenu-cgu']?>
                <div style="page-break-after: always;"></div>
                <?= utf8_decode($this->content['mandatRecouvrement']) ?>
        </div>
    </div>
</div>
</body>
</html>
