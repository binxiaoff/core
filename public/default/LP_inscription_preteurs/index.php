<?php $url_site = 'https://' . $_SERVER['HTTP_HOST']; ?>
<!DOCTYPE html>
<!--[if lt IE 7]>
<html class="lt-ie9 lt-ie8 lt-ie7" lang="fr"> <![endif]-->
<!--[if IE 7]>
<html class="lt-ie9 lt-ie8" lang="fr"> <![endif]-->
<!--[if IE 8]>
<html class="lt-ie9" lang="fr"> <![endif]-->
<!--[if gt IE 8]><!-->
<html lang="fr"> <!--<![endif]-->
<head>
    <title>Unilend : les particuliers pr&ecirc;tent aux entreprises fran&ccedil;aises</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <meta name="Author" content="dynamic creative - Agence cr&eacute;ative pas NET, mais WEB &eacute;norm&eacute;ment"/>
    <meta name="description" content="Sur Unilend, tout le monde peut pr&ecirc;ter aux entreprises fran&ccedil;aises et recevoir des int&eacute;r&ecirc;ts."/>
    <meta name="keywords" content="Financement entreprise, pr&ecirc;t &agrave; des entreprises, investissement direct, peer-to-peer lending, crowdfunding"/>
    <meta name="viewport" content="initial-scale = 1.0,maximum-scale = 1.0"/>
    <meta name="apple-mobile-web-app-capable" content="yes">

    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
    <link href="css/font.css" type="text/css" rel="stylesheet" media="all">
    <link href="css/base.css" type="text/css" rel="stylesheet" media="all">
    <link href="css/global.css" type="text/css" rel="stylesheet" media="all">
    <link href="css/responsive.css" type="text/css" rel="stylesheet" media="all">
    <link href="css/jquery.c2selectbox.css" type="text/css" rel="stylesheet" media="all"/>
    <link href="css/bootstrap.css" type="text/css" rel="stylesheet" media="all"/>
    <link rel="stylesheet" href="css/jquery.nouislider.css"/>

    <!--[if IE]><script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
</head>
<body>
<!-- Google Tag Manager -->
<noscript><iframe src="//www.googletagmanager.com/ns.html?id=GTM-MB66VL"
                  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        '//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window, document, 'script', 'dataLayer', 'GTM-MB66VL');</script>
<!-- End Google Tag Manager -->
<?php

if (! empty($_GET["utm_source"])) {
    $source = $_GET["utm_source"];
} else {
    $source = "";
}
if (! empty($_GET["utm_source2"])) {
    $source2 = $_GET["utm_source2"];
} else {
    $source2 = "";
}
if (! empty($_GET["nom"])) {
    $nom = $_GET["nom"];
} else {
    $nom = "";
}
if (! empty($_GET["prenom"])) {
    $prenom = $_GET["prenom"];
} else {
    $prenom = "";
}
if (! empty($_GET["email"])) {
    $email = $_GET["email"];
} else {
    $email = "";
}
if (! empty($_GET["civilite"])) {
    $civilite = $_GET["civilite"];
} else {
    $civilite = "";
}

$slug_origine = 'LP_inscription_preteurs';
$page         = (isset($_GET['page']) && $_GET['page'] == 'lexpress' ? $_GET['page'] : '');

