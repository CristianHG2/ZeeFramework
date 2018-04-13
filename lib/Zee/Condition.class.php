<?php

namespace Zee;

class Condition
{
	public $inits = array('w', 'l', 'o', 'lt', 'gt', 'lk', 'li');

	public $limit = '';
	public $wheres 	 = array();
	public $whereslt = [];
	public $wheresgt = [];
	public $wherelk = [];

	public $order = null;

	public function __construct($stmts)
	{
		if ( !is_array($stmts) )
			$stmts = array($stmts);

		foreach ( $stmts as $i )
		{
			$re = '/([a-z]*)\((.*)\)/U';

			preg_match_all($re, $i, $matches, PREG_SET_ORDER, 0);

			$matches = $matches[0];

			if ( !in_array($matches[1], $this->inits) )
				throw new Exception('Unsupported statement '.$matches[1]);

			switch ( $matches[1] )
			{
				case 'w':
					$args = explode(',', $matches[2]);

					if ( count($args) !== 2 )
						throw new Exception('Invalid argument count for statement '.$matches[0]);

					preg_match_all('/([A-z0-9_-]*)/', $args[0], $matches);

					if ( count($matches[0]) !== 2 )
						throw new Exception('Invalid characters for statement '.$matches[0]);

					$this->wheres[] = array($args[0], trim($args[1]));
				break;
				case 'o':
					$args = explode(',', $matches[2]);

					if ( count($args) !== 2 )
						throw new Exception('Invalid argument count for statement '.$matches[0]);

					$this->order = [$args[0], trim($args[1])];
				break;
				case 'li':
					$args = explode(', ', $matches[2]);

					if ( count($args) !== 2 || count($args) !== 1 )
						throw new Exception('Invalid argument count for statement '.$matches[0]);

					$this->limit = $args;
				break;
				case 'lt':
					$args = explode(',', $matches[2]);

					if ( count($args) !== 2 )
						throw new Exception('Invalid argument count for statement '.$matches[0]);

					preg_match_all('/([A-z0-9_-]*)/', $args[0], $matches);

					if ( count($matches[0]) !== 2 )
						throw new Exception('Invalid characters for statement '.$matches[0]);

					$this->whereslt[] = array($args[0], trim($args[1]));
				break;
				case 'gt':
					$args = explode(',', $matches[2]);

					if ( count($args) !== 2 )
						throw new Exception('Invalid argument count for statement '.$matches[0]);

					preg_match_all('/([A-z0-9_-]*)/', $args[0], $matches);

					if ( count($matches[0]) !== 2 )
						throw new Exception('Invalid characters for statement '.$matches[0]);

					$this->wheresgt[] = array($args[0], trim($args[1]));
				break;
				case 'lk':
					$args = explode(',', $matches[2]);

					if ( count($args) !== 2 )
						throw new Exception('Invalid argument count for statement '.$matches[0]);

					preg_match_all('/([A-z0-9_-]*)/', $args[0], $matches);

					if ( count($matches[0]) !== 2 )
						throw new Exception('Invalid characters for statement '.$matches[0]);

					$this->wherelk[] = array($args[0], '%'.trim($args[1]).'%');
				break;
			}
		}
	}

	public function getStmt()
	{
		$return = '';

		$params = array();
		$tempArr = array();

		$key = 0;

		$w = '';

		foreach ( $this->wheres as $i )
		{
			$w = ' WHERE ';

			$key++;
			$tempArr[] = $i[0].' = :'.$key.'_'.$i[0];
			$params[$key.'_'.$i[0]] = $i[1];
		}

		foreach ( $this->whereslt as $i )
		{
			$w = ' WHERE ';

			$key++;
			$tempArr[] = $i[0].' <= :'.$key.'_'.$i[0];
			$params[$key.'_'.$i[0]] = $i[1];
		}

		foreach ( $this->wheresgt as $i )
		{
			$w = ' WHERE ';

			$key++;
			$tempArr[] = $i[0].' >= :'.$key.'_'.$i[0];
			$params[$key.'_'.$i[0]] = $i[1];
		}

		foreach ( $this->wherelk as $i )
		{
			$w = ' WHERE ';

			$key++;
			$tempArr[] = $i[0].' LIKE :'.$key.'_'.$i[0];
			$params[$key.'_'.$i[0]] = $i[1];			
		}

		$order = '';

		if ( !is_null($this->order) )
			$order = ' ORDER BY '.$this->order[0].' '.$this->order[1];

		if ( count($this->limit) === 2 )
			$limit = ' LIMIT '.$this->limit[0].' OFFSET '.$this->limit[1];
		elseif ( count($this->limit) === 1 )
			$limit = ' LIMIT '.$this->limit[0];
		else
			$limit = '';

		return array($w.implode(' AND ', $tempArr).$order.$limit, $params);
	}
}
