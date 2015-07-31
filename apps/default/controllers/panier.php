<?php

class panierController extends bootstrap
{
	var $Command;
	
	function panierController(&$command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
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