if ($page == 'lexpress') {
    $slug_origine = 'LP_inscription_preteurs_lexpress'
    ?>
    <style type="text/css">
        #chiffres, #presse{display:none;}
    </style>
    <?php
}
?>
<div id="form">
    <section class="wrapper">
        <form action="#" method="post" id="form_inscription" class="etape1" novalidate>
            <div id="form_header">
                <h1>Inscrivez-vous</h1>
                <h2>Et d&eacute;couvrez Unilend</h2>
            </div>
            <div class="form_content etape1">
                <select name="civilite" id="inscription_civilite" class="custom-select">
                        <option value=""><?php if ($civilite == "Mme") { echo "Madame"; } elseif ($civilite == "M.") { echo "Monsieur"; } else { echo "Civilit&eacute;"; } ?></option>
                        <option <?php if ($civilite == "M.") { echo "selected"; } ?> value="M.">Monsieur</option>
                        <option <?php if ($civilite == "Mme") { echo "selected"; } ?> value="Mme">Madame</option>
                </select>
                <input type="text" id="inscription_nom" name="nom" placeholder="Nom" maxlength="255" value="<?php echo $nom; ?>">
                <input type="text" id="inscription_prenom" name="prenom" placeholder="Pr&eacute;nom" maxlength="255" value="<?php echo $prenom; ?>">
                <input type="email" id="inscription_email" name="email" placeholder="E-mail" maxlength="255" value="<?php echo $email; ?>">
                <button type="submit" id="inscription_submit" name="valider">S'inscrire</button>
            </div>
            <div class="form_content etape2">
                <input type="password" id="inscription_mdp" name="password" placeholder="Choisissez un mot de passe" maxlength="255">
                <input type="password" id="inscription_mdp2" name="inscription_mdp2" placeholder="Confirmez votre mot de passe" maxlength="255">
                <p>Votre mot de passe doit au moins contenir 6 caract&egrave;res dont une majuscule et une minuscule.</p>
                <input type="text" id="inscription_question" name="question" placeholder="Choisissez une question secr&egrave;te" maxlength="255">
                <input type="text" id="inscription_reponse" name="reponse" placeholder="Choisissez une r&eacute;ponse" maxlength="255">
                <input type="text" id="inscription_adresse_fiscale" name="adresse_fiscale" placeholder="Adresse" maxlength="255">
                <input type="text" id="inscription_cp_fiscale" name="cp_fiscale" placeholder="Code postal" maxlength="5" data-autocomplete="post_code">
                <input type="text" id="inscription_ville_fiscale" name="ville_fiscale" placeholder="Ville" maxlength="255" data-autocomplete="city">
                <select id="inscription_id_pays_fiscale" name="id_pays_fiscale" class="custom-select">
                    <option value="">Pays</option>
                    <option value="1">France</option>
                    <option value="2">Afghanistan</option>
                    <option value="3">Afrique du Sud</option>
                    <option value="4">Albanie</option>
                    <option value="5">Alg&eacute;rie</option>
                    <option value="6">Allemagne</option>
                    <option value="7">Andorre</option>
                    <option value="8">Angola</option>
                    <option value="9">Antigua-et-Barbuda</option>
                    <option value="10">Arabie saoudite</option>
                    <option value="11">Argentine</option>
                    <option value="12">Arm&eacute;nie</option>
                    <option value="13">Australie</option>
                    <option value="14">Autriche</option>
                    <option value="15">Azerba&iuml;djan</option>
                    <option value="16">Bahamas</option>
                    <option value="17">Bahre&iuml;n</option>
                    <option value="18">Bangladesh</option>
                    <option value="19">Barbade</option>
                    <option value="20">Bi&eacute;lorussie</option>
                    <option value="21">Belgique</option>
                    <option value="22">Belize</option>
                    <option value="23">B&eacute;nin</option>
                    <option value="24">Bhoutan</option>
                    <option value="25">Birmanie</option>
                    <option value="26">Bolivie</option>
                    <option value="27">Bosnie-Herz&eacute;govine</option>
                    <option value="28">Botswana</option>
                    <option value="29">Br&eacute;sil</option>
                    <option value="30">Brunei</option>
                    <option value="31">Bulgarie</option>
                    <option value="32">Burkina Faso</option>
                    <option value="33">Burundi</option>
                    <option value="34">Cambodge</option>
                    <option value="35">Cameroun</option>
                    <option value="36">Canada</option>
                    <option value="37">Cap-Vert</option>
                    <option value="38">R&eacute;publique centrafricaine</option>
                    <option value="39">Chili</option>
                    <option value="40">Chine</option>
                    <option value="41">Chypre</option>
                    <option value="42">Colombie</option>
                    <option value="43">Comores</option>
                    <option value="44">R&eacute;publique du Congo</option>
                    <option value="45">R&eacute;publique d&eacute;mocratique du Congo</option>
                    <option value="46">Cor&eacute;e du Nord</option>
                    <option value="47">Cor&eacute;e du Sud</option>
                    <option value="48">Costa Rica</option>
                    <option value="49">C&ocirc;te d'Ivoire</option>
                    <option value="50">Croatie Croatie</option>
                    <option value="51">Cuba</option>
                    <option value="52">Danemark</option>
                    <option value="53">Djibouti</option>
                    <option value="54">R&eacute;publique dominicaine</option>
                    <option value="55">Dominique</option>
                    <option value="56">&eacute;gypte</option>
                    <option value="57">&eacute;mirats arabes unis</option>
                    <option value="58">&eacute;quateur</option>
                    <option value="59">&eacute;rythr&eacute;e</option>
                    <option value="60">Espagne</option>
                    <option value="61">Estonie</option>
                    <option value="62">&eacute;tats-Unis</option>
                    <option value="63">&eacute;thiopie</option>
                    <option value="64">Fidji</option>
                    <option value="65">Finlande</option>
                    <option value="66">Gabon</option>
                    <option value="67">Gambie</option>
                    <option value="68">G&eacute;orgie</option>
                    <option value="69">Ghana</option>
                    <option value="70">Gr&egrave;ce</option>
                    <option value="71">Grenade</option>
                    <option value="72">Guatemala</option>
                    <option value="73">Guin&eacute;e</option>
                    <option value="74">Guin&eacute;e-Bissau</option>
                    <option value="75">Guin&eacute;e</option>
                    <option value="76">Guyana</option>
                    <option value="77">Ha&iuml;ti</option>
                    <option value="78">Honduras</option>
                    <option value="79">Hongrie</option>
                    <option value="80">Inde</option>
                    <option value="81">Indon&eacute;sie</option>
                    <option value="82">Irak</option>
                    <option value="83">Iran</option>
                    <option value="84">Irlande</option>
                    <option value="85">Islande</option>
                    <option value="86">Isra&euml;l</option>
                    <option value="87">Italie</option>
                    <option value="88">Jama&iuml;que</option>
                    <option value="89">Japon</option>
                    <option value="90">Jordanie</option>
                    <option value="91">Kazakhstan</option>
                    <option value="92">Kenya</option>
                    <option value="93">Kirghizistan</option>
                    <option value="94">Kiribati</option>
                    <option value="95">Kowe&iuml;t</option>
                    <option value="96">Laos</option>
                    <option value="97">Lesotho</option>
                    <option value="98">Lettonie</option>
                    <option value="99">Liban</option>
                    <option value="100">Liberia</option>
                    <option value="101">Libye</option>
                    <option value="102">Liechtenstein</option>
                    <option value="103">Lituanie</option>
                    <option value="104">Luxembourg</option>
                    <option value="105">Mac&eacute;doine</option>
                    <option value="106">Madagascar</option>
                    <option value="107">Malaisie</option>
                    <option value="108">Malawi</option>
                    <option value="109">Maldives</option>
                    <option value="110">Mali</option>
                    <option value="111">Malte</option>
                    <option value="112">Maroc</option>
                    <option value="113">&Icirc;les Marshall</option>
                    <option value="114">Maurice</option>
                    <option value="115">Mauritanie</option>
                    <option value="116">Mexique</option>
                    <option value="117">Micron&eacute;sie</option>
                    <option value="118">Moldavie</option>
                    <option value="119">Monaco</option>
                    <option value="120">Mongolie</option>
                    <option value="121">Mont&eacute;n&eacute;gro</option>
                    <option value="122">Mozambique</option>
                    <option value="123">Namibie</option>
                    <option value="124">Nauru</option>
                    <option value="125">N&eacute;pal</option>
                    <option value="126">Nicaragua</option>
                    <option value="127">Niger</option>
                    <option value="128">Nigeria</option>
                    <option value="129">Norv&egrave;ge</option>
                    <option value="130">Nouvelle-Z&eacute;lande</option>
                    <option value="131">Oman</option>
                    <option value="132">Ouganda</option>
                    <option value="133">Ouzb&eacute;kistan</option>
                    <option value="134">Pakistan</option>
                    <option value="135">Palaos</option>
                    <option value="136">Panama</option>
                    <option value="137">Papouasie-Nouvelle-Guin&eacute;e</option>
                    <option value="138">Paraguay</option>
                    <option value="139">Pays-Bas</option>
                    <option value="140">P&eacute;rou</option>
                    <option value="141">Philippines</option>
                    <option value="142">Pologne</option>
                    <option value="195">Polynésie française</option>
                    <option value="143">Portugal</option>
                    <option value="144">Qatar</option>
                    <option value="145">Russie</option>
                    <option value="146">Salvador</option>
                    <option value="147">Syrie</option>
                    <option value="148">R&eacute;publique tch&egrave;que</option>
                    <option value="149">Tanzanie</option>
                    <option value="150">Roumanie</option>
                    <option value="151">Royaume-Uni</option>
                    <option value="152">Rwanda</option>
                    <option value="153">Sainte-Lucie</option>
                    <option value="154">Saint-Christophe-et-Ni&eacute;v&egrave;s</option>
                    <option value="155">Saint-Marin</option>
                    <option value="156">Saint-Vincent-et-les Grenadines</option>
                    <option value="157">Salomon</option>
                    <option value="158">Samoa</option>
                    <option value="159">Tom&eacute;-et-Principe</option>
                    <option value="160">S&eacute;n&eacute;gal</option>
                    <option value="161">Serbie</option>
                    <option value="162">Seychelles</option>
                    <option value="163">Sierra Leone</option>
                    <option value="164">Singapour</option>
                    <option value="165">Slovaquie</option>
                    <option value="166">Slov&eacute;nie</option>
                    <option value="167">Somalie</option>
                    <option value="168">Soudan</option>
                    <option value="169">Soudan du Sud</option>
                    <option value="170">Sri Lanka</option>
                    <option value="171">Su&egrave;de</option>
                    <option value="172">Suisse</option>
                    <option value="173">Suriname</option>
                    <option value="174">Swaziland</option>
                    <option value="175">Tadjikistan</option>
                    <option value="176">Tchad</option>
                    <option value="177">Tha&iuml;lande</option>
                    <option value="178">Timor oriental</option>
                    <option value="179">Togo</option>
                    <option value="180">Tonga</option>
                    <option value="181">Trinit&eacute;-et-Tobago</option>
                    <option value="182">Tunisie</option>
                    <option value="183">Turkm&eacute;nistan</option>
                    <option value="184">Turquie</option>
                    <option value="185">Tuvalu</option>
                    <option value="186">Ukraine</option>
                    <option value="187">Uruguay</option>
                    <option value="188">Vanuatu</option>
                    <option value="189">Venezuela</option>
                    <option value="190">Vi&ecirc;t Nam</option>
                    <option value="191">Y&eacute;men</option>
                    <option value="192">Zambie</option>
                    <option value="193">Zimbabwe</option>
                </select>
                <div class="cb-holder checked">
                    <label for="inscription_check_adresse">Mon adresse de correspondance est la m&ecirc;me que mon adresse fiscale. Sinon, d&eacute;cochez la case et indiquez votre adresse de correspondance.</label>
                    <input checked="checked" type="checkbox" class="custom-chekckbox" name="inscription_check_adresse" id="inscription_check_adresse">
                </div>
                <div id="inscription_correspondance">
                    <input type="text" id="inscription_adresse_correspondance" name="adresse" placeholder="Adresse" maxlength="255">
                    <input type="text" id="inscription_cp_correspondance" name="cp" placeholder="Code postal" maxlength="5" data-autocomplete="post_code">
                    <input type="text" id="inscription_ville_correspondance" name="ville" placeholder="Ville" maxlength="255" data-autocomplete="city">
                    <select id="inscription_id_pays_correspondance" name="id_pays" class="custom-select">
                        <option value="">Pays</option>
                        <option value="1">France</option>
                        <option value="2">Afghanistan</option>
                        <option value="3">Afrique du Sud</option>
                        <option value="4">Albanie</option>
                        <option value="5">Alg&eacute;rie</option>
                        <option value="6">Allemagne</option>
                        <option value="7">Andorre</option>
                        <option value="8">Angola</option>
                        <option value="9">Antigua-et-Barbuda</option>
                        <option value="10">Arabie saoudite</option>
                        <option value="11">Argentine</option>
                        <option value="12">Arm&eacute;nie</option>
                        <option value="13">Australie</option>
                        <option value="14">Autriche</option>
                        <option value="15">Azerba&iuml;djan</option>
                        <option value="16">Bahamas</option>
                        <option value="17">Bahre&iuml;n</option>
                        <option value="18">Bangladesh</option>
                        <option value="19">Barbade</option>
                        <option value="20">Bi&eacute;lorussie</option>
                        <option value="21">Belgique</option>
                        <option value="22">Belize</option>
                        <option value="23">B&eacute;nin</option>
                        <option value="24">Bhoutan</option>
                        <option value="25">Birmanie</option>
                        <option value="26">Bolivie</option>
                        <option value="27">Bosnie-Herz&eacute;govine</option>
                        <option value="28">Botswana</option>
                        <option value="29">Br&eacute;sil</option>
                        <option value="30">Brunei</option>
                        <option value="31">Bulgarie</option>
                        <option value="32">Burkina Faso</option>
                        <option value="33">Burundi</option>
                        <option value="34">Cambodge</option>
                        <option value="35">Cameroun</option>
                        <option value="36">Canada</option>
                        <option value="37">Cap-Vert</option>
                        <option value="38">R&eacute;publique centrafricaine</option>
                        <option value="39">Chili</option>
                        <option value="40">Chine</option>
                        <option value="41">Chypre</option>
                        <option value="42">Colombie</option>
                        <option value="43">Comores</option>
                        <option value="44">R&eacute;publique du Congo</option>
                        <option value="45">R&eacute;publique d&eacute;mocratique du Congo</option>
                        <option value="46">Cor&eacute;e du Nord</option>
                        <option value="47">Cor&eacute;e du Sud</option>
                        <option value="48">Costa Rica</option>
                        <option value="49">C&ocirc;te d Ivoire</option>
                        <option value="50">Croatie Croatie</option>
                        <option value="51">Cuba</option>
                        <option value="52">Danemark</option>
                        <option value="53">Djibouti</option>
                        <option value="54">R&eacute;publique dominicaine</option>
                        <option value="55">Dominique</option>
                        <option value="56">&eacute;gypte</option>
                        <option value="57">&eacute;mirats arabes unis</option>
                        <option value="58">&eacute;quateur</option>
                        <option value="59">&eacute;rythr&eacute;e</option>
                        <option value="60">Espagne</option>
                        <option value="61">Estonie</option>
                        <option value="62">&eacute;tats-Unis</option>
                        <option value="63">&eacute;thiopie</option>
                        <option value="64">Fidji</option>
                        <option value="65">Finlande</option>
                        <option value="66">Gabon</option>
                        <option value="67">Gambie</option>
                        <option value="68">G&eacute;orgie</option>
                        <option value="69">Ghana</option>
                        <option value="70">Gr&egrave;ce</option>
                        <option value="71">Grenade</option>
                        <option value="72">Guatemala</option>
                        <option value="73">Guin&eacute;e</option>
                        <option value="74">Guin&eacute;e-Bissau</option>
                        <option value="75">Guin&eacute;e</option>
                        <option value="76">Guyana</option>
                        <option value="77">Ha&iuml;ti</option>
                        <option value="78">Honduras</option>
                        <option value="79">Hongrie</option>
                        <option value="80">Inde</option>
                        <option value="81">Indon&eacute;sie</option>
                        <option value="82">Irak</option>
                        <option value="83">Iran</option>
                        <option value="84">Irlande</option>
                        <option value="85">Islande</option>
                        <option value="86">Isra&euml;l</option>
                        <option value="87">Italie</option>
                        <option value="88">Jama&iuml;que</option>
                        <option value="89">Japon</option>
                        <option value="90">Jordanie</option>
                        <option value="91">Kazakhstan</option>
                        <option value="92">Kenya</option>
                        <option value="93">Kirghizistan</option>
                        <option value="94">Kiribati</option>
                        <option value="95">Kowe&iuml;t</option>
                        <option value="96">Laos</option>
                        <option value="97">Lesotho</option>
                        <option value="98">Lettonie</option>
                        <option value="99">Liban</option>
                        <option value="100">Liberia</option>
                        <option value="101">Libye</option>
                        <option value="102">Liechtenstein</option>
                        <option value="103">Lituanie</option>
                        <option value="104">Luxembourg</option>
                        <option value="105">Mac&eacute;doine</option>
                        <option value="106">Madagascar</option>
                        <option value="107">Malaisie</option>
                        <option value="108">Malawi</option>
                        <option value="109">Maldives</option>
                        <option value="110">Mali</option>
                        <option value="111">Malte</option>
                        <option value="112">Maroc</option>
                        <option value="113">&Icirc;les Marshall</option>
                        <option value="114">Maurice</option>
                        <option value="115">Mauritanie</option>
                        <option value="116">Mexique</option>
                        <option value="117">Micron&eacute;sie</option>
                        <option value="118">Moldavie</option>
                        <option value="119">Monaco</option>
                        <option value="120">Mongolie</option>
                        <option value="121">Mont&eacute;n&eacute;gro</option>
                        <option value="122">Mozambique</option>
                        <option value="123">Namibie</option>
                        <option value="124">Nauru</option>
                        <option value="125">N&eacute;pal</option>
                        <option value="126">Nicaragua</option>
                        <option value="127">Niger</option>
                        <option value="128">Nigeria</option>
                        <option value="129">Norv&egrave;ge</option>
                        <option value="130">Nouvelle-Z&eacute;lande</option>
                        <option value="131">Oman</option>
                        <option value="132">Ouganda</option>
                        <option value="133">Ouzb&eacute;kistan</option>
                        <option value="134">Pakistan</option>
                        <option value="135">Palaos</option>
                        <option value="136">Panama</option>
                        <option value="137">Papouasie-Nouvelle-Guin&eacute;e</option>
                        <option value="138">Paraguay</option>
                        <option value="139">Pays-Bas</option>
                        <option value="140">P&eacute;rou</option>
                        <option value="141">Philippines</option>
                        <option value="142">Pologne</option>
                        <option value="195">Polynésie française</option>
                        <option value="143">Portugal</option>
                        <option value="144">Qatar</option>
                        <option value="145">Russie</option>
                        <option value="146">Salvador</option>
                        <option value="147">Syrie</option>
                        <option value="148">R&eacute;publique tch&egrave;que</option>
                        <option value="149">Tanzanie</option>
                        <option value="150">Roumanie</option>
                        <option value="151">Royaume-Uni</option>
                        <option value="152">Rwanda</option>
                        <option value="153">Sainte-Lucie</option>
                        <option value="154">Saint-Christophe-et-Ni&eacute;v&egrave;s</option>
                        <option value="155">Saint-Marin</option>
                        <option value="156">Saint-Vincent-et-les Grenadines</option>
                        <option value="157">Salomon</option>
                        <option value="158">Samoa</option>
                        <option value="159">Tom&eacute;-et-Principe</option>
                        <option value="160">S&eacute;n&eacute;gal</option>
                        <option value="161">Serbie</option>
                        <option value="162">Seychelles</option>
                        <option value="163">Sierra Leone</option>
                        <option value="164">Singapour</option>
                        <option value="165">Slovaquie</option>
                        <option value="166">Slov&eacute;nie</option>
                        <option value="167">Somalie</option>
                        <option value="168">Soudan</option>
                        <option value="169">Soudan du Sud</option>
                        <option value="170">Sri Lanka</option>
                        <option value="171">Su&egrave;de</option>
                        <option value="172">Suisse</option>
                        <option value="173">Suriname</option>
                        <option value="174">Swaziland</option>
                        <option value="175">Tadjikistan</option>
                        <option value="176">Tchad</option>
                        <option value="177">Tha&iuml;lande</option>
                        <option value="178">Timor oriental</option>
                        <option value="179">Togo</option>
                        <option value="180">Tonga</option>
                        <option value="181">Trinit&eacute;-et-Tobago</option>
                        <option value="182">Tunisie</option>
                        <option value="183">Turkm&eacute;nistan</option>
                        <option value="184">Turquie</option>
                        <option value="185">Tuvalu</option>
                        <option value="186">Ukraine</option>
                        <option value="187">Uruguay</option>
                        <option value="188">Vanuatu</option>
                        <option value="189">Venezuela</option>
                        <option value="190">Vi&ecirc;t Nam</option>
                        <option value="191">Y&eacute;men</option>
                        <option value="192">Zambie</option>
                        <option value="193">Zimbabwe</option>
                    </select>
                    <div class="clear"></div>
                </div>
                <input type="tel" id="inscription_telephone" name="telephone" placeholder="T&eacute;l&eacute;phone" maxlength="10">
                <select id="inscription_id_nationalite" name="id_nationalite" class="custom-select">
                    <option value="">Nationalit&eacute;</option>
                    <option value="35">Autre</option>
                    <option value="1">France</option>
                    <option value="2">Allemagne</option>
                    <option value="3">Autriche</option>
                    <option value="4">Belgique</option>
                    <option value="5">Bulgarie</option>
                    <option value="6">Chypre</option>
                    <option value="7">Croatie</option>
                    <option value="8">Danemark</option>
                    <option value="9">Espagne</option>
                    <option value="10">Estonie</option>
                    <option value="11">Finlande</option>
                    <option value="12">Gr&egrave;ce</option>
                    <option value="13">Hongrie</option>
                    <option value="14">Irlande</option>
                    <option value="15">Islande</option>
                    <option value="16">Italie</option>
                    <option value="17">Lettonie</option>
                    <option value="18">Liechtenstein</option>
                    <option value="19">Lithuanie</option>
                    <option value="20">Luxembourg</option>
                    <option value="21">Malte</option>
                    <option value="22">Monaco</option>
                    <option value="23">Norv&egrave;ge</option>
                    <option value="24">Pays-Bas</option>
                    <option value="25">Pologne</option>
                    <option value="195">Polynésie française</option>
                    <option value="26">Portugal</option>
                    <option value="27">R&eacute;publique tch&egrave;que</option>
                    <option value="28">Roumanie</option>
                    <option value="29">Royaume-Uni</option>
                    <option value="30">Saint-Marin</option>
                    <option value="31">Slovaquie</option>
                    <option value="32">Slov&eacute;nie</option>
                    <option value="33">Su&egrave;de</option>
                    <option value="34">Suisse</option>
                </select>
                <div class="clear"></div>
                <input type="text" id="inscription_date_naissance" name="date_naissance" placeholder="Date de naissance (jj/mm/aaaa)" maxlength="10">
                <p id="errorAge"></p>
                <input type="text" id="inscription_commune_naissance" name="commune_naissance" placeholder="Commune de naissance*" maxlength="255" data-autocomplete="birth_city"/>
                <input type="hidden" name="insee_birth" id="insee_birth">
                <select id="inscription_id_pays_naissance" name="id_pays_naissance" class="custom-select">
                    <option value="">Pays de naissance</option>
                    <option value="1">France</option>
                    <option value="2">Afghanistan</option>
                    <option value="3">Afrique du Sud</option>
                    <option value="4">Albanie</option>
                    <option value="5">Alg&eacute;rie</option>
                    <option value="6">Allemagne</option>
                    <option value="7">Andorre</option>
                    <option value="8">Angola</option>
                    <option value="9">Antigua-et-Barbuda</option>
                    <option value="10">Arabie saoudite</option>
                    <option value="11">Argentine</option>
                    <option value="12">Arm&eacute;nie</option>
                    <option value="13">Australie</option>
                    <option value="14">Autriche</option>
                    <option value="15">Azerba&iuml;djan</option>
                    <option value="16">Bahamas</option>
                    <option value="17">Bahre&iuml;n</option>
                    <option value="18">Bangladesh</option>
                    <option value="19">Barbade</option>
                    <option value="20">Bi&eacute;lorussie</option>
                    <option value="21">Belgique</option>
                    <option value="22">Belize</option>
                    <option value="23">B&eacute;nin</option>
                    <option value="24">Bhoutan</option>
                    <option value="25">Birmanie</option>
                    <option value="26">Bolivie</option>
                    <option value="27">Bosnie-Herz&eacute;govine</option>
                    <option value="28">Botswana</option>
                    <option value="29">Br&eacute;sil</option>
                    <option value="30">Brunei</option>
                    <option value="31">Bulgarie</option>
                    <option value="32">Burkina Faso</option>
                    <option value="33">Burundi</option>
                    <option value="34">Cambodge</option>
                    <option value="35">Cameroun</option>
                    <option value="36">Canada</option>
                    <option value="37">Cap-Vert</option>
                    <option value="38">R&eacute;publique centrafricaine</option>
                    <option value="39">Chili</option>
                    <option value="40">Chine</option>
                    <option value="41">Chypre</option>
                    <option value="42">Colombie</option>
                    <option value="43">Comores</option>
                    <option value="44">R&eacute;publique du Congo</option>
                    <option value="45">R&eacute;publique d&eacute;mocratique du Congo</option>
                    <option value="46">Cor&eacute;e du Nord</option>
                    <option value="47">Cor&eacute;e du Sud</option>
                    <option value="48">Rica Costa Rica</option>
                    <option value="49">C&ocirc;te d Ivoire</option>
                    <option value="50">Croatie Croatie</option>
                    <option value="51">Cuba</option>
                    <option value="52">Danemark</option>
                    <option value="53">Djibouti</option>
                    <option value="54">R&eacute;publique dominicaine</option>
                    <option value="55">Dominique</option>
                    <option value="56">&eacute;gypte</option>
                    <option value="57">&eacute;mirats arabes unis</option>
                    <option value="58">&eacute;quateur</option>
                    <option value="59">&eacute;rythr&eacute;e</option>
                    <option value="60">Espagne</option>
                    <option value="61">Estonie</option>
                    <option value="62">&eacute;tats-Unis</option>
                    <option value="63">&eacute;thiopie</option>
                    <option value="64">Fidji</option>
                    <option value="65">Finlande</option>
                    <option value="66">Gabon</option>
                    <option value="67">Gambie</option>
                    <option value="68">G&eacute;orgie</option>
                    <option value="69">Ghana</option>
                    <option value="70">Gr&egrave;ce</option>
                    <option value="71">Grenade</option>
                    <option value="72">Guatemala</option>
                    <option value="73">Guin&eacute;e</option>
                    <option value="74">Guin&eacute;e-Bissau</option>
                    <option value="75">Guin&eacute;e</option>
                    <option value="76">Guyana</option>
                    <option value="77">Ha&iuml;ti</option>
                    <option value="78">Honduras</option>
                    <option value="79">Hongrie</option>
                    <option value="80">Inde</option>
                    <option value="81">Indon&eacute;sie</option>
                    <option value="82">Irak</option>
                    <option value="83">Iran</option>
                    <option value="84">Irlande</option>
                    <option value="85">Islande</option>
                    <option value="86">Isra&euml;l</option>
                    <option value="87">Italie</option>
                    <option value="88">Jama&iuml;que</option>
                    <option value="89">Japon</option>
                    <option value="90">Jordanie</option>
                    <option value="91">Kazakhstan</option>
                    <option value="92">Kenya</option>
                    <option value="93">Kirghizistan</option>
                    <option value="94">Kiribati</option>
                    <option value="95">Kowe&iuml;t</option>
                    <option value="96">Laos</option>
                    <option value="97">Lesotho</option>
                    <option value="98">Lettonie</option>
                    <option value="99">Liban</option>
                    <option value="100">Liberia</option>
                    <option value="101">Libye</option>
                    <option value="102">Liechtenstein</option>
                    <option value="103">Lituanie</option>
                    <option value="104">Luxembourg</option>
                    <option value="105">Mac&eacute;doine</option>
                    <option value="106">Madagascar</option>
                    <option value="107">Malaisie</option>
                    <option value="108">Malawi</option>
                    <option value="109">Maldives</option>
                    <option value="110">Mali</option>
                    <option value="111">Malte</option>
                    <option value="112">Maroc</option>
                    <option value="113">&Icirc;les Marshall</option>
                    <option value="114">Maurice</option>
                    <option value="115">Mauritanie</option>
                    <option value="116">Mexique</option>
                    <option value="117">Micron&eacute;sie</option>
                    <option value="118">Moldavie</option>
                    <option value="119">Monaco</option>
                    <option value="120">Mongolie</option>
                    <option value="121">Mont&eacute;n&eacute;gro</option>
                    <option value="122">Mozambique</option>
                    <option value="123">Namibie</option>
                    <option value="124">Nauru</option>
                    <option value="125">N&eacute;pal</option>
                    <option value="126">Nicaragua</option>
                    <option value="127">Niger</option>
                    <option value="128">Nigeria</option>
                    <option value="129">Norv&egrave;ge</option>
                    <option value="130">Nouvelle-Z&eacute;lande</option>
                    <option value="131">Oman</option>
                    <option value="132">Ouganda</option>
                    <option value="133">Ouzb&eacute;kistan</option>
                    <option value="134">Pakistan</option>
                    <option value="135">Palaos</option>
                    <option value="136">Panama</option>
                    <option value="137">Papouasie-Nouvelle-Guin&eacute;e</option>
                    <option value="138">Paraguay</option>
                    <option value="139">Pays-Bas</option>
                    <option value="140">P&eacute;rou</option>
                    <option value="141">Philippines</option>
                    <option value="142">Pologne</option>
                    <option value="195">Polynésie française</option>
                    <option value="143">Portugal</option>
                    <option value="144">Qatar</option>
                    <option value="145">Russie</option>
                    <option value="146">Salvador</option>
                    <option value="147">Syrie</option>
                    <option value="148">R&eacute;publique tch&egrave;que</option>
                    <option value="149">Tanzanie</option>
                    <option value="150">Roumanie</option>
                    <option value="151">Royaume-Uni</option>
                    <option value="152">Rwanda</option>
                    <option value="153">Sainte-Lucie</option>
                    <option value="154">Saint-Christophe-et-Ni&eacute;v&egrave;s</option>
                    <option value="155">Saint-Marin</option>
                    <option value="156">Saint-Vincent-et-les Grenadines</option>
                    <option value="157">Salomon</option>
                    <option value="158">Samoa</option>
                    <option value="159">Tom&eacute;-et-Principe</option>
                    <option value="160">S&eacute;n&eacute;gal</option>
                    <option value="161">Serbie</option>
                    <option value="162">Seychelles</option>
                    <option value="163">Sierra Leone</option>
                    <option value="164">Singapour</option>
                    <option value="165">Slovaquie</option>
                    <option value="166">Slov&eacute;nie</option>
                    <option value="167">Somalie</option>
                    <option value="168">Soudan</option>
                    <option value="169">Soudan du Sud</option>
                    <option value="170">Sri Lanka</option>
                    <option value="171">Su&egrave;de</option>
                    <option value="172">Suisse</option>
                    <option value="173">Suriname</option>
                    <option value="174">Swaziland</option>
                    <option value="175">Tadjikistan</option>
                    <option value="176">Tchad</option>
                    <option value="177">Tha&iuml;lande</option>
                    <option value="178">Timor oriental</option>
                    <option value="179">Togo</option>
                    <option value="180">Tonga</option>
                    <option value="181">Trinit&eacute;-et-Tobago</option>
                    <option value="182">Tunisie</option>
                    <option value="183">Turkm&eacute;nistan</option>
                    <option value="184">Turquie</option>
                    <option value="185">Tuvalu</option>
                    <option value="186">Ukraine</option>
                    <option value="187">Uruguay</option>
                    <option value="188">Vanuatu</option>
                    <option value="189">Venezuela</option>
                    <option value="190">Vi&ecirc;t Nam</option>
                    <option value="191">Y&eacute;men</option>
                    <option value="192">Zambie</option>
                    <option value="193">Zimbabwe</option>
                </select>
                <div class="clear"></div>
                <div class="cb-holder">
                    <label id="label_checkbox_inscription_cgv" for="inscription_cgv"></label>
                    <p id="label_inscription_cgv">J'ai lu et j'accepte les
                        <a href="<?= $url_site ?>/cgv_preteurs" target="_blank">Conditions G&eacute;n&eacute;rales de Vente</a> d'Unilend
                    </p>
                    <input type="checkbox" class="custom-chekckbox" name="inscription_cgv" id="inscription_cgv">
                </div>
                <button type="submit" id="inscription_submit2" name="valider">je finis mon inscription<br/><span>en transmettant d&egrave;s maintenant mes documents</span></button>
                <p>Ou</p>
                <button type="submit" id="voir_projets" name="valider-projets">Voir les projets<br/><span>et finir mon inscription plus tard</span></button>
                <div id="tracking"></div>
                <div class="clear"></div>
            </div>
        </form>
    </section>
