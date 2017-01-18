<?php

class temporary_links_login extends temporary_links_login_crud
{
    const PASSWORD_TOKEN_LIFETIME_SHORT  = 'T1H';
    const PASSWORD_TOKEN_LIFETIME_MEDIUM = 'T6H';
    const PASSWORD_TOKEN_LIFETIME_LONG   = '1W';

    public function __construct($bdd, $params = '')
    {
        parent::temporary_links_login($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $resultat = $this->bdd->query('SELECT * FROM `temporary_links_login`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : '')));
        $result   = array();
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    /**
     * @param int    $clientId
     * @param string $lifetime
     * @return string
     */
    public function generateTemporaryLink($clientId, $lifetime = self::PASSWORD_TOKEN_LIFETIME_SHORT)
    {
        $token      = bin2hex(openssl_random_pseudo_bytes(16));
        $expiryDate = (new \DateTime('NOW'))->add(new \DateInterval('P' . $lifetime));

        $this->id_client = $clientId;
        $this->token     = $token;
        $this->expires   = $expiryDate->format('Y-m-d H:i:s');
        $this->create();

        return $token;
    }

    /**
     * @param int $clientId
     */
    public function revokeTemporaryLinks($clientId)
    {
        $queryBuilder = $this->bdd->createQueryBuilder();
        $queryBuilder
            ->update('temporary_links_login')
            ->set('expires', 'NOW()')
            ->where('id_client = :clientId')
            ->andWhere('expires > NOW()')
            ->setParameter('clientId', $clientId);

        $queryBuilder->execute();
    }
}
