<?php
include_once("ThreadManager.php");
ThreadManager::warmUp($processes = 2); // will create 2 threads and keep them active 
ThreadManager::var("seconds",1); // will create and bind this varible to all threads, when binden in the threads it can be accessed like any variable
for($i = 0;$i < 2; $i++){
	$closure = function ($seconds) {
	sleep($seconds);	
	if (true) {
		echo "hello\n";
	}
};
	ThreadManager::execute($closure); 
}
ThreadManager::wait(); # wait for threads to finish
ThreadManager::shutdown(); # will kill and clean memory
?>