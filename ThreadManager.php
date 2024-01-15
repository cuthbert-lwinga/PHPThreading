<?PHP
include_once("Memory.php");
include_once("Thread.php");
include_once("FunctionParser.php");
include_once("Type.php");
include_once "GlobalInjector.php";

use NameSpaceThreadActionType\Type;

class ThreadManager {
    private static $threads = [];
    private static $lastThreadInserted = 0;
    private static $memory = NULL;
    public static $clockCycle = 1000;

    public static function warmUp($count = 2){
        
        for ($i=0; $i < $count; $i++) {
            $pid = self::init();
            if ($pid!==NULL){
                self::$threads[] = $pid;
            }
        }

    }

    public static function wait(){
        
        for ($i=0; $i < count(self::$threads); $i++) {
            self::join(self::$threads[$i]);
        }
        
    }

    public static function shutdown(){
        
        for ($i=0; $i < count(self::$threads); $i++) {
            self::kill(self::$threads[$i]);
        }

        ThreadManager::$memory->erase();

        
    }



    // returns pid
    public static function init(){
        self::startMem();
        $thread = new Thread();
        if ($thread->createThread()) {
            // succeded
            self::addThread($thread);
        }else{
            throw new Exception("Could not create thread");
        }

        return $thread->pid;
    }

    private static function startMem(){
        if (ThreadManager::$memory === NULL) {
            ThreadManager::$memory = new Memory();
        }
    }

    public static function addThread($thread){

        ThreadManager::acquireLock();
        ThreadManager::$memory->addThread($thread);
        ThreadManager::releaseLock();
        
    }
    public static function var($name, $value,$acquire = true){
        // self::startMem();
        
        if ($acquire){
            ThreadManager::acquireLock();
        }
        
        ThreadManager::$memory->var($name, $value);
        
        if ($acquire){
            ThreadManager::releaseLock();
        }
    }

    public static function getVar($name,$acquire = true){
        // self::startMem();
        if ($acquire){
            ThreadManager::acquireLock();
        }

        $return = ThreadManager::$memory->getVariables();
        
        if ($acquire){
            ThreadManager::releaseLock();
        }

        return @$return[$name];
    }

    public static function execute($closure,$pid=NULL){
        self::startMem();
        ThreadManager::acquireLock();
        $identifier = (ThreadManager::$memory->func($closure));
        
        if ($pid === NULL){
            $processes = ThreadManager::$memory->threads;//ThreadManager::$memory->loadChecker();
            
            if ($processes === NULL){
                return NULL;
            }
            // $index = (count($processes) - 1);
            $thread = $processes[self::$lastThreadInserted];
            
            self::$lastThreadInserted = (self::$lastThreadInserted + 1) % count($processes);
            
            if($thread!==NULL){
                                
                $thread = $thread->pid;

                $found = ThreadManager::$memory->assignFunctionToThread($identifier,$thread);
                
                if ($found==false) {
                    throw new Exception("\n This thread was not found, to add function [$identifier] \n");
                }

            }
        }
        else{
            ThreadManager::$memory->assignFunctionToThread($identifier,$pid);
        }

        ThreadManager::releaseLock();
        return $identifier;
    }

    public static function getThread($pid,$acquire = false){
        return ThreadManager::$memory->getThread($pid,$acquire);
    }

    public static function updateBiding(){
         //if (self::getVariables()!==NULL){
                  GlobalInjector::setGlobals(self::getVariables());
                  eval(GlobalInjector::setGlobalsTofunction(self::getVariables()));
         // }
    }

    public static function kill($pid){
        ThreadManager::$memory->updateThreadState($pid,Type::Kill);
        posix_kill($pid, SIGTERM);
    }

    public static function acquireLock(){
        return ThreadManager::$memory->semAcquire();
    }

    public static function releaseLock(){
        return ThreadManager::$memory->semRelease();
    }

    public static function updateState($pid,$state){
        ThreadManager::acquireLock();
        $return = ThreadManager::$memory->updateThreadState($pid,$state);
        ThreadManager::releaseLock();
        return $return;
    }

    public static function getVariables(){
        return ThreadManager::$memory->getVariables();
    }

    public static function getFunction($i){
        return ThreadManager::$memory->getFunction($i);
    }

    public static function updateExecutedFunctions($thread,$functions){
        ThreadManager::acquireLock();
        $return = ThreadManager::$memory->updateExecutedFunctions($thread,$functions);
        ThreadManager::releaseLock();
    }

    public static function join($pid){

        ThreadManager::$memory->semAcquire();
        $thread = self::getThread($pid);
        ThreadManager::$memory->semRelease();
        $recivednul = 0;
        if ($thread !== NULL) {

                        while(count($thread->callStack)>0){
                            usleep(self::$clockCycle);
                            ThreadManager::$memory->semAcquire();
                            $temp = self::getThread($pid);
                            ThreadManager::$memory->semRelease();
                            if ($temp!==NULL) {
                                $recivednul = 0;
                                $thread = $temp;
                                // echo "\nchanged\n";
                            }else{
                                $recivednul += 1;
                            }
            
                            if ($recivednul > 200) {
                                echo "\nRAMPANT\n";
                                return;
                            }
                            
                            // var_dump($thread);
                        }

        }

        // echo "\n ALL DONE \n";

    }

    public static function dumpMemory(){
        ThreadManager::$memory->reload();
        var_dump(ThreadManager::$memory);
    }



}


?>