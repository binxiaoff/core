<!DOCTYPE html>
<html>
<head>
    <title>Simulation webservice </title>
    <script type="text/javascript" src="<?= $this->surl ?>/scripts/admin/external/jquery/jquery.js"></script>
    <script>
        $(function () {
            $('#ws-provider-form').submit(function (event) {
                console.log($('#resource_label option:selected'))
                console.log($('#resource_label option:selected').val())
                if (! $('#resource_label option:selected').val()) {
                    event.preventDefault()
                    alert('Veuillez sélectionner un webservice')
                }
            })
        })
    </script>
</head>
<body>
    <form action="<?= $this->lurl ?>/simulation/wsProvider" method="post" id="ws-provider-form">
        <table>
            <thead>
            <tr>
                <th>Provider</th>
                <th>SIREN</th>
                <th>CountryCode</th>
                <th>ID bilan</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <select name="resource_label" id="resource_label" title="Nom du WS">
                        <option value="">-Sélectionner un webservice-</option>
                        <?php foreach ($this->resources as $resource) : ?>
                            <option value="<?= $resource['label'] ?>"<?= isset($_POST['resource_label']) && $_POST['resource_label'] === $resource['label'] ? ' selected' : '' ?>>
                                <?= $resource['provider_name'] . ' -> ' . $resource['resource_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="text" name="siren" value="<?= isset($_POST['siren']) ? $_POST['siren'] : '' ?>" title="SIREN">
                </td>
                <td>
                    <input type="text" name="countryCode" value="<?= isset($_POST['countryCode']) ? $_POST['countryCode'] : 'FR' ?>" title="Pays">
                </td>
                <td>
                    <input type="text" name="balanceId" value="<?= isset($_POST['balanceId']) ? $_POST['balanceId'] : '' ?>" title="Numéro de bilan Altares">
                </td>
            </tr>
            <tr>
                <td content="3"><input type="submit" name="send" value="Valider"></td>
            </tr>
            </tbody>
        </table>
    </form>
    <?php

    if (false === empty($this->result)) {
        echo '<pre>';
        var_export($this->result);
        echo '</pre>';
    }

    ?>
</body>
</html>
