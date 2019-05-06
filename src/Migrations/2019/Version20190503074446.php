<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190503074446 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Create project signature database schema';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql(
            <<<'CREATETABLE'
CREATE TABLE project_attachment_signature (
  id INT AUTO_INCREMENT NOT NULL,
  id_project_attachment INT NOT NULL,
  id_signatory INT NOT NULL,
  docusign_envelope_id INT DEFAULT NULL,
  status SMALLINT NOT NULL,
  updated DATETIME DEFAULT NULL,
  added DATETIME NOT NULL,
  INDEX IDX_FEB360CDE2DB1026 (id_project_attachment),
  INDEX IDX_FEB360CD2B0DC78F (id_signatory),
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
CREATETABLE
        );
        $this->addSql('ALTER TABLE project_attachment_signature ADD CONSTRAINT FK_FEB360CDE2DB1026 FOREIGN KEY (id_project_attachment) REFERENCES project_attachment (id)');
        $this->addSql('ALTER TABLE project_attachment_signature ADD CONSTRAINT FK_FEB360CD2B0DC78F FOREIGN KEY (id_signatory) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE project_attachment RENAME INDEX id_project TO IDX_61F9A289F12E799E');
        $this->addSql('ALTER TABLE project_attachment RENAME INDEX id_attachment TO IDX_61F9A289DCD5596C');

        $this->addSql('INSERT INTO translations (locale, section, name, translation, added) VALUES ("fr_FR", "mail-title", "document-signature", "Nouvelle demande de signature électronique", NOW())');
        $this->addSql(
            <<<'INSERTMAIL'
INSERT INTO mail_templates (
    id_mail_template,
    type,
    locale,
    part,
    recipient_type,
    sender_name,
    sender_email,
    subject,
    content,
    compiled_content,
    id_header,
    id_footer,
    status,
    added
) VALUES (
    10,
    'document-signature',
    'fr_FR',
    'content',
    'external',
    'Crédit Agricole Lending Services',
    'contact@ca-lendingservices.com',
    'Nouvelle demande de signature électronique',
    '<p>Bonjour [EMV DYN]firstName[EMV /DYN],</p>
<p>Une nouvelle demande de signature électronique de document vient d’arriver. Cliquez sur le lien ci-dessous pour accéder à l’interface de signature électronique.</p>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="cta" align="center">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="[EMV DYN]signatureUrl[EMV /DYN]" style="height:50px;v-text-anchor:middle;width:270px;" arcsize="5%" strokecolor="#2bc9af" fillcolor="#2bc9af">
                <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:18px;">Signer le document</center>
            </v:roundrect>
            <![endif]-->
            <a href="[EMV DYN]signatureUrl[EMV /DYN]" class="btn-primary">Signer le document</a>
        </td>
    </tr>
</table>',
    '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<!-- Outlook Conditional --><!--[if gte mso 15]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG />
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]--><meta charset="UTF-8">
<meta http-equiv="x-ua-compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Cabin:400,700,400">
<!--[if mso]>
    <style>
        body,#body,table,td {
            font-family:Arial,Helvetica,sans-serif !important;
        }
    </style>
    <![endif]--><style type="text/css">
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
            font-family:"Cabin",Trebuchet MS,Helvetica,sans-serif;
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
<body style="height: 100%; margin: 0; padding: 0; width: 100%; background: #f8f6f8; color: #302a32; font-family: "Cabin",Trebuchet MS,Helvetica,sans-serif; font-size: 16px; line-height: 27px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
<table id="body" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0; mso-table-rspace: 0; height: 100%; margin: 0; padding: 0; width: 100%; background: #f8f6f8; color: #302a32; font-family: "Cabin",Trebuchet MS,Helvetica,sans-serif; font-size: 16px; line-height: 27px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;"><tr>
<td align="center" valign="top" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;">
            <!-- BEGIN TEMPLATE -->
            <table id="container" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse; mso-table-lspace: 0; mso-table-rspace: 0; width: 600px; max-width: 600px;">
