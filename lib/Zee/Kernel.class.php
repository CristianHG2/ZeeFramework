<?php

namespace Zee;

class Kernel
{
	static $SiteVars;
	private static $IncludedGates = [];

	
	static function AutoLoad($class)
	{
		$class = str_replace('\\', '/', $class);
		
		if ( file_exists(($path = LIB_DIR.'/'.$class.'.class.php')) )
			return include $path;

		if ( file_exists(($path = DATA_DIR.'/'.$class.'class.php')) )
			return include $path;

		$class = explode('/', $class);
		$class = $class[count($class) - 1];

		if ( \Zee\Model::TableExists($class) )
			return include self::CreateClass($class, '\Zee\Model');
	}

	static function CreateClass($class, $parent = '', $directory = DATA_DIR)
	{
		if ( is_writeable($directory) )
		{
			$path = $directory.'/'.$class.'.class.php';
			file_put_contents($path, '<?php '."\n\n".'namespace Zee\Data; '."\n\n".'class '.$class.(( strlen($parent) > 0 ) ? ' extends '.$parent : '').' { }');

			return $path;
		}
		else
			throw new Exception('Could not write to directory '.$directory);
	}

	static function Halt()
	{
		$Args = func_get_args();

		if ( is_object($Args[0]) )
		{
			$e = $Args[0];
			$e = (object) ['str' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()];
		}
		else
			$e = (object) ['str' => $Args[1], 'file' => $Args[2], 'line' => $Args[3]];

		$d = self::ErrorFile(serialize($e));

		\Response::Output('System error, code '.$d, false);
		exit;
	}

	static function ErrorFile($text, $email = true)
	{
		if ( !file_exists(ERROR_LOG.'/main.log') )
			file_put_contents(ERROR_LOG.'/main.log', json_encode([]));

		$db = json_decode(file_get_contents(ERROR_LOG.'/main.log'), true);
		$code = uniqid().time();

		while ( isset($db[$code]) )
		{
			$code = uniqid().time();
		}

		$db[$code] = $text;

		$json = json_encode($db);

		file_put_contents(ERROR_LOG.'/main.log', $json);

		return $code;
	}

	static function GetVar($var)
	{
		return self::$SiteVars[$var];
	}

	static function RunGates()
	{
		global $page;

		$Gates = Config('Gates')->GetAll();
		$controller = getcwd().'/'.basename($_SERVER['PHP_SELF']);

		foreach ( $Gates as $Gate => $GateConditions )
		{
			$files = [];

			foreach ( $GateConditions as $Condition )
			{
				$Condition = ROOT_DIR.$Condition;
				$files = array_merge($files, glob($Condition));
			}

			if ( in_array($controller, $files) )
			{
				$GatePath = GATES_DIR.'/'.$Gate.'.gate.php';

				if ( !file_exists($GatePath) )
					throw new \Exception('No such gate '.$GatePath);

				if ( !in_array($Gate, self::$IncludedGates) )
				{					
					include $GatePath;
					self::$IncludedGates[] = $Gate;

					$FunctionName = $Gate.'GateFunction';
					$page['gate'] = call_user_func($$FunctionName);
				}
			}
		}
	}

	static function SendBuffer()
	{
		global $page;

		if ( isset($page['id']) )
		{
			$Buffer = new Output($page['id'], $page);

			if ( isset($page['name']) )
				$Buffer->Vars['name'] = $page['name'];

			$Buffer->Run();

			self::$SiteVars = $Buffer->Vars;
		}
	}
}