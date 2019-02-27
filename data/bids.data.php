<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Bids as BidsEntity, Wallet
};

class bids extends bids_crud
{
    const CACHE_KEY_PROJECT_BIDS = 'bids-projet';

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `bids`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT count(*) FROM `bids` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_bid')
    {
        $sql    = 'SELECT * FROM `bids` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function getAvgPreteur($id_lender, $champ = 'amount', $status = '')
    {
        if ($status != '') {
            $status = ' AND status IN(' . $status . ')';
        }

        $sql = 'SELECT AVG(' . $champ . ') as avg FROM bids WHERE id_wallet = ' . $id_lender . $status;

        $result = $this->bdd->query($sql);
        $avg    = $this->bdd->result($result);
        if ($avg == '') {
            $avg = 0;
        } else {
            $avg = $avg / 100;
        }
        return $avg;
    }

    public function sumBidsEncours($id_lender)
    {
        $sql = 'SELECT SUM(amount) FROM `bids` WHERE id_wallet = ' . $id_lender . ' AND status = ' . BidsEntity::STATUS_PENDING;

        $result  = $this->bdd->query($sql);
        $montant = (int)($this->bdd->result($result, 0, 0));
        return $montant / 100;
    }

    public function sum($where = '', $champ)
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT SUM(' . $champ . ') FROM `bids` ' . $where;

        $result = $this->bdd->query($sql);
        $return = (int)($this->bdd->result($result, 0, 0));

        return $return;
    }

