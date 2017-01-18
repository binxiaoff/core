<form action="" method="post">
    <table>
        <thead>
        <th>Provider</th>
        <th>Siren</th>
        <th>CountryCode</th>
        <th>Id Bilan</th>
        </thead>
        <tbody>
        <tr>
            <td>
                <select name="resourceId">
                    <option>-Select a web service-</option>
                    <?php foreach ($this->resources as $resource) : ?>
                        <option value="<?= $resource['id_resource'] ?>">
                            <?= $resource['provider_name'] . ' -> ' . $resource['resource_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="text" name="siren" value="<?= (isset($_POST['siren']) ? $_POST['siren'] : '') ?>"/>
            </td>
            <td>
                <input type="text" name="countryCode" value="<?= (isset($_POST['countryCode']) ? $_POST['countryCode'] : 'FR') ?>"/>
            </td>
            <td>
                <input type="text" name="balanceId" value="<?= (isset($_POST['balanceId']) ? $_POST['balanceId'] : 'FR') ?>"/>
            </td>
        </tr>
        <tr>
            <td content="3"><input type="submit" name="send" value="Valider"/></td>
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