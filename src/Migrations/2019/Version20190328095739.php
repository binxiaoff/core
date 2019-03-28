<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailTemplates;

final class Version20190328095739 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Demo emails content';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // Delete content first in order to avoid foreign key issue
        $this->addSql('DELETE FROM mail_templates WHERE part = "content"');
        $this->addSql('DELETE FROM mail_templates WHERE part != "content"');

        $this->addSql('INSERT INTO mail_templates (id_mail_template, type, locale, part, content, status, added) VALUES (1, "header", "fr_FR", "header", "' . addslashes($this->getHeaderContent()) . '", ' . MailTemplates::STATUS_ACTIVE . ', NOW())');
        $this->addSql('INSERT INTO mail_templates (id_mail_template, type, locale, part, content, status, added) VALUES (2, "footer", "fr_FR", "footer", "' . addslashes($this->getFooterContent()) . '", ' . MailTemplates::STATUS_ACTIVE . ', NOW())');
        $this->addSql('INSERT INTO mail_templates (id_mail_template, type, locale, part, recipient_type, sender_name, sender_email, subject, content, id_header, id_footer, status, added) VALUES (3, "project-new", "fr_FR", "content", "external", "Crédit Agricole Lending Services", "lending-services@creditagricole.fr", "Un nouveau dossier vient d‘être déposé", "' . addslashes($this->getNewProjectContent()) . '", 1, 2, ' . MailTemplates::STATUS_ACTIVE . ', NOW())');
        $this->addSql('INSERT INTO mail_templates (id_mail_template, type, locale, part, recipient_type, sender_name, sender_email, subject, content, id_header, id_footer, status, added) VALUES (4, "project-scoring", "fr_FR", "content", "external", "Crédit Agricole Lending Services", "lending-services@creditagricole.fr", "Nouvelle notation dossier", "' . addslashes($this->getProjectScoringContent()) . '", 1, 2, ' . MailTemplates::STATUS_ACTIVE . ', NOW())');
        $this->addSql('INSERT INTO mail_templates (id_mail_template, type, locale, part, recipient_type, sender_name, sender_email, subject, content, id_header, id_footer, status, added) VALUES (5, "project-publication", "fr_FR", "content", "external", "Crédit Agricole Lending Services", "lending-services@creditagricole.fr", "Un dossier vient d‘être publié", "' . addslashes($this->getProjectPublicationContent()) . '", 1, 2, ' . MailTemplates::STATUS_ACTIVE . ', NOW())');
        $this->addSql('INSERT INTO mail_templates (id_mail_template, type, locale, part, recipient_type, sender_name, sender_email, subject, content, id_header, id_footer, status, added) VALUES (6, "bid-new", "fr_FR", "content", "external", "Crédit Agricole Lending Services", "lending-services@creditagricole.fr", "Une nouvelle offre de participation vient d‘être formulée", "' . addslashes($this->getNewBidContent()) . '", 1, 2, ' . MailTemplates::STATUS_ACTIVE . ', NOW())');
        $this->addSql('INSERT INTO mail_templates (id_mail_template, type, locale, part, recipient_type, sender_name, sender_email, subject, content, id_header, id_footer, status, added) VALUES (7, "bid-accepted", "fr_FR", "content", "external", "Crédit Agricole Lending Services", "lending-services@creditagricole.fr", "Votre offre de participation a été acceptée", "' . addslashes($this->getAcceptedBidContent()) . '", 1, 2, ' . MailTemplates::STATUS_ACTIVE . ', NOW())');
        $this->addSql('INSERT INTO mail_templates (id_mail_template, type, locale, part, recipient_type, sender_name, sender_email, subject, content, id_header, id_footer, status, added) VALUES (8, "bid-rejected", "fr_FR", "content", "external", "Crédit Agricole Lending Services", "lending-services@creditagricole.fr", "Votre offre de participation a été rejetée", "' . addslashes($this->getRejectedBidContent()) . '", 1, 2, ' . MailTemplates::STATUS_ACTIVE . ', NOW())');
        $this->addSql('INSERT INTO mail_templates (id_mail_template, type, locale, part, recipient_type, sender_name, sender_email, subject, content, id_header, id_footer, status, added) VALUES (9, "project-funding-end", "fr_FR", "content", "external", "Crédit Agricole Lending Services", "lending-services@creditagricole.fr", "Fin de la période de financement", "' . addslashes($this->getFundingEndContent()) . '", 1, 2, ' . MailTemplates::STATUS_ACTIVE . ', NOW())');

        $this->addSql('DELETE FROM translations WHERE section = "mail-title" AND name IN ("project-new", "project-scoring", "project-publication", "bid-new", "bid-accepted", "bid-rejected", "project-funding-end")');

        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "mail-title", "project-new", "Nouveau dossier", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "mail-title", "project-scoring", "Publication d‘un nouveau dossier", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "mail-title", "project-publication", "Nouvelle notation dossier", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "mail-title", "bid-new", "Nouvelle offre de participation", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "mail-title", "bid-accepted", "Offre de participation rejetée", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "mail-title", "bid-rejected", "Fin de la période de financement", NOW())');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "mail-title", "project-funding-end", "Offre de participation acceptée", NOW())');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM mail_templates WHERE id_mail_template BETWEEN 1 AND 9');
        $this->addSql('DELETE FROM translations WHERE section = "mail-title" AND name IN ("project-new", "project-scoring", "project-publication", "bid-new", "bid-accepted", "bid-rejected", "project-funding-end")');
    }

    /**
     * @return string
     */
    private function getHeaderContent(): string
    {
        return <<<HEADER
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <!-- Outlook Conditional -->
    <!--[if gte mso 15]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG />
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Cabin:400,700,400">
    <!--[if mso]>
    <style>
        body,#body,table,td {
            font-family:Arial,Helvetica,sans-serif !important;
        }
    </style>
    <![endif]-->
    <style type="text/css">
        body,#body{
            height:100%;
            margin:0;
            padding:0;
            width:100%;
        }
        p{
            margin-top:18px;
            margin-bottom:18px;
            padding:0;
        }
        table{
            border-collapse:collapse;
            mso-table-lspace:0;
            mso-table-rspace:0;
        }
        h1,h2{
            display:block;
            margin:0;
            padding:0;
        }
        img{
            -ms-interpolation-mode:bicubic;
        }
        img,a img{
            border:0;
            height:auto;
            outline:none;
            text-decoration:none;
            margin:0 !important;
        }
        #outlook a{
            padding:0;
        }
        a{
            color:#2bc9af;
            font-weight:normal;
            text-decoration:none;
        }
        a:hover{
            text-decoration:underline;
        }
        p,a,td{
            -ms-text-size-adjust:100%;
            -webkit-text-size-adjust:100%;
        }
        body,#body{
            background:#f8f6f8;
            color:#302a32;
            font-family:\'\'Cabin\'\',Trebuchet MS,Helvetica,sans-serif;
            font-size:16px;
            line-height:27px;
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
        }
        p{
            color:#302a32;
            font-size:16px;
            line-height:27px;
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
        }
        h1,h2{
            color:#2bc9af;
            font-weight:normal;
        }
        h1{
            font-size:22px;
            line-height:1.25em;
        }
        h2{
            font-size:20px;
            line-height:1.15em;
        }
        ul li {
            line-height: 27px;
        }
        .text-primary {
            color: #2bc9af;
        }
        .text-left {
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        b, strong {
            font-weight: 500;
        }
        #container{
            width:600px;
            max-width:600px;
        }
        .image{
            height:auto;
            max-width:510px;
        }
        #header,#title,#content,#footer,#under,#above,#motive{
            box-sizing: border-box;
            padding-right:45px;
            padding-left:45px;
        }
        #header,#content{
            background:#fff;
        }
        #header,#header-full{
            border-top:6px solid #2bc9af;
        }
        #header{
            padding-top:36px;
            padding-bottom:12px;
        }
        #header-full{
            background:#fafafa;
        }
        #logo{
            width: 36px;
            padding-right:8px;
        }
        #header-full #logo{
            width:50%;
            padding-left:45px;
        }
        #header-image{
            width:50%;
            text-align:right;
        }
        #title{
            padding-top:36px;
            background:#fff;
        }
        #title hr{
            padding:0;
            margin:18px 0;
            display:block;
            clear:both;
            border:0 none;
            width:60px;
            height:2px;
            background-color:#2bc9af;
        }
        #content{
            padding-bottom:18px;
        }
        .c-p1{
            color:#2bc9af;
        }
        .c-t2{
            color:#787679;
        }
        .c-t3{
            color:#9f9d9f;
        }
        .c-white{
            color: #fff;
        }
        .underline{
            text-decoration:underline;
        }
        #above{
            background:#b9b8bA;
        }
        #above p{
            text-align:center;
        }
        #above p,#under p,.alert p{
            font-size:16px;
            line-height:24px;
        }
        #motive{
            background:#eceaed;
        }
        #motive p{
            font-size:14px !important;
            line-height:20px !important;
            margin:10px 0 !important;
        }
        #motive b{
            float:right;
        }
        #footer{
            padding-top:36px;
            padding-bottom:36px;
            background:#2bc9af;
        }
        #footer .left,#footer .right{
            width:50%;
        }
        #footer .right{
            text-align:right;
        }
        #footer .right p{
            color:#e49dc0;
            font-size:14px;
            margin-bottom:0;
        }
        #footer .right a{
            color:#e49dc0;
        }
        #footer .left a{
            color:#fff;
        }
        #footer .left a:hover{
            text-decoration:underline;
        }
        #footer .right img{
            display:inline-block;
            margin-left:4px;
        }
        #under p{
            text-align:center;
        }
        .cta{
            text-align:center;
            padding-top:18px;
            padding-bottom:18px;
        }
        .btn-primary,.btn-outline{
            mso-hide:all;
            display:inline-block;
            border-radius:4px;
            border-width:2px;
            border-style:solid;
            min-width:120px;
            padding-left:30px;
            padding-right:30px;
            padding-top:11px;
            padding-bottom:11px;
            font-size:18px;
            text-decoration:none;
        }
        .btn-primary{
            border-color:#2bc9af;
            background:#2bc9af;
            color:#fff;
        }
        .btn-primary:hover{
            border-color:#9a1d5a;
            background:#9a1d5a;
            text-decoration:none;
        }
        .btn-primary:active{
            border-color:#85194d;
            background:#85194d;
        }
        .btn-outline{
            border-color:#2bc9af;
            color:#2bc9af;
        }
        .btn-outline:hover{
            border-color:#9a1d5a;
            color:#9a1d5a;
            text-decoration:none;
        }
        .btn-outline:active{
            border-color:#85194d;
            color:#85194d;
        }
        .alert{
            padding-left:20px;
            padding-right:20px;
            margin-top:18px;
            margin-bottom:18px;
            background:#FDF4FA;
        }
        .alert .left{
            padding-top:18px;
            padding-right:20px;
        }
        .table {
            width: 100%;
            margin-bottom: 18px;
        }
        .td {
            padding: 3px;
            font-size: 14px;
        }
        .th {
            padding: 3px;
            font-size: 14px;
            font-weight: 500;
            border-bottom: 1px solid #E3E4E5;
        }
        .tf {
            padding: 3px;
            font-size: 14px;
            border-top: 1px solid #E3E4E5;
        }
        @media only screen and (max-width: 670px){
            .left,.right{
                width:100% !important;
                float:left !important;
            }
            #container{
                width:100% !important;
                max-width:100% !important;
            }
            #header,#title,#content,#footer,#under,#above,#motive{
                padding-right:20px !important;
                padding-left:20px !important;
            }
            #header-full #logo{
                padding-top:25px !important;
                padding-left:20px !important;
            }
            #header-full #logo img{
                width:150px !important;
            }
            #header-image img{
                width:175px !important;
            }
            #title{
                padding-top:18px;
            }
            .alert .left{
                text-align:center !important;
            }
            #footer .right{
                padding-top:18px;
                text-align:left !important;
            }
            h1{
                font-size:22px !important;
            }
            h2,h3{
                font-size:20px !important;
            }
            #footer .right img{
                margin-left:0 !important;
                margin-right:4px;
            }
            #motive b{
                float:none !important;
                display:block;
            }
        }
    </style>
