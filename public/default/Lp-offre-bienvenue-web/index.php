<?php

if (date('Y') == 2015) {
    header('Location: https://www.unilend.fr/LP_inscription_preteurs/');
    die;
}
?>
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
    <title>Unilend : les particuliers prêtent aux entreprises françaises</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <meta name="Author" content="dynamic creative - Agence créative pas NET, mais WEB énormément"/>
    <meta name="description" content="Sur Unilend, tout le monde peut prêter aux entreprises françaises et recevoir des intérêts."/>
    <meta name="keywords" content="Financement entreprise, prêt à des entreprises, investissement direct, peer-to-peer lending, crowdfunding"/>
    <meta name="viewport" content="initial-scale = 1.0,maximum-scale = 1.0"/>
    <meta name="apple-mobile-web-app-capable" content="yes">

    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link href="css/font.css" type="text/css" rel="stylesheet" media="all">
    <link href="css/base.css" type="text/css" rel="stylesheet" media="all">
    <link href="css/global.css" type="text/css" rel="stylesheet" media="all">
    <link href="css/responsive.css" type="text/css" rel="stylesheet" media="all">
    <link href="css/jquery.c2selectbox.css" type="text/css" rel="stylesheet" media="all"/>
    <link href="css/bootstrap.css" type="text/css" rel="stylesheet" media="all"/>
    <link rel="stylesheet" href="css/jquery.nouislider.css"/>

    <!--[if IE]><script src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
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

$slug_origine = "Lp-offre-bienvenue-web";
$url = 'https://www.unilend.fr';

