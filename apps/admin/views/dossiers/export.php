<?php $iOldestAnnualAccountsId = end(array_keys($this->aBalanceSheets)); ?>
"Notation externe"
<?php if ($this->bIsProblematicCompany) : ?>"Cette société a déjà eu des problèmes"<?php endif; ?>
"Notes externes";"";"Déclaration client"
"Grade SFAC";"<?= empty($this->aRatings['grade_sfac']) ? 'N/A' : $this->aRatings['grade_sfac'] ?>";"Chiffe d'affaires declaré par client";"<?= $this->iDeclaredRevenue ?>"
"Score Altares";"<?php if (isset($this->aRatings['score_altares'])) : ?><?= $this->aRatings['score_altares'] ?> / 20<?php else : ?>N/A<?php endif; ?>";"Résultat d'exploitation declaré par client";"<?= $this->iDeclaredOperatingIncome ?>"
"Score sectoriel Altares";"<?php if (isset($this->aRatings['score_sectoriel_altares'])) : ?><?= round($this->aRatings['score_sectoriel_altares'] / 5) ?> / 20<?php else : ?>N/A<?php endif; ?>";"Fonds propres declarés par client";"<?= $this->iDeclaredCapitalStock ?>"
"Note Infolegale";"<?= empty($this->aRatings['note_infolegale']) ? 'N/A' : $this->aRatings['note_infolegale'] ?>"
"Présence de RPC < 6 mois";"<?= isset($this->aRatings['rpc_6mois']) && '1' === $this->aRatings['rpc_6mois'] ? 'Oui' : 'Non' ?>"
"Présence de RPC > 12 mois";"<?= isset($this->aRatings['rpc_12mois']) && '1' === $this->aRatings['rpc_12mois'] ? 'Oui' : 'Non' ?>"
"Grade FIBEN / Note interne banque";"<?php if (isset($this->aRatings['grabe_fiben'])) : ?><?= $this->aRatings['grabe_fiben'] ?><?php endif; ?>";"<?php if (isset($this->aRatings['note_interne_banque'])) : ?><?= $this->aRatings['note_interne_banque'] ?><?php endif; ?>";"<?php if (isset($this->aRatings['nom_banque'])) : ?><?= $this->aRatings['nom_banque'] ?><?php endif; ?>"
"Grade dirigeant FIBEN";"<?php if (isset($this->aRatings['grabe_dirigeant_fiben'])) : ?><?= $this->aRatings['grabe_dirigeant_fiben'] ?><?php endif; ?>"
"Score sectoriel Xerfi";"<?php if (isset($this->aRatings['xerfi'], $this->aRatings['xerfi_unilend'])) : ?><?= $this->aRatings['xerfi'] ?> / <?= $this->aRatings['xerfi_unilend'] ?><?php else : ?>N/A<?php endif; ?>"
"Date du privilège le plus récent";"<?php if (isset($this->aRatings['date_dernier_privilege']) && false === empty($this->aRatings['date_dernier_privilege'])) : ?><?= $this->dates->formatDate($this->aRatings['date_dernier_privilege'], 'd/m/Y') ?><?php endif; ?>"
"Dernière situation de trésorerie connue";"<?php if (isset($this->aRatings['date_tresorerie']) && false === empty($this->aRatings['date_tresorerie'])) : ?><?= $this->dates->formatDate($this->aRatings['date_tresorerie'], 'd/m/Y') ?><?php endif; ?>";"<?php if (isset($this->aRatings['montant_tresorerie'])) : ?><?= $this->aRatings['montant_tresorerie'] ?><?php endif; ?>"
"Délais de paiement Altares (à date)";"<?php if (isset($this->aRatings['delais_paiement_altares'])) : ?><?= $this->aRatings['delais_paiement_altares'] ?><?php endif; ?>"
"Délais de paiement du secteur";"<?php if (isset($this->aRatings['delais_paiement_secteur'])) : ?><?= $this->aRatings['delais_paiement_secteur'] ?><?php endif; ?>"
"Dailly";"<?php if (isset($this->aRatings['dailly']) && '1' === $this->aRatings['dailly']) : ?>Oui<?php else : ?>Non<?php endif; ?>"
"Affacturage";"<?php if (isset($this->aRatings['affacturage']) && '1' === $this->aRatings['affacturage']) : ?>Oui<?php else : ?>Non<?php endif; ?>"
""
"Capital restant dû à date";"<?= $this->fCompanyOwedCapital ?>"
<?php if (false === empty($this->aCompanyProjects)) : ?>
""
"Projets de cette société (SIREN identique)"
"ID";"Nom";"Date demande";"Date modification";"Montant";"Durée";"Statut";"Commercial";"Analyste"
<?php foreach ($this->aCompanyProjects as $iIndex => $aProject) : ?>
"<?= $aProject['id_project'] ?>";"<?= $aProject['title'] ?>";"<?= $this->dates->formatDate($aProject['added'], 'd/m/Y') ?>";"<?= $this->dates->formatDate($aProject['updated'], 'd/m/Y') ?>";"<?= $aProject['amount'] ?>";"<?= $aProject['period'] ?> mois";"<?= $aProject['status_label'] ?>";"<?= $aProject['sales_person'] ?>";"<?= $aProject['analyst'] ?>";
<?php endforeach; ?>
<?php endif; ?>
""
"Bilans"
"Actif";<?php foreach ($this->aAnnualAccounts as $aAnnualAccounts): ?>"<?= $this->dates->formatDate($aAnnualAccounts['cloture_exercice_fiscal'], 'd/m/Y') ?> (<?= $aAnnualAccounts['duree_exercice_fiscal'] ?> mois)";<?php if ($aAnnualAccounts['id_bilan'] != $iOldestAnnualAccountsId) { ?>"";<?php } ?><?php endforeach; ?>

