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

class transactions_types extends transactions_types_crud
{
    const TYPE_LENDER_SUBSCRIPTION                 = 1;
    const TYPE_LENDER_LOAN                         = 2;
    const TYPE_LENDER_CREDIT_CARD_CREDIT           = 3;
    const TYPE_LENDER_BANK_TRANSFER_CREDIT         = 4;
    const TYPE_LENDER_REPAYMENT                    = 5;
    const TYPE_BORROWER_REPAYMENT                  = 6;
    const TYPE_DIRECT_DEBIT                        = 7;
    const TYPE_LENDER_WITHDRAWAL                   = 8;
    const TYPE_BORROWER_BANK_TRANSFER_CREDIT       = 9;
    const TYPE_UNILEND_REPAYMENT                   = 10;
    const TYPE_UNILEND_BANK_TRANSFER               = 11;
    const TYPE_FISCAL_BANK_TRANSFER                = 12;
    const TYPE_REGULATION_COMMISSION               = 13;
    const TYPE_LENDER_REGULATION                   = 14;
    const TYPE_BORROWER_REPAYMENT_REJECTION        = 15;
    const TYPE_WELCOME_OFFER                       = 16;
    const TYPE_WELCOME_OFFER_CANCELLATION          = 17;
    const TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER = 18;
    const TYPE_SPONSORSHIP_SPONSORED_REWARD        = 19;
    const TYPE_SPONSORSHIP_SPONSOR_REWARD          = 20;
    const TYPE_BORROWER_ANTICIPATED_REPAYMENT      = 22;
    const TYPE_LENDER_ANTICIPATED_REPAYMENT        = 23;
    const TYPE_REGULATION_BANK_TRANSFER            = 24;
    const TYPE_RECOVERY_BANK_TRANSFER              = 25;
    const TYPE_LENDER_RECOVERY_REPAYMENT           = 26;

    public function __construct($bdd, $params = '')
    {
        parent::transactions_types($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `transactions_types`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $result = $this->bdd->query('SELECT COUNT(*) FROM `transactions_types` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_transaction_type')
    {
        $sql    = 'SELECT * FROM `transactions_types` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }
}
