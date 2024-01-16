<?php
include_once "SharedFile.php";
include_once "FunctionParser.php";
include_once "GlobalInjector.php";
include_once "Type.php";

use NameSpaceSharedFile\SharedFile;
use NameSpaceThreadActionType\Type;

function analyzeUnserialize($serializedString) {
    // Check if the customErrorHandler function is already defined
    if (!function_exists('customErrorHandler')) {
        // Custom error handler function
        function customErrorHandler($errno, $errstr, $errfile, $errline) {
            if ($errno == E_NOTICE || $errno == E_WARNING) {
                // Check if the error is related to unserialize
                if (strpos($errstr, 'unserialize') !== false) {
                    // Handle unserialize warning or notice
                    $GLOBALS['unserializeError'] = $errstr;
                }
            }

            // Continue with the default PHP error handler
            return false;
        }
    }

    // Check if the error handler is not already set
    if (!set_error_handler('customErrorHandler')) {
        // Set the custom error handler
        set_error_handler('customErrorHandler');
    }

    // Attempt to unserialize the data
    $unserializedData = unserialize($serializedString);

    // Restore the default error handler
    restore_error_handler();

    // Check if there was an unserialize error
    if (isset($GLOBALS['unserializeError'])) {
        return $GLOBALS['unserializeError'];
    }

    return null; // No error occurred
}


class Memory
{
    private static $holder = "Memory";
    public $pids = [];
    public $variables = [];
    public $threads = [];
    public $functions = [];
    public static $object = null;
    private $updated = false;
    private $semaphoreKey = null;
    private $semaphore;
    private $executed = []; // ["function"=>index,"output"=>"","error"=>""]
    private static $File;

    public function __construct()
    {
        if (self::$object === null) {
        	$this->semaphoreKey = ftok(__FILE__, 'a');
            SharedFile::initialize(Memory::$holder, $type = "w+");
            $this->semaphore = sem_get($this->semaphoreKey, 1, 0666, 1);
            self::$File = tempnam(
                sys_get_temp_dir(),
                "shared_file_Memory.bin"
            );
            // file_put_contents(self::$File, '');
            SharedFile::write(Memory::$holder, self::$File);
            self::$object = $this;
            $this->save();
        }
    }

	public function serialize() {
        // Serialize only necessary properties (excluding $semaphore)
        $instance = Memory::getInstance();
        // var_dump($instance->findSerializeError([$instance->pids, $instance->variables, $instance->threads, $instance->functions, $instance->updated, $instance->semaphoreKey,$instance->executed]));
        return @serialize([$instance->pids, $instance->variables, $instance->threads, $instance->functions, $instance->updated, $instance->semaphoreKey,$instance->executed]);
    }

    public function unserialize($serialized) {
        // Unserialize the data
     	$instance = Memory::getInstance(); 
        list($instance->pids, $instance->variables, $instance->threads, $instance->functions, $instance->updated, $instance->semaphoreKey,$instance->executed) = @unserialize($serialized);

	// $errorDetails = analyzeUnserialize($serialized);

	// if ($errorDetails !== null) {
	//     // echo "Unserialize error: $errorDetails";
	// } 


     }


public function findSerializeError($originalData) {
    $recalculatedData = preg_replace('!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $originalData);

    $max = max(strlen($originalData), strlen($recalculatedData));

    echo "Original Data:\n";
    echo $originalData . "\n\n";
    echo "Recalculated Data:\n";
    echo $recalculatedData . "\n\n";

    for ($i = 0; $i < $max; $i++) {
        $charOriginal = isset($originalData[$i]) ? $originalData[$i] : '';
        $charRecalculated = isset($recalculatedData[$i]) ? $recalculatedData[$i] : '';

        if ($charOriginal !== $charRecalculated) {
            echo "Difference at position $i:\n";
            echo "  Original:      $charOriginal (ORD: " . ord($charOriginal) . ")\n";
            echo "  Recalculated:  $charRecalculated (ORD: " . ord($charRecalculated) . ")\n";

            $start = max(0, $i - 20);
            $length = 40;

            echo "  Original Section:      " . substr($originalData, $start, $length) . "\n";
            echo "  Recalculated Section:  " . substr($recalculatedData, $start, $length) . "\n\n";
        }
    }
}

    public function var($name, $value)
    {
    
    	$instance = Memory::getInstance();
    	// sem_acquire($instance->semaphore);
    	$instance->reload();
        $instance->variables[$name] = $value;
        $instance->save();
        // sem_release($instance->semaphore);
    
    }



    public function getVariables()
    {

    	$instance = Memory::getInstance();
    	// sem_acquire($instance->semaphore);
    	$instance->reload();
    	// sem_release($instance->semaphore);
        return $instance->variables;

    }

    public function addThread($thread)
    {
    	$instance = Memory::getInstance();
    	// sem_acquire($instance->semaphore);
    	$instance->reload();
        $instance->threads[] = $thread;

        $instance->save();

        // sem_release($instance->semaphore);
    }

    public function getExecuted()
    {
    	$instance = Memory::getInstance();
    	// sem_acquire($instance->semaphore);
    	$instance->reload();
    	return $instance->executed;
        // sem_release($instance->semaphore);
    }

    public function clearExecuted()
    {
    	$instance = Memory::getInstance();
    	// sem_acquire($instance->semaphore);
    	$instance->reload();
    	$instance->executed = [];
        $instance->save();
        // sem_release($instance->semaphore);
    }

    public function updateThreadState($pid,$state)
    {
    	$instance = Memory::getInstance();
    	// sem_acquire($instance->semaphore);
    	$instance->reload();
    	if ($instance->threads !== NULL) {
    	    	for($i = 0; $i < count($instance->threads);$i++) {
    	        	if ($instance->threads[$i]->pid === $pid) {
    	        		$instance->threads[$i]->state = $state;
    	        	}
    	        }
    	}
        $instance->save();
        // sem_release($instance->semaphore);
    }

    public function semAcquire(){
    	$instance = Memory::getInstance();
    	sem_acquire($instance->semaphore);
    }

    public function semRelease(){
    	$instance = Memory::getInstance();
    	sem_release($instance->semaphore);
    }

    public function getThread($thread,$acquire = false)
    {
    	$instance = Memory::getInstance();
    	$instance->reload($acquire);
		
    	if ($instance->threads == NULL) {
    		//var_dump($instance);
    	}

		if ($instance->threads){
	        for($i = 0; $i < count($instance->threads);$i++) {
	        	if ($instance->threads[$i]->pid == $thread) {
	        		return $instance->threads[$i];
	        	}
	        }
        }

        return NULL;
    }


    public function updateExecutedFunctions($thread,$functions)
    {
    	$instance = Memory::getInstance();
    	// sem_acquire($instance->semaphore);
    	$instance->reload();

    	$instance->executed = $instance->executed + $functions;
    	$keysAsInt = array_map('intval', array_keys($functions));

    	for($i = 0; $i < count($instance->threads);$i++) {
        	if ($instance->threads[$i]->pid == $thread) {
        		$instance->threads[$i]->callStack = array_diff($instance->threads[$i]->callStack, $keysAsInt);
        	}
        }

        $instance->save();
        // sem_release($instance->semaphore);
        
    }

    public function getFunction($i)
    {
    	$instance = Memory::getInstance();
    	$instance->reload();

    	if ($instance->functions !== NULL){
    	        if ($i<count($instance->functions)) {
    	        	return $instance->functions[$i];
    	        }
    	}
        return NULL;
    }

    public function func($functionOrClosure)
    {	
    	
    	$return = NULL;
    	$instance = Memory::getInstance();
    	// sem_acquire($instance->semaphore);
        $instance->reload($acquire = false);
        $function = new functionDeclaration();
        
        $return = $function->parseAndSetFunction($functionOrClosure);

        if ($return === NULL) {
        	// sem_release($instance->semaphore);
            	throw new Exception(" Whoopsie-daisy! 🌪️ The ops have caught you in a bit of a tangle. 👮🏿‍♂️ Right now, we're only rolling out the red carpet for anonymous functions or functions that have already declared their presence. 🎭 So, let's keep it hush-hush for now! 🤫 Keep rocking the code and stay cheeky! 🚀😄");
        }else{
        	$instance->functions[] = $function;
        	$return = count($instance->functions) - 1;
        }

        $instance->save();
       
        // sem_release($instance->semaphore);
    	return $return;
    }

    // this will returna list of pid with the most - least amount of unctions in their execute stack
    public function loadChecker(){
    $instance = Memory::getInstance();
    // sem_acquire($instance->semaphore);
    $threads = $instance->threads; // Assuming $instance->functions should be $instance->threads
    // sem_release($instance->semaphore);
    $ordered = [];

    // Check if there are threads
    if (count($threads) === 0) {
        throw new Exception("\n 🚨🚓 Wee-woo, wee-woo! Officer on duty, please initialize a thread for this operation! 🚓🚨  \n");
        return NULL;
    }

    // Calculate and store the count of callStack for each thread
    $threadCounts = [];
    foreach ($threads as $thread) {
        $threadCounts[$thread->pid] = count($thread->callStack);
    }

    // Sort threads based on the count of callStack in descending order
    arsort($threadCounts);

    // Create an ordered array of threads
    foreach ($threadCounts as $threadId => $count) {
        foreach ($threads as $thread) {
            if ($thread->pid === $threadId) {
                $ordered[] = $thread;
                break;
            }
        }
    }

    return $ordered;
}


    public function assignFunctionToThread($function,$pid){
    
    	$instance = Memory::getInstance();
        $found = true;
        $instance->reload();
    	$thread = $instance->getThread($pid);
    	$found = false;
    	
    	if ($thread === null) {
    		return $found;	
    	}
    	
    	$found = true;

    	for ($i=0; $i < count($instance->threads); $i++) {
    		
    		if ($instance->threads[$i]->pid === $pid ) {
    			
    			$instance->threads[$i]->callStack[] = $function;
    		}

    	}

    	$instance->save();

    	return $found;

    }

    public function save($acquire=false)
    {

    	if ($acquire) {
    		sem_acquire(self::$object->semaphore);
    	}

    	$instance = Memory::getInstance();
        $serializedData = $this->serialize($instance);
        file_put_contents(self::$File, $serializedData);

        if ($acquire) {
    		sem_release(self::$object->semaphore);
    	}    
    }

    public function reload($acquire=false)
    {
    	if ($acquire) {
    		sem_acquire(self::$object->semaphore);
    	}
    	

    	$File = SharedFile::read(Memory::$holder);
        if ((self::$object !== null) && file_exists($File)) {
            $serializedData = file_get_contents($File);
            $this->unserialize($serializedData);
        }

        if ($acquire) {
    		sem_release(self::$object->semaphore);
    	}
    }

    // Other functions

    public static function getInstance()
    {
        return self::$object;
    }

    public static function erase()
    {
        if (file_exists(self::$File)) {
        	unlink(self::$File);
        	// Release the semaphore
	        sem_remove(self::$object->semaphore);
	        // Optionally, you may unset the property if you no longer need it
	        unset(self::$object->semaphoreKey);
        }
    }


}

?>