?>
<div id="form">
    <section class="wrapper">
        <form action="#" method="post" id="form_inscription" class="etape1" novalidate>
            <div class="form_promo border10">
                <a href="#bloc_mentions" class="macaron"><span>20 €</span> <b>OFFERTS</b> pour prêter !</a>
            </div>
            <div id="form_header">
                <h1>Inscrivez-vous</h1>
                <h2>Et découvrez Unilend</h2>
            </div>
            <div class="form_content etape1">
                <select name="civilite" id="inscription_civilite" class="custom-select">
                        <option value=""><?php if ($civilite == "Mme") { echo "Madame"; } elseif ($civilite == "M.") { echo "Monsieur"; } else { echo "Civilité*"; } ?></option>
                        <option <?php if ($civilite == "M.") { echo "selected"; } ?> value="M.">Monsieur</option>
                        <option <?php if ($civilite == "Mme") { echo "selected"; } ?> value="Mme">Madame</option>
                </select>
                <input type="text" id="inscription_nom" name="nom" placeholder="Nom*" maxlength="255" value="<?php echo $nom; ?>">
                <input type="text" id="inscription_prenom" name="prenom" placeholder="Prénom*" maxlength="255" value="<?php echo $prenom; ?>">
                <input type="email" id="inscription_email" name="email" placeholder="E-mail*" maxlength="255" value="<?php echo $email; ?>">
                <button type="submit" id="inscription_submit" name="valider">S'inscrire</button>
                <p class="champs_obligatoires">* Champs obligatoires</p>
            </div>
            <div class="form_content etape2">
                <input type="password" id="inscription_mdp" name="password" placeholder="Choisissez un mot de passe*" maxlength="255">
                <input type="password" id="inscription_mdp2" name="inscription_mdp2" placeholder="Confirmez votre mot de passe*" maxlength="255">
                <p>Votre mot de passe doit au moins contenir 6 caractères dont une majuscule et une minuscule.</p>
                <input type="text" id="inscription_question" name="question" placeholder="Choisissez une question secrète" maxlength="255">
                <input type="text" id="inscription_reponse" name="reponse" placeholder="Choisissez une réponse" maxlength="255">
                <input type="text" id="inscription_adresse_fiscale" name="adresse_fiscale" placeholder="Adresse*" maxlength="255">
                <input type="text" id="inscription_ville_fiscale" name="ville_fiscale" placeholder="Ville*" maxlength="255">
                <input type="text" id="inscription_cp_fiscale" name="cp_fiscale" placeholder="Code postal*" maxlength="5">
                <select id="inscription_id_pays_fiscale" name="id_pays_fiscale" class="custom-select">
                    <option value="">Pays*</option>
                    <option value="1">France</option>
                    <option value="2">Afghanistan</option>
                    <option value="3">Afrique du Sud</option>
                    <option value="4">Albanie</option>
                    <option value="5">Algérie</option>
                    <option value="6">Allemagne</option>
                    <option value="7">Andorre</option>
                    <option value="8">Angola</option>
                    <option value="9">Antigua-et-Barbuda</option>
                    <option value="10">Arabie saoudite</option>
                    <option value="11">Argentine</option>
                    <option value="12">Arménie</option>
                    <option value="13">Australie</option>
                    <option value="14">Autriche</option>
                    <option value="15">Azerbaïdjan</option>
                    <option value="16">Bahamas</option>
                    <option value="17">Bahreïn</option>
                    <option value="18">Bangladesh</option>
                    <option value="19">Barbade</option>
                    <option value="20">Biélorussie</option>
                    <option value="21">Belgique</option>
                    <option value="22">Belize</option>
                    <option value="23">Bénin</option>
                    <option value="24">Bhoutan</option>
                    <option value="25">Birmanie</option>
                    <option value="26">Bolivie</option>
                    <option value="27">Bosnie-Herzégovine</option>
                    <option value="28">Botswana</option>
                    <option value="29">Brésil</option>
                    <option value="30">Brunei</option>
                    <option value="31">Bulgarie</option>
                    <option value="32">Burkina Faso</option>
                    <option value="33">Burundi</option>
                    <option value="34">Cambodge</option>
                    <option value="35">Cameroun</option>
                    <option value="36">Canada</option>
                    <option value="37">Cap-Vert</option>
                    <option value="38">République centrafricaine</option>
                    <option value="39">Chili</option>
                    <option value="40">Chine</option>
                    <option value="41">Chypre</option>
                    <option value="42">Colombie</option>
                    <option value="43">Comores</option>
                    <option value="44">République du Congo</option>
                    <option value="45">République démocratique du Congo</option>
                    <option value="46">Corée du Nord</option>
                    <option value="47">Corée du Sud</option>
                    <option value="48">Rica Costa Rica</option>
                    <option value="49">Côte d Ivoire</option>
                    <option value="50">Croatie Croatie</option>
                    <option value="51">Cuba</option>
                    <option value="52">Danemark</option>
                    <option value="53">Djibouti</option>
                    <option value="54">République dominicaine</option>
                    <option value="55">Dominique</option>
                    <option value="56">Égypte</option>
                    <option value="57">Émirats arabes unis</option>
                    <option value="58">Équateur</option>
                    <option value="59">Érythrée</option>
                    <option value="60">Espagne</option>
                    <option value="61">Estonie</option>
                    <option value="62">États-Unis</option>
                    <option value="63">Éthiopie</option>
                    <option value="64">Fidji</option>
                    <option value="65">Finlande</option>
                    <option value="66">Gabon</option>
                    <option value="67">Gambie</option>
                    <option value="68">Géorgie</option>
                    <option value="69">Ghana</option>
                    <option value="70">Grèce</option>
                    <option value="71">Grenade</option>
                    <option value="72">Guatemala</option>
                    <option value="73">Guinée</option>
                    <option value="74">Guinée-Bissau</option>
                    <option value="75">Guinée</option>
                    <option value="76">Guyana</option>
                    <option value="77">Haïti</option>
                    <option value="78">Honduras</option>
                    <option value="79">Hongrie</option>
                    <option value="80">Inde</option>
                    <option value="81">Indonésie</option>
                    <option value="82">Irak</option>
                    <option value="83">Iran</option>
                    <option value="84">Irlande</option>
                    <option value="85">Islande</option>
                    <option value="86">Israël</option>
                    <option value="87">Italie</option>
                    <option value="88">Jamaïque</option>
                    <option value="89">Japon</option>
                    <option value="90">Jordanie</option>
                    <option value="91">Kazakhstan</option>
                    <option value="92">Kenya</option>
                    <option value="93">Kirghizistan</option>
                    <option value="94">Kiribati</option>
                    <option value="95">Koweït</option>
                    <option value="96">Laos</option>
                    <option value="97">Lesotho</option>
                    <option value="98">Lettonie</option>
                    <option value="99">Liban</option>
                    <option value="100">Liberia</option>
                    <option value="101">Libye</option>
                    <option value="102">Liechtenstein</option>
                    <option value="103">Lituanie</option>
                    <option value="104">Luxembourg</option>
                    <option value="105">Macédoine</option>
                    <option value="106">Madagascar</option>
                    <option value="107">Malaisie</option>
                    <option value="108">Malawi</option>
                    <option value="109">Maldives</option>
                    <option value="110">Mali</option>
                    <option value="111">Malte</option>
                    <option value="112">Maroc</option>
                    <option value="113">Îles Marshall</option>
                    <option value="114">Maurice</option>
                    <option value="115">Mauritanie</option>
                    <option value="116">Mexique</option>
                    <option value="117">Micronésie</option>
                    <option value="118">Moldavie</option>
                    <option value="119">Monaco</option>
                    <option value="120">Mongolie</option>
                    <option value="121">Monténégro</option>
                    <option value="122">Mozambique</option>
                    <option value="123">Namibie</option>
                    <option value="124">Nauru</option>
                    <option value="125">Népal</option>
                    <option value="126">Nicaragua</option>
                    <option value="127">Niger</option>
                    <option value="128">Nigeria</option>
                    <option value="129">Norvège</option>
                    <option value="130">Nouvelle-Zélande</option>
                    <option value="131">Oman</option>
                    <option value="132">Ouganda</option>
                    <option value="133">Ouzbékistan</option>
                    <option value="134">Pakistan</option>
                    <option value="135">Palaos</option>
                    <option value="136">Panama</option>
                    <option value="137">Papouasie-Nouvelle-Guinée</option>
                    <option value="138">Paraguay</option>
                    <option value="139">Pays-Bas</option>
                    <option value="140">Pérou</option>
                    <option value="141">Philippines</option>
                    <option value="142">Pologne</option>
                    <option value="143">Portugal</option>
                    <option value="144">Qatar</option>
                    <option value="145">Russie</option>
                    <option value="146">Salvador</option>
                    <option value="147">Syrie</option>
                    <option value="148">République tchèque</option>
                    <option value="149">Tanzanie</option>
                    <option value="150">Roumanie</option>
                    <option value="151">Royaume-Uni</option>
                    <option value="152">Rwanda</option>
                    <option value="153">Sainte-Lucie</option>
                    <option value="154">Saint-Christophe-et-Niévès</option>
                    <option value="155">Saint-Marin</option>
                    <option value="156">Saint-Vincent-et-les Grenadines</option>
                    <option value="157">Salomon</option>
                    <option value="158">Samoa</option>
                    <option value="159">Tomé-et-Principe</option>
                    <option value="160">Sénégal</option>
                    <option value="161">Serbie</option>
                    <option value="162">Seychelles</option>
                    <option value="163">Sierra Leone</option>
                    <option value="164">Singapour</option>
                    <option value="165">Slovaquie</option>
                    <option value="166">Slovénie</option>
                    <option value="167">Somalie</option>
                    <option value="168">Soudan</option>
                    <option value="169">Soudan du Sud</option>
                    <option value="170">Sri Lanka</option>
                    <option value="171">Suède</option>
                    <option value="172">Suisse</option>
                    <option value="173">Suriname</option>
                    <option value="174">Swaziland</option>
                    <option value="175">Tadjikistan</option>
                    <option value="176">Tchad</option>
                    <option value="177">Thaïlande</option>
                    <option value="178">Timor oriental</option>
                    <option value="179">Togo</option>
                    <option value="180">Tonga</option>
                    <option value="181">Trinité-et-Tobago</option>
                    <option value="182">Tunisie</option>
                    <option value="183">Turkménistan</option>
                    <option value="184">Turquie</option>
                    <option value="185">Tuvalu</option>
                    <option value="186">Ukraine</option>
                    <option value="187">Uruguay</option>
                    <option value="188">Vanuatu</option>
                    <option value="189">Venezuela</option>
                    <option value="190">Viêt Nam</option>
                    <option value="191">Yémen</option>
                    <option value="192">Zambie</option>
                    <option value="193">Zimbabwe</option>
                </select>
                <div class="cb-holder checked">
                    <label for="inscription_check_adresse">Mon adresse de correspondance est la même que mon adresse fiscale. Sinon, décochez la case et indiquez votre adresse de correspondance.</label>
                    <input checked="checked" type="checkbox" class="custom-chekckbox" name="inscription_check_adresse" id="inscription_check_adresse">
                </div>
                <div id="inscription_correspondance">
                    <input type="text" id="inscription_adresse_correspondance" name="adresse" placeholder="Adresse" maxlength="255">
                    <input type="text" id="inscription_ville_correspondance" name="ville" placeholder="Ville" maxlength="255">
                    <input type="text" id="inscription_cp_correspondance" name="cp" placeholder="Code postal" maxlength="5">
                    <select id="inscription_id_pays_correspondance" name="id_pays" class="custom-select">
                        <option value="">Pays</option>
                        <option value="1">France</option>
                        <option value="2">Afghanistan</option>
                        <option value="3">Afrique du Sud</option>
                        <option value="4">Albanie</option>
                        <option value="5">Algérie</option>
                        <option value="6">Allemagne</option>
                        <option value="7">Andorre</option>
                        <option value="8">Angola</option>
                        <option value="9">Antigua-et-Barbuda</option>
                        <option value="10">Arabie saoudite</option>
                        <option value="11">Argentine</option>
                        <option value="12">Arménie</option>
                        <option value="13">Australie</option>
                        <option value="14">Autriche</option>
                        <option value="15">Azerbaïdjan</option>
                        <option value="16">Bahamas</option>
                        <option value="17">Bahreïn</option>
                        <option value="18">Bangladesh</option>
                        <option value="19">Barbade</option>
                        <option value="20">Biélorussie</option>
                        <option value="21">Belgique</option>
                        <option value="22">Belize</option>
                        <option value="23">Bénin</option>
                        <option value="24">Bhoutan</option>
                        <option value="25">Birmanie</option>
                        <option value="26">Bolivie</option>
                        <option value="27">Bosnie-Herzégovine</option>
                        <option value="28">Botswana</option>
                        <option value="29">Brésil</option>
                        <option value="30">Brunei</option>
                        <option value="31">Bulgarie</option>
                        <option value="32">Burkina Faso</option>
                        <option value="33">Burundi</option>
                        <option value="34">Cambodge</option>
                        <option value="35">Cameroun</option>
                        <option value="36">Canada</option>
                        <option value="37">Cap-Vert</option>
                        <option value="38">République centrafricaine</option>
                        <option value="39">Chili</option>
                        <option value="40">Chine</option>
                        <option value="41">Chypre</option>
                        <option value="42">Colombie</option>
                        <option value="43">Comores</option>
                        <option value="44">République du Congo</option>
                        <option value="45">République démocratique du Congo</option>
                        <option value="46">Corée du Nord</option>
                        <option value="47">Corée du Sud</option>
                        <option value="48">Rica Costa Rica</option>
                        <option value="49">Côte d Ivoire</option>
                        <option value="50">Croatie Croatie</option>
                        <option value="51">Cuba</option>
                        <option value="52">Danemark</option>
                        <option value="53">Djibouti</option>
                        <option value="54">République dominicaine</option>
                        <option value="55">Dominique</option>
                        <option value="56">Égypte</option>
                        <option value="57">Émirats arabes unis</option>
                        <option value="58">Équateur</option>
                        <option value="59">Érythrée</option>
                        <option value="60">Espagne</option>
                        <option value="61">Estonie</option>
                        <option value="62">États-Unis</option>
                        <option value="63">Éthiopie</option>
                        <option value="64">Fidji</option>
                        <option value="65">Finlande</option>
                        <option value="66">Gabon</option>
                        <option value="67">Gambie</option>
                        <option value="68">Géorgie</option>
                        <option value="69">Ghana</option>
                        <option value="70">Grèce</option>
                        <option value="71">Grenade</option>
                        <option value="72">Guatemala</option>
                        <option value="73">Guinée</option>
                        <option value="74">Guinée-Bissau</option>
                        <option value="75">Guinée</option>
                        <option value="76">Guyana</option>
                        <option value="77">Haïti</option>
                        <option value="78">Honduras</option>
                        <option value="79">Hongrie</option>
                        <option value="80">Inde</option>
                        <option value="81">Indonésie</option>
                        <option value="82">Irak</option>
                        <option value="83">Iran</option>
                        <option value="84">Irlande</option>
                        <option value="85">Islande</option>
                        <option value="86">Israël</option>
                        <option value="87">Italie</option>
                        <option value="88">Jamaïque</option>
                        <option value="89">Japon</option>
                        <option value="90">Jordanie</option>
                        <option value="91">Kazakhstan</option>
                        <option value="92">Kenya</option>
                        <option value="93">Kirghizistan</option>
                        <option value="94">Kiribati</option>
                        <option value="95">Koweït</option>
                        <option value="96">Laos</option>
                        <option value="97">Lesotho</option>
                        <option value="98">Lettonie</option>
                        <option value="99">Liban</option>
                        <option value="100">Liberia</option>
                        <option value="101">Libye</option>
                        <option value="102">Liechtenstein</option>
                        <option value="103">Lituanie</option>
                        <option value="104">Luxembourg</option>
                        <option value="105">Macédoine</option>
                        <option value="106">Madagascar</option>
                        <option value="107">Malaisie</option>
                        <option value="108">Malawi</option>
                        <option value="109">Maldives</option>
                        <option value="110">Mali</option>
                        <option value="111">Malte</option>
                        <option value="112">Maroc</option>
                        <option value="113">Îles Marshall</option>
                        <option value="114">Maurice</option>
                        <option value="115">Mauritanie</option>
                        <option value="116">Mexique</option>
                        <option value="117">Micronésie</option>
                        <option value="118">Moldavie</option>
                        <option value="119">Monaco</option>
                        <option value="120">Mongolie</option>
                        <option value="121">Monténégro</option>
                        <option value="122">Mozambique</option>
                        <option value="123">Namibie</option>
                        <option value="124">Nauru</option>
                        <option value="125">Népal</option>
                        <option value="126">Nicaragua</option>
                        <option value="127">Niger</option>
                        <option value="128">Nigeria</option>
                        <option value="129">Norvège</option>
                        <option value="130">Nouvelle-Zélande</option>
                        <option value="131">Oman</option>
                        <option value="132">Ouganda</option>
                        <option value="133">Ouzbékistan</option>
                        <option value="134">Pakistan</option>
                        <option value="135">Palaos</option>
                        <option value="136">Panama</option>
                        <option value="137">Papouasie-Nouvelle-Guinée</option>
                        <option value="138">Paraguay</option>
                        <option value="139">Pays-Bas</option>
                        <option value="140">Pérou</option>
                        <option value="141">Philippines</option>
                        <option value="142">Pologne</option>
                        <option value="143">Portugal</option>
                        <option value="144">Qatar</option>
                        <option value="145">Russie</option>
                        <option value="146">Salvador</option>
                        <option value="147">Syrie</option>
                        <option value="148">République tchèque</option>
                        <option value="149">Tanzanie</option>
                        <option value="150">Roumanie</option>
                        <option value="151">Royaume-Uni</option>
                        <option value="152">Rwanda</option>
                        <option value="153">Sainte-Lucie</option>
                        <option value="154">Saint-Christophe-et-Niévès</option>
                        <option value="155">Saint-Marin</option>
                        <option value="156">Saint-Vincent-et-les Grenadines</option>
                        <option value="157">Salomon</option>
                        <option value="158">Samoa</option>
                        <option value="159">Tomé-et-Principe</option>
                        <option value="160">Sénégal</option>
                        <option value="161">Serbie</option>
                        <option value="162">Seychelles</option>
                        <option value="163">Sierra Leone</option>
                        <option value="164">Singapour</option>
                        <option value="165">Slovaquie</option>
                        <option value="166">Slovénie</option>
                        <option value="167">Somalie</option>
                        <option value="168">Soudan</option>
                        <option value="169">Soudan du Sud</option>
                        <option value="170">Sri Lanka</option>
                        <option value="171">Suède</option>
                        <option value="172">Suisse</option>
                        <option value="173">Suriname</option>
                        <option value="174">Swaziland</option>
                        <option value="175">Tadjikistan</option>
                        <option value="176">Tchad</option>
                        <option value="177">Thaïlande</option>
                        <option value="178">Timor oriental</option>
                        <option value="179">Togo</option>
                        <option value="180">Tonga</option>
                        <option value="181">Trinité-et-Tobago</option>
                        <option value="182">Tunisie</option>
                        <option value="183">Turkménistan</option>
                        <option value="184">Turquie</option>
                        <option value="185">Tuvalu</option>
                        <option value="186">Ukraine</option>
                        <option value="187">Uruguay</option>
                        <option value="188">Vanuatu</option>
                        <option value="189">Venezuela</option>
                        <option value="190">Viêt Nam</option>
                        <option value="191">Yémen</option>
                        <option value="192">Zambie</option>
                        <option value="193">Zimbabwe</option>
                    </select>
                    <div class="clear"></div>
                </div>
                <input type="tel" id="inscription_telephone" name="telephone" placeholder="Téléphone*" maxlength="10">
                <select id="inscription_id_nationalite" name="id_nationalite" class="custom-select">
                    <option value="">Nationalité*</option>
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
                    <option value="12">Grèce</option>
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
                    <option value="23">Norvège</option>
                    <option value="24">Pays-Bas</option>
                    <option value="25">Pologne</option>
                    <option value="26">Portugal</option>
                    <option value="27">République tchèque</option>
                    <option value="28">Roumanie</option>
                    <option value="29">Royaume-Uni</option>
                    <option value="30">Saint-Marin</option>
                    <option value="31">Slovaquie</option>
                    <option value="32">Slovénie</option>
                    <option value="33">Suède</option>
                    <option value="34">Suisse</option>
                </select>
                <div class="clear"></div>
                <input type="text" id="inscription_date_naissance" name="date_naissance" placeholder="Date de naissance (jj/mm/aaaa)*" maxlength="10">
                <p id="errorAge"></p>
                <input type="text" id="inscription_commune_naissance" name="commune_naissance" placeholder="Commune de naissance*" maxlength="255">
                <select id="inscription_id_pays_naissance" name="id_pays_naissance" class="custom-select">
                    <option value="">Pays de naissance*</option>
                    <option value="1">France</option>
                    <option value="2">Afghanistan</option>
                    <option value="3">Afrique du Sud</option>
                    <option value="4">Albanie</option>
                    <option value="5">Algérie</option>
                    <option value="6">Allemagne</option>
                    <option value="7">Andorre</option>
                    <option value="8">Angola</option>
                    <option value="9">Antigua-et-Barbuda</option>
                    <option value="10">Arabie saoudite</option>
                    <option value="11">Argentine</option>
                    <option value="12">Arménie</option>
                    <option value="13">Australie</option>
                    <option value="14">Autriche</option>
                    <option value="15">Azerbaïdjan</option>
                    <option value="16">Bahamas</option>
                    <option value="17">Bahreïn</option>
                    <option value="18">Bangladesh</option>
                    <option value="19">Barbade</option>
                    <option value="20">Biélorussie</option>
                    <option value="21">Belgique</option>
                    <option value="22">Belize</option>
                    <option value="23">Bénin</option>
                    <option value="24">Bhoutan</option>
                    <option value="25">Birmanie</option>
                    <option value="26">Bolivie</option>
                    <option value="27">Bosnie-Herzégovine</option>
                    <option value="28">Botswana</option>
                    <option value="29">Brésil</option>
                    <option value="30">Brunei</option>
                    <option value="31">Bulgarie</option>
                    <option value="32">Burkina Faso</option>
                    <option value="33">Burundi</option>
                    <option value="34">Cambodge</option>
                    <option value="35">Cameroun</option>
                    <option value="36">Canada</option>
                    <option value="37">Cap-Vert</option>
                    <option value="38">République centrafricaine</option>
                    <option value="39">Chili</option>
                    <option value="40">Chine</option>
                    <option value="41">Chypre</option>
                    <option value="42">Colombie</option>
                    <option value="43">Comores</option>
                    <option value="44">République du Congo</option>
                    <option value="45">République démocratique du Congo</option>
                    <option value="46">Corée du Nord</option>
                    <option value="47">Corée du Sud</option>
                    <option value="48">Rica Costa Rica</option>
                    <option value="49">Côte d Ivoire</option>
                    <option value="50">Croatie Croatie</option>
                    <option value="51">Cuba</option>
                    <option value="52">Danemark</option>
                    <option value="53">Djibouti</option>
                    <option value="54">République dominicaine</option>
                    <option value="55">Dominique</option>
                    <option value="56">Égypte</option>
                    <option value="57">Émirats arabes unis</option>
                    <option value="58">Équateur</option>
                    <option value="59">Érythrée</option>
                    <option value="60">Espagne</option>
                    <option value="61">Estonie</option>
                    <option value="62">États-Unis</option>
                    <option value="63">Éthiopie</option>
                    <option value="64">Fidji</option>
                    <option value="65">Finlande</option>
                    <option value="66">Gabon</option>
                    <option value="67">Gambie</option>
                    <option value="68">Géorgie</option>
                    <option value="69">Ghana</option>
                    <option value="70">Grèce</option>
                    <option value="71">Grenade</option>
                    <option value="72">Guatemala</option>
                    <option value="73">Guinée</option>
                    <option value="74">Guinée-Bissau</option>
                    <option value="75">Guinée</option>
                    <option value="76">Guyana</option>
                    <option value="77">Haïti</option>
                    <option value="78">Honduras</option>
                    <option value="79">Hongrie</option>
                    <option value="80">Inde</option>
                    <option value="81">Indonésie</option>
                    <option value="82">Irak</option>
                    <option value="83">Iran</option>
                    <option value="84">Irlande</option>
                    <option value="85">Islande</option>
                    <option value="86">Israël</option>
                    <option value="87">Italie</option>
                    <option value="88">Jamaïque</option>
                    <option value="89">Japon</option>
                    <option value="90">Jordanie</option>
                    <option value="91">Kazakhstan</option>
                    <option value="92">Kenya</option>
                    <option value="93">Kirghizistan</option>
                    <option value="94">Kiribati</option>
                    <option value="95">Koweït</option>
                    <option value="96">Laos</option>
                    <option value="97">Lesotho</option>
                    <option value="98">Lettonie</option>
                    <option value="99">Liban</option>
                    <option value="100">Liberia</option>
                    <option value="101">Libye</option>
                    <option value="102">Liechtenstein</option>
                    <option value="103">Lituanie</option>
                    <option value="104">Luxembourg</option>
                    <option value="105">Macédoine</option>
                    <option value="106">Madagascar</option>
                    <option value="107">Malaisie</option>
                    <option value="108">Malawi</option>
                    <option value="109">Maldives</option>
                    <option value="110">Mali</option>
                    <option value="111">Malte</option>
                    <option value="112">Maroc</option>
                    <option value="113">Îles Marshall</option>
                    <option value="114">Maurice</option>
                    <option value="115">Mauritanie</option>
                    <option value="116">Mexique</option>
                    <option value="117">Micronésie</option>
                    <option value="118">Moldavie</option>
                    <option value="119">Monaco</option>
                    <option value="120">Mongolie</option>
                    <option value="121">Monténégro</option>
                    <option value="122">Mozambique</option>
                    <option value="123">Namibie</option>
                    <option value="124">Nauru</option>
                    <option value="125">Népal</option>
                    <option value="126">Nicaragua</option>
                    <option value="127">Niger</option>
                    <option value="128">Nigeria</option>
                    <option value="129">Norvège</option>
                    <option value="130">Nouvelle-Zélande</option>
                    <option value="131">Oman</option>
                    <option value="132">Ouganda</option>
                    <option value="133">Ouzbékistan</option>
                    <option value="134">Pakistan</option>
                    <option value="135">Palaos</option>
                    <option value="136">Panama</option>
                    <option value="137">Papouasie-Nouvelle-Guinée</option>
                    <option value="138">Paraguay</option>
                    <option value="139">Pays-Bas</option>
                    <option value="140">Pérou</option>
                    <option value="141">Philippines</option>
                    <option value="142">Pologne</option>
                    <option value="143">Portugal</option>
                    <option value="144">Qatar</option>
                    <option value="145">Russie</option>
                    <option value="146">Salvador</option>
                    <option value="147">Syrie</option>
                    <option value="148">République tchèque</option>
                    <option value="149">Tanzanie</option>
                    <option value="150">Roumanie</option>
                    <option value="151">Royaume-Uni</option>
                    <option value="152">Rwanda</option>
                    <option value="153">Sainte-Lucie</option>
                    <option value="154">Saint-Christophe-et-Niévès</option>
                    <option value="155">Saint-Marin</option>
                    <option value="156">Saint-Vincent-et-les Grenadines</option>
                    <option value="157">Salomon</option>
                    <option value="158">Samoa</option>
                    <option value="159">Tomé-et-Principe</option>
                    <option value="160">Sénégal</option>
                    <option value="161">Serbie</option>
                    <option value="162">Seychelles</option>
                    <option value="163">Sierra Leone</option>
                    <option value="164">Singapour</option>
                    <option value="165">Slovaquie</option>
                    <option value="166">Slovénie</option>
                    <option value="167">Somalie</option>
                    <option value="168">Soudan</option>
                    <option value="169">Soudan du Sud</option>
                    <option value="170">Sri Lanka</option>
                    <option value="171">Suède</option>
                    <option value="172">Suisse</option>
                    <option value="173">Suriname</option>
                    <option value="174">Swaziland</option>
                    <option value="175">Tadjikistan</option>
                    <option value="176">Tchad</option>
                    <option value="177">Thaïlande</option>
                    <option value="178">Timor oriental</option>
                    <option value="179">Togo</option>
                    <option value="180">Tonga</option>
                    <option value="181">Trinité-et-Tobago</option>
                    <option value="182">Tunisie</option>
                    <option value="183">Turkménistan</option>
                    <option value="184">Turquie</option>
                    <option value="185">Tuvalu</option>
                    <option value="186">Ukraine</option>
                    <option value="187">Uruguay</option>
                    <option value="188">Vanuatu</option>
                    <option value="189">Venezuela</option>
                    <option value="190">Viêt Nam</option>
                    <option value="191">Yémen</option>
                    <option value="192">Zambie</option>
                    <option value="193">Zimbabwe</option>
                </select>
                <div class="clear"></div>
                <div class="cb-holder">
                    <label id="label_checkbox_inscription_cgv" for="inscription_cgv"></label>
                    <p id="label_inscription_cgv">J'ai lu et j'accepte les
                        <a href="https://www.unilend.fr/cgv_preteurs" target="_blank">Conditions Générales de Vente</a> d'Unilend
                    </p>
                    <input type="checkbox" class="custom-chekckbox" name="inscription_cgv" id="inscription_cgv">
                </div>
                <button type="submit" id="inscription_submit2" name="valider">je finis mon inscription<br/><span>en transmettant dès maintenant mes documents</span></button>
                <p>Ou</p>
                <button type="submit" id="voir_projets" name="valider-projets">Voir les projets<br/><span>et finir mon inscription plus tard</span></button>
                <p class="champs_obligatoires">* Champs obligatoires</p>
                <div id="tracking"></div>
                <div class="clear"></div>
            </div>
        </form>
    </section>
