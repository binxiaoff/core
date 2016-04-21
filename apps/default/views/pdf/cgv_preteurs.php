<html>
<head>
    <title>CGV preteurs</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="<?=$this->surl?>/styles/default/pdf/style.css" type="text/css" media="all" />
</head>
<body>
<div class="cgu-wrapper">
    <div class="main" style="padding-bottom: 0px;">
        <div class="shell" style="width:750px;">
            <div class="logo"></div>
            <?= $this->content['contenu-cgu']?>
            <div style="page-break-after: always;"></div>
            <?= utf8_decode($this->content['mandatRecouvrement']) ?>
            <?php if (false === empty($this->content['mandatRecouvrementAvecPret'])) : ?>
                <div style="page-break-after: always;"></div>
                <?= utf8_decode($this->content['mandatRecouvrementAvecPret']) ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