</head>
<body>
<table id="body" align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td align="center" valign="top">
            <!-- BEGIN TEMPLATE -->
            <table id="container" border="0" cellpadding="0" cellspacing="0" width="600">
                <!-- BEGIN HEADER -->
                <tr>
                    <td id="header" valign="top">
                        <p>
                            <img src="[EMV DYN]staticUrl[EMV /DYN]/assets/images/logo/logo-and-type-209x44.png" alt="Crédit Agricole Lending Services" width="209" height="44">
                        </p>
                        <h2>[EMV DYN]title[EMV /DYN]</h2>
                    </td>
                </tr>
                <!-- END HEADER -->
                <!-- BEGIN CONTENT -->
                <tr>
                    <td id="content">
HEADER;
    }

    /**
     * @return string
     */
    private function getFooterContent(): string
    {
        return <<<FOOTER
</td>
</tr>
<!-- END CONTENT -->
<!-- BEGIN FOOTER -->
<tr>
    <td id="under" align="center">
        <p class="c-t3">
            Crédit Agricole Lending Services
        </p>
    </td>
</tr>
<!-- END FOOTER -->
</table>
<!-- END TEMPLATE -->
</td>
</tr>
</table>
</body>
</html>
FOOTER;
    }

    /**
     * @return string
     */
    private function getNewProjectContent(): string
    {
        return <<<CONTENT
<p>Bonjour [EMV DYN]firstName[EMV /DYN],<p>
<p>Un nouveau dossier vient d‘être déposé sur votre plateforme <a href="[EMV DYN]frontUrl[EMV /DYN]">Crédit Agricole Lending Services</a>. Pour le consulter, <a href="[EMV DYN]projectUrl[EMV /DYN]">cliquez ici</a>.
<p>Caractéristiques principales&nbsp;:</p>
<ul>
<li>Emprunteur&nbsp;: [EMV DYN]borrower[EMV /DYN]</li>
<li>Montant&nbsp;: [EMV DYN]amount[EMV /DYN]&nbsp;€</li>
<li>Maturité&nbsp;: [EMV DYN]duration[EMV /DYN]</li>
</ul>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="cta" align="center">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="[EMV DYN]projectUrl[EMV /DYN]" style="height:50px;v-text-anchor:middle;width:270px;" arcsize="5%" strokecolor="#2bc9af" fillcolor="#2bc9af">
                <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:18px;">Consulter le dossier</center>
            </v:roundrect>
            <![endif]-->
            <a href="[EMV DYN]projectUrl[EMV /DYN]" class="btn-primary">Consulter le dossier</a>
        </td>
    </tr>
</table>
CONTENT;
    }

    /**
     * @return string
     */
    private function getProjectScoringContent(): string
    {
        return <<<CONTENT
<p>Bonjour [EMV DYN]firstName[EMV /DYN],</p>
<p>Une notation vient d‘être modifiée sur le dossier <a href="[EMV DYN]projectUrl[EMV /DYN]">[EMV DYN]projectName[EMV /DYN]</a>.</p>
<p>Nouvelle notation [EMV DYN]scoringName[EMV /DYN]&nbsp;: [EMV DYN]scoringValue[EMV /DYN]</p>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="cta" align="center">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="[EMV DYN]projectUrl[EMV /DYN]" style="height:50px;v-text-anchor:middle;width:270px;" arcsize="5%" strokecolor="#2bc9af" fillcolor="#2bc9af">
                <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:18px;">Consulter le dossier</center>
            </v:roundrect>
            <![endif]-->
            <a href="[EMV DYN]projectUrl[EMV /DYN]" class="btn-primary">Consulter le dossier</a>
        </td>
    </tr>
</table>
CONTENT;
    }

    /**
     * @return string
     */
    private function getProjectPublicationContent(): string
    {
        return <<<CONTENT
<p>Bonjour [EMV DYN]firstName[EMV /DYN],</p>
<p>Le dossier <a href="[EMV DYN]projectUrl[EMV /DYN]">[EMV DYN]projectName[EMV /DYN]</a> vient d‘être publié. Il est à présent possible de formuler une offre de participation sur ce dossier.
<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="cta" align="center">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="[EMV DYN]projectUrl[EMV /DYN]" style="height:50px;v-text-anchor:middle;width:270px;" arcsize="5%" strokecolor="#2bc9af" fillcolor="#2bc9af">
                <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:18px;">Consulter le dossier</center>
            </v:roundrect>
            <![endif]-->
            <a href="[EMV DYN]projectUrl[EMV /DYN]" class="btn-primary">Consulter le dossier</a>
        </td>
    </tr>
</table>
CONTENT;
    }

    /**
     * @return string
     */
    private function getNewBidContent(): string
    {
        return <<<CONTENT
<p>Bonjour [EMV DYN]firstName[EMV /DYN],</p>
<p>Une nouvelle offre de participation vient d‘être formulée sur le dossier <a href="[EMV DYN]projectUrl[EMV /DYN]">[EMV DYN]projectName[EMV /DYN]</a> par [EMV DYN]bidderName[EMV /DYN].</p>
<ul>
<li>Montant&nbsp;: [EMV DYN]bidAmount[EMV /DYN]&nbsp;€</li>
<li>Taux de référence&nbsp;: [EMV DYN]bidRateIndex[EMV /DYN]</li>
<li>Marge&nbsp;: [EMV DYN]bidMarginRate[EMV /DYN]&nbsp;%</li>
<li>Souhaite être agent&nbsp;: [EMV DYN]bidAgent[EMV /DYN]</li>
</ul>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="cta" align="center">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="[EMV DYN]projectUrl[EMV /DYN]" style="height:50px;v-text-anchor:middle;width:270px;" arcsize="5%" strokecolor="#2bc9af" fillcolor="#2bc9af">
                <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:18px;">Consulter le dossier</center>
            </v:roundrect>
            <![endif]-->
            <a href="[EMV DYN]projectUrl[EMV /DYN]" class="btn-primary">Consulter le dossier</a>
        </td>
    </tr>
</table>
CONTENT;
    }

    /**
     * @return string
     */
    private function getAcceptedBidContent(): string
    {
        return <<<CONTENT
<p>Bonjour [EMV DYN]firstName[EMV /DYN],<p>
<p>Votre offre de participation sur le projet <a href="[EMV DYN]projectUrl[EMV /DYN]">[EMV DYN]projectName[EMV /DYN]</a> a été acceptée.</p>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="cta" align="center">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="[EMV DYN]projectUrl[EMV /DYN]" style="height:50px;v-text-anchor:middle;width:270px;" arcsize="5%" strokecolor="#2bc9af" fillcolor="#2bc9af">
                <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:18px;">Consulter le dossier</center>
            </v:roundrect>
            <![endif]-->
            <a href="[EMV DYN]projectUrl[EMV /DYN]" class="btn-primary">Consulter le dossier</a>
        </td>
    </tr>
</table>
CONTENT;
    }

    /**
     * @return string
     */
    private function getRejectedBidContent(): string
    {
        return <<<CONTENT
<p>Bonjour [EMV DYN]firstName[EMV /DYN],<p>
<p>Votre offre de participation sur le projet <a href="[EMV DYN]projectUrl[EMV /DYN]">[EMV DYN]projectName[EMV /DYN]</a> a été rejetée. Tant que le dossier est en cours de financement, vous pouvez formuler d‘autres offres de participation.</p>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="cta" align="center">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="[EMV DYN]projectUrl[EMV /DYN]" style="height:50px;v-text-anchor:middle;width:270px;" arcsize="5%" strokecolor="#2bc9af" fillcolor="#2bc9af">
                <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:18px;">Consulter le dossier</center>
            </v:roundrect>
            <![endif]-->
            <a href="[EMV DYN]projectUrl[EMV /DYN]" class="btn-primary">Consulter le dossier</a>
        </td>
    </tr>
</table>
CONTENT;
    }

    /**
     * @return string
     */
    private function getFundingEndContent(): string
    {
        return <<<CONTENT
<p>Bonjour [EMV DYN]firstName[EMV /DYN],</p>
<p>Le financement du dossier <a href="[EMV DYN]projectUrl[EMV /DYN]">[EMV DYN]projectName[EMV /DYN]</a> est maintenant terminé. Il ne vous reste plus qu‘à signer électroniquement le contrat pour valider votre participation au financement.</p>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="cta" align="center">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="[EMV DYN]signatureUrl[EMV /DYN]" style="height:50px;v-text-anchor:middle;width:270px;" arcsize="5%" strokecolor="#2bc9af" fillcolor="#2bc9af">
                <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:18px;">Signer le contrat</center>
            </v:roundrect>
            <![endif]-->
            <a href="[EMV DYN]signatureUrl[EMV /DYN]" class="btn-primary">Signer le contrat</a>
        </td>
    </tr>
</table>
CONTENT;
    }
}
