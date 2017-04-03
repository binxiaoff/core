<form action="" method="post">
    <table>
        <thead>
        <tr>
            <th>Provider</th>
            <th>SIREN</th>
            <th>Number of days old</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                <select name="resource_label">
                    <option>-Select a web service-</option>
                    <?php foreach ($this->resources as $resource) : ?>
                        <option value="<?= $resource['label'] ?>">
                            <?= $resource['provider_name'] . ' -> ' . $resource['resource_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input title="siren" type="text" name="siren" value="<?= isset($_POST['siren']) ? $_POST['siren'] : '' ?>">
            </td>
            <td>
                <input title="Age de la réponse en jours" type="text" name="nbDaysAgo" value="<?= isset($_POST['nbDaysAgo']) ? $_POST['nbDaysAgo'] : '3' ?>">
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
    var_dump($this->result);
}