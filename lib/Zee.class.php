<?php

class Zee
{
	static $UnendedTags = ['link'];

	private static function GenerateTag($name, $attribute = [], $end = '')
	{
		$attr = [];

		foreach ( $attribute as $key => $val )
			$attr[] = $key.'="'.$val.'"';

		if ( $end === true )
			$end = '</'.$name.'>';

		return '<'.$name.' '.implode(' ', $attr).'>'.$end;
	}

	private static function Tag($tagName, $attributes = '', $condition = false)
	{
		global $page;

		if ( $condition !== false && strpos($condition, '!') === 0 )
		{
			if ( $page['id'] === substr($condition, 1, strlen($condition)) )
				return '';
		}

		if ( $condition !== false && strpos($condition, '!') !== 0 )
		{
			if ( $condition !== $page['id'] )
				return '';
		}

		preg_match_all('/\((.*):(.*)\)/U', $attributes, $matches, PREG_SET_ORDER);

		$attributes = [];

		foreach ( $matches as $set )
		{
			if ( count($set) === 3 )
				$attributes[$set[1]] = $set[2];
		}

		if ( in_array($tagName, self::$UnendedTags) )
			return self::GenerateTag($tagName, $attributes);
		else
			return self::GenerateTag($tagName, $attributes, true);
	}

	private static function ResourceWrap($file, $sub = false)
	{
		if ( $sub !== false )
			$sub = $sub.'/';

		return Config('Site')->get('resource').$sub.$file;
	}

	static function HTTP($error)
	{
		return http_response_code($error);
	}

	static function Css($file, $condition = false)
	{
		$file = self::ResourceWrap($file, 'css');

		return self::Tag('link', '(href:'.$file.'?v='.time().')(rel:stylesheet)(type:text/css)', $condition);
	}

	static function Js($file, $condition = false)
	{
		$file = self::ResourceWrap($file, 'js');

		return self::Tag('script', '(src:'.$file.'?v='.time().')(type:text/javascript)', $condition);	
	}

	static function Wrap($file)
	{
		return self::ResourceWrap($file, false);
	}

	static function ViewRaw($view)
	{
		ob_start();

			include VIEWS_DIR.'/'.$view.'.php';
			$content = ob_get_contents();

		ob_end_clean();

		return $content;
	}
}
