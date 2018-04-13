<?php

namespace Zee;

class Config
{
	private $data;
	private $filename;

	public function __construct($JSON, $file)
	{
		$this->data = $JSON;
		$this->filename = $file;
	}

	public function Format($string, $params = array())
	{
		if ( $this->exists($string) )
			$string = $this->Get($string);

		if ( !is_array($params) && is_string($params) )
			$params = array($params);	

		foreach ( $params as $key => $i )
		{
			if ( strpos($i, '$') === 0 )
			{
				$name = \substr($i, 1, strlen($i));
				$params[$key] = $this->data->$name;
			}
		}
		
		return vsprintf($string, $params);
	}

	public function GetAll()
	{
		return $this->data;
	}

	private function RecurseFetch($name)
	{
		$return = $this->data;

		if ( count($Struct = explode('/', $name)) > 1 )
		{
			foreach ( $Struct as $i )
			{
				if ( !isset($return->$i) )
					return false;
				elseif ( is_array($return->$i) )
					$return = $return->$i;
				else
					$return = $return->$i;
			}

			return $return;
		}
		else
		{
			if ( isset($this->data->$name) )
				return $this->data->$name;
			else
				return false;
		}
	}

	public function Exists($name)
	{
		return (($this->RecurseFetch($name) !== false) ? true : false);
	}

	public function Get($name)
	{
		return $this->RecurseFetch($name);
	}

	public function Set($name, $value)
	{
		$this->data->$name = $value;

		if ( !file_put_contents($this->filename, json_encode($this->data, JSON_PRETTY_PRINT)) )
			throw new \Exception('Could not write to '.$this->filename);
	}

	public function __set($name, $value)
	{
		return $this->Set($name, $value);
	}

	public function __get($name)
	{
		return $this->Get($name);
	}
}