</div>
<div id="home" class="wrapper100">
    <section class="wrapper">
        <div class="logo disp_0">
            <div class="disp_1">
                <a href="#" id="logo"></a>
            </div>
            <div class="disp_2 t_right">
                <div class="">
                </div>
            </div>
        </div>

        <h1>Prêtez directement aux entreprises</h1>
        <h2>Recevez chaque mois vos intérêts</h2>
        <div class="w_1 bloc_mac">
            <div class="center">
                <a href="#bloc_mentions" class="macaron"><img src="img/macaron.png"/></a>
            </div>
        </div>

        <ul>
            <li>
                <h3><span>1</span> Choisissez</h3>
                <p>
                    Sélectionnez les entreprises auxquelles vous souhaitez prêter.<br/>
                    Leur capacité de remboursement a été soigneusement étudiée.
                </p>
            </li>
            <li>
                <h3><span>2</span> PRÊTEZ À PARTIR DE 20€</h3>
                <p>
                    Choisissez le montant (à partir de 20€) et le taux (entre 4% et 10%)<br/>
                    des prêts que vous souhaitez réaliser‏.
                </p>
            </li>
            <li>
                <h3><span>3</span> Recevez des intérêts</h3>
                <p>
                    Tous les mois, vous recevez vos remboursements et<br/>
                    vos intérêts, que vous pouvez prêter à nouveau.
                </p>
            </li>
        </ul>
        <div class="conditions" id="bloc_mentions">Conditions de l'offre</div>
        <div class="mentions">
            Offre valable jusqu’au 31/12/2014 dans la limite de cinq cent nouveaux inscrits et réservée aux personnes physiques, capables, majeures.

            L’offre est réservée aux nouveaux inscrits dont l’inscription est validée par Unilend. Seules les personnes physiques de nationalité française, ou possédant une nationalité d’un pays de l’Espace Economique Européen, et disposant d’un compte bancaire en euros en France pourront bénéficier de l’offre.

            Les 20€ seront versés sur le compte Unilend du client dans le mois suivant la validation du compte et ne pourront servir qu’à prêter sur Unilend. Le client pourra prêter cette somme à l’entreprise de son choix parmi les entreprises présentées sur le site et ce dans un délai de 3 mois suivant la validation de son inscription. En cas de non utilisation de cette somme dans ce délai pour un prêt, Unilend se réserve le droit de reprendre ce montant non utilisé.

            Une seule prime de 20€ par personne et par compte Unilend est octroyée. Offre non cumulable avec toute offre commerciale ou de parrainage en cours. Cette offre est régie par la loi française.

            Voilà, c’est tout. Si vous avez lu jusqu’ici, il ne vous reste plus qu’à tester !
        </div>
        <div class="scroll2"><a href="#pourquoi_unilend"></a></div>
    </section>
