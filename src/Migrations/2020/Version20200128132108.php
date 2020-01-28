<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200128132108 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-817 Update layout';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
UPDATE mail_header SET content = '
  <mj-column><mj-image align="left" width="60px" src="{{ url("front_image", {imageFileName: "KLS.png"}) }}"/></mj-column>
  <mj-column>
      <mj-text align="right" color="#ffffff" font-size="10px" font-family="Arial" line-height="50px">
          FINANCER MIEUX, FINANCER ENSEMBLE
      </mj-text>
  </mj-column>
' WHERE name = 'header'
SQL
        );
        $this->addSql(
            <<<'SQL'
UPDATE mail_footer SET content = '
  <mj-column>
      <mj-text font-weight="100" font-size="11px" line-height="1.5" align="center" padding-top="40px" color="#3F2865">
          Copyright © 2020 KLS, tous droits réservés.<br/>
          Où nous trouver : 50 rue la Boétie, 75008 Paris, France<br/>
          <a href="{{ url("front_home") }}" style="color:#3F2865;font-size:11px;">Gérer vos préférences</a> | <a href="{{ url("front_home") }}" style="color:#3F2865;font-size:11px;">Vous
              désabonner</a>
      </mj-text>
  </mj-column>
' WHERE name = 'footer'
SQL
        );
        $this->addSql(
            <<<'SQL'
    UPDATE mail_layout SET content = '
      <mjml>
        <mj-head>
            <mj-style>
                a { color: #3F2865; font-weight: 600; text-decoration: none; }
            </mj-style>
        </mj-head>
        <mj-body>
            <mj-section background-color="#3F2865" padding="0">
                {% block header %}
                    <mj-column><mj-image align="left" width="60px" src="{{ url("front_image", {imageFileName: "KLS.png"}) }}"/></mj-column>
                    <mj-column>
                        <mj-text align="right" color="#ffffff" font-size="10px" font-family="Arial" line-height="50px">
                            FINANCER MIEUX, FINANCER ENSEMBLE
                        </mj-text>
                    </mj-column>
                {% endblock %}
            </mj-section>
            <mj-section padding="0">
                <mj-column width="560px" background-color="#F5F4F7" padding="0 40px 30px">
                    {% block body %}{% endblock %}
                </mj-column>
            </mj-section>
            <mj-section>
                {% block footer %}
                    <mj-column>
                        <mj-text font-weight="100" font-size="11px" line-height="1.5" align="center" padding-top="40px" color="#3F2865">
                            Copyright © 2020 KLS, tous droits réservés.<br/>
                            Où nous trouver : 50 rue la Boétie, 75008 Paris, France<br/>
                            <a href="{{ url("front_home") }}" style="color:#3F2865;font-size:11px;">Gérer vos préférences</a>
                          | <a href="{{ url("front_home") }}" style="color:#3F2865;font-size:11px;">Vous désabonner</a>
                        </mj-text>
                    </mj-column>
                {% endblock %}
            </mj-section>
        </mj-body>
    </mjml>
' WHERE name = 'layout'
SQL
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
    UPDATE mail_header SET content = '<p>
    <img src="{{ asset("images/logo/logo-and-type-245x52@2x.png") }}" alt="Crédit Agricole Lending Services" width="209" height="44">
</p>
<h2>{{ title|default() }}</h2>' WHERE name = 'header'
SQL
        );
        $this->addSql(
            <<<'SQL'
    UPDATE mail_footer SET content = '<p class="c-t3">Crédit Agricole Lending Services</p>' WHERE name = 'footer'
SQL
        );
        $this->addSql(
            <<<'SQL'
    UPDATE mail_layout SET content = '
1	layout	fr_FR	"<!DOCTYPE html PUBLIC ""-//W3C//DTD XHTML 1.0 Transitional//EN"" ""http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"">
<html xmlns=""http://www.w3.org/1999/xhtml"" lang=""fr"" xmlns:v=""urn:schemas-microsoft-com:vml"" xmlns:o=""urn:schemas-microsoft-com:office:office"">
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
    <meta charset=""UTF-8"">
    <meta http-equiv=""x-ua-compatible"" content=""IE=edge"">
    <meta name=""viewport"" content=""width=device-width, initial-scale=1"">
    <link rel=""stylesheet"" type=""text/css"" href=""https://fonts.googleapis.com/css?family=Cabin:400,700,400"">
    <!--[if mso]>
    <style>
        body,#body,table,td {
            font-family:Arial,Helvetica,sans-serif !important;
        }
    </style>
    <![endif]-->
    <style type=""text/css"">
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
<table id=""body"" align=""center"" border=""0"" cellpadding=""0"" cellspacing=""0"" width=""100%"">
    <tr>
        <td align=""center"" valign=""top"">
            <!-- BEGIN TEMPLATE -->
            <table id=""container"" border=""0"" cellpadding=""0"" cellspacing=""0"" width=""600"">
                <!-- BEGIN HEADER -->
                <tr>
                    <td id=""header"" valign=""top"">
                        {% block header %} {% endblock %}
                    </td>
                </tr>
                <!-- END HEADER -->
                <!-- BEGIN CONTENT -->
                <tr>
                    <td id=""content"">
                        {% block body %} {% endblock %}
                    </td>
                </tr>
<!-- END CONTENT -->
<!-- BEGIN FOOTER -->
<tr>
    <td id=""under"" align=""center"">
        {% block footer %} {% endblock %}
    </td>
</tr>
<!-- END FOOTER -->
</table>
<!-- END TEMPLATE -->
</td>
</tr>
</table>
</body>
</html>"		2019-11-05 14:46:55	2019-11-05 14:46:55
' WHERE name = 'layout'
SQL
        );
    }
}
