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
class product_attribute_type extends product_attribute_type_crud
{
    const ELIGIBLE_LENDER_NATIONALITY       = 'product_eligible_lender_nationality';
    const ELIGIBLE_BORROWER_COMPANY_COUNTRY = 'product_eligible_borrower_company_country';
    const TARGET_COUNTRY                    = 'product_target_country';
    const ELIGIBLE_BORROWER_COMPANY_TYPE    = 'product_eligible_borrower_company_type';
    const ELIGIBLE_BORROWING_MOTIVE         = 'product_eligible_borrowing_motive';
    const MIN_LOAN_DURATION_IN_MONTH        = 'product_min_loan_duration_in_month';
    const MAX_LOAN_DURATION_IN_MONTH        = 'product_max_loan_duration_in_month';

    public function __construct($bdd, $params = '')
    {
        parent::product_attribute_type($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `product_attribute_type`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $result   = array();
        $resultat = $this->bdd->query($sql);
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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `product_attribute_type` ' . $where), 0, 0);
    }

    public function exist($id, $field = 'id_type')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `product_attribute_type` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }
}