</div><!-- home -->

<div id="pourquoi_unilend" class="wrapper100 bg_gris">
    <section class="wrapper">
        <a href="#" id="logo"><img src="img/unilend.png" alt="Unilend - Vos intérêts se rencontrent" width="252" height="60"></a>
        <h1>Pourquoi <span>Unilend ?</span></h1>
        <ul>
            <li>Bénéficiez d’un compte totalement gratuit</li>
            <li>Choisissez librement le taux</li>
            <li>Prêtez à des taux attractifs entre 4% et 10%</li>
            <li>Recevez des revenus mensuels</li>
            <li>Faites travaillez votre argent dans les PME françaises</li>
        </ul>
        <p class="fleche">Inscrivez-vous et reprenez le pouvoir sur votre argent</p>
        <div class="scroll2"><a href="#projet_analyse"></a></div>
    </section>
</div><!-- pourquoi_unilend -->

<div id="projet_analyse" class="wrapper100 bg_gris">
    <section class="wrapper">
        <a href="#" id="logo"><img src="img/unilend.png" alt="Unilend - Vos intérêts se rencontrent" width="252" height="60"></a>
        <h1>Chaque projet est <span>rigoureusement analysé</span></h1>
        <ul id="projet_analyse_ul">
            <li>
                Analyse des données financières fournies par Altares,
                membre du réseau Dun & Bradstreet, leader mondial
                de la fourniture de données sur les sociétés
            </li>
            <li>
                Étude approfondie des 3 derniers bilans et<br/>
                du projet de l’entreprise
            </li>
            <li>Examen des capacités de remboursement</li>
        </ul>
        <p id="projet_analyse_tous"><img src="img/picto_fl.png"/></p>
        <p id="projet_analyse_tous2">
            Les projets à financer sur Unilend présentent
            une capacité de remboursement éprouvée
        </p>
        <div class="clear"></div>
        <h3>Exemples de projets financés</h3>
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
                            <img src="img/complement-europe.png" alt="Complément Europe" width="160" height="120">
                            <p>Complément Europe</p>
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
        <p class="fleche">Choisissez vos projets. Pour les découvrir, inscrivez-vous</p>
        <div class="scroll2"><a href="#simulez_vos_interets"></a></div>
    </section>
