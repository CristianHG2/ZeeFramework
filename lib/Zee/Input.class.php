<?php

namespace Zee;

class Input
{
	static $Context;
	static $Manual = false;

	static function SetContext($data)
	{
		self::$Manual = true;
		self::$Context = $data;
	}

	static function ProcessContext()
	{
		global $_P;

		if ( !self::$Manual )
			self::$Context = $_P;
	}

	static function Set($inputs, $empty = true)
	{
		self::ProcessContext();

		foreach ( $inputs as $query )
		{
			if ( !isset(self::$Context[$query]) )
				return false;
			else
			{
				if ( $empty && strlen(self::$Context[$query]) < 1 )
					return false;
			}
		}

		return true;
	}

	static function String($val, $space = ' ')
	{
		self::ProcessContext();

		if ( $space !== ' ' )
			$space = '';

		return ((preg_match('/([A-z'.$space.'])/', self::$Context[$val]) === 0) ? false : true);
	}

	static function Digit($val)
	{
		self::ProcessContext();

		return ctype_digit($val);
	}

	static function Has($chars, $val)
	{
		for ( $i = 0; $i <= (strlen($chars) - 1); $i++ )
		{
			if ( strpos($val, $chars[$i]) !== false )
				return true;
		}

		return false;
	}
}