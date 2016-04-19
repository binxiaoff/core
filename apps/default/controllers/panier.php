<?php

class panierController extends bootstrap
{
	var $Command;

	public function initialize()
	{
		parent::initialize();
		
		$this->catchAll = true;
	}
	function _default()
	{
		echo 'ctrl panier def  ';
	}
	
	
	function _identification()
	{
		
		echo 'ctrl panier ident ';
	}
}