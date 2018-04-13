<?php

namespace Zee;

class Property
{
	static $PropReg = array();

	static function Register($Name, $obj)
	{
		if ( !is_object($obj) )
			throw new Exception('You may only register objects as page properties.');

		self::$PropReg[$Name] = $obj;
	}

	static function Get($name, $prop)
	{
		return self::$PropReg[$name]->$prop;
	}

	static function RegisterExists($name)
	{
		if ( isset(self::$PropReg[$name]) )
			return true;
		else
			return false;
	}

	static function PropertyExists($objectName, $Propname)
	{
		if ( self::RegisterExists($objectName) )
		{
			if ( isset(self::$PropReg[$objectName]->$Propname) )
				return true;
			else
				return false;
		}
		else
			return false;
	}
}