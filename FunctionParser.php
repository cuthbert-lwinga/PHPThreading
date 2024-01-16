<?PHP

class functionDeclaration{
	public $isFunctionCallableReady;
    public $isFunctionClosure;
    public $functionName;
    public $arguments;
    public $function;
    public $body;

	public function setFunctionData($isFunctionCallableReady,$isFunctionClosure, $functionName, $arguments, $function, $body)
    {
        $this->isFunctionCallableReady = $isFunctionCallableReady;
        $this->isFunctionClosure = $isFunctionClosure;
        $this->functionName = $functionName;
        $this->arguments = $arguments;
        $this->function = $function;
        $this->body = $body;
    }

    public function generateExecutableString(){
    	if($this->isFunctionCallableReady){
    		return $this->function;
    	}else{
    		return $this->functionName." = ".$this->function;
    	}

    	return "echo couldn't make ".$this->functionName." callable ";

    }

    public function generateCallableString(){
    	return '$result = '.$this->functionName.str_replace('&', '', $this->arguments);
    }

    public function extractVariablesPassedByRefrence($inputString) {
    // Use a regular expression to match variables with '&'
    $pattern = '/&\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';
    preg_match_all($pattern, $inputString, $matches);

    // Extracted variables with '&'
    $matchedVariables = $matches[1];

    return $matchedVariables;
}

	public function getFunctionCodeAsString($closure) {
	    $reflection = new ReflectionFunction($closure);
	    $filename = $reflection->getFileName();
	    $startLine = $reflection->getStartLine();
	    $endLine = $reflection->getEndLine();

	    $fileLines = file($filename);
	    $closureCode = implode('', array_slice($fileLines, $startLine - 1, $endLine - $startLine + 1));

	    return $closureCode;
	}


 public function getClosureCodeAsString($closure) {
	    $reflection = new ReflectionFunction($closure);
	    $filename = $reflection->getFileName();
	    $startLine = $reflection->getStartLine();
	    $endLine = $reflection->getEndLine();

	    $fileLines = file($filename);
	    $closureCode = implode('', array_slice($fileLines, $startLine - 1, $endLine - $startLine + 1));

	    return $closureCode;
	}


	public function getParameterNamesFromString($functionString) {
    $parameterPattern = '/\(\s*(.*?)\s*\)/';
    
    if (preg_match($parameterPattern, $functionString, $matches)) {
        $parameterList = $matches[1];
        $parameterNames = preg_split('/\s*,\s*/', $parameterList, -1, PREG_SPLIT_NO_EMPTY);
        return $parameterNames;
    } else {
        return [];
    }
}


	public function extractClosure($func){
		$arr = explode("(",$func);
	    unset($arr[0]);
	    $arr = array_values($arr);
	    $arr = implode("",$arr);
	    $arr = explode(");",$arr);
	    $arr[1] = ";";
	    $arr = implode("",$arr);
	    return $arr;
	}

	public function isClosure($functionString) {
    // Check for the common closure pattern
    $closurePattern = '/^\\s*function\\s*\\(/';
    if (preg_match($closurePattern, $functionString)) {
        return true;
    }

    // Check for the arrow function syntax (PHP 7.4+)
    $arrowFunctionPattern = '/^\\s*fn\\(/';
    if (preg_match($arrowFunctionPattern, $functionString)) {
        return true;
    }

    // Not a closure
    return false;
}

public function generateClosureCallString($closureString) {
    // Extract parameter names from the closure string
    preg_match('/function\s*\((.*?)\)/', $closureString, $matches);
    $parameterNames = isset($matches[1]) ? explode(',', $matches[1]) : [];

    // Trim parameter names and remove leading '$'
    $parameterNames = array_map(function ($param) {
        return '$'.trim($param, " \$");
    }, $parameterNames);

    // Generate the closure call string
    $arguments = "(" . implode(', ', $parameterNames) . ")";
    $caller = explode("=",$closureString);
    return trim($caller[0]).$arguments.";";
}

public function parseAndSetFunction($function){

	if (is_string($function)){
	
		$func = $this->getFunctionCodeAsString($function);
		// $code = str_replace(["\r", "\n", "\r\n"], ' ', $func);
		$parsed = $this->analyzeNonClosureFunction($func);

		if ($parsed === NULL) {
			return NULL;
		}

		$this->setFunctionData($parsed["isFunctionCallableReady"],$parsed["isFunctionClosure"], $parsed["functionName"], $parsed["arguments"], $parsed["function"], $parsed["body"]);
		return true;
	}else{
		
		if (is_callable($function)) {
				
			$func = $this->getClosureCodeAsString($function);   
			$code = str_replace(["\r", "\n", "\r\n"], ' ', $func);

    		$parsed = $this->closureCodeParsed($func);			
			    
			if ($parsed === NULL) {
				return NULL;
			}

			$this->setFunctionData($parsed["isFunctionCallableReady"],$parsed["isFunctionClosure"], $parsed["functionName"], $parsed["arguments"], $parsed["function"], $parsed["body"]);
			return true;
		}

	}

    return NULL;
}

// setFunctionData($isFunctionClosure, $functionName, $arguments, $function, $body)

// new functions 

	private function closureCodeParsed($code){

		$parsed = $this->analyzeFunctionStringClosureWithVariable($code);
		if ($parsed===NULL) {
			$parsed = $this->analyzeFunctionStringClosureWithNoVariable($code);
			if ($parsed !== NULL) {
				return $parsed;
			}	
		}else{
			return $parsed;
		}

		return NULL;
	}

	public function analyzeFunctionStringClosureWithVariable($inputString) {
	    $regex = '/(\$[a-z_]\w*)(\s*=\s*)(\s*function|fn\s*)(\s*\(.*?\)\s*)(\s*\{(?:[^{}]|(?R)|\{(?:[^{}]|(?R))*\})*\}\s*)/i';
	    $matches = [];

	    if (preg_match($regex, $inputString, $matches)) {

	    	$arguments = $matches[4];
	    	$arguments = explode("use", $arguments);

	        return [
	        	'isFunctionCallableReady' => true,
	            'isFunctionClosure' => true,
	            'functionName' => trim($matches[1]),
	            'arguments' => trim($arguments[0]),
	            'body' => trim($matches[5]),
	            'function' => trim($inputString),
	        ];
	    } else {
	        return null;
	    }
	}

	public function analyzeFunctionStringClosureWithNoVariable($inputString) {
    $functionRegex = '/(\s*function|fn\s*)(\s*\(.*?\)\s*)(\s*\{(?:[^{}]|(?R)|\{(?:[^{}]|(?R))*\})*\}\s*)/i';
    $matches = [];

    if (preg_match($functionRegex, $inputString, $matches)) {
		
		// var_dump($matches);
    	
    	$arguments = $matches[2];
	    $arguments = explode("use", $arguments);

        return [
            'isFunctionCallableReady' => false,
            'isFunctionClosure' => true,
            'functionName' => $this->generateUniqueVariable(),
            'arguments' => trim($arguments[0]),
            'body' => trim($matches[3]),
            'function' => trim($matches[0]),
        ];

    } else {
        return null;
    }
}

public function analyzeNonClosureFunction($inputString) {
    $nonClosureRegex = '/(\s*function\s+)([a-z_]\w*)(\s*\((.*?)\)\s*)(\s*\{([\s\S]*?)\}\s*)/i';
    $matches = [];

    if (preg_match($nonClosureRegex, $inputString, $matches)) {
        return [
        	'isFunctionCallableReady' => true,
            'isFunctionClosure' => false,
            'functionName' => trim($matches[2]),
            'arguments' => trim($matches[3]),
            'body' => trim($matches[5]),
            'function' => trim($matches[0]),
        ];
    } else {
        return null;
    }
}


	private function generateUniqueVariable() {
    // Generate a random string for uniqueness
    $randomString = bin2hex(random_bytes(4));

    // Create a unique variable name
    $variableName = '$var_' . $randomString;

    return $variableName;
	}


}
?>