</div><!-- projet_analyse -->

<div id="simulez_vos_interets" class="wrapper100">
    <section class="wrapper">
        <a href="#" id="logo"><img src="img/unilend.png" alt="Unilend - Vos intérêts se rencontrent" width="252" height="60"></a>
        <h1>Simulez <span>vos intérêts</span></h1>

        <section id="pret">
            <h1>Vous prêtez</h1>
            <div id="pret_left">
                <div>
                    <p>La somme de</p>
                    <input type="text" id="pret_somme" name="pret_somme" placeholder="ex: 1 000">
                    <div class="clear"></div>
                </div>
                <div>
                    <p>Au taux de</p>
                    <select name="pret_taux" id="pret_taux" class="custom-select">
                        <option value="">10,0 %</option>
                        <option selected value="0.100">10,0 %</option>
                        <option value="0.099">9,9 %</option>
                        <option value="0.098">9,8 %</option>
                        <option value="0.097">9,7 %</option>
                        <option value="0.096">9,6 %</option>
                        <option value="0.095">9,5 %</option>
                        <option value="0.094">9,4 %</option>
                        <option value="0.093">9,3 %</option>
                        <option value="0.092">9,2 %</option>
                        <option value="0.091">9,1 %</option>
                        <option value="0.090">9,0 %</option>
                        <option value="0.089">8,9 %</option>
                        <option value="0.088">8,8 %</option>
                        <option value="0.087">8,7 %</option>
                        <option value="0.086">8,6 %</option>
                        <option value="0.085">8,5 %</option>
                        <option value="0.084">8,4 %</option>
                        <option value="0.083">8,3 %</option>
                        <option value="0.082">8,2 %</option>
                        <option value="0.081">8,1 %</option>
                        <option value="0.080">8,0 %</option>
                        <option value="0.079">7,9 %</option>
                        <option value="0.078">7,8 %</option>
                        <option value="0.077">7,7 %</option>
                        <option value="0.076">7,6 %</option>
                        <option value="0.075">7,5 %</option>
                        <option value="0.074">7,4 %</option>
                        <option value="0.073">7,3 %</option>
                        <option value="0.072">7,2 %</option>
                        <option value="0.071">7,1 %</option>
                        <option value="0.070">7,0 %</option>
                        <option value="0.069">6,9 %</option>
                        <option value="0.068">6,8 %</option>
                        <option value="0.067">6,7 %</option>
                        <option value="0.066">6,6 %</option>
                        <option value="0.065">6,5 %</option>
                        <option value="0.064">6,4 %</option>
                        <option value="0.063">6,3 %</option>
                        <option value="0.062">6,2 %</option>
                        <option value="0.061">6,1 %</option>
                        <option value="0.060">6,0 %</option>
                        <option value="0.059">5,9 %</option>
                        <option value="0.058">5,8 %</option>
                        <option value="0.057">5,7 %</option>
                        <option value="0.056">5,6 %</option>
                        <option value="0.055">5,5 %</option>
                        <option value="0.054">5,4 %</option>
                        <option value="0.053">5,3 %</option>
                        <option value="0.055">5,2 %</option>
                        <option value="0.051">5,1 %</option>
                        <option value="0.050">5,0 %</option>
                        <option value="0.049">4,9 %</option>
                        <option value="0.048">4,8 %</option>
                        <option value="0.047">4,7 %</option>
                        <option value="0.046">4,6 %</option>
                        <option value="0.048">4,5 %</option>
                        <option value="0.044">4,4 %</option>
                        <option value="0.043">4,3 %</option>
                        <option value="0.042">4,2 %</option>
                        <option value="0.041">4,1 %</option>
                        <option value="0.040">4,0 %</option>
                    </select>
                    <div class="clear"></div>
                </div>
            </div>
            <div id="pret_right">
                <p>Durée du prêt</p>
                <div id="dc_slider-step"></div>
                <ul>
                    <li>
                        <p>24</p>
                        <p>mois</p>
                    </li>
                    <li>
                        <p>36</p>
                        <p>mois</p>
                    </li>
                    <li>
                        <p>48</p>
                        <p>mois</p>
                    </li>
                    <li>
                        <p>60</p>
                        <p>mois</p>
                    </li>
                </ul>
                <div class="clear"></div>
            </div>
            <div class="clear"></div>
            <button id="simuler">Simuler</button>
            <p id="erreur_simulation"></p>
        </section>

        <section id="recu">
            <div>
                <h1>Vous recevez</h1>
                <div id="recu_left">
                    <div>
                        <p>La somme de</p>
                        <p id="recu_somme">1 000</p>
                        <div class="clear"></div>
                    </div>
                </div>
                <div id="recu_right">
                    <p>
                        soit <span>25,36</span> €<br/>
                        pendant <span>48</span> mois
                    </p>
                </div>
                <div class="clear"></div>
                <div id="recu_result">
                    <p>
                        Soit <span><span>217,40</span> €</span><br/>
                        <span>d'intérêts bruts</span>
                    </p>
                    <p>=</p>
                    <p>
                        <span>21,74</span><br/>
                        du montant prêté
                    </p>
                    <div class="clear"></div>
                </div>
            </div>
        </section>

        <p class="fleche">Inscrivez-vous et commencez à prêter en quelques clics</p>
        <div class="scroll2"><a href="#chiffres"></a></div>
    </section>