<!-- BEGIN HEADER --><tr>
<td id="header" valign="top" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; box-sizing: border-box; padding-right: 45px; padding-left: 45px; background: #fff; border-top: 6px solid #2bc9af; padding-top: 36px; padding-bottom: 12px;">
                        <p style="margin-top: 18px; margin-bottom: 18px; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #302a32; font-size: 16px; line-height: 27px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
                            <img src="[EMV DYN]staticUrl[EMV /DYN]/assets/images/logo/logo-and-type-209x44.png" alt="Crédit Agricole Lending Services" width="209" height="44" style="-ms-interpolation-mode: bicubic; border: 0; height: auto; outline: none; text-decoration: none; margin: 0 !important;"></p>
                        <h2 style="display: block; margin: 0; padding: 0; color: #2bc9af; font-weight: normal; font-size: 20px; line-height: 1.15em;">[EMV DYN]title[EMV /DYN]</h2>
                    </td>
                </tr>
<!-- END HEADER --><!-- BEGIN CONTENT --><tr>
<td id="content" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; box-sizing: border-box; padding-right: 45px; padding-left: 45px; background: #fff; padding-bottom: 18px;">
<p style="margin-top: 18px; margin-bottom: 18px; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #302a32; font-size: 16px; line-height: 27px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">Bonjour [EMV DYN]firstName[EMV /DYN],</p>
<p style="margin-top: 18px; margin-bottom: 18px; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #302a32; font-size: 16px; line-height: 27px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">Une nouvelle demande de signature électronique de document vient d’arriver. Cliquez sur le lien ci-dessous pour accéder à l’interface de signature électronique.</p>

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0; mso-table-rspace: 0;"><tr>
<td class="cta" align="center" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; text-align: center; padding-top: 18px; padding-bottom: 18px;">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="[EMV DYN]signatureUrl[EMV /DYN]" style="height:50px;v-text-anchor:middle;width:270px;" arcsize="5%" strokecolor="#2bc9af" fillcolor="#2bc9af">
                <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:18px;">Signer le document</center>
            </v:roundrect>
            <![endif]-->
            <a href="[EMV DYN]signatureUrl[EMV /DYN]" class="btn-primary" style="font-weight: normal; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; mso-hide: all; display: inline-block; border-radius: 4px; border-width: 2px; border-style: solid; min-width: 120px; padding-left: 30px; padding-right: 30px; padding-top: 11px; padding-bottom: 11px; font-size: 18px; text-decoration: none; border-color: #2bc9af; background: #2bc9af; color: #fff;">Signer le document</a>
        </td>
    </tr></table>
</td>
</tr>
<!-- END CONTENT --><!-- BEGIN FOOTER --><tr>
<td id="under" align="center" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; box-sizing: border-box; padding-right: 45px; padding-left: 45px;">
        <p class="c-t3" style="margin-top: 18px; margin-bottom: 18px; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; color: #9f9d9f; font-size: 16px; line-height: 24px; text-align: center;">
            Crédit Agricole Lending Services
        </p>
    </td>
