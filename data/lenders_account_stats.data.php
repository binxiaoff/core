<?php

class lenders_account_stats extends lenders_account_stats_crud
{

	public function __construct($bdd,$params='')
    {
        parent::lenders_account_stats($bdd,$params);
    }

       public function create($cs='')
    {
        $id = parent::create($cs);
        return $id;
    }

}