</div>
<div id="home" class="wrapper100">
    <section class="wrapper">
        <a href="#" id="logo"><img src="img/unilend.png" alt="Unilend - Vos int&eacute;r&ecirc;ts se rencontrent" width="252" height="60"></a>
        <h1>Pr&ecirc;tez directement aux entreprises</h1>
        <h2>Recevez chaque mois vos int&eacute;r&ecirc;ts</h2>
        <ul>
            <li>
                <h3><span>1</span> Choisissez</h3>
                <p>
                    S&eacute;lectionnez les entreprises auxquelles vous souhaitez pr&ecirc;ter.<br/>
                    Leur solidit&eacute; a &eacute;t&eacute; soigneusement &eacute;tudi&eacute;e par Unilend.
                </p>
            </li>
            <li>
                <h3><span>2</span> Pr&ecirc;tez entre 4 % et 10 %</h3>
                <p>
                    Choisissez le montant (&agrave; partir de 20&euro;) et le taux<br/>
                    des pr&ecirc;ts que vous souhaitez r&eacute;aliser.
                </p>
            </li>
            <li>
                <h3><span>3</span> Recevez des int&eacute;r&ecirc;ts</h3>
                <p>
                    Tous les mois, vous recevez vos remboursements et<br/>
                    vos int&eacute;r&ecirc;ts, que vous pouvez pr&ecirc;ter &agrave; nouveau.
                </p>
            </li>
        </ul>
        <div class="scroll"></div>
    </section>
