<!DOCTYPE html>
<html>
<head>
    <title>Simulation webservice </title>
    <script type="text/javascript" src="<?= $this->surl ?>/scripts/admin/external/jquery/jquery.js"></script>
    <script>
        $(function () {
            $('#stored-data-form').submit(function (event) {
                if (! $('#resource_label option:selected').val()) {
                    event.preventDefault()
                    alert('Veuillez sélectionner un webservice')
                }
            })
        })
    </script>
</head>
<body>
    <form action="<?= $this->lurl ?>/simulation/storedData" method="post" id="stored-data-form">
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
                    <select name="resource_label" id="resource_label" title="Nom du WS">
                        <option>-Select a web service-</option>
                        <?php foreach ($this->resources as $resource) : ?>
                            <option value="<?= $resource['label'] ?>"<?= isset($_POST['resource_label']) && $_POST['resource_label'] === $resource['label'] ? ' selected' : '' ?>>
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
    <?php if (false === empty($this->result)) : ?>
        <pre><?php var_export($this->result); ?></pre>
    <?php endif; ?>
</body>
</html>
