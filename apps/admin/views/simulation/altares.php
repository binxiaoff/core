<form action="" method="post">
    <table>
        <tr>
            <td><label>siren : </label></td>
            <td><input type="text" name="siren" value="<?= (isset($_POST['siren']) ? $_POST['siren'] : '') ?>"/></td>
        </tr>
        <tr>
            <td><label>API : </label></td>
            <td>
                <input type="radio" name="api" value="1" <?= (false === isset($_POST['api']) || $_POST['api'] == 1 ? 'checked' : '') ?> >Eligibilité<br>
                <input type="radio" name="api" value="2" <?= (isset($_POST['api']) && $_POST['api'] == 2 ? 'checked' : '') ?>>Bilan<br>
            </td>
        </tr>
        <tr>
            <td><label>valider : </label></td>
            <td><input type="submit" name="send" value="Valider"/></td>
        </tr>
    </table>
</form>

<?php
if (false === empty($this->result)) {
    echo '<pre>';
    print_r($this->result);
    echo '</pre>';
}