</div><!-- home -->

<div id="pourquoi_unilend" class="wrapper100 bg_gris">
    <section class="wrapper">
        <a href="#" id="logo"><img src="img/unilend.png" alt="Unilend - Vos int&eacute;r&ecirc;ts se rencontrent" width="252" height="60"></a>
        <h1>Pourquoi <span>Unilend ?</span></h1>
        <ul>
            <li>B&eacute;n&eacute;ficiez d'un compte totalement gratuit</li>
            <li>Choisissez librement le taux</li>
            <li>Pr&ecirc;tez &agrave; des taux attractifs entre 4% et 10%</li>
            <li>Recevez des revenus mensuels</li>
            <li>Faites travailler votre argent dans les PME fran&ccedil;aises</li>
        </ul>
        <p class="fleche">Inscrivez-vous et reprenez le pouvoir sur votre argent</p>
        <div class="scroll"></div>
    </section>
</div><!-- pourquoi_unilend -->

<div id="projet_analyse" class="wrapper100 bg_gris">
    <section class="wrapper">
        <a href="#" id="logo"><img src="img/unilend.png" alt="Unilend - Vos int&eacute;r&ecirc;ts se rencontrent" width="252" height="60"></a>
        <h1>Chaque projet est <span>rigoureusement analys&eacute;</span></h1>
        <ul id="projet_analyse_ul">
            <li>
                Analyse des donn&eacute;es financi&egrave;res fournies par Altares,
                membre du r&eacute;seau Dun & Bradstreet, leader mondial
                de la fourniture de donn&eacute;es sur les soci&eacute;t&eacute;s
            </li>
            <li>
                &Eacute;tude approfondie des 3 derniers bilans et<br/>
                du projet de l'entreprise
            </li>
            <li>Examen des capacit&eacute;s de remboursement</li>
        </ul>
        <p id="projet_analyse_tous"></p>
        <p id="projet_analyse_tous2">
            Les projets &agrave; financer sur Unilend pr&eacute;sentent
            une capacit&eacute; de remboursement &eacute;prouv&eacute;e
        </p>
        <div class="clear"></div>
        <h3>Exemples de projets financ&eacute;s</h3>
        <div id="slider_projet">
            <div>
                <button id="slide_prec"></button>
                <div>

                    <div>
                        <div>
                            <img src="img/Giglam.jpeg" alt="Giglam" width="160" height="120">
                            <p>Giglam Conseils</p>
                        </div>
                        <div>
                            <img src="img/hmbc.png" alt="HMBC" width="160" height="120">
                            <p>HMBC</p>
                        </div>
                        <div>
                            <img src="img/ralf-tech.png" alt="Ralf Tech" width="160" height="120">
                            <p>Ralf Tech</p>
                        </div>
                        <div>
                            <img src="img/semafer.jpg" alt="Semafer" width="160" height="120">
                            <p>Semafer</p>
                        </div>
                        <div>
                            <img src="img/complement-europe.png" alt="Compl&eacute;ment Europe" width="160" height="120">
                            <p>Compl&eacute;ment Europe</p>
                        </div>
                        <div>
                            <img src="img/snri.png" alt="SNRI" width="160" height="120">
                            <p>SNRI</p>
                        </div>
                    </div>

                </div>
                <button id="slide_suiv"></button>
            </div>
        </div>
        <div class="clear"></div>
        <p class="fleche">Choisissez vos projets. Pour les d&eacute;couvrir, inscrivez-vous</p>
        <div class="scroll"></div>
    </section>
