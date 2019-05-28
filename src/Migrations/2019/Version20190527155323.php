<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190527155323 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-167 Forgotten password email';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'TRANSLATIONS'
INSERT IGNORE INTO translations (locale, section, name, translation, added) VALUES 
  ('fr_FR', 'password-forgotten', 'invalid-email-format-error-message', 'Le format de l’adresse email est invalide', NOW()),
  ('fr_FR', 'password-forgotten', 'invalid-security-token-error-message', 'Jeton de sécurité invalide', NOW()),
  ('fr_FR', 'reset-password', 'page-title', 'Réinitialisation de votre mot de passe', NOW()),
  ('fr_FR', 'reset-password', 'submit-button', 'Réinitialiser le mot de passe', NOW()),
  ('fr_FR', 'reset-password', 'invalid-link-error-message', 'Lien d’initialisation de mot de passe invalide ou expiré.', NOW()),
  ('fr_FR', 'reset-password', 'reset-success-message', 'Votre mot de passe a été modifié avec succès.', NOW()),
  ('fr_FR', 'password-init', 'page-title', 'Initialisation de votre mot de passe', NOW()),
  ('fr_FR', 'password-init', 'form-submit-button', 'Initialiser le mot de passe', NOW()),
  ('fr_FR', 'password-init', 'invalid-link-error-message', 'Lien de réinitialisation de mot de passe invalide ou expiré.', NOW()),
  ('fr_FR', 'common-validator', 'password-invalid', 'Mot de passe invalide.', NOW()),
  ('fr_FR', 'common-validator', 'password-not-equal', 'La confirmation du mot de passe et le mot de passe ne correspondent pas.', NOW()),
  ('fr_FR', 'common-validator', 'secret-question-invalid', 'Question secrète invalide.', NOW()),
  ('fr_FR', 'common-validator', 'secret-answer-invalid', 'Réponse secrète invalide.', NOW()),
  ('fr_FR', 'common-validator', 'missing-new-password', 'Vous devez saisir un nouveau mot de passe.', NOW()),
  ('fr_FR', 'common-validator', 'missing-new-password-confirmation', 'Vous devez saisir la confirmation du nouveau mot de passe.', NOW())
TRANSLATIONS
        );

        $this->addSql("INSERT INTO translations (locale, section, name, translation, added) VALUES ('fr_FR', 'mail-title', 'forgotten-password', 'Mot de passe oublié', NOW())");
        $this->addSql(
            <<<'MAIL'
INSERT INTO mail_templates (id_mail_template, type, locale, part, recipient_type, sender_name, sender_email, subject, content, compiled_content, id_header, id_footer, status, added)
VALUES (11, 'forgotten-password', 'fr_FR', 'content', 'external', 'Crédit Agricole Lending Services', 'contact@ca-lendingservices.com', 'Votre mot de passe', '<p>Bonjour [EMV DYN]firstName[EMV /DYN],</p>
<p>Vous avez demandé à réinitialiser votre mot de passe pour le compte [EMV DYN]email[EMV /DYN].</p>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="cta" align="center">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="[EMV DYN]passwordLink[EMV /DYN]" style="height:50px;v-text-anchor:middle;width:270px;" arcsize="5%" strokecolor="#2bc9af" fillcolor="#2bc9af">
                <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:18px;">Réinitialiser le mot de passe</center>
            </v:roundrect>
            <![endif]-->
            <a href="[EMV DYN]passwordLink[EMV /DYN]" class="btn-primary">Réinitialiser le mot de passe</a>
        </td>
    </tr>
</table>', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
<body style="height: 100%; margin: 0; padding: 0; width: 100%; background: #f8f6f8; color: #302a32; font-family: \'\'Cabin\'\',Trebuchet MS,Helvetica,sans-serif; font-size: 16px; line-height: 27px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
<table id="body" align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0; mso-table-rspace: 0; height: 100%; margin: 0; padding: 0; width: 100%; background: #f8f6f8; color: #302a32; font-family: \'\'Cabin\'\',Trebuchet MS,Helvetica,sans-serif; font-size: 16px; line-height: 27px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;"><tr>
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
<p style="margin-top: 18px; margin-bottom: 18px; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #302a32; font-size: 16px; line-height: 27px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">Vous avez demandé à réinitialiser votre mot de passe pour le compte [EMV DYN]email[EMV /DYN].</p>

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0; mso-table-rspace: 0;"><tr>
<td class="cta" align="center" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; text-align: center; padding-top: 18px; padding-bottom: 18px;">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="[EMV DYN]passwordLink[EMV /DYN]" style="height:50px;v-text-anchor:middle;width:270px;" arcsize="5%" strokecolor="#2bc9af" fillcolor="#2bc9af">
                <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:18px;">R&eacute;initialiser le mot de passe</center>
            </v:roundrect>
            <![endif]-->
            <a href="[EMV DYN]passwordLink[EMV /DYN]" class="btn-primary" style="font-weight: normal; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; mso-hide: all; display: inline-block; border-radius: 4px; border-width: 2px; border-style: solid; min-width: 120px; padding-left: 30px; padding-right: 30px; padding-top: 11px; padding-bottom: 11px; font-size: 18px; text-decoration: none; border-color: #2bc9af; background: #2bc9af; color: #fff;">Réinitialiser le mot de passe</a>
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
MAIL
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translations WHERE section IN ("password-forgotten", "reset-password", "password-init", "common-validator")');
        $this->addSql('DELETE FROM translations WHERE section = "mail-title" AND name = "forgotten-password"');
        $this->addSql('DELETE FROM mail_templates WHERE type = "forgotten-password"');
    }
}