    public function getNbPreteurs($id_project)
    {
        $sql = '
            SELECT COUNT(DISTINCT id_wallet) 
            FROM bids 
            WHERE id_project = ' . $id_project . ' AND status = ' . BidsEntity::STATUS_PENDING;

        $result = $this->bdd->query($sql);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function getLenders($iProjectId, $aStatus = array())
    {
        $iProjectId = $this->bdd->escape_string($iProjectId);
        $sStatus    = '';
        if (false === empty($aStatus)) {
            $sStatus = implode(',', $aStatus);
            $sStatus = $this->bdd->escape_string($sStatus);
        }
        $sQuery = '
            SELECT id_wallet,
                COUNT(*) AS bid_nb,
                SUM(amount) AS amount_sum
            FROM  bids
            WHERE id_project = ' . $iProjectId;

        if ('' !== $sStatus) {
            $sQuery .= ' AND status IN (' . $sStatus . ')';
        }

        $sQuery .= '
            GROUP BY id_wallet';

        $rQuery   = $this->bdd->query($sQuery);
        $aLenders = array();
        while ($aRow = $this->bdd->fetch_assoc($rQuery)) {
            $aLenders[] = $aRow;
        }

        return $aLenders;
    }

    public function shuffleAutoBidOrder($iProjectId)
    {
        $sShuffle = 'UPDATE  bids
                     SET ordre = (@current_order := @current_order + 1)
                     WHERE id_project = ' . $iProjectId . '
                     AND id_autobid != 0
                     ORDER BY RAND()';
        $this->bdd->query('SET @current_order := 0');
        $this->bdd->query($sShuffle);
    }

    /**
     * @param int $projectId
     * @return array
     */
    public function getBidsSummary($projectId)
    {
        $bidsByRate = array();

        if ($projectId) {
            $sql = '
                SELECT
                    rate,
                    COUNT(*) AS bidsCount,
                    SUM(IF(status = ' . BidsEntity::STATUS_PENDING . ', 1, 0)) AS activeBidsCount,
                    SUM(ROUND(amount / 100, 2)) AS totalAmount,
                    SUM(IF(status = ' . BidsEntity::STATUS_PENDING . ', ROUND(amount / 100, 2), 0)) AS activeTotalAmount,
                    IF(SUM(amount) > 0, ROUND(SUM(IF(status = ' . BidsEntity::STATUS_REJECTED . ', 0, ROUND(amount / 100, 2))) / SUM(ROUND(amount / 100, 2)) * 100, 1), 100) AS activePercentage
                FROM bids
                WHERE id_project = ' . $projectId . '
                GROUP BY rate
                ORDER BY rate DESC';

            $query = $this->bdd->query($sql);
            while ($row = $this->bdd->fetch_assoc($query)) {
                // Array keys cannot be float type and we need to remove the 0 decimal
                $rate = (string) ((float) $row['rate']);
                $bidsByRate[$rate] = $row;
            }
        }

        return $bidsByRate;
    }

    /**
     * @param int $projectId
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function getFirstProjectBidsByLender($projectId, $limit = 100, $offset = 0)
    {
        $bids = [];

        // This only works with MySQL as long as non-agregated columns could not be use on other DB systems
        $query = $this->bdd->query('
            SELECT bids.*, MIN(status) AS min_status
            FROM (SELECT * FROM bids WHERE id_project = ' . $projectId . ' ORDER BY id_wallet ASC, added ASC, id_bid ASC) bids
            GROUP BY id_wallet
            LIMIT ' . $limit . ' OFFSET ' . $offset
        );

        while ($row = $this->bdd->fetch_assoc($query)) {
            $bids[] = $row;
        }

        return $bids;
    }

    /**
     * @param int $projectId
     *
     * @return int
     *
     * @throws Exception
     */
    public function countLendersOnProject(int $projectId): int
    {
        $query = '
            SELECT COUNT(DISTINCT id_wallet) 
            FROM bids 
            WHERE status IN (:status) AND id_project = :projectId';

        $statement = $this->bdd->executeQuery(
            $query,
            ['projectId' => $projectId, 'status' => [BidsEntity::STATUS_PENDING, BidsEntity::STATUS_ACCEPTED]],
            ['section' => \PDO::PARAM_INT, 'status' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
        );

        return (int) $statement->fetchColumn(0);
    }

    /**
     * @param int $bidStatus
     * @param int|null $projectId
     * @param int|null $lenderId
     * @return array
     * @throws \Exception
     */
    public function getBidsByStatus($bidStatus, $projectId = null, $lenderId = null )
    {
        $sql = '
            SELECT
                id_bid,
                id_wallet,
                id_project,
                id_autobid,
                amount,
                rate,
                ordre,
                status,
                added,
                updated,
                ROUND(amount / 100) AS amount_euro
            FROM bids
            WHERE status = :status';

        if (false === empty($projectId)) {
            $sql .= ' AND id_project = :id_project';
            $bind['id_project'] = $projectId;
            $type['id_project'] = \PDO::PARAM_INT;
        }

        if (false === empty($lenderId)) {
            $sql .= ' AND id_wallet = :id_wallet';
            $bind['id_wallet'] = $lenderId;
            $type['id_wallet'] = \PDO::PARAM_INT;
        }

        $sql .= ' ORDER BY id_bid DESC';
        $bind['status'] = $bidStatus;
        $type['status'] = \PDO::PARAM_INT;

        return $this->bdd->executeQuery($sql, $bind, $type)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param Wallet        $wallet
     * @param DateTime|null $dateTimeStart
     * @param DateTime|null $dateTimeEnd
     *
     * @return array
     * @throws Exception
     */
    public function getBidsByLenderAndDates(Wallet $wallet, ?\DateTime $dateTimeStart = null, ?\DateTime $dateTimeEnd = null): array
    {
        $sql = '
            SELECT
                b.id_project, 
                b.id_bid, 
                w.id_client, 
                b.added, 
                CASE b.STATUS 
                    WHEN ' . BidsEntity::STATUS_PENDING . ' THEN "En cours" 
                    WHEN ' . BidsEntity::STATUS_ACCEPTED . ' THEN "OK" 
                    WHEN ' . BidsEntity::STATUS_REJECTED . ' THEN "KO" 
                END AS status, 
                ROUND(b.amount / 100) AS amount, 
                b.rate AS rate
            FROM bids b
              INNER JOIN wallet w ON w.id = b.id_wallet
            WHERE b.id_wallet = :idWallet';

        if ($dateTimeStart && $dateTimeEnd) {
            $dateTimeStart->setTime(0, 0, 0);
            $dateTimeEnd->setTime(23, 59, 59);
            $sql .= ' AND (b.added BETWEEN :dateStart AND :dateEnd)';
        }

        $paramValues = ['idWallet' => $wallet->getId(), 'dateStart' => $dateTimeStart, 'dateEnd' => $dateTimeEnd];
        $paramTypes  = ['idWallet' => \PDO::PARAM_INT, 'dateStart' => 'datetime', 'dateEnd' => 'datetime'];

        $statement = $this->bdd->executeQuery($sql, $paramValues, $paramTypes);
        $result    = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function getMaxCountBidsPerDay()
    {
        $query = '
            SELECT MAX(t.numberBids)
            FROM (
                SELECT COUNT(id_bid) AS numberBids
                FROM bids
                GROUP BY DATE(added)
            ) AS t';

        $statement = $this->bdd->executeQuery($query);

        return $statement->fetchColumn(0);
    }
}