</div><!-- projet_analyse -->

<div id="chiffres" class="wrapper100 bg_gris">
    <section class="wrapper">
        <a href="#" id="logo"><img src="img/unilend.png" alt="Unilend - Vos int&eacute;r&ecirc;ts se rencontrent" width="252" height="60"></a>
        <h1>Unilend <span>en chiffres</span></h1>
        <p>Depuis le lancement d'Unilend jusqu'&agrave; fin octobre 2014</p>
        <div id="chiffres_left">
            <ul>
                <li>
                    <p>67</p>
                    <p>Projets<br/>financ&eacute;s</p>
                </li>
                <li>
                    Record de financement<br/>d'un projet :
                    <p>312 &euro;/min</p>
                </li>
                <li>
                    Record du dossier<br/>100% financ&eacute; :
                    <p>45 min</p>
                </li>
            </ul>
        </div>
        <div id="chiffres_right">
            <ul>
                <li>
                    <p id="tooltip1" data-toggle="tooltip" data-placement="top" title="Montants pr&ecirc;t&eacute;s par la communaut&eacute; Unilend aux entreprises fran&ccedil;aises">Montants pr&ecirc;t&eacute;s</p>
                    <div class="chiffres">
                        <p class="m5">5</p>
                        <p>4</p>
                        <p>7</p>
                        <p class="m5">0</p>
                        <p>1</p>
                        <p>5</p>
                        <p>0</p>
                        <div class="clear"></div>
                    </div>
                    <div class="clear"></div>
                </li>
                <li>
                    <p id="tooltip2" data-toggle="tooltip" data-placement="top" title="Cumul des versements (capital et int&eacute;r&ecirc;ts bruts) rembours&eacute;s aux pr&ecirc;teurs">Remboursements<br/>aux pr&ecirc;teurs</p>
                    <div class="chiffres">
                        <p>4</p>
                        <p>9</p>
                        <p class="m5">8</p>
                        <p>1</p>
                        <p>8</p>
                        <p>8</p>
                        <div class="clear"></div>
                    </div>
                    <div class="clear"></div>
                </li>
                <li>
                    <p id="tooltip3" data-toggle="tooltip" data-placement="top" title="Cumul des int&eacute;r&ecirc;ts bruts revers&eacute;s aux pr&ecirc;teurs">Int&eacute;r&ecirc;ts bruts<br/>revers&eacute;s aux pr&ecirc;teurs</p>
                    <div class="chiffres">
                        <p>1</p>
                        <p>4</p>
                        <p class="m5">9</p>
                        <p>1</p>
                        <p>3</p>
                        <p>1</p>
                        <div class="clear"></div>
                    </div>
                    <div class="clear"></div>
                </li>
            </ul>
        </div>
        <div class="clear"></div>
        <p class="fleche">Inscrivez-vous et pr&ecirc;tez &agrave; partir de 20&euro; jusqu'&agrave; 1 000 000&euro;</p>
        <div class="scroll"></div>
    </section>
</div><!-- chiffres -->