</tr>
<!-- END FOOTER -->
</table>
<!-- END TEMPLATE -->
</td>
</tr></table>
</body>
</html>', 1, 2, 1, NOW())
INSERTMAIL
        );

        // No rollback needed
        $this->addSql('UPDATE mail_templates SET sender_email = "contact@ca-lendingservices.com" WHERE 1');
        $this->addSql(
            <<<'UPDATEMAIL'
UPDATE mail_templates
SET content = '<p>Bonjour [EMV DYN]firstName[EMV /DYN],</p>
<p>Le financement du dossier <a href="[EMV DYN]projectUrl[EMV /DYN]">[EMV DYN]projectName[EMV /DYN]</a> est terminé. D’ici peu, vous recevrez la liste des documents à signer électroniquement.</p>',
  compiled_content = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<!-- Outlook Conditional --><!--[if gte mso 15]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG />
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]--><meta charset="UTF-8">
<meta http-equiv="x-ua-compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Cabin:400,700,400">
<!--[if mso]>
    <style>
        body,#body,table,td {
            font-family:Arial,Helvetica,sans-serif !important;
        }
    </style>
    <![endif]--><style type="text/css">
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
            font-family:"Cabin",Trebuchet MS,Helvetica,sans-serif;
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
<body style="height: 100%; margin: 0; padding: 0; width: 100%; background: #f8f6f8; color: #302a32; font-family: "Cabin",Trebuchet MS,Helvetica,sans-serif; font-size: 16px; line-height: 27px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
<table id="body" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0; mso-table-rspace: 0; height: 100%; margin: 0; padding: 0; width: 100%; background: #f8f6f8; color: #302a32; font-family: "Cabin",Trebuchet MS,Helvetica,sans-serif; font-size: 16px; line-height: 27px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;"><tr>
<td align="center" valign="top" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;">
            <!-- BEGIN TEMPLATE -->
            <table id="container" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse; mso-table-lspace: 0; mso-table-rspace: 0; width: 600px; max-width: 600px;">
<!-- BEGIN HEADER --><tr>
<td id="header" valign="top" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; box-sizing: border-box; padding-right: 45px; padding-left: 45px; background: #fff; border-top: 6px solid #2bc9af; padding-top: 36px; padding-bottom: 12px;">
                        <p style="margin-top: 18px; margin-bottom: 18px; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #302a32; font-size: 16px; line-height: 27px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
                            <img src="[EMV DYN]staticUrl[EMV /DYN]/assets/images/logo/logo-and-type-209x44.png" alt="Crédit Agricole Lending Services" width="209" height="44" style="-ms-interpolation-mode: bicubic; border: 0; height: auto; outline: none; text-decoration: none; margin: 0 !important;"></p>
                        <h2 style="display: block; margin: 0; padding: 0; color: #2bc9af; font-weight: normal; font-size: 20px; line-height: 1.15em;">[EMV DYN]title[EMV /DYN]</h2>
                    </td>
                </tr>
<!-- END HEADER --><!-- BEGIN CONTENT --><tr>
<td id="content" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; box-sizing: border-box; padding-right: 45px; padding-left: 45px; background: #fff; padding-bottom: 18px;">
<p style="margin-top: 18px; margin-bottom: 18px; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #302a32; font-size: 16px; line-height: 27px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">Bonjour [EMV DYN]firstName[EMV /DYN],</p>
<p style="margin-top: 18px; margin-bottom: 18px; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #302a32; font-size: 16px; line-height: 27px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">Le financement du dossier <a href="[EMV DYN]projectUrl[EMV /DYN]" style="color: #2bc9af; font-weight: normal; text-decoration: none; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;">[EMV DYN]projectName[EMV /DYN]</a> est terminé. D’ici peu, vous recevrez la liste des documents à signer électroniquement.</p>
</td>
</tr>
<!-- END CONTENT --><!-- BEGIN FOOTER --><tr>
<td id="under" align="center" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; box-sizing: border-box; padding-right: 45px; padding-left: 45px;">
        <p class="c-t3" style="margin-top: 18px; margin-bottom: 18px; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; color: #9f9d9f; font-size: 16px; line-height: 24px; text-align: center;">
            Crédit Agricole Lending Services
        </p>
    </td>
</tr>
<!-- END FOOTER -->
</table>
<!-- END TEMPLATE -->
</td>
</tr></table>
</body>
</html>'
WHERE id_mail_template = 9
UPDATEMAIL
        );
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP TABLE project_attachment_signature');
        $this->addSql('ALTER TABLE project_attachment RENAME INDEX idx_61f9a289f12e799e TO id_project');
        $this->addSql('ALTER TABLE project_attachment RENAME INDEX idx_61f9a289dcd5596c TO id_attachment');

        $this->addSql('DELETE FROM translations WHERE section = "mail-title" AND name = "document-signature"');
        $this->addSql('DELETE FROM mail_templates WHERE id_mail_template = 10');
    }
}
