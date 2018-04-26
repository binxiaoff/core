<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Attachment, BankAccount, ClientAddress, CompanyAddress
};

?>
<style>
    #data-history-table tbody th {
        background-color: #6d1f4f;
        border-left: 1px solid #6d1f4f;
        border-right: 1px solid #6d1f4f;
        color: #fff;
        font-weight: normal;
    }
</style>
<h2>Historique des données personnelles</h2>
<table id="data-history-table" class="table">
    <thead>
    <tr>
        <th>Donnée</th>
        <th>Valeur d'origine</th>
        <th>Nouvelle valeur</th>
        <th>Utilisateur</th>
    </tr>
    </thead>
    <tbody>
        <?php foreach ($this->dataHistory as $timestamp => $timeHistory) : ?>
            <tr>
                <th colspan="4">
                    <?= $timeHistory[0]['date']->format('d/m/Y H\hi') ?>
                </th>
            </tr>
            <?php foreach ($timeHistory as $dataHistory) : ?>
                <tr>
                    <td>
                        <?php

                        switch ($dataHistory['type']) {
                            case 'data':
                                echo ucfirst($dataHistory['name']);
                                break;
                            case 'bank_account':
                                switch ($dataHistory['name']) {
                                    case 'modification':
                                        echo 'Modification RIB';
                                        break;
                                    case 'validation':
                                        echo 'Validation RIB';
                                        break;
                                    case 'archival':
                                        echo 'Archivage RIB';
                                        break;
                                }
                                break;
                            case 'address':
                                switch ($dataHistory['name']) {
                                    case 'modification_main':
                                        echo 'Modification adresse fiscale';
                                        break;
                                    case 'validation_main':
                                        echo 'Validation adresse fiscale';
                                        break;
                                    case 'archival_main':
                                        echo 'Archivage adresse fiscale';
                                        break;
                                    case 'modification_postal':
                                        echo 'Modification adresse postale';
                                        break;
                                    case 'validation_postal':
                                        echo 'Validation adresse postale';
                                        break;
                                    case 'archival_postal':
                                        echo 'Archivage adresse postale';
                                        break;
                                }
                                break;
                            case 'attachment':
                                $separatorPosition = strpos($dataHistory['name'], '_');
                                $action            = substr($dataHistory['name'], 0, $separatorPosition);
                                $attachementType   = substr($dataHistory['name'], $separatorPosition + 1);

                                switch ($action) {
                                    case 'upload':
                                        echo 'Chargement ' . $attachementType;
                                        break;
                                    case 'archival':
                                        echo 'Archivage ' . $attachementType;
                                        break;
                                }
                                break;
                        }

                        ?>
                    </td>
                    <?php foreach (['old', 'new'] as $historyValue) : ?>
                        <td>
                            <?php

                            switch ($dataHistory['type']) {
                                case 'bank_account':
                                    if ($dataHistory[$historyValue] instanceof BankAccount) {
                                        $bankAccount = $dataHistory[$historyValue];
                                        echo '
                                            IBAN : ' . $bankAccount->getIban() . '<br>
                                            BIC : ' . $bankAccount->getBic();
                                    }
                                    break;
                                case 'address':
                                    if ($dataHistory[$historyValue] instanceof ClientAddress || $dataHistory[$historyValue] instanceof CompanyAddress) {
                                        $address = $dataHistory[$historyValue];
                                        echo
                                            ($address->getAddress() ?? '') . '<br>' .
                                            ($address->getZip() ?? '') . ' ' . ($address->getCity() ?? '') . '<br>' .
                                            ($address->getIdCountry() ? $address->getIdCountry()->getFr() : '');
                                    }
                                    break;
                                case 'attachment':
                                    if ($dataHistory[$historyValue] instanceof Attachment) {
                                        $attachement = $dataHistory[$historyValue];
                                        echo '<a href="/attachment/download/id/' . $attachement->getId() . '/file/' . urlencode($attachement->getPath()) . '">' .
                                            ($attachement->getOriginalName() ?? $attachement->getPath()) .
                                            ' <img src="' . $this->surl . '/images/admin/attach.png" alt="">' .
                                            '</a>';
                                    }
                                    break;
                                default:
                                    if (is_string($dataHistory[$historyValue])) {
                                        echo $dataHistory[$historyValue];
                                    }
                                    break;
                            }

                            ?>
                        </td>
                    <?php endforeach; ?>
                    <td>
                        <?php if (null !== $dataHistory['user']) : ?>
                            <?= $dataHistory['user']->getFirstname() ?> <?= $dataHistory['user']->getName() ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </tbody>
</table>
