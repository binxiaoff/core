<?php
// **************************************************************************************************** //
// ***************************************    ASPARTAM    ********************************************* //
// **************************************************************************************************** //
//
// Copyright (c) 2008-2011, equinoa
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
// associated documentation files (the "Software"), to deal in the Software without restriction,
// including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
// subject to the following conditions:
// The above copyright notice and this permission notice shall be included in all copies
// or substantial portions of the Software.
// The Software is provided "as is", without warranty of any kind, express or implied, including but
// not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.
// In no event shall the authors or copyright holders equinoa be liable for any claim,
// damages or other liability, whether in an action of contract, tort or otherwise, arising from,
// out of or in connection with the software or the use or other dealings in the Software.
// Except as contained in this notice, the name of equinoa shall not be used in advertising
// or otherwise to promote the sale, use or other dealings in this Software without
// prior written authorization from equinoa.
//
//  Version : 2.4.0
//  Date : 21/03/2011
//  Coupable : CM
//
// **************************************************************************************************** //

class bids extends bids_crud
{
    const STATUS_BID_PENDING                  = 0;
    const STATUS_BID_ACCEPTED                 = 1;
    const STATUS_BID_REJECTED                 = 2;
    const STATUS_AUTOBID_REJECTED_TEMPORARILY = 3;

    const CACHE_KEY_PROJECT_BIDS = 'bids-projet';

    public function __construct($bdd, $params = '')
    {
        parent::bids($bdd, $params);
    }

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

    public function getSoldeBid($idProject, $rate = null, $status = [])
    {
        $queryBuilder = $this->bdd->createQueryBuilder();
        $queryBuilder
            ->select('SUM(amount)')
            ->from('bids')
            ->where('id_project=:id_project')
            ->setParameter('id_project', $idProject);

        if (false === empty($rate)) {
            $queryBuilder->andWhere('ROUND(rate, 1) = ROUND(:rate, 1)');
            $queryBuilder->setParameter('rate', $rate);
        }

        if (is_array($status) && false === empty($status)) {
            $queryBuilder->andWhere('status in (:status)');
            $queryBuilder->setParameter('status', $status, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
        }

        $statement = $queryBuilder->execute();
        $solde = $statement->fetchColumn(0);

        if (null === $solde) {
            $solde = 0;
        } else {
            $solde = ($solde / 100);
        }

        return $solde;
    }

    public function getAvgPreteur($id_lender, $champ = 'amount', $status = '')
    {
        if ($status != '') {
            $status = ' AND status IN(' . $status . ')';
        }

        $sql = 'SELECT AVG(' . $champ . ') as avg FROM bids WHERE id_lender_account = ' . $id_lender . $status;

        $result = $this->bdd->query($sql);
        $avg    = $this->bdd->result($result);
        if ($avg == '') {
            $avg = 0;
        } else {
            $avg = $avg / 100;
        }
        return $avg;
    }

    // solde des bids d'un preteur
    public function getBidsEncours($id_project, $id_lender)
    {
        $nbEncours = $this->counter('id_project = ' . $id_project . ' AND id_lender_account = ' . $id_lender . ' AND status = 0');

        $sql = 'SELECT SUM(amount) as solde FROM bids WHERE id_project = ' . $id_project . ' AND id_lender_account = ' . $id_lender . ' AND status = 0';

        $result = $this->bdd->query($sql);
        $solde  = $this->bdd->result($result);
        if ($solde == '') {
            $solde = 0;
        } else {
            $solde = ($solde / 100);
        }

        return array('solde' => $solde, 'nbEncours' => $nbEncours);
    }

    public function sumBidsEncours($id_lender)
    {
        $sql = 'SELECT SUM(amount) FROM `bids` WHERE id_lender_account = ' . $id_lender . ' AND status = 0';

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
            SELECT COUNT(DISTINCT id_lender_account) 
            FROM bids 
            WHERE id_project = ' . $id_project . ' AND status = ' . self::STATUS_BID_PENDING;

        $result = $this->bdd->query($sql);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function getProjectMaxRate(\projects $project)
    {
        $result = $this->bdd->query('
            SELECT MAX(rate) 
            FROM bids 
            WHERE id_project = ' . $project->id_project . ' AND status = ' . self::STATUS_BID_PENDING);

        return round($this->bdd->result($result), 1);
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
            SELECT id_lender_account,
                COUNT(*) AS bid_nb,
                SUM(amount) AS amount_sum
            FROM bids
            WHERE id_project = ' . $iProjectId;

        if ('' !== $sStatus) {
            $sQuery .= ' AND status IN (' . $sStatus . ')';
        }

        $sQuery .= '
            GROUP BY id_lender_account';

        $rQuery   = $this->bdd->query($sQuery);
        $aLenders = array();
        while ($aRow = $this->bdd->fetch_assoc($rQuery)) {
            $aLenders[] = $aRow;
        }

        return $aLenders;
    }

    public function getAutoBids($iProjectId, $iStatus, $iLimit = 100, $iOffset = 0)
    {
        $sQuery = 'SELECT * FROM `bids` b
                   INNER JOIN autobid ab ON ab.id_autobid = b.id_autobid
                   WHERE b.id_project = ' . $iProjectId . '
                   AND b.status = ' . $iStatus . '
                   LIMIT ' . $iLimit . ' OFFSET ' . $iOffset;

        $rQuery = $this->bdd->query($sQuery);
        $aBids  = array();
        while ($aRow = $this->bdd->fetch_assoc($rQuery)) {
            $aBids[] = $aRow;
        }

        return $aBids;
    }

    public function getAcceptationPossibilityRounded()
    {
        $sQuery = '
            SELECT b.rate, COUNT(DISTINCT b.id_bid) AS count_bid
            FROM bids b
            INNER JOIN accepted_bids ab ON ab.id_bid = b.id_bid
            INNER JOIN projects p ON p.id_project = b.id_project
            WHERE p.status >= :funded
                AND p.status != :fundingKo
            GROUP BY b.rate
            ORDER BY b.rate DESC';

        try {
            $statement = $this->bdd->executeQuery($sQuery, array('funded' => \projects_status::FUNDE, 'fundingKo' => \projects_status::FUNDING_KO), array('funded' => \PDO::PARAM_INT, 'fundingKo' => \PDO::PARAM_INT), new \Doctrine\DBAL\Cache\QueryCacheProfile(300, md5(__METHOD__)));
            $result    = $statement->fetchAll(PDO::FETCH_ASSOC);
            $statement->closeCursor();
        } catch (\Doctrine\DBAL\DBALException $ex) {
            return false;
        }
        $iTotal = 0;

        foreach ($result as $aRow) {
            $iTotal += $aRow['count_bid'];
        }

        $aPercentage = array();
        $iSubTotal   = 0;

        foreach ($result as $aRate) {
            $iSubTotal += $aRate['count_bid'];
            $sRate               = (string) number_format($aRate['rate'], 1);
            $aPercentage[$sRate] = ($iSubTotal / $iTotal) * 100;

            if ($aPercentage[$sRate] < 1) {
                $aPercentage[$sRate] = 1;
            } elseif ($aPercentage[$sRate] > 99) {
                $aPercentage[$sRate] = 99;
            } else {
                $aPercentage[$sRate] = (int) $aPercentage[$sRate];
            }
        }
        return $aPercentage;
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
                    SUM(IF(status = 0, 1, 0)) AS activeBidsCount,
                    SUM(ROUND(amount / 100, 2)) AS totalAmount,
                    SUM(IF(status = 0, ROUND(amount / 100, 2), 0)) AS activeTotalAmount,
                    IF(SUM(amount) > 0, ROUND(SUM(IF(status = 2, 0, ROUND(amount / 100, 2))) / SUM(ROUND(amount / 100, 2)) * 100, 1), 100) AS activePercentage
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
     * @return array
     */
    public function getLastProjectBidsByLender($projectId, $limit = 100, $offset = 0)
    {
        $bids = array();

        // This only works with MySQL as long as non-agregated columns could not be use on other DB systems
        $query = $this->bdd->query('
            SELECT *
            FROM (SELECT * FROM bids WHERE id_project = ' . $projectId . ' ORDER BY id_lender_account ASC, id_bid DESC) bids
            GROUP BY id_lender_account
            LIMIT ' . $limit . ' OFFSET ' . $offset
        );

        while ($row = $this->bdd->fetch_assoc($query)) {
            $bids[] = $row;
        }

        return $bids;
    }

    public function countBidsOnProjectByStatusForLender($iLenderId, $iProjectID, $iStatus)
    {
        $aBind = array('lenderId' => $iLenderId, 'projectId' => $iProjectID, 'status' => $iStatus);
        $aType = array('lenderId' => \PDO::PARAM_INT, 'projectId' => \PDO::PARAM_INT, 'status' => \PDO::PARAM_INT);

        $sQuery = 'SELECT count(*) FROM bids WHERE id_lender_account = :lenderId AND id_project = :projectId and status = :status';
        $oStatement = $this->bdd->executeQuery($sQuery, $aBind, $aType);

        return $oStatement->fetchColumn(0);
    }

    public function countLendersOnProject($projectId)
    {
        $aBind = array('projectId' => $projectId);
        $aType = array('section' => \PDO::PARAM_INT);

        $sQuery     = 'SELECT COUNT(DISTINCT id_lender_account) FROM `bids` WHERE id_project = :projectId';
        $oStatement = $this->bdd->executeQuery($sQuery, $aBind, $aType);

        return $oStatement->fetchColumn(0);
    }

    public function getNumberActiveBidsByRate($projectId)
    {
        $aBind = array('projectId' => $projectId, 'bidStatus' => self::STATUS_BID_PENDING);
        $aType = array('projectId' => \PDO::PARAM_INT, 'bidStatus' => \PDO::PARAM_INT);

        $sQuery = ' SELECT rate, count(*) as nb_bids
                    FROM bids
                    WHERE id_project = :projectId AND status = :bidStatus
                    GROUP BY rate ORDER BY rate DESC';

        $oStatement = $this->bdd->executeQuery($sQuery, $aBind, $aType);
        $bids  = array();
        while ($aRow = $oStatement->fetch(\PDO::FETCH_ASSOC)) {
            $bids[] = $aRow;
        }

        return $bids;
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
                id_lender_account,
                id_project,
                id_autobid,
                id_lender_wallet_line,
                amount,
                rate,
                ordre,
                status,
                checked,
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
            $sql .= ' AND id_lender_account = :id_lender';
            $bind['id_lender'] = $lenderId;
            $type['id_lender'] = \PDO::PARAM_INT;
        }

        $sql .= ' ORDER BY id_bid DESC';
        $bind['status'] = $bidStatus;
        $type['status'] = \PDO::PARAM_INT;

        return $this->bdd->executeQuery($sql, $bind, $type)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param \lenders_accounts $lender
     * @param DateTime|null $dateTimeStart
     * @param DateTime|null $dateTimeEnd
     * @return array
     */
    public function getBidsByLenderAndDates(\lenders_accounts $lender, $dateTimeStart = null, $dateTimeEnd = null)
    {
        $sql = '
            SELECT  b.id_project, b.id_bid, la.id_client_owner, b.added, (CASE b.STATUS WHEN 0 THEN "En cours" WHEN 1 THEN "OK" WHEN 2 THEN "KO" END) AS status, ROUND((b.amount / 100), 0) AS amount, REPLACE (b.rate, ".", ",") AS rate
            FROM bids b
            INNER JOIN lenders_accounts la ON la.id_lender_account = b.id_lender_account
            WHERE b.id_lender_account = :idLenderAccount';

        if ($dateTimeStart && $dateTimeEnd) {
            $sql .= ' AND (b.added BETWEEN :dateStart AND :dateEnd)';
        }

        $paramValues = array('idLenderAccount' => $lender->id_lender_account, 'dateStart' => $dateTimeStart, 'dateEnd' => $dateTimeEnd);
        $paramTypes  = array('idLenderAccount' => \PDO::PARAM_INT, 'dateStart' => 'datetime', 'dateEnd' => 'datetime');

        $statement = $this->bdd->executeQuery($sql, $paramValues, $paramTypes);
        $result    = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function getMaxCountBidsPerDay()
    {
        $query = 'SELECT MAX(t.numberBids)
                    FROM (
                           SELECT count(id_bid) AS numberBids
                           FROM bids
                           GROUP BY DATE(added)) AS t';

        $statement = $this->bdd->executeQuery($query);

        return $statement->fetchColumn(0);
    }


}