<div id="presse" class="wrapper100">
    <section class="wrapper">
        <a href="#" id="logo"><img src="img/unilend.png" alt="Unilend - Vos int&eacute;r&ecirc;ts se rencontrent" width="252" height="60"></a>
        <h1>La presse parle <span>d'Unilend</span></h1>
        <img id="presse_logos" src="img/presse.jpg" alt="BFM Business - Le Monde - Capital - Le Point - Le Nouvel Observateur - L'Express - Oiuest France - 01net. - Le Figaro Economie" width="354" height="199">
        <div class="clear"></div>
        <div class="presse_liste">
            <section>
                <img id="video" src="img/le-monde.png" alt="Le Monde" width="90" height="40">
                <p>"Nouveaut&eacute;, Unilend permet de pr&ecirc;ter de l'argent directement aux PME"</p>
                <p>21/10/2013</p>
                <div class="clear"></div>
            </section>
            <section>
                <img id="video" src="img/capital.png" alt="Capital" width="90" height="40">
                <p>"Les internautes peuvent d&eacute;sormais pr&ecirc;ter en direct &agrave; des PME"</p>
                <p>30/01/2014</p>
                <div class="clear"></div>
            </section>
            <section>
                <img id="video" src="img/figaro.png" alt="Le Figaro Economie" width="90" height="40">
                <p>"Les gains peuvent atteindre 10 % l'an"</p>
                <p>17/05/2014</p>
                <div class="clear"></div>
            </section>
            <section>
                <img id="video" src="img/le-particulier.png" alt="Le Particulier" width="90" height="40">
                <p>"Percevez des int&eacute;r&ecirc;ts en finan&ccedil;ant des projets d'entreprises"</p>
                <p>01/06/2014</p>
                <div class="clear"></div>
            </section>
        </div>

        <div class="presse_liste">
            <section>
                <img id="video" src="img/presse1.jpg" alt="" width="80" height="80">
                <p>"Il nous faut d&eacute;velopper des sources fiables de financements non bancaires, tel que le financement participatif."</p>
                <p>11/09/2014</p>
                <p>Mario Draghi, Pr&eacute;sident de la Banque Centrale Europ&eacute;enne</p>
                <div class="clear"></div>
            </section>
            <section>
                <img id="video" src="img/presse2.jpg" alt="" width="80" height="80">
                <p>"Il faut des financements alternatifs pour accompagner les entreprises."</p>
                <p>15/09/2014</p>
                <p>Emmanuel Macron, ministre de l'Economie, de l'Industrie et du Num&eacute;rique</p>
                <div class="clear"></div>
            </section>
        </div>
        <div class="clear"></div>
        <p class="fleche">Inscrivez-vous et d&eacute;couvrez l'&eacute;fficacit&eacute; d'Unilend pour investir votre argent</p>
        <div class="scroll"></div>
    </section>
</div><!-- presse -->

<div id="qui_sommes_nous" class="wrapper100 bg_gris">
    <section class="wrapper">
        <a href="#" id="logo"><img src="img/unilend.png" alt="Unilend - Vos int&eacute;r&ecirc;ts se rencontrent" width="252" height="60"></a>
        <h1>Qui sommes-nous ?</h1>
        <p>Unilend propose une nouvelle forme de finance pour permettre :</p>
        <ul>
            <li>Aux entreprises d'emprunter directement et simplement aupr&egrave;s du grand public</li>
            <li>Aux &eacute;pargnants de pr&ecirc;ter de l'argent directement aux entreprises en recevant des int&eacute;r&ecirc;ts.</li>
        </ul>
        <p>
            <span>Unilend</span> est intermédiaire en financement participatif (IFP) inscrit à l’ORIAS (www.orias.fr) sous le numéro 15006955.<br>
            Le service de paiement Unilend est distribué par la Société française pour le financement des PME - SFF PME SAS, agent prestataire de services de paiement mandaté par la SFPMEI et enregistré auprès de l'Autorité de contrôle prudentiel et de résolution (ACPR) sous le numéro 790766034. Les informations d'enregistrement sont disponibles sur le site du registre des agents financiers en cliquant <a href="https://www.regafi.fr/spip.php?rubrique1">ici</a>.<br>
            Le service de paiement Unilend est fourni par la Société financière du porte-monnaie électronique interbancaire (SFPMEI), société par actions simplifiée au capital de 3 732 089 euros, dont le siège social est situé 29 rue du Louvre - 75002 Paris, immatriculée au registre du commerce et des sociétés de Paris sous le numéro 422 721 274. La SFPMEI est un établissement de crédit (code établissement 14378) agréé en date du 30 décembre 1999 par l’Autorité de contrôle prudentiel et de résolution (ACPR).
        </p>
        <br/>
        <p>
            <strong>Pr&ecirc;ter pr&eacute;sente un risque de non-remboursement : r&eacute;partissez bien vos pr&ecirc;ts et ne pr&ecirc;tez que de l'argent dont vous n'avez pas besoin imm&eacute;diatement.</strong>
        </p>
        <h2>Nos partenaires</h2>
        <div>
            <ul>
                <li><img src="img/sfpmei.png" alt="SFPMEI" width="110" height="62"></li>
                <li><img src="img/altares.png" alt="Altares - La connaissance inter-entreprises" width="110" height="62"></li>
                <li><img src="img/norton.png" alt="Norton secured - powered by VeriSign" width="110" height="62"></li>
                <li><img src="img/financement-participatif-france.png" alt="Financement Participatif France" width="110" height="62"></li>
            </ul>
            <div class="clear"></div>
        </div>

    </section>
</div><!-- qui_sommes_nous -->

