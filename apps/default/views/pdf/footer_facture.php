<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
    <title>UNILEND</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <style type="text/css">

        .container {
            margin: 0 auto;
            width: 892px;
        }
        .footer {
            color: #B20066;
            font-family: 'Calibri-Regular', arial, sans-serif;
            font-size: 9px;
            line-height: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main">
            <div class="footer">
                <p><?= $this->titreUnilend ?> <span>•</span> <?= $this->raisonSociale ?> - <?= $this->sffpme ?> <span>•</span> <?= $this->capital ?></p>
                <p><?= $this->raisonSocialeAdresse ?> <span>•</span> <?= $this->telephone ?> <span>•</span> <?= $this->rcs ?> <span>•</span> <?= $this->tvaIntra ?></p>
            </div><!-- /.footer -->
        </div><!-- /.main -->
    </div><!-- /.container -->
</body>
</html>