</div><!-- simulez_vos_interets -->

<div id="presse" class="wrapper100">
    <section class="wrapper">
        <a href="#" id="logo"><img src="img/unilend.png" alt="Unilend - Vos intérêts se rencontrent" width="252" height="60"></a>
        <h1>La presse parle <span>d'Unilend</span></h1>
        <img id="presse_logos" src="img/presse.jpg" alt="BFM Business - Le Monde - Capital - Le Point - Le Nouvel Observateur - L'Express - Oiuest France - 01net. - Le Figaro Economie" width="354" height="199">
        <div class="clear"></div>
        <div class="presse_liste">
            <section>
                <img id="video" src="img/le-monde.png" alt="Le Monde" width="90" height="40">
                <p>"Nouveauté, Unilend permet de prêter de l’argent directement aux PME"</p>
                <p>21/10/2013</p>
                <div class="clear"></div>
            </section>
            <section>
                <img id="video" src="img/capital.png" alt="Capital" width="90" height="40">
                <p>"Les internautes peuvent désormais prêter en direct à des PME"</p>
                <p>30/01/2014</p>
                <div class="clear"></div>
            </section>
            <section>
                <img id="video" src="img/figaro.png" alt="Le Figaro Economie" width="90" height="40">
                <p>"Les gains peuvent atteindre 10 % l’an"</p>
                <p>17/05/2014</p>
                <div class="clear"></div>
            </section>
            <section>
                <img id="video" src="img/le-particulier.png" alt="Le Particulier" width="90" height="40">
                <p>"Percevez des intérêts en finançant des projets d’entreprises"</p>
                <p>01/06/2014</p>
                <div class="clear"></div>
            </section>
        </div>

        <div class="presse_liste">
            <section>
                <img id="video" src="img/presse1.jpg" alt="" width="80" height="80">
                <p>"Il nous faut développer des sources fiables de financements non bancaires, tel que le financement participatif."</p>
                <p>11/09/2014</p>
                <p>Mario Draghi, Président de la Banque Centrale Européenne</p>
                <div class="clear"></div>
            </section>
            <section>
                <img id="video" src="img/presse2.jpg" alt="" width="80" height="80">
                <p>"Il faut des financements alternatifs pour accompagner les entreprises."</p>
                <p>15/09/2014</p>
                <p>Emmanuel Macron, ministre de l'Economie, de l'Industrie et du Numérique</p>
                <div class="clear"></div>
            </section>
        </div>
        <div class="clear"></div>
        <p class="fleche">Inscrivez-vous et découvrez l’efficacité d’Unilend pour investir votre argent</p>
        <div class="scroll2"><a href="#qui_sommes_nous"></a></div>
    </section>