<button id="scrollUp"></button>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript">window.jQuery || document.write('<script type="text/javascript" src="js/jquery-1.9.1.min.js"><\/script>')</script>
<script src="js/jquery.c2selectbox.js" type="text/javascript"></script>
<script src="js/jquery.nouislider.all.js"></script>
<script src="js/jquery.placeholder.js"></script>
<script src='js/jquery.base64.js'></script>
<script src='js/bootstrap.min.js'></script>
<script src="js/jquery.touchSwipe.min.js" type="text/javascript"></script>
<script src="https://crypto-js.googlecode.com/svn/tags/3.1.2/build/rollups/md5.js"></script>
<script src="js/global.js" type="text/javascript"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
<script>
    $(function () {

        $('input').keydown(function () {
            $(this).removeClass('error');
        });
        $('#inscription_date_naissance').keydown(function () {
            $('#errorAge').html('');
        });
        $('select').change(function () {
            $(this).next('.c2-sb-wrap').removeClass('error');
        });
        $('#inscription_cgv').change(function () {
            $(this).parent().find('label').removeClass('error');
        });
        $('#errorAge').html('');

        var civilite = '';
        var nom = '';
        var prenom = '';
        var email = '';

        $("button#inscription_submit2").click(function () {
            $("button#inscription_submit2").addClass("clicked");
            $("button#voir_projets").removeClass("clicked");
        });
        $("button#voir_projets").click(function () {
            $("button#voir_projets").addClass("clicked");
            $("button#inscription_submit2").removeClass("clicked");
        });

        $('#form_inscription').submit(function (event) {
            event.preventDefault();

            $('html, body').animate({
                scrollTop: 0
            }, 1000, 'swing');

            var inscription_civilite = $('#inscription_civilite').val();
            var inscription_nom = $.trim($('#inscription_nom').val());
            var inscription_prenom = $.trim($('#inscription_prenom').val());
            var inscription_email = $.trim($('#inscription_email').val());

            var inscription_mdp = $.trim($('#inscription_mdp').val());
            var inscription_mdp2 = $.trim($('#inscription_mdp2').val());
            var inscription_question = $.trim($('#inscription_question').val());
            var inscription_reponse = $.trim($('#inscription_reponse').val());
            var inscription_adresse_fiscale = $.trim($('#inscription_adresse_fiscale').val());
            var inscription_ville_fiscale = $.trim($('#inscription_ville_fiscale').val());
            var inscription_cp_fiscale = $.trim($('#inscription_cp_fiscale').val());
            var inscription_id_pays_fiscale = $('#inscription_id_pays_fiscale').val();
            // var inscription_check_adresse = $('#inscription_check_adresse').val();
            var inscription_adresse_correspondance = $.trim($('#inscription_adresse_correspondance').val());
            var inscription_ville_correspondance = $.trim($('#inscription_ville_correspondance').val());
            var inscription_cp_correspondance = $.trim($('#inscription_cp_correspondance').val());
            var inscription_id_pays_correspondance = $('#inscription_id_pays_correspondance').val();
            var inscription_telephone = $.trim($('#inscription_telephone').val());
            var inscription_id_nationalite = $('#inscription_id_nationalite').val();
            var inscription_date_naissance = $('#inscription_date_naissance').val();
            var inscription_commune_naissance = $.trim($('#inscription_commune_naissance').val());
            var inscription_id_pays_naissance = $('#inscription_id_pays_naissance').val();
            var insee_birth = $('#insee_birth').val();
            var inscription_cgv = $('#inscription_cgv');
            var utm_source = '<?php echo $source; ?>';
            var utm_source2 = '<?php echo $source2; ?>';
            var slug_origine = '<?php echo $slug_origine; ?>';

            if ($('#form_inscription').hasClass('etape1')) {

                var erreur = 0;

                if (!inscription_civilite) {
                    $('#inscription_civilite').next('.c2-sb-wrap').addClass('error');
                    erreur = 1;
                }
                if (!inscription_nom) {
                    $('#inscription_nom').addClass('error');
                    erreur = 1;
                }
                if (!inscription_prenom) {
                    $('#inscription_prenom').addClass('error');
                    erreur = 1;
                }
                if (!inscription_email) {
                    $('#inscription_email').addClass('error');
                    erreur = 1;
                }
                if (!validateEmail(inscription_email)) {
                    $('#inscription_email').addClass('error');
                    erreur = 1;
                }
                if (erreur == 1) {
                    return false;
                } else {
                    // AJAX
                    var key = 'unilend';
                    var hash = CryptoJS.MD5(key);
                    var time = $.now();
                    var token = $.base64.btoa(hash + '-' + time);
                    var localdate = new Date();
                    var mois = localdate.getMonth() + 1;
                    var jour = localdate.getDate();
                    var heure = localdate.getHours();
                    var minutes = localdate.getMinutes();
                    var secondes = localdate.getSeconds();
                    if (mois < 10) {
                        mois = '0' + mois;
                    }
                    if (jour < 10) {
                        jour = '0' + jour;
                    }
                    if (heure < 10) {
                        heure = '0' + heure;
                    }
                    if (minutes < 10) {
                        minutes = '0' + minutes;
                    }
                    if (secondes < 10) {
                        secondes = '0' + secondes;
                    }

                    var date = localdate.getFullYear() + '-' + mois + '-' + jour + ' ' + heure + ':' + minutes + ':' + secondes;
                    email = inscription_email;
                    nom = inscription_nom;
                    prenom = inscription_prenom;
                    civilite = inscription_civilite;

                    var DATA = '&token=' + token + '&utm_source=' + utm_source + '&utm_source2=' + utm_source2 + '&slug_origine=' + slug_origine + '&date=' + date + '&email=' + email + '&nom=' + nom + '&prenom=' + prenom + '&civilite=' + civilite;

                    $.ajax({
                        type: "POST",
                        url: "<?= $url_site ?>/collect/prospect",
                        data: DATA,
                        success: function (data) {
                            var parsedDate = jQuery.parseJSON(data);

                            if (parsedDate.reponse == 'OK') {
                                $('#form_inscription').removeClass('etape1');
                                $('#form_inscription').addClass('etape2');

                                $('html, body').animate({
                                    scrollTop: 0
                                }, 1000, 'swing');

                                $('#form_header').fadeOut('fast', function () {
                                    $('#form_header').html('<h1>Compl&eacute;tez</h1><h2>Votre inscription</h2>');
                                    $('#form_header').fadeIn();
                                });

                                $('#form_inscription > .form_content.etape1').fadeOut('fast', function () {
                                    $('#form').css('position', 'relative');
                                    $('#form > .wrapper').addClass('etape2');
                                    $("#tracking").html('<iframe src="https://tracking.unilend-partners.com/mastertags/3.html?action=cpca&pid=3&type=15"  width="1" height="1" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" style="border:none;"></iframe>');

                                    $('#form_inscription > .form_content.etape2').fadeIn();
                                });
                            }
                            else {
                                $.each(parsedDate.reponse, function (index, value) {
                                    var intituleErreur = value.erreur;

                                    if (intituleErreur == "Nom") {
                                        $('#inscription_nom').addClass('error');
                                    }
                                    if (intituleErreur == "Prenom") {
                                        $('#inscription_prenom').addClass('error');
                                    }
                                    if (intituleErreur == "Email" || intituleErreur == "Format email") {
                                        $('#inscription_email').addClass('error');
                                    }
                                    if (intituleErreur == "Email existant" && parsedDate.reponse.length > 1) {
                                        $('#inscription_email').addClass('error');
                                    }
                                    else {
                                        $('#form_inscription').removeClass('etape1');
                                        $('#form_inscription').addClass('etape2');

                                        $('#form_header').fadeOut('fast', function () {
                                            $('#form_header').html('<h1>Compl&eacute;tez</h1><h2>Votre inscription</h2>');
                                            $('#form_header').fadeIn();
                                        });

                                        $('#form_inscription > .form_content.etape1').fadeOut('fast', function () {
                                            $('#form').css('position', 'relative');
                                            $('#form > .wrapper').addClass('etape2');
                                            $("#tracking").html('<iframe src="https://tracking.unilend-partners.com/mastertags/3.html?action=cpca&pid=3&type=15"  width="1" height="1" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" style="border:none;"></iframe>');

                                            $('#form_inscription > .form_content.etape2').fadeIn();
                                        });
                                    }
                                });
                            }
                        }
                    });
                    return false;
                }
            }
            else if ($('#form_inscription').hasClass('etape2')) {

                var idSubmit = $("button[type=submit].clicked").attr("id");

                var erreur = 0;

                var localdate = new Date();
                var annee = localdate.getFullYear();
                var mois = localdate.getMonth() + 1;
                var jour = localdate.getDate();
                var heure = localdate.getHours();
                var minutes = localdate.getMinutes();
                var secondes = localdate.getSeconds();

                if (mois < 10) {
                    mois = '0' + mois;
                }
                if (jour < 10) {
                    jour = '0' + jour;
                }
                if (heure < 10) {
                    heure = '0' + heure;
                }
                if (minutes < 10) {
                    minutes = '0' + minutes;
                }
                if (secondes < 10) {
                    secondes = '0' + secondes;
                }

                if (!inscription_mdp) {
                    $('#inscription_mdp').addClass('error');
                    erreur = 1;
                }
                if (inscription_mdp.length < 6) {
                    $('#inscription_mdp').addClass('error');
                    erreur = 1;
                }
                if (inscription_mdp.replace(/[^A-Z]/g, "").length == 0) {
                    $('#inscription_mdp').addClass('error');
                    erreur = 1;
                }
                if (!inscription_mdp2) {
                    $('#inscription_mdp2').addClass('error');
                    erreur = 1;
                }
                if (inscription_mdp2 != inscription_mdp) {
                    $('#inscription_mdp2').addClass('error');
                    erreur = 1;
                }
                if (!inscription_adresse_fiscale) {
                    $('#inscription_adresse_fiscale').addClass('error');
                    erreur = 1;
                }
                if (!inscription_ville_fiscale) {
                    $('#inscription_ville_fiscale').addClass('error');
                    erreur = 1;
                }
                if (!inscription_cp_fiscale) {
                    $('#inscription_cp_fiscale').addClass('error');
                    erreur = 1;
                }
                if (controlePostCodeCity($('#inscription_cp_fiscale'), $('#inscription_ville_fiscale'), $('#inscription_id_pays_fiscale'), false) == false) {
                    erreur = 1;
                }
                if ($('#inscription_check_adresse').is(':checked')) {
                    inscription_adresse_correspondance = '';
                    inscription_ville_correspondance = '';
                    inscription_cp_correspondance = '';
                    inscription_id_pays_correspondance = '';
                } else {
                    if (!inscription_adresse_correspondance) {
                        $('#inscription_adresse_correspondance').addClass('error');
                        erreur = 1;
                    }
                    if (!inscription_ville_correspondance) {
                        $('#inscription_ville_correspondance').addClass('error');
                        erreur = 1;
                    }
                    if (!inscription_cp_correspondance) {
                        $('#inscription_cp_correspondance').addClass('error');
                        erreur = 1;
                    }
                    if (controlePostCodeCity($('#inscription_cp_correspondance'), $('#inscription_ville_correspondance'), $('#inscription_id_pays_correspondance'), false) == false) {
                        erreur = 1;
                    }
                    if (!inscription_id_pays_correspondance) {
                        $('#inscription_id_pays_correspondance').next('.c2-sb-wrap').addClass('error');
                        erreur = 1;
                    }
                }
                if (!inscription_telephone) {
                    $('#inscription_telephone').addClass('error');
                    erreur = 1;
                }
                if (inscription_telephone.length != 10 || !$.isNumeric(inscription_telephone)) {
                    $('#inscription_telephone').addClass('error');
                    erreur = 1;
                }
                if (!inscription_id_nationalite) {
                    $('#inscription_id_nationalite').next('.c2-sb-wrap').addClass('error');
                    erreur = 1;
                }
                var verif_date = 0;
                if (!inscription_date_naissance) {
                    $('#inscription_date_naissance').addClass('error');
                    erreur = 1;
                    verif_date = 1;
                }
                if (!validateDate(inscription_date_naissance)) {
                    $('#inscription_date_naissance').addClass('error');
                    if (verif_date == 0) {
                        $('#errorAge').html('La date doit &ecirc;tre au format jj/mm/aaaa');
                    }
                    erreur = 1;
                    verif_date = 1;
                }
                var date_naissance = inscription_date_naissance;
                var split_date = date_naissance.split('/');

                if (split_date[2] > annee) {
                    $('#inscription_date_naissance').addClass('error');
                    if (verif_date == 0) {
                        $('#errorAge').html('Ann&eacute;e invalide');
                    }
                    erreur = 1;
                    verif_date = 1;
                }
                if (split_date[1] > 12) {
                    $('#inscription_date_naissance').addClass('error');
                    if (verif_date == 0) {
                        $('#errorAge').html('Mois invalide');
                    }
                    erreur = 1;
                    verif_date = 1;
                }
                if (split_date[0] > 31) {
                    $('#inscription_date_naissance').addClass('error');
                    if (verif_date == 0) {
                        $('#errorAge').html('Jours invalide');
                    }
                    erreur = 1;
                    verif_date = 1;
                }

                var majeur = 0;

                if (split_date[2] < (annee - 18)) {
                    majeur = 1;
                }
                else if (split_date[2] == (annee - 18)) {
                    if (split_date[1] < mois) {
                        majeur = 1;
                    }
                    else if (split_date[1] == mois) {
                        if (split_date[0] <= jour) {
                            majeur = 1;
                        }
                        else {
                            majeur = 0;
                        }
                    }
                    else {
                        majeur = 0;
                    }
                }
                else {
                    majeur = 0;
                }

                if (majeur == 0 && verif_date == 0) {
                    $('#inscription_date_naissance').addClass('error');
                    $('#errorAge').html('Vous devez &ecirc;tre majeur');
                    erreur = 1;
                }
                if (!inscription_commune_naissance) {
                    $('#inscription_commune_naissance').addClass('error');
                    erreur = 1;
                }
                if ("1" == inscription_id_pays_naissance && !insee_birth) {
                    $('#inscription_commune_naissance').addClass('error');
                    erreur = 1;
                }
                if (!inscription_id_pays_naissance) {
                    $('#inscription_id_pays_naissance').next('.c2-sb-wrap').addClass('error');
                    erreur = 1;
                }

                if (!inscription_cgv.is(":checked")) {
                    $('#inscription_cgv').parent().find('label').addClass('error');
                    erreur = 1;
                }
                if (erreur == 1) {
                    return false;
                }
                else {
                    // AJAX
                    var key = 'unilend';
                    var hash = CryptoJS.MD5(key);
                    var time = $.now();
                    var token = $.base64.btoa(hash + '-' + time);
                    var passwordMd5 = CryptoJS.MD5(inscription_mdp);

                    $.ajax({
                        method: "POST",
                        url: "<?= $url_site ?>/collect/inscription",
                        data: 'token=' + token
                        + '&utm_source=' + utm_source
                        + '&utm_source2=' + utm_source2
                        + '&slug_origine=' + slug_origine
                        + '&date=' + annee + '-' + mois + '-' + jour + ' ' + heure + ':' + minutes + ':' + secondes
                        + '&email=' + inscription_email
                        + '&nom=' + inscription_nom
                        + '&prenom=' + inscription_prenom
                        + '&civilite=' + inscription_civilite
                        + '&password=' + passwordMd5
                        + '&question=' + inscription_question
                        + '&reponse=' + inscription_reponse
                        + '&adresse_fiscale=' + inscription_adresse_fiscale
                        + '&ville_fiscale=' + inscription_ville_fiscale
                        + '&cp_fiscale=' + inscription_cp_fiscale
                        + '&id_pays_fiscale=' + inscription_id_pays_fiscale
                        + '&adresse=' + inscription_adresse_correspondance
                        + '&ville=' + inscription_ville_correspondance
                        + '&cp=' + inscription_cp_correspondance
                        + '&id_pays=' + inscription_id_pays_correspondance
                        + '&telephone=' + inscription_telephone
                        + '&id_nationalite=' + inscription_id_nationalite
                        + '&date_naissance=' + split_date[2] + '-' + split_date[1] + '-' + split_date[0]
                        + '&commune_naissance=' + inscription_commune_naissance
                        + '&id_pays_naissance=' + inscription_id_pays_naissance
                        + '&insee_birth=' + insee_birth
                        + '&signature_cgv=' + 1
                        + '&forme_preteur=' + 1,
                        success: function (data) {
                            var parsedDate = jQuery.parseJSON(data);

                            console.log(parsedDate);

                            if (parsedDate.reponse == 'OK') {
                                var url = parsedDate.URL;

                                if (idSubmit == "inscription_submit2") {
                                    $(location).attr('href', url);
                                }
                                else if (idSubmit == "voir_projets") {
                                    $(location).attr('href', '<?= $url_site ?>/projets-a-financer');
                                }
                            }
                            else {
                                $.each(parsedDate.reponse, function (index, value) {
                                    var intituleErreur = value.erreur;

                                    console.log(intituleErreur);

                                    if (intituleErreur == "Mot de passe") {
                                        $('#inscription_mdp').addClass('error');
                                    }
                                    if (intituleErreur == "Question secr&egrave;te") {
                                        $('#inscription_question').addClass('error');
                                    }
                                    if (intituleErreur == "Reponse secr&egrave;te") {
                                        $('#inscription_reponse').addClass('error');
                                    }
                                    if (intituleErreur == "Adresse fiscale") {
                                        $('#inscription_adresse_fiscale').addClass('error');
                                    }
                                    if (intituleErreur == "Ville fiscale") {
                                        $('#inscription_ville_fiscale').addClass('error');
                                    }
                                    if (intituleErreur == "Code postal fiscale") {
                                        $('#inscription_cp_fiscale').addClass('error');
                                    }
                                    if (intituleErreur == "Pays fiscale") {
                                        $('#inscription_id_pays_fiscale').next('.c2-sb-wrap').addClass('error');
                                    }
                                    if (intituleErreur == "Adresse") {
                                        $('#inscription_adresse_correspondance').addClass('error');
                                    }
                                    if (intituleErreur == "Ville") {
                                        $('#inscription_ville_correspondance').addClass('error');
                                    }
                                    if (intituleErreur == "Code postal") {
                                        $('#inscription_cp_correspondance').addClass('error');
                                    }
                                    if (intituleErreur == "Pays") {
                                        $('#inscription_id_pays_correspondance').next('.c2-sb-wrap').addClass('error');
                                    }
                                    if (intituleErreur == "T&eacute;l&eacute;phone") {
                                        $('#inscription_telephone').addClass('error');
                                    }
                                    if (intituleErreur == "Nationalit&eacute;") {
                                        $('#inscription_id_nationalite').next('.c2-sb-wrap').addClass('error');
                                    }
                                    if (intituleErreur == "Date de naissance") {
                                        $('#inscription_date_naissance').addClass('error');
                                    }
                                    if (intituleErreur == "Commune de naissance") {
                                        $('#inscription_commune_naissance').addClass('error');
                                    }
                                    if (intituleErreur == "Pays de naissance") {
                                        $('#inscription_id_pays_naissance').next('.c2-sb-wrap').addClass('error');
                                    }
                                    if (intituleErreur == "Signature cgv") {
                                        $('#inscription_cgv').parent().find('label').addClass('error');
                                    }
                                });
                            }
                        }
                    });

                    return false;
                }
            }
            else {
                return false;
            }
        });

        initAutocompleteCity();
    });
    function validateEmail(emailAddress) {
        var emailRegex = new RegExp(/^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/);
        var valid = emailRegex.test(emailAddress);
        if (!valid) {
            return false;
        } else
            return true;
    }

    function validateDate(date) {
        var dateRegex = new RegExp(/^([0-9]{2}[\/][0-9]{2}[\/][0-9]{4})$/);
        var valid = dateRegex.test(date);
        if (!valid) {
            return false;
        } else
            return true;
    }

    function initAutocompleteCity() {
        $('[data-autocomplete]').each(function () {
            if ($(this).data('autocomplete') == 'city' || $(this).data('autocomplete') == 'post_code' || $(this).data('autocomplete') == 'birth_city') {
                var getBirthPlace = '';
                if ($(this).data('autocomplete') == 'birth_city') {
                    getBirthPlace = 'birthplace';
                }
                $(this).autocomplete({
                    source: '<?= $url_site ?>/ajax/get_cities/' + getBirthPlace + '/',
                    minLength: 3,
                    search: function (event, ui) {
                        if ($(this).data('autocomplete') == 'birth_city') {
                            $("#insee_birth").val('');
                        }
                        $(this).removeClass('error');
                    },
                    select: function (event, ui) {
                        event.preventDefault();

                        var myRegexp = /(.+)\s\((.+)\)/;
                        var match = myRegexp.exec(ui.item.label);

                        if (match != null) {
                            switch ($(this).data('autocomplete')) {
                                case 'birth_city' :
                                    $(this).val(match[1]).removeClass('error');
                                    $("#insee_birth").val(ui.item.value);
                                    break;
                                case 'city' :
                                    $(this).val(match[1]).removeClass('error');
                                    $(this).siblings("[data-autocomplete='post_code']")
                                        .val(match[2])
                                        .removeClass('error');
                                    break;
                                case 'post_code' :
                                    $(this).val(match[2]).removeClass('error');
                                    $(this).siblings("[data-autocomplete='city']")
                                        .val(match[1])
                                        .removeClass('error');
                                    break;
                            }
                        }
                    }
                });
            }
        });
    }

    function controlePostCodeCity(elmCp, elmCity, elmCountry, async) {
        async = typeof async !== 'undefined' ? async : true;
        var result = false;
        $.ajax({
            url: '<?= $url_site ?>/ajax/checkPostCodeCity/' + elmCp.val() + '/' + elmCity.val() + '/' + elmCountry.val(),
            method: 'GET',
            async: async
        }).done(function (data) {
            if (data == 'ok') {
                elmCp.removeClass('error');
                elmCity.removeClass('error');
                result = true;
            } else {
                elmCp.addClass('error');
                elmCity.addClass('error');
                result = false;
            }
        });
        return result;
    }
</script>
</body>
</html>