"Capital souscrit non appelé";"AA";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['AA']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AA'] - $aBalanceSheet['AA']) / $aBalanceSheet['AA'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['AA'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Frais d'établissement";"AB";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['AB']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AB'] - $aBalanceSheet['AB']) / $aBalanceSheet['AB'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['AB'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Frais de développement";"AD";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['AD']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AD'] - $aBalanceSheet['AD']) / $aBalanceSheet['AD'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['AD'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Concessions, brevets et droits similaires";"AF";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['AF']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AF'] - $aBalanceSheet['AF']) / $aBalanceSheet['AF'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['AF'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Fonds commercial";"AH";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['AH']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AH'] - $aBalanceSheet['AH']) / $aBalanceSheet['AH'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['AH'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres immos Incorpo";"AJ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['AJ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AJ'] - $aBalanceSheet['AJ']) / $aBalanceSheet['AJ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['AJ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Avances et acomptes sur immos Incorpo";"AL";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['AL']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AL'] - $aBalanceSheet['AL']) / $aBalanceSheet['AL'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['AL'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Immobilisations incorporelles";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('AB', 'AD', 'AF', 'AH', 'AJ', 'AL'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Terrains";"AN";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['AN']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AN'] - $aBalanceSheet['AN']) / $aBalanceSheet['AN'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['AN'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Constructions";"AP";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['AP']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AP'] - $aBalanceSheet['AP']) / $aBalanceSheet['AP'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['AP'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"ITMOI";"AR";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['AR']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AR'] - $aBalanceSheet['AR']) / $aBalanceSheet['AR'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['AR'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres immo corpo";"AT";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['AT']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AT'] - $aBalanceSheet['AT']) / $aBalanceSheet['AT'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['AT'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Immos en cours";"AV";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['AV']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AV'] - $aBalanceSheet['AV']) / $aBalanceSheet['AV'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['AV'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Avances et acomptes sur immos corpo";"AX";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['AX']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['AX'] - $aBalanceSheet['AX']) / $aBalanceSheet['AX'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['AX'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Immobilisations corporelles";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('AN', 'AP', 'AR', 'AT', 'AV', 'AX'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Participations évaluées selon la méthode";"CS";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['CS']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CS'] - $aBalanceSheet['CS']) / $aBalanceSheet['CS'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['CS'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres participations";"CU";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['CU']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CU'] - $aBalanceSheet['CU']) / $aBalanceSheet['CU'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['CU'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Créances rattachées à des participations";"BB";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['BB']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BB'] - $aBalanceSheet['BB']) / $aBalanceSheet['BB'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['BB'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres titres immobilisés";"BD";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['BD']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BD'] - $aBalanceSheet['BD']) / $aBalanceSheet['BD'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['BD'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Prêts";"BF";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['BF']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BF'] - $aBalanceSheet['BF']) / $aBalanceSheet['BF'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['BF'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres immobilisations financières";"BH";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['BH']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BH'] - $aBalanceSheet['BH']) / $aBalanceSheet['BH'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['BH'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Immobilisations financières";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('CS', 'CU', 'BB', 'BD', 'BF', 'BH'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Total actif immobilisé";"BJ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['BJ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BJ'] - $aBalanceSheet['BJ']) / $aBalanceSheet['BJ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['BJ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Matières premières";"BL";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['BL']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BL'] - $aBalanceSheet['BL']) / $aBalanceSheet['BL'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['BL'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"En-cours de bien";"BN";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['BN']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BN'] - $aBalanceSheet['BN']) / $aBalanceSheet['BN'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['BN'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"En-cours de services";"BP";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['BP']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BP'] - $aBalanceSheet['BP']) / $aBalanceSheet['BP'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['BP'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Produits Intermédiaires et finis";"BR";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['BR']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BR'] - $aBalanceSheet['BR']) / $aBalanceSheet['BR'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['BR'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Marchandises";"BT";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['BT']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BT'] - $aBalanceSheet['BT']) / $aBalanceSheet['BT'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['BT'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Stocks";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('BL', 'BN', 'BP', 'BR', 'BT'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Avances et acomptes versés sur commande";"BV";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['BV']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BV'] - $aBalanceSheet['BV']) / $aBalanceSheet['BV'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['BV'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Clients et comptes rattachés";"BX";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['BX']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BX'] - $aBalanceSheet['BX']) / $aBalanceSheet['BX'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['BX'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres créances + K souscrit non appelé";"BZ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['BZ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BZ'] - $aBalanceSheet['BZ']) / $aBalanceSheet['BZ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['BZ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Capital souscrit appelé non versé";"CB";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['CB']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CB'] - $aBalanceSheet['CB']) / $aBalanceSheet['CB'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['CB'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Créances clients et autres";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('BV', 'BX', 'BZ', 'CB'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Disponibilités";"CF";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['CF']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CF'] - $aBalanceSheet['CF']) / $aBalanceSheet['CF'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['CF'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"VMP";"CD";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['CD']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CD'] - $aBalanceSheet['CD']) / $aBalanceSheet['CD'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['CD'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Trésorerie";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('CF', 'CD'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Charges constatées d'avance";"CH";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['CH']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CH'] - $aBalanceSheet['CH']) / $aBalanceSheet['CH'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['CH'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Total actif circulant";"CJ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['CJ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CJ'] - $aBalanceSheet['CJ']) / $aBalanceSheet['CJ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['CJ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Frais d'émission d'emprunt à étaler";"CW";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['CW']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CW'] - $aBalanceSheet['CW']) / $aBalanceSheet['CW'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['CW'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Primes de remboursement des obligations";"CM";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['CM']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CM'] - $aBalanceSheet['CM']) / $aBalanceSheet['CM'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['CM'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Ecarts de conversion actif";"CN";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) { ?>"<?= empty($aBalanceSheet['CN']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CN'] - $aBalanceSheet['CN']) / $aBalanceSheet['CN'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['CN'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Comptes de régularisation";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('CH', 'CW', 'CM', 'CN'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Total actif";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('AA', 'AB', 'AD', 'AF', 'AH', 'AJ', 'AL', 'AN', 'AP', 'AR', 'AT', 'AV', 'AX', 'CS', 'CU', 'BB', 'BD', 'BF', 'BH', 'BL', 'BN', 'BP', 'BR', 'BT', 'BV', 'BX', 'BZ', 'CB', 'CF', 'CD', 'CH', 'CW', 'CM', 'CN'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Passif"
"Capital social";"DA";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
          ?>"<?= empty($aBalanceSheet['DA']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DA'] - $aBalanceSheet['DA']) / $aBalanceSheet['DA'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['DA'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Total capitaux propres";"DL";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['DL']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DL'] - $aBalanceSheet['DL']) / $aBalanceSheet['DL'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['DL'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres fonds propres";"DO";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['DO']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DO'] - $aBalanceSheet['DO']) / $aBalanceSheet['DO'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['DO'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Total fonds propres";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('DL', 'DO'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Amortissements sur Immobilisations";"BK";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['BK']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['BK'] - $aBalanceSheet['BK']) / $aBalanceSheet['BK'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['BK'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dépréciation de l'actif circulant";"CK";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['CK']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['CK'] - $aBalanceSheet['CK']) / $aBalanceSheet['CK'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['CK'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Provisions pour risques et charges";"DR";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['DR']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DR'] - $aBalanceSheet['DR']) / $aBalanceSheet['DR'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['DR'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Emprunts obligataires convertibles";"DS";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['DS']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DS'] - $aBalanceSheet['DS']) / $aBalanceSheet['DS'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['DS'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres emprunts obligataires";"DT";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['DT']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DT'] - $aBalanceSheet['DT']) / $aBalanceSheet['DT'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['DT'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Emprunts et dettes auprès des établissements de crédit";"DU";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['DU']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DU'] - $aBalanceSheet['DU']) / $aBalanceSheet['DU'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['DU'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Emprunts et dettes financières divers";"DV";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['DV']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DV'] - $aBalanceSheet['DV']) / $aBalanceSheet['DV'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['DV'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dettes financières";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('DS', 'DT', 'DU', 'DV'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Avances et accomptes reçus";"DW";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['DW']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DW'] - $aBalanceSheet['DW']) / $aBalanceSheet['DW'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['DW'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dettes fournisseurs et comptes rattachés";"DX";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['DX']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DX'] - $aBalanceSheet['DX']) / $aBalanceSheet['DX'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['DX'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dettes fournisseurs";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('DW', 'DX'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Dettes fiscales et sociales";"DY";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['DY']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DY'] - $aBalanceSheet['DY']) / $aBalanceSheet['DY'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['DY'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dettes sur immobilisations et comptes rattachés";"DZ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['DZ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['DZ'] - $aBalanceSheet['DZ']) / $aBalanceSheet['DZ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['DZ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres dettes";"EA";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
         ?>"<?= empty($aBalanceSheet['EA']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['EA'] - $aBalanceSheet['EA']) / $aBalanceSheet['EA'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['EA'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres dettes";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('DY', 'DZ', 'EA'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Produits constatés d'avance";"EB";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['EB']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['EB'] - $aBalanceSheet['EB']) / $aBalanceSheet['EB'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['EB'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Écarts de conversion passif";"ED";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['ED']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['ED'] - $aBalanceSheet['ED']) / $aBalanceSheet['ED'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['ED'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Comptes de ";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('EB', 'ED'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Total passif";"";<?php
    $iPreviousTotal = null;

    foreach ($this->aBalanceSheets as $aBalanceSheet) {
        $iTotal = $this->sumBalances(array('DL', 'DO', 'BK', 'CK', 'DR', 'DS', 'DT', 'DU', 'DV', 'DW', 'DX', 'DY', 'DZ', 'EA', 'EB', 'ED'), $aBalanceSheet);

        if (false === is_null($iPreviousTotal)) {
            ?>"<?= empty($iPreviousTotal) ? '' : round(($iPreviousTotal - $iTotal) / $iPreviousTotal * 100) . ' %' ?>";<?php } ?>"<?= $iTotal ?>";<?php
        $iPreviousTotal = $iTotal;
    }
    ?>

"Autres infos"
"2 : CBC, et soldes créditeurs de banques et CCP";"EH";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['EH']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['EH'] - $aBalanceSheet['EH']) / $aBalanceSheet['EH'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['EH'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"2 : Prêt participatif";"EI";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['EI']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['EI'] - $aBalanceSheet['EI']) / $aBalanceSheet['EI'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['EI'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"4 : Crédit bail Mobilier";"HP";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['HP']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HP'] - $aBalanceSheet['HP']) / $aBalanceSheet['HP'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['HP'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"4 : Crédit bail Immobilier";"HQ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['HQ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HQ'] - $aBalanceSheet['HQ']) / $aBalanceSheet['HQ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['HQ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"A5 : Investissements";"0J";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['0J']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['0J'] - $aBalanceSheet['0J']) / $aBalanceSheet['0J'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['0J'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"A8 : Dettes à rembourser ds l'année (à plus de 1 an à l'origine)";"VH";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['VH']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['VH'] - $aBalanceSheet['VH']) / $aBalanceSheet['VH'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['VH'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"A8 : Groupes et associés (placés en "Emprunts et dettes diverses")";"VI";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['VI']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['VI'] - $aBalanceSheet['VI']) / $aBalanceSheet['VI'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['VI'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Compte de résultat"
"Chiffre d'Affaires nets";"FL";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['FL']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FL'] - $aBalanceSheet['FL']) / $aBalanceSheet['FL'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['FL'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Production stockée";"FM";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['FM']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FM'] - $aBalanceSheet['FM']) / $aBalanceSheet['FM'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['FM'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Production immobilisée";"FN";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['FN']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FN'] - $aBalanceSheet['FN']) / $aBalanceSheet['FN'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['FN'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Subventions d'exploitation";"FO";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['FO']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FO'] - $aBalanceSheet['FO']) / $aBalanceSheet['FO'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['FO'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Reprises sur amort. et prov., transferts de charges";"FP";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['FP']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FP'] - $aBalanceSheet['FP']) / $aBalanceSheet['FP'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['FP'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres produits";"FQ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
           ?>"<?= empty($aBalanceSheet['FQ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FQ'] - $aBalanceSheet['FQ']) / $aBalanceSheet['FQ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['FQ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Achats de marchandises";"FS";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['FS']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FS'] - $aBalanceSheet['FS']) / $aBalanceSheet['FS'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['FS'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Variation de stock (marchandises)";"FT";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['FT']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FT'] - $aBalanceSheet['FT']) / $aBalanceSheet['FT'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['FT'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Achats de matières premières et autres approv.";"FU";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['FU']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FU'] - $aBalanceSheet['FU']) / $aBalanceSheet['FU'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['FU'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Variation de stock (matières premiières et approv.)";"FV";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['FV']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FV'] - $aBalanceSheet['FV']) / $aBalanceSheet['FV'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['FV'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres achats et charges externes";"FW";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['FW']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FW'] - $aBalanceSheet['FW']) / $aBalanceSheet['FW'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['FW'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Impots, taxes et versements assimilés";"FX";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['FX']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FX'] - $aBalanceSheet['FX']) / $aBalanceSheet['FX'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['FX'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Salaires et traitements";"FY";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['FY']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FY'] - $aBalanceSheet['FY']) / $aBalanceSheet['FY'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['FY'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Charges sociales";"FZ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['FZ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['FZ'] - $aBalanceSheet['FZ']) / $aBalanceSheet['FZ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['FZ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dotations aux amortissements";"GA";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['GA']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GA'] - $aBalanceSheet['GA']) / $aBalanceSheet['GA'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['GA'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dotations aux provisions";"GB";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['GB']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GB'] - $aBalanceSheet['GB']) / $aBalanceSheet['GB'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['GB'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dotations aux provisions (sur actif circulant)";"GC";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['GC']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GC'] - $aBalanceSheet['GC']) / $aBalanceSheet['GC'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['GC'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dotation aux provisions (pour risques et charges)";"GD";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['GD']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GD'] - $aBalanceSheet['GD']) / $aBalanceSheet['GD'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['GD'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Autres charges";"GE";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
          ?>"<?= empty($aBalanceSheet['GE']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GE'] - $aBalanceSheet['GE']) / $aBalanceSheet['GE'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['GE'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Résultat d'Exploitation";"GG";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['GG']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GG'] - $aBalanceSheet['GG']) / $aBalanceSheet['GG'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['GG'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Résultat financier";"GV";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['GV']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GV'] - $aBalanceSheet['GV']) / $aBalanceSheet['GV'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['GV'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Reprises sur amort. et prov., transferts de charges fi";"GM";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['GM']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GM'] - $aBalanceSheet['GM']) / $aBalanceSheet['GM'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['GM'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dotations financières aux amort. Et prov.";"GQ";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['GQ']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GQ'] - $aBalanceSheet['GQ']) / $aBalanceSheet['GQ'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['GQ'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Total Charges Financières";"GU";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['GU']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GU'] - $aBalanceSheet['GU']) / $aBalanceSheet['GU'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['GU'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"RCAI";"GW";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['GW']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['GW'] - $aBalanceSheet['GW']) / $aBalanceSheet['GW'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['GW'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Produits exceptionnels sur opérations de gestion";"HA";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['HA']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HA'] - $aBalanceSheet['HA']) / $aBalanceSheet['HA'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['HA'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Produits exceptionnels sur opérations de capital";"HB";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['HB']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HB'] - $aBalanceSheet['HB']) / $aBalanceSheet['HB'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['HB'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Reprises sur provisions et transferts de charges";"HC";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['HC']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HC'] - $aBalanceSheet['HC']) / $aBalanceSheet['HC'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['HC'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Charges exceptionnelles sur opérations de gestion";"HE";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['HE']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HE'] - $aBalanceSheet['HE']) / $aBalanceSheet['HE'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['HE'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Charges exceptionnelles sur opérations en capital";"HF";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['HF']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HF'] - $aBalanceSheet['HF']) / $aBalanceSheet['HF'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['HF'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Dotations exceptionnelles aux amts et provisions";"HG";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['HG']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HG'] - $aBalanceSheet['HG']) / $aBalanceSheet['HG'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['HG'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }
    ?>

"Résultat net";"HN";<?php
    $iColumn = 0;
    $iPreviousBalanceSheetId = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        if (false === is_null($iPreviousBalanceSheetId)) {
            ?>"<?= empty($aBalanceSheet['HN']) ? '' : round(($this->aBalanceSheets[$iPreviousBalanceSheetId]['HN'] - $aBalanceSheet['HN']) / $aBalanceSheet['HN'] * 100) . ' %' ?>";<?php } ?>"<?= $aBalanceSheet['HN'] ?>";<?php
        $iPreviousBalanceSheetId = $iBalanceSheetId;
    }

$iBalanceSheetsCount     = count($this->aBalanceSheets);
$aOperationalCashFlow    = array();
$aGrossOperatingSurplus  = array();
$aMediumLongTermDebt     = array();
$aBalanceTotal           = array();
$iLastAnnualAccountsId   = current(array_keys($this->aBalanceSheets));
$iOldestAnnualAccountsId = end(array_keys($this->aBalanceSheets));

foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
    $aOperationalCashFlow[$iBalanceSheetId] =
        $aBalanceSheet['HN']
        - $aBalanceSheet['FP']
        + $aBalanceSheet['GA']
        + $aBalanceSheet['GB']
        + $aBalanceSheet['GC']
        + $aBalanceSheet['GD']
        - $aBalanceSheet['GM']
        + $aBalanceSheet['GQ']
        - $aBalanceSheet['HB']
        - $aBalanceSheet['HC']
        + $aBalanceSheet['HF']
        + $aBalanceSheet['HG'];

    $aGrossOperatingSurplus[$iBalanceSheetId] =
        $aBalanceSheet['GG']
        + $aBalanceSheet['GA']
        + $aBalanceSheet['GB']
        + $aBalanceSheet['GC']
        + $aBalanceSheet['GD']
        - $aBalanceSheet['FP']
        - $aBalanceSheet['FQ']
        + $aBalanceSheet['GE'];

    $aMediumLongTermDebt[$iBalanceSheetId] =
        $aBalanceSheet['DS']
        + $aBalanceSheet['DT']
        + $aBalanceSheet['DU']
        + $aBalanceSheet['DV']
        - $aBalanceSheet['EH']
        - $aBalanceSheet['VI'];

    $aBalanceTotal[$iBalanceSheetId] =
        $aBalanceSheet['AN']
        + $aBalanceSheet['AP']
        + $aBalanceSheet['AR']
        + $aBalanceSheet['AT']
        + $aBalanceSheet['AV']
        + $aBalanceSheet['AX']
        + $aBalanceSheet['AB']
        + $aBalanceSheet['AD']
        + $aBalanceSheet['AF']
        + $aBalanceSheet['AH']
        + $aBalanceSheet['AJ']
        + $aBalanceSheet['AL']
        + $aBalanceSheet['CS']
        + $aBalanceSheet['CU']
        + $aBalanceSheet['BB']
        + $aBalanceSheet['BD']
        + $aBalanceSheet['BF']
        + $aBalanceSheet['BH']
        + $aBalanceSheet['BL']
        + $aBalanceSheet['BN']
        + $aBalanceSheet['BP']
        + $aBalanceSheet['BR']
        + $aBalanceSheet['BT']
        + $aBalanceSheet['BV']
        + $aBalanceSheet['BX']
        + $aBalanceSheet['BZ']
        + $aBalanceSheet['CB']
        + $aBalanceSheet['CH']
        + $aBalanceSheet['CF']
        + $aBalanceSheet['CD'];

}
?>

"Ratios et analyses"
"Solvabilité"
"Dette financière nette";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber =
            $aBalanceSheet['DS']
            + $aBalanceSheet['DT']
            + $aBalanceSheet['DU']
            + $aBalanceSheet['DV']
            - $aBalanceSheet['CF']
            - $aBalanceSheet['CD']
            - $aBalanceSheet['EH']
            - $aBalanceSheet['VI'];

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"CAF";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = $aOperationalCashFlow[$iBalanceSheetId];

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"CAF disponible";<?php
    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        ?>"<?= ($aOperationalCashFlow[$iBalanceSheetId] - $this->aBalanceSheets[$iBalanceSheetId]['VH']) ?>";<?php
        break;
    }
    ?>

"CAF moyenne pondérée sur 3 ans";"<?php if (3 === $iBalanceSheetsCount) : ?><?php list($iSecondToLastOperationalCashFlow, $iPreviousOperationalCashFlow, $iLastOperationalCashFlow) = array_values($aOperationalCashFlow); ?><?= round((2 * $iLastOperationalCashFlow + $iPreviousOperationalCashFlow + 0.5 * $iSecondToLastOperationalCashFlow) / 3.5, 2) ?><?php endif; ?>";

"DMLT / CAF";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aOperationalCashFlow[$iBalanceSheetId]) ? 0 : round($aMediumLongTermDebt[$iBalanceSheetId] / $aOperationalCashFlow[$iBalanceSheetId], 2);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"DMLT / EBE";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aGrossOperatingSurplus[$iBalanceSheetId]) ? 0 : round($aMediumLongTermDebt[$iBalanceSheetId] / $aGrossOperatingSurplus[$iBalanceSheetId], 2);

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Solvabilité générale";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor = $aBalanceSheet['DW']
            + $aBalanceSheet['DX']
            + $aBalanceSheet['EH']
            - $aBalanceSheet['VI'];
        $iCurrentNumber = empty($iDivisor) ? 0 : (
                $aBalanceSheet['BL']
                + $aBalanceSheet['BN']
                + $aBalanceSheet['BP']
                + $aBalanceSheet['BR']
                + $aBalanceSheet['BT']
                + $aBalanceSheet['BV']
                + $aBalanceSheet['BX']
                + $aBalanceSheet['BZ']
                + $aBalanceSheet['CB']
                + $aBalanceSheet['CH']
            ) / $iDivisor;

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Liquidité générale";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor = $aBalanceSheet['DW']
            + $aBalanceSheet['DX']
            + $aBalanceSheet['DY']
            + $aBalanceSheet['DZ']
            + $aBalanceSheet['EA']
            + $aBalanceSheet['EB']
            + $aBalanceSheet['ED']
            - $aBalanceSheet['VI'];
        $iCurrentNumber = empty($iDivisor) ? 0 : (
                $aBalanceSheet['BL']
                + $aBalanceSheet['BN']
                + $aBalanceSheet['BP']
                + $aBalanceSheet['BR']
                + $aBalanceSheet['BT']
                + $aBalanceSheet['BV']
                + $aBalanceSheet['BX']
                + $aBalanceSheet['BZ']
                + $aBalanceSheet['CB']
                + $aBalanceSheet['CH']
                + $aBalanceSheet['CF']
                + $aBalanceSheet['CD']
            ) / $iDivisor;

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Liquidité réduite";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor = $aBalanceSheet['DW']
            + $aBalanceSheet['DX']
            + $aBalanceSheet['DY']
            + $aBalanceSheet['DZ']
            + $aBalanceSheet['EA']
            + $aBalanceSheet['EB']
            + $aBalanceSheet['ED']
            - $aBalanceSheet['VI'];
        $iCurrentNumber = empty($iDivisor) ? 0 : (
                $aBalanceSheet['BV']
                + $aBalanceSheet['BX']
                + $aBalanceSheet['BZ']
                + $aBalanceSheet['CB']
                + $aBalanceSheet['CH']
                + $aBalanceSheet['CF']
                + $aBalanceSheet['CD']
            ) / $iDivisor;

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Endettement et structure"
"FP / Total bilan net (%)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? 0 : (
                $aBalanceSheet['DL']
                + $aBalanceSheet['DO']
            ) / $aBalanceTotal[$iBalanceSheetId] * 100;

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Quasi FP / Total bilan net";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? 0 : (
                $aBalanceSheet['DL']
                + $aBalanceSheet['DO']
                + $aBalanceSheet['EI']
                + $aBalanceSheet['VI']
            ) / $aBalanceTotal[$iBalanceSheetId];

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"DMLT / Total bilan net (%)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? 0 : $aMediumLongTermDebt[$iBalanceSheetId] / $aBalanceTotal[$iBalanceSheetId] * 100;

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"DMLT / Quasi FP";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor = $aBalanceSheet['DL']
            + $aBalanceSheet['DO']
            + $aBalanceSheet['EI']
            + $aBalanceSheet['VI'];
        $iCurrentNumber = empty($iDivisor) ? 0 : $aMediumLongTermDebt[$iBalanceSheetId] / $iDivisor;

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"DMLT / Total bilan net";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? 0 : $aMediumLongTermDebt[$iBalanceSheetId] / $aBalanceTotal[$iBalanceSheetId];

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"GEARING (%)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor = $aBalanceSheet['DL']
            + $aBalanceSheet['DO']
            + $aBalanceSheet['VI'];
        $iCurrentNumber = empty($iDivisor) ? 0 : (
                $aBalanceSheet['DS']
                + $aBalanceSheet['DT']
                + $aBalanceSheet['DU']
                + $aBalanceSheet['DV']
                - $aBalanceSheet['CF']
                - $aBalanceSheet['CD']
                - $aBalanceSheet['EH']
                - $aBalanceSheet['VI']
            ) / $iDivisor * 100;

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Charges fi / EBE";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor = $aBalanceSheet['GG']
            + $aBalanceSheet['GA']
            + $aBalanceSheet['GB']
            + $aBalanceSheet['GC']
            + $aBalanceSheet['GD']
            - $aBalanceSheet['FP']
            - $aBalanceSheet['FQ']
            + $aBalanceSheet['GE'];
        $iCurrentNumber = empty($iDivisor) ? 0 : abs($aBalanceSheet['GU']) / $iDivisor;

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Charges fi / Résultat net";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceSheet['HN']) ? 0 : abs($aBalanceSheet['GU']) / $aBalanceSheet['HN'];

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"CCA stables";<?php if (1 === $iBalanceSheetsCount) { ?>"<?= $this->aBalanceSheets[$iLastAnnualAccountsId]['VI'] ?>";<?php } elseif (2 <= $iBalanceSheetsCount) {
        $iLastNumber = $this->aBalanceSheets[$iLastAnnualAccountsId]['VI'];
        foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
            if ($iBalanceSheetId !== $iLastAnnualAccountsId) {
                ?>"<?= min($iLastNumber, $aBalanceSheet['VI']) ?>";<?php
                break;
            }
        }
    }
    ?>

"Rotations"
"FR";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber =
            $aBalanceSheet['DL']
            + $aBalanceSheet['DO']
            + $aBalanceSheet['DU']
            - $aBalanceSheet['EH']
            + $aBalanceSheet['VI']
            - (
                $aBalanceSheet['CS']
                + $aBalanceSheet['CU']
                + $aBalanceSheet['BB']
                + $aBalanceSheet['BD']
                + $aBalanceSheet['BF']
                + $aBalanceSheet['BH']
                + $aBalanceSheet['AN']
                + $aBalanceSheet['AP']
                + $aBalanceSheet['AR']
                + $aBalanceSheet['AT']
                + $aBalanceSheet['AV']
                + $aBalanceSheet['AX']
                + $aBalanceSheet['AB']
                + $aBalanceSheet['AD']
                + $aBalanceSheet['AF']
                + $aBalanceSheet['AH']
                + $aBalanceSheet['AJ']
                + $aBalanceSheet['AL']
                - $aBalanceSheet['BK']
            );

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"FR (en jours de CA)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor       = $aBalanceSheet['FL'] * (1 + $this->fVATRate);
        $iCurrentNumber = empty($iDivisor) ? 0 : (
                $aBalanceSheet['DL']
                + $aBalanceSheet['DO']
                + $aBalanceSheet['DU']
                - $aBalanceSheet['EH']
                + $aBalanceSheet['VI']
                - (
                    $aBalanceSheet['CS']
                    + $aBalanceSheet['CU']
                    + $aBalanceSheet['BB']
                    + $aBalanceSheet['BD']
                    + $aBalanceSheet['BF']
                    + $aBalanceSheet['BH']
                    + $aBalanceSheet['AN']
                    + $aBalanceSheet['AP']
                    + $aBalanceSheet['AR']
                    + $aBalanceSheet['AT']
                    + $aBalanceSheet['AV']
                    + $aBalanceSheet['AX']
                    + $aBalanceSheet['AB']
                    + $aBalanceSheet['AD']
                    + $aBalanceSheet['AF']
                    + $aBalanceSheet['AH']
                    + $aBalanceSheet['AJ']
                    + $aBalanceSheet['AL']
                    - $aBalanceSheet['BK']
                )
            ) / $iDivisor * 360;

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"BFR";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber =
            $aBalanceSheet['BL']
            + $aBalanceSheet['BN']
            + $aBalanceSheet['BP']
            + $aBalanceSheet['BR']
            + $aBalanceSheet['BT']
            + $aBalanceSheet['BV']
            + $aBalanceSheet['BX']
            + $aBalanceSheet['BZ']
            + $aBalanceSheet['CB']
            + $aBalanceSheet['CH']
            - (
                $aBalanceSheet['DV']
                + $aBalanceSheet['DW']
                + $aBalanceSheet['DX']
                + $aBalanceSheet['DY']
                + $aBalanceSheet['DZ']
                + $aBalanceSheet['EA']
                + $aBalanceSheet['EB']
                + $aBalanceSheet['ED']
                - $aBalanceSheet['VI']
            );

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"BFR (en jours de CA)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor       = $aBalanceSheet['FL'] * (1 + $this->fVATRate);
        $iCurrentNumber = empty($iDivisor) ? 0 : (
                $aBalanceSheet['BL']
                + $aBalanceSheet['BN']
                + $aBalanceSheet['BP']
                + $aBalanceSheet['BR']
                + $aBalanceSheet['BT']
                + $aBalanceSheet['BV']
                + $aBalanceSheet['BX']
                + $aBalanceSheet['BZ']
                + $aBalanceSheet['CB']
                + $aBalanceSheet['CH']
                - (
                    $aBalanceSheet['DV']
                    + $aBalanceSheet['DW']
                    + $aBalanceSheet['DX']
                    + $aBalanceSheet['DY']
                    + $aBalanceSheet['DZ']
                    + $aBalanceSheet['EA']
                    + $aBalanceSheet['EB']
                    + $aBalanceSheet['ED']
                    - $aBalanceSheet['VI']
                )
            ) / $iDivisor * 360;

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Trésorerie nette";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber =
            $aBalanceSheet['CF']
            + $aBalanceSheet['CD']
            - $aBalanceSheet['EH'];

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Stocks (en jours de CA)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceSheet['FL']) ? 0 : (
                $aBalanceSheet['BL']
                + $aBalanceSheet['BN']
                + $aBalanceSheet['BP']
                + $aBalanceSheet['BR']
                + $aBalanceSheet['BT']
            ) / $aBalanceSheet['FL'] * 360;

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Créances clients (en jours de CA)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor       = $aBalanceSheet['FL'] * (1 + $this->fVATRate);
        $iCurrentNumber = empty($iDivisor) ? 0 : $aBalanceSheet['BX'] / $iDivisor * 360;

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Dettes fournisseurs (en jours de CA)";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor       = $aBalanceSheet['FL'] * (1 + $this->fVATRate);
        $iCurrentNumber = empty($iDivisor) ? 0 : ($aBalanceSheet['DW'] + $aBalanceSheet['DX']) / $iDivisor * 360;

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Écart créances clients - Dettes fournisseurs";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iDivisor       = $aBalanceSheet['FL'] * (1 + $this->fVATRate);
        $iCurrentNumber = empty($iDivisor) ? 0 : (
                $aBalanceSheet['BX']
                - $aBalanceSheet['DW']
                - $aBalanceSheet['DX']
            ) / $iDivisor * 360;

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Rentabilité"
"EBE / CA";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceSheet['FL']) ? 0 : $aGrossOperatingSurplus[$iBalanceSheetId] / $aBalanceSheet['FL'];

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Résultat net / CA";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceSheet['FL']) ? 0 : $aBalanceSheet['HN'] / $aBalanceSheet['FL'];

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Disponibilités / Total bilan net";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceTotal[$iBalanceSheetId]) ? 0 : $aBalanceSheet['CF'] / $aBalanceTotal[$iBalanceSheetId];

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>

"Rentabilité des capitaux";<?php
    $iPreviousNumber = null;

    foreach ($this->aBalanceSheets as $iBalanceSheetId => $aBalanceSheet) {
        $iCurrentNumber = empty($aBalanceSheet['DA']) ? 0 : $aBalanceSheet['DL'] / $aBalanceSheet['DA'];

        if (false === is_null($iPreviousNumber)) {
            ?>"<?= empty($iCurrentNumber) ? 0 : round(($iPreviousNumber - $iCurrentNumber) / $iCurrentNumber * 100) . ' %' ?>";<?php } ?>"<?= $iCurrentNumber === '' ? 0 : round($iCurrentNumber, 2) ?>";<?php
        $iPreviousNumber = $iCurrentNumber;
    }
    ?>