</div><!-- presse -->

<div id="qui_sommes_nous" class="wrapper100 bg_gris">
    <section class="wrapper">
        <a href="#" id="logo"><img src="img/unilend.png" alt="Unilend - Vos intérêts se rencontrent" width="252" height="60"></a>
        <h1>Qui sommes-nous ?</h1>
        <p>Unilend propose une nouvelle forme de finance pour permettre :</p>
        <ul>
            <li>Aux entreprises d'emprunter directement et simplement auprès du grand public</li>
            <li>Aux épargnants de prêter de l'argent directement aux entreprises en recevant des intérêts.</li>
        </ul>
        <p>
            Le service de paiement <span>Unilend</span> est distribué par la Société française pour le financement des PME - SFF PME SAS, agent prestataire de services de paiement mandaté par la SFPMEI et déclaré auprès de l'Autorité de contrôle prudentiel et de résolution (ACPR).
        </p>
        <br/>
        <p class="cadre_1">
            <strong>Prêter présente un risque de non-remboursement : répartissez bien vos prêts et ne prêtez que de l'argent dont vous n'avez pas besoin immédiatement.</strong>
        </p>
        <h2>Nos partenaires</h2>
        <div>
            <ul class="bloc_inline">
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
<!-- // <script src="js/jquery-1.9.1.min.js"></script> -->
<script type="text/javascript">window.jQuery || document.write('<script type="text/javascript" src="js/jquery-1.9.1.min.js"><\/script>')</script>
<script src="js/jquery.c2selectbox.js" type="text/javascript"></script>
<script src="js/jquery.nouislider.all.js"></script>
<script src="js/jquery.placeholder.js"></script>
<script src='js/jquery.base64.js'></script>
<script src='js/bootstrap.min.js'></script>
<script src="js/jquery.touchSwipe.min.js" type="text/javascript"></script>
<script src="//crypto-js.googlecode.com/svn/tags/3.1.2/build/rollups/md5.js"></script>
<script src="js/global.js" type="text/javascript"></script>
<script>
    $(function () {
        // promo mentions
        $('.macaron').click(function () {
            if (!$('.mentions').is(':visible')) $(".mentions").slideToggle(300, function() {});
        });
        $('.conditions').click(function () {
            $(".mentions").slideToggle(300, function () {
                //
            });
        });
        $('.mentions').click(function () {
            $(".mentions").slideToggle(300, function () {
                //
            });
        });
        //
        $('a[href^="#bloc_mentions"]').click(function () { // console.log("test")
            var id = $(this).attr("href");
            var offset = $(id).offset().top;
            $('html, body').animate({scrollTop: offset}, 'slow');
            //return false;
        });
        //
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
            var inscription_cgv = $('#inscription_cgv');
            var utm_source = '<?php echo $source; ?>';
            var utm_source2 = '<?php echo $source2; ?>';
            var slug_origine = '<?php echo $slug_origine; ?>';

            if ($('#form_inscription').hasClass('etape1')) {

                var erreur = 0;

                if (!inscription_civilite) {
                    $('#inscription_civilite').next('.c2-sb-wrap').addClass('error');
                    var erreur = 1;
                }
                if (!inscription_nom) {
                    $('#inscription_nom').addClass('error');
                    var erreur = 1;
                }
                if (!inscription_prenom) {
                    $('#inscription_prenom').addClass('error');
                    var erreur = 1;
                }
                if (!inscription_email) {
                    $('#inscription_email').addClass('error');
                    var erreur = 1;
                }
                if (!validateEmail(inscription_email)) {
                    $('#inscription_email').addClass('error');
                    var erreur = 1;
                }
                if (erreur == 1) {
                    return false;
                }
                else {

                    // AJAX

                    var key = 'unilend';
                    var hash = CryptoJS.MD5(key);
                    var time = $.now();

                    // var token = '<?php echo $token; ?>';
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
                        url: "https://www.unilend.fr/collect/prospect",
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
                                    $('#form_header').html('<h1>Complétez</h1><h2>Votre inscription</h2>');
                                    $('#form_header').fadeIn();
                                });

                                $('#form_inscription > .form_content.etape1').fadeOut('fast', function () {
                                    $('#form').css('position', 'relative');
                                    $('#form > .wrapper').addClass('etape2');

                                    var tracking1 = '<img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/tr?ev=6021615722883&amp;cd[value]=0.00&amp;cd[currency]=EUR&amp;noscript=1" />';

                                    var tracking2 = '<iframe src="https://tracking.unilend-partners.com/mastertags/3.html?action=lead&pid=3&type=9&uniqueid=??"  width="1" height="1" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" style="border:none;"></iframe>';


                                    $("#tracking").html(tracking1 + tracking2);


                                    /* $("#tracking").html('<iframe src="https://tracking.unilend-partners.com/mastertags/3.html?action=lead&pid=3&type=9&uniqueid=??"  width="1" height="1" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" style="border:none;"></iframe>');*/
                                    $('#form_inscription > .form_content.etape2').fadeIn();
                                });
                            }
                            else {
                                var key = 'unilend';
                                var hash = CryptoJS.MD5(key);
                                var time = $.now();
                                var token = $.base64.btoa(hash + '-' + time);

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
                                            $('#form_header').html('<h1>Complétez</h1><h2>Votre inscription</h2>');
                                            $('#form_header').fadeIn();
                                        });

                                        $('#form_inscription > .form_content.etape1').fadeOut('fast', function () {
                                            $('#form').css('position', 'relative');
                                            $('#form > .wrapper').addClass('etape2');

                                            var tracking1 = '<img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/tr?ev=6021615722883&amp;cd[value]=0.00&amp;cd[currency]=EUR&amp;noscript=1" />';

                                            var tracking2 = '<iframe src="https://tracking.unilend-partners.com/mastertags/3.html?action=lead&pid=3&type=9&uniqueid=??"  width="1" height="1" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" style="border:none;"></iframe>';

                                            $("#tracking").html(tracking1 + tracking2);

                                            /*$("#tracking").html('<iframe src="https://tracking.unilend-partners.com/mastertags/3.html?action=lead&pid=3&type=9&uniqueid=??"  width="1" height="1" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" style="border:none;"></iframe>');*/
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
                    var erreur = 1;
                }
                if (inscription_mdp.length < 6) {
                    $('#inscription_mdp').addClass('error');
                    var erreur = 1;
                }
                if (inscription_mdp.replace(/[^A-Z]/g, "").length == 0) {
                    $('#inscription_mdp').addClass('error');
                    var erreur = 1;
                }
                if (!inscription_mdp2) {
                    $('#inscription_mdp2').addClass('error');
                    var erreur = 1;
                }
                if (inscription_mdp2 != inscription_mdp) {
                    $('#inscription_mdp2').addClass('error');
                    var erreur = 1;
                }
                if (!inscription_adresse_fiscale) {
                    $('#inscription_adresse_fiscale').addClass('error');
                    var erreur = 1;
                }
                if (!inscription_ville_fiscale) {
                    $('#inscription_ville_fiscale').addClass('error');
                    var erreur = 1;
                }
                if (!inscription_cp_fiscale) {
                    $('#inscription_cp_fiscale').addClass('error');
                    var erreur = 1;
                }
                if (!$.isNumeric(inscription_cp_fiscale)) {
                    $('#inscription_cp_fiscale').addClass('error');
                    var erreur = 1;
                }
                if (!inscription_id_pays_fiscale) {
                    $('#inscription_id_pays_fiscale').next('.c2-sb-wrap').addClass('error');
                    var erreur = 1;
                }
                if (!inscription_adresse_correspondance && !inscription_ville_correspondance && !inscription_cp_correspondance && !inscription_id_pays_correspondance) {
                    inscription_adresse_correspondance = '';
                    inscription_ville_correspondance = '';
                    inscription_cp_correspondance = '';
                    inscription_id_pays_correspondance = '';
                }
                else {
                    if (!inscription_adresse_correspondance) {
                        $('#inscription_adresse_correspondance').addClass('error');
                        var erreur = 1;
                    }
                    if (!inscription_ville_correspondance) {
                        $('#inscription_ville_correspondance').addClass('error');
                        var erreur = 1;
                    }
                    if (!inscription_cp_correspondance) {
                        $('#inscription_cp_correspondance').addClass('error');
                        var erreur = 1;
                    }
                    if (!$.isNumeric(inscription_cp_correspondance)) {
                        $('#inscription_cp_correspondance').addClass('error');
                        var erreur = 1;
                    }
                    if (!inscription_id_pays_correspondance) {
                        $('#inscription_id_pays_correspondance').next('.c2-sb-wrap').addClass('error');
                        var erreur = 1;
                    }
                }
                if (!inscription_telephone) {
                    $('#inscription_telephone').addClass('error');
                    var erreur = 1;
                }
                if (inscription_telephone.length != 10 || !$.isNumeric(inscription_telephone)) {
                    $('#inscription_telephone').addClass('error');
                    var erreur = 1;
                }
                if (!inscription_id_nationalite) {
                    $('#inscription_id_nationalite').next('.c2-sb-wrap').addClass('error');
                    var erreur = 1;
                }
                var verif_date = 0;
                if (!inscription_date_naissance) {
                    $('#inscription_date_naissance').addClass('error');
                    var erreur = 1;
                    var verif_date = 1;
                }
                if (!validateDate(inscription_date_naissance)) {
                    $('#inscription_date_naissance').addClass('error');
                    if (verif_date == 0) {
                        $('#errorAge').html('La date doit être au format jj/mm/aaaa');
                    }
                    var erreur = 1;
                    var verif_date = 1;
                }
                var date_naissance = inscription_date_naissance;
                var split_date = date_naissance.split('/');

                if (split_date[2] > annee) {
                    $('#inscription_date_naissance').addClass('error');
                    if (verif_date == 0) {
                        $('#errorAge').html('Année invalide');
                    }
                    var erreur = 1;
                    var verif_date = 1;
                }
                if (split_date[1] > 12) {
                    $('#inscription_date_naissance').addClass('error');
                    if (verif_date == 0) {
                        $('#errorAge').html('Mois invalide');
                    }
                    var erreur = 1;
                    var verif_date = 1;
                }
                if (split_date[0] > 31) {
                    $('#inscription_date_naissance').addClass('error');
                    if (verif_date == 0) {
                        $('#errorAge').html('Jours invalide');
                    }
                    var erreur = 1;
                    var verif_date = 1;
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
                    $('#errorAge').html('Vous devez être majeur');
                    var erreur = 1;
                }
                if (!inscription_commune_naissance) {
                    $('#inscription_commune_naissance').addClass('error');
                    var erreur = 1;
                }
                if (!inscription_id_pays_naissance) {
                    $('#inscription_id_pays_naissance').next('.c2-sb-wrap').addClass('error');
                    var erreur = 1;
                }
                if (!inscription_cgv.is(":checked")) {
                    $('#inscription_cgv').parent().find('label').addClass('error');
                    var erreur = 1;
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

                    var date = annee + '-' + mois + '-' + jour + ' ' + heure + ':' + minutes + ':' + secondes;
                    email = inscription_email;
                    nom = inscription_nom;
                    prenom = inscription_prenom;
                    civilite = inscription_civilite;
                    var password = CryptoJS.MD5(inscription_mdp);
                    var question = inscription_question;
                    var reponse = inscription_reponse;
                    var adresse_fiscale = inscription_adresse_fiscale;
                    var ville_fiscale = inscription_ville_fiscale;
                    var cp_fiscale = inscription_cp_fiscale;
                    var id_pays_fiscale = inscription_id_pays_fiscale;
                    var adresse = inscription_adresse_correspondance;
                    var ville = inscription_ville_correspondance;
                    var cp = inscription_cp_correspondance;
                    var id_pays = inscription_id_pays_correspondance;
                    var telephone = inscription_telephone;
                    var id_nationalite = inscription_id_nationalite;
                    var new_date_naissance = split_date[2] + '-' + split_date[1] + '-' + split_date[0];
                    var commune_naissance = inscription_commune_naissance;
                    var id_pays_naissance = inscription_id_pays_naissance;
                    var signature_cgv = 1;
                    var forme_preteur = 1;

                    var DATA = '&token=' + token + '&utm_source=' + utm_source + '&utm_source2=' + utm_source2 + '&slug_origine=' + slug_origine + '&date=' + date + '&email=' + email + '&nom=' + nom + '&prenom=' + prenom + '&civilite=' + civilite + '&password=' + password + '&question=' + question + '&reponse=' + reponse + '&adresse_fiscale=' + adresse_fiscale + '&ville_fiscale=' + ville_fiscale + '&cp_fiscale=' + cp_fiscale + '&id_pays_fiscale=' + id_pays_fiscale + '&adresse=' + adresse + '&ville=' + ville + '&cp=' + cp + '&id_pays=' + id_pays + '&telephone=' + telephone + '&id_nationalite=' + id_nationalite + '&date_naissance=' + new_date_naissance + '&commune_naissance=' + commune_naissance + '&id_pays_naissance=' + id_pays_naissance + '&signature_cgv=' + signature_cgv + '&forme_preteur=' + forme_preteur;

                    $.ajax({
                        type: "POST",
                        url: "https://www.unilend.fr/collect/inscription",
                        data: DATA,
                        success: function (data) {
                            var parsedDate = jQuery.parseJSON(data);

                            // console.log(parsedDate);

                            if (parsedDate.reponse == 'OK') {
                                var url = parsedDate.URL;

                                if (idSubmit == "inscription_submit2") {
                                    $(location).attr('href', url);
                                }
                                else if (idSubmit == "voir_projets") {
                                    $(location).attr('href', 'https://www.unilend.fr/projets-a-financer');
                                }
                            }
                            else {
                                var key = 'unilend';
                                var hash = CryptoJS.MD5(key);
                                var time = $.now();
                                var token = $.base64.btoa(hash + '-' + time);

                                $.each(parsedDate.reponse, function (index, value) {
                                    var intituleErreur = value.erreur;

                                    // console.log(intituleErreur);

                                    if (intituleErreur == "Mot de passe") {
                                        $('#inscription_mdp').addClass('error');
                                    }
                                    if (intituleErreur == "Question secrète") {
                                        $('#inscription_question').addClass('error');
                                    }
                                    if (intituleErreur == "Reponse secrète") {
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
                                    if (intituleErreur == "Téléphone") {
                                        $('#inscription_telephone').addClass('error');
                                    }
                                    if (intituleErreur == "Nationalité") {
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
    });
</script>
</body>
</html>
