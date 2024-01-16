<?php
include_once("ThreadManager.php");
ThreadManager::warmUp($processes = 2); // will create 20 threads and keep them active 
ThreadManager::var("x",0);
for($i = 0;$i < 2; $i++){
	ThreadManager::execute(function ($x) {
		ThreadManager::acquireLock();
		$currentX = ThreadManager::getVar("x",$acquire_sem_lock = false);
		ThreadManager::var("x", $currentX + 1,$acquire_sem_lock = false);
		echo "PID:: ".getmypid()." of x = ".ThreadManager::getVar("x",$acquire_sem_lock = false)."\n";
		ThreadManager::releaseLock();
		return ThreadManager::getVar("x",$acquire_sem_lock = false);
	}); 
}
ThreadManager::wait();

var_dump(ThreadManager::getOutput());

ThreadManager::shutdown();
?>