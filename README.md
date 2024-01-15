# PHPThreading 🤖🤖

![PHP Version](https://img.shields.io/badge/php-^7.0-blue) 
![Neural Network GIF](https://media.giphy.com/media/0gAOD9uZFfCXGvCU9u/giphy.gif)

## Introduction 🌐🚀
Welcome to PHPThreading, an innovative library designed to seamlessly implement multi-process functionality in PHP. This powerful library serves as a bridge between the dynamic capabilities of executing multiple operations across various processes. Taking it a step further, PHPThreading empowers you to delve into exciting features like data sharing, process control flow, task allocation, and much more, which we'll explore in detail shortly.

## Motivation 🌟
I crafted this library to enhance one of my existing projects—a neural network in PHP. While the network could successfully train and identify the benchmark of the infamous Fashion-MNIST dataset, it was taking an exorbitant amount of time. Training a model for a year, or even for hours, days, or months, felt impractical. Recognizing the need for improvement, I developed PHPThreading to comprehensively handle large matrices more efficiently, providing a significant boost to performance. 🚀💡

## Table of Contents 📚
- [Introduction 🎉](#introduction)
- [Motivation 🛠](#motivation)
- [Examples 📚](#examples)
  - [Executing on Multiple Threads 🚀🧵](#executing-on-multiple-threaded)
  - [Data Distribution 🔄 ](#data-distribution)
  - [ Data Sharing with Mutex Locks 🔄🔒](#data-sharing-with-mutex-locks)
- [Technical Considerations 🤔](#technical-considerations)
- [License 📝](#license)

## Executing on Multiple Threads 🚀🧵
Harness the power of parallelism with PHPThreading's multi-threading capabilities! By including the "ThreadManager.php" file and warming up two processes, you kickstart the simultaneous execution of anonymous functions across threads. The intelligent distribution ensures immediate execution, and as each thread echoes a message with its unique process ID, you get a dynamic and concurrent experience. After the execution, PHPThreading gracefully waits for all threads to finish and then efficiently shuts down, keeping your memory clean.
```php
include_once("ThreadManager.php");
ThreadManager::warmUp($processes = 2); // will create 2 threads and keep them active 

# When passing anonymous functions to be executed, they are intelligently distributed and split among active threads, resulting in immediate execution. 
for($i = 0;$i < 4; $i++){
    ThreadManager::execute(function () {
        echo "\n Hello from ".getmypid()."\n";
}); 
}
ThreadManager::wait(); # wait for threads to finish
ThreadManager::shutdown(); # will kill and clean memory
```

## Data Distribution 📊💡
Navigating the challenges of process execution often involves a critical question: how do we seamlessly pass and share data among threads to achieve targeted process outcomes? PHP Threading introduces an innovative solution through its binding method. On the main thread, simply introduce a variable like ```seconds``` using the binding method, and voila! This variable becomes instantly accessible in all threads by its name, making the process both intuitive and efficient. Imagine the ease of calling ```$seconds``` in your functions; the magic lies in the case-sensitive alignment of parameters, ensuring that data is effortlessly transmitted and received with precision. With PHP Threading, data distribution becomes a sophisticated yet streamlined endeavor. When passing data between processes or threads, it's important to keep in mind that only serializable objects can be passed. Non-serializable objects, such as those with mutex locks, cannot be passed between processes or threads.
```php
include_once("ThreadManager.php");
ThreadManager::warmUp($processes = 2); // will create 2 threads and keep them active 
ThreadManager::var("seconds",1); // will create and bind this varible to all threads, when binden in the threads it can be accessed like any variable
for($i = 0;$i < 20; $i++){
    ThreadManager::execute(function ($seconds) {
    sleep($seconds);    
}); 
}
ThreadManager::wait(); # wait for threads to finish
ThreadManager::shutdown(); # will kill and clean memory
```

## Data Sharing with Mutex Locks 🔄🔒

PHP Threading simplifies multi-threaded programming with mutex locks and intuitive variable management provided by ThreadManager, making data sharing across threads seamless. You can initiate 2 active threads with `ThreadManager::warmUp($processes = 2)`, declare and initialize the variable 'x' to 0 with `ThreadManager::var("x", 0)`, and then execute each thread's function in a secured section that ensures data integrity. The program waits for all threads to finish with `ThreadManager::wait()` and gracefully shuts down with `ThreadManager::shutdown()`.

```php
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
    }); 
}
ThreadManager::wait();
ThreadManager::shutdown();
```
## Output
Experience the harmony of data sharing and synchronization, exemplified in the output:
```bash
PID:: 68552 of x = 1
PID:: 68553 of x = 2
[Finished in 79ms]
```
## Technical Considerations 🤔

When venturing into the realm of multi-threading with PHPThreading, it's crucial to tread carefully and consider a few technical aspects for optimal performance.

### Balancing Overhead ⚖️

Executing tasks concurrently can introduce overhead, and it's imperative to assess whether the gains outweigh the costs. Depending on the complexity of your operations and the nature of your workload, the overhead introduced by managing multiple threads might be a worthwhile investment or may lead to diminishing returns.

### Mindful Thread Allocation 🧵

PHPThreading provides the flexibility to create and manage threads dynamically, but it's advised to exercise caution when allocating threads. Allocating an excessively high number of threads, such as 1000, is not recommended. While PHPThreading is robust, such a high thread count may strain system resources and potentially lead to suboptimal performance.

## Technical Considerations 🤔

When navigating the terrain of multi-threading with PHPThreading, it's paramount to take into account the intricacies of Mutex locks, as they play a crucial role in ensuring data integrity during data sharing.

### Mutex Locks for Data Sharing 🔒

While the ability to share data among threads is a powerful feature of PHPThreading, the use of Mutex locks introduces an essential consideration. Mutex locks act as safeguards, preventing multiple threads from accessing shared data simultaneously and potentially causing race conditions.

### Pausing Threads ⏸️

When a thread acquires a Mutex lock, it essentially signals to other threads that they must pause until the lock is released. This mechanism ensures that only one thread at a time can access the shared data, maintaining order and preventing conflicts.

### Deadlocks ⚠️

However, it's crucial to exercise caution when implementing Mutex locks. If not managed properly, the misuse of Mutex locks can lead to deadlocks—situations where threads are indefinitely paused, waiting for a lock that is never released. Deadlocks can significantly impact the performance and responsiveness of your program.

### Balancing Parallelism and Synchronization ⚖️

While Mutex locks provide a robust solution for data sharing, it's crucial to strike a balance between parallelism and synchronization. Excessive use of locks or prolonged lock-holding times can negate the advantages of multi-threading, potentially leading to performance bottlenecks.

### Cloning the Repository
Start by cloning the repository from GitHub:
```bash
git clone git@github.com:cuthbert-lwinga/PHPThreading.git
```

## License 📝
PHPThreading is made available under the MIT License. This means that you're free to use, modify, distribute, and sell this software, but you must include the original copyright notice and this license notice in any copies or substantial portions of the software.