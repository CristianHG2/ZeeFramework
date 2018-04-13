<?php

namespace Zee;

class Output
{
	public $Vars = array();
	public $Queue = array();

	private $IncludedGates = [];

	public function __construct($viewId, $vars)
	{
		$viewIdStr = $viewId;
		$viewId .= '.php';

		if ( !file_exists(VIEWS_DIR.'/'.$viewId) )
			throw new Exception('No such view '.VIEWS_DIR.'/'.$viewId);

		$this->Vars = $vars;
		$Headers = Config('Headers');

		if ( !$Headers->Exists($viewIdStr) )
			$Settings = $Headers->default;
		else
			$Settings = $Headers->$viewIdStr;

		$this->Queue = [$Settings->Head, $Settings->Header, $viewId, $Settings->Footer];
	}

	public function Tokenize($string)
	{
		preg_match_all('/{([A-z0-9_\\:]*)\((.*)\)}/U', $string, $Functions, PREG_SET_ORDER);
		preg_match_all('/%([A-z0-9_]*)%/U', $string, $SiteVars, PREG_SET_ORDER);
		preg_match_all('/\[([A-z0-9_]*)->([A-z0-9_]*)\]/U', $string, $ObjectTok, PREG_SET_ORDER);

		$FunctionTokens = array();
		$SitevarTokens = array();
		$ObjectTokens = array();

		foreach ( $SiteVars as $Var )
		{
			$stdC = new \stdClass;
			$stdC->Name = $Var[1];
			$stdC->Valid = true;
			$stdC->String = $Var[0];

			$SitevarTokens[] = $stdC;
		}

		foreach ( $ObjectTok as $Obj )
		{
			$stdC = new \stdClass;

			$stdC->Name = $Obj[1];
			$stdC->Prop = $Obj[2];
			$stdC->Valid = true;
			$stdC->String = $Obj[0];

			$ObjectTokens[] = $stdC;
		}

		foreach ( $Functions as $Func )
		{
			$stdC = new \stdClass;

			$stdC->Name = $Func[1];

			$Tokens = array();
			$Encapsulated = false;

			$TokenIndex = 0;

			$Escape = false;

			$JustClosed = false;

			for ( $i = 0; $i <= (strlen($Func[2]) - 1); $i++ )
			{
				$char = $Func[2][$i];

				if ( $Encapsulated && $char === '"' && !$Escape )
				{
					$Encapsulated = false;
					$TokenIndex++;

					$JustClosed = true;
				}

				if ( $Encapsulated && $char === '\\' )
					$Escape = true;

				if ( $Encapsulated )
					$Tokens[$TokenIndex] .= $char;

				if ( !$Encapsulated && $char === '"' && !$JustClosed )
				{
					$Encapsulated = true;
					$Tokens[$TokenIndex] = '';
				}
				elseif ( !$Encapsulated && ($char !== '"' && $char !== ',' && $char !== ' ') )
					throw new Exception('Unexpected character '.$char.' on function call '.$Func[0]);

				$JustClosed = false;
			}

			if ( $Encapsulated )
				throw new Exception('Unexpected end of string on function call '.$Func[0]);

			$stdC->Params = $Tokens;

			if ( !isset($stdC->Valid) )
				$stdC->Valid = true;

			$stdC->String = $Func[0];

			$FunctionTokens[] = $stdC;
		}

		return (object) ['Functions' => $FunctionTokens, 'Sitevars' => $SitevarTokens, 'ObjTok' => $ObjectTokens];
	}

	public function Interpret($string, $viewName)
	{
		$Tokens = $this->Tokenize($string);

		foreach ( $Tokens->Functions as $Function )
		{
			if ( is_callable($Function->Name) )
				$string = str_replace($Function->String, call_user_func_array($Function->Name, $Function->Params), $string);
			else
				throw new Exception('No such function or method '.$Function->String.' or its return value is false');
		}

		foreach ( $Tokens->Sitevars as $Var )
		{
			if ( isset($this->Vars[$Var->Name]) )
				$string = str_replace($Var->String, $this->Vars[$Var->Name], $string);
			else
				throw new Exception('No such site variable '.$Var->Name);
		}

		foreach ( $Tokens->ObjTok as $Prop )
		{
			if ( Property::PropertyExists($Prop->Name, $Prop->Prop) )
				$string = str_replace($Prop->String, Property::Get($Prop->Name, $Prop->Prop), $string);
			else
				throw new Exception('No such property '.$Prop->Name.'->'.$Prop->Prop);
		}

		return $string;
	}

	public function Run()
	{
		$HTML = '';

		foreach ( $this->Queue as $page )
		{
			ob_start();

			include VIEWS_DIR.'/'.$page;

			$HTML .= $this->Interpret(ob_get_contents(), $page);

			if ( DEBUG )
				ob_end_flush();
			else
				ob_end_clean();
		}

		print($HTML);
	}
}