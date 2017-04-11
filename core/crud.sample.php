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
--initialisation--    }

    public function get($id, $field = '--id--')
    {
        $sql = 'SELECT * FROM  `--table--` WHERE ' . $field . ' = "' . $id . '"';
        $result = $this->bdd->query($sql);

        if ($this->bdd->num_rows($result) == 1) {
            $record = $this->bdd->fetch_assoc($result);

--remplissage--
            return true;
        } else {
            $this->unsetData();
            return false;
        }
    }

    public function update($cs = '')
    {
--escapestring--
        $sql = 'UPDATE `--table--` SET --updatefields-- WHERE --id-- = "' . $this->--id-- . '"';
        $this->bdd->query($sql);

        if ($cs == '') {
            --controleslug--
        } else {
            --controleslugmulti--
        }

        $this->get($this->--id--, '--id--');
    }

    public function delete($id, $field = '--id--')
    {
        if ($id == '') {
            $id = $this->--id--;
        }
        $sql = 'DELETE FROM `--table--` WHERE ' . $field . ' = "' . $id . '"';
        $this->bdd->query($sql);
    }

    public function create($cs = '')
    {
--escapestring--
        $sql = 'INSERT INTO `--table--`(--clist--) VALUES(--cvalues--)';
        $this->bdd->query($sql);

        $this->--id-- = $this->bdd->insert_id();

        if ($cs == '') {
            --controleslug--
        } else {
            --controleslugmulti--
        }

        $this->get($this->--id--, '--id--');

        return $this->--id--;
    }

    public function unsetData()
    {
--initialisation--    }

    public function multiInsert($data)
    {
        $insert  = [];
        $columns = implode(',', array_keys($data[0]));
        foreach ($data as $row) {
            $insertRow = [];
            foreach ($row as $column) {
                $insertRow[] = '"' . $column . '"';
            }
            $insert[] = '(' . implode(',', $insertRow) . ')';
        }

        $query = 'INSERT INTO `--table--` (' . $columns . ') VALUES ' . implode(',', $insert);
        $this->bdd->query($query);
    }
}
