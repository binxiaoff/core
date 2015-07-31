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
//  Version : 2.3.1 
//  Date : 04/02/2011
//  Coupable : CM
//                                                                                   
// **************************************************************************************************** //

class Command
{
	var $Name = '';
	var $Function = '';
	var $Parameters = array();
	var $Language = false;

	function Command($controllerName,$functionName,$paramArray,$langue=false)
	{
		$this->Parameters = $paramArray;
		$this->Name = $controllerName;
		$this->Function = $functionName;
		$this->Language = $langue;
	}

	function getControllerName()
	{
		return $this->Name;
	}

	function setControllerName($controllerName)
	{
		$this->Name = $controllerName;
	}

	function getFunction()
	{
		return $this->Function;
	}

	function setFunction($functionName)
	{
		$this->Function = $functionName;
	}

	function getParameters()
	{
		return (array)$this->Parameters;
	}

	function setParameters($controllerParameters)
	{
		$this->Parameters = $controllerParameters;
	}
}