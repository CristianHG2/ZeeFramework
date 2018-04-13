<?php

namespace Zee;

class Model
{
	public $Table;
	public $Suffix;

	public $TestString;

	public $emptySuffix;

	private static $_instance = array();

	public function initModel($Table)
	{
		$db = Config('Database');

		$Table = explode('\\', $Table);
		$Table = $Table[count($Table) - 1];

		$this->Suffix = '';
		$this->emptySuffix = (object)['text' => '', 'params' => []];

		$this->Table = strtolower($Table);
		$this->dbh = new \PDO($db->Format('Dsn', array('$Host', '$Db')), $db->User, $db->Pswd, array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION));
	}

	static function Test($string)
	{
		self::GetInstance($instance);
		$instance->TestString = $string;
	}

	static function GetColumns()
	{
		self::GetInstance($instance);

		$stmt = $instance->dbh->prepare('DESCRIBE '.$instance->Table);
		$stmt->execute();

		$columns = array();

		foreach ( $stmt->fetchAll() as $row )
			$columns[] = $row['Field'];

		return $columns;
	}

	public function Update($data)
	{
		$instance = $this;

		$setStmt = array();

		$stmtParams = array();

		$ind = 0;

		foreach ( $data as $key => $i )
		{
			$stmtParams['values'.$ind] = $i;

			$setStmt[] = $key.' = :values'.$ind;

			$ind++;
		}

		$suffix = $instance->GetSuffix();
		
		$stmt = $instance->dbh->prepare('UPDATE '.$instance->Table.' SET '.implode(', ', $setStmt).$suffix->text);

		return $stmt->execute(array_merge($stmtParams, $suffix->params));
	}

	public function RowCount()
	{
		$instance = $this;

		$suffix = $instance->GetSuffix();
		$stmt = $instance->dbh->prepare('SELECT 1 FROM '.$instance->Table.$suffix->text);

		$stmt->execute($suffix->params);

		return $stmt->rowCount();
	}

	static function Insert($data)
	{
		self::GetInstance($instance);

		$keys = implode(', :', array_keys($data));
		$stmt = $instance->dbh->prepare('INSERT INTO '.$instance->Table.' ('.str_replace(':', '', $keys).') VALUES (:'.$keys.')');

		foreach ( $data as $key => $i )
			$data[':'.$key] = $i;

		$exec = $stmt->execute($data);
		$id = $instance->dbh->lastInsertId();

		return (($exec) ? $id : false);
	}

	static function TableExists($table)
	{
		$table = explode('/', $table);
		$table = $table[count($table) - 1];
		$table = strtolower($table);

		$db = Config('Database');

		$dbh = new \PDO($db->Format('Dsn', array('$Host', '$Db')), $db->User, $db->Pswd);

		$stmt = $dbh->prepare($db->Format('Query/TableExists', $table));

		return $stmt->execute();	
	}

	static function GetInstance(&$reference)
	{
		$class = get_called_class();

		if ( !isset(self::$_instance[$class]) )
		{
			self::$_instance[$class] = new $class;
			self::$_instance[$class]->initModel($class);
		}

		$reference = self::$_instance[$class];
	}

	public static function Clear()
	{
		self::GetInstance($instance);
		$instance->Suffix = '';

		return $instance;
	}

	public static function Select()
	{
		self::GetInstance($instance);

		$params = func_get_args();

		if ( is_array($params[0]) )
			$params = $params[0];

		$suffix = $instance->GetSuffix();

		$stmt = $instance->dbh->prepare('SELECT '.implode(', ', $params).' FROM '.$instance->Table.$suffix->text);

		$stmt->execute($suffix->params);

		$s = $stmt->fetchAll(\PDO::FETCH_ASSOC);

		return $s;
	}

	public function GetSuffix()
	{
		$s = new \stdClass();

		if ( !is_array($this->Suffix) )
		{
			$s->text = '';
			$s->params = array();
		}
		else
		{
			$s->text = ' '.$this->Suffix[0];
			$s->params = $this->Suffix[1];
		}

		return $s;
	}

	public function Delete()
	{
		$instance = $this;

		$suffix = $instance->GetSuffix();
		$stmt = $instance->dbh->prepare('DELETE FROM '.$instance->Table.$suffix->text);

		if ( !$stmt->execute($suffix->params) )
			return false;

		return $stmt->rowCount();
	}

	static function Condition($condition)
	{
		self::GetInstance($instance);

		$instance->Suffix = (new Condition($condition))->getStmt();

		return $instance;
	}
}