<?PHP
include_once "SharedFile.php";
include_once "FunctionParser.php";
include_once "GlobalInjector.php";
include_once "Type.php";
include_once("ThreadManager.php");

use NameSpaceSharedFile\SharedFile;
use NameSpaceThreadActionType\Type;

class Thread
{
    public $pid = null;
    public $state = Type::Idle;
   	public $callStack = [];
    public $dataUpToDate = false;

    public function __construct()
    {
        // Constructor
    }

    public function __destruct()
    {
        // Constructor
    }

    // SETTERS

    // GETTERS

    private function updateState($state){
    	GlobalInjector::setGlobals($state);
    }

    public function createThread()
    {
        $pid = pcntl_fork();

        if ($pid == -1) {
            // Fork failed
            //die('Could not create thread.');
        	return false;
        } elseif ($pid) {
            // This is the parent process
            $this->pid = $pid;
            $this->state = Type::Idle;
            return true;
        } else {
            // This is the child process
            try{
                $this->runThread();
            }catch(Exception $e){
            	echo "thread dropped. $e";
            }
        }
    }

    // Function to run in the child thread
    private function runThread()
    {
    	$i = 0;
    	$executed = [];
    	$executedDetails = [];
    	$recivednul = 0;
    	// $arg1 = 1;
    	// $arg2 = 2;
    	// $i < 4
    	// $i < 30
    	$hello = false;
    	$process = getmypid();
    	while(true){
            // Perform tasks specific to the thread
    		if (empty($this->callStack)){
    		    $thread = ThreadManager::getThread($process,$acquire = true);

    		    $vars = ThreadManager::getVariables();
    			if ($vars!== NULL) {
    			    GlobalInjector::setGlobals($vars);
    			    eval(GlobalInjector::setGlobalsTofunction($vars));
    			}

    		 }else{
    		 	$thread = $this;
    		 }

    		if ($thread === NULL) {
    			if($recivednul > 10){
    			    echo "\n$process 🧟‍♂️🧠 Zombieee!! 💥💥💥💥🔫, wheeh! one less zombie off the streets. \n";
    			    exit();
    			}

    			$recivednul += 1;
    			sleep(0.1);
    			continue;
    		}

    		$recivednul = 0;
    		

    		// // echo "\n -------------------- $i -------------------- \n";
    		if (empty($this->callStack)){
    		    // echo "\n STACK EMPTY  ".getmypid()."\n";
    			if (empty($executed)) {
    				// echo "\n STACK EMPTY  AND CALL STACK EMPTY \n";
    				$notInCallStack = array_diff($thread->callStack, $this->callStack);
    				// var_dump("notInCallStack");
    				// var_dump($notInCallStack);
    				// ThreadManager::dumpMemory();
    				$this->callStack = $notInCallStack;
    			
    			}
    		}

    		if (count($this->callStack)>0){
    			ThreadManager::updateState(getmypid(),Type::Active);
    			$this->state = Type::Active;
    			// pop stack
    			// $functionIdex = $this->callStack[0];
    			// unset($this->callStack[0]);
    			// $this->callStack = array_values($this->callStack);

    			    $functionIdex = array_shift($this->callStack);
				    // unset($this->callStack[0]);
				    // $this->callStack = array_values($this->callStack);
				

    			
    			// ThreadManager::acquireLock();
    			

    			// ThreadManager::releaseLock();
    			// get the function from mem and execute
    			$function = ThreadManager::getFunction($functionIdex);
    			// echo "\n PID ".getmypid()." WILL EXECUTE $functionIdex\n";
    			 
    			 if ($function!=NULL){
    			    $errors = NULL;
    			 
    			    try{
    			     	eval($function->generateExecutableString().";");
    			     	eval($function->generateCallableString().";");
    			    }catch (Exception $e) {
    			 		// Catch and handle the exception
    			 		$errors =  $e->getMessage();
    			 	}
    			 					
    				$executed[] = $functionIdex;
    			    $result = isset($result)?$result:NULL;
    			    $executedDetails[] = ["function"=>$functionIdex,"output"=>$result,"error"=>$errors];
    			 
    			 }
    		}	

    		$waiting = array_diff($this->callStack, $executed);
    		if((count($waiting) < 1) && (count($executed) > 0) ){
    			// Tell main thread we exectued the follwing command and yieled the output or error 
    			// echo "\n STACK EMPTY  AND FINISHED FIRST BATCH OF EXECUTION \n";
    			$outputs = [];

    				for ($i=0; $i < count($executedDetails); $i++) { 
    					$outputs[$executedDetails[$i]["function"]] = ["output"=>$executedDetails[$i]["output"],"error"=>$executedDetails[$i]["error"]];
    				}

    			ThreadManager::updateExecutedFunctions(getmypid(),$outputs);
    			//var_dump($function);
    			$this->callStack = [];
    			$executed = [];
    			$executedDetails = [];
    			// ThreadManager::updateState(getmypid(),Type::Idle);
    			}

    			if ($this->state == Type::Kill) {
    				break;
    			}

    		$i+=1;
            // Exit the child thread
            // sleep(1);
            usleep(ThreadManager::$clockCycle);
            }

            // echo "\n THREAD DONE \n";
            ThreadManager::updateState(getmypid(),Type::Kill);
            posix_kill(getmypid(), SIGTERM);
            //ThreadManager::dumpMemory();
        exit();
    }

    private function findMissingElements($array1, $array2) {
    // Find elements in $array2 that are not in $array1
    
    $missingElements = array_diff($array1, $array2);

    return $missingElements;
	
	}

    // Function to destroy a thread
    public function destroyThread()
    {
        if (isset($this->pid)) {
            posix_kill($this->pid, SIGKILL);
            pcntl_waitpid($this->pid, $status);
            $this->pid = NULL;
            return true;
        }
        return false;
    }


}

?>