<?php

class --classe--
{
    /** @var \Unilend\Bridge\Doctrine\DBAL\Connection */
    protected $bdd;

    --declaration--

    public function --table--($bdd, $params = '')
    {
        $this->bdd = $bdd;
        if ($params == '') {
            $params = [];
        }
        $this->params = $params;
        --initialisation--
    }

    public function get($list_field_value)
    {
        $list = '';
        foreach ($list_field_value as $champ => $valeur) {
            $list .= ' AND `' . $champ . '` = "' . $valeur . '" ';
        }

        $sql    = 'SELECT * FROM `--table--` WHERE 1=1 ' . $list . ' ';
        $result = $this->bdd->query($sql);

        if ($this->bdd->num_rows($result) == 1) {
            $record = $this->bdd->fetch_array($result);

            --remplissage--
            return true;
        } else {
            $this->unsetData();
            return false;
        }
    }

    public function update($list_field_value)
    {
        --escapestring--

        $list = '';
        foreach ($list_field_value as $champ => $valeur) {
            $list .= ' AND `' . $champ . '` = "' . $valeur . '" ';
        }

        $sql = 'UPDATE `--table--` SET --updatefields-- WHERE 1=1 ' . $list . ' ';
        $this->bdd->query($sql);

        --controleslugmulti--

        $this->get($list_field_value);
    }

    public function delete($list_field_value)
    {
        $list = '';
        foreach ($list_field_value as $champ => $valeur) {
            $list .= ' AND `' . $champ . '` = "' . $valeur . '" ';
        }

        $sql = 'DELETE FROM `--table--` WHERE 1 = 1 ' . $list . ' ';
        $this->bdd->query($sql);
    }

    public function create($list_field_value)
    {
        --escapestring--

        $sql = 'INSERT INTO `--table--`(--clist--) VALUES(--cvalues--)';
        $this->bdd->query($sql);

        --controleslugmulti--

        $this->get($list_field_value);
    }

    public function unsetData()
    {
        --initialisation--
    }

    public function multiInsert($data)
    {
        $insert  = [];
        $columns = implode(',', array_keys($data[0]));
        foreach ($data as $row) {
            $insertRow = [];
            foreach ($row as $column) {
                $insertRow[] = '"' . $column . '"';
            }
            $insert[] = '(' . implode(',', $insertRow) .')';
        }

       $query = 'INSERT INTO `--table--` (' . $columns . ') VALUES '.implode(',', $insert);
        $this->bdd->query($query);
    }
}
