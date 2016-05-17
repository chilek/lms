#!/usr/bin/env php
<?php

	define('RELOAD_LOCK_FILE', '/tmp/lms-billingd-reload-1.lock');
	define('RELOAD_UNLOCK_FILE', '/tmp/lms-billingd-reload-2.lock');

	if (!class_exists('Thread'))
		die("Thread class doesn't exist!" . PHP_EOL
			. "Please install PHP pthreads extension." . PHP_EOL);
			
	$pid = pcntl_fork();
	
	// fork error
	if($pid < 0)
		exit;

	// parent process
	if($pid > 0)
		exit;

	// child process
	if($pid == 0)
	{
		posix_setsid();
		
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($socket, '127.0.0.1', 4100);
		socket_listen($socket);

		// Open log file
			$logfh = fopen("/var/log/lms-billingd.log", "a+");

		// Set process name
			if (function_exists('cli_set_process_title'))
				cli_set_process_title('lms-billingd');

		// Include files
			include 'WorkerTemplate.class.php';
			include 'ClientConnection.class.php';
			include 'PrefixManager.class.php';
			include 'CustomerManager.class.php';

			include 'ReloadTemplate.class.php';
			include 'reloadConfiguration.php';	
		
			include 'lms_stub.php';
		
		// Change File Mask
			//umask(0);
			
		// Close Standard File Descriptors
			 // fclose(STDIN);
			 // fclose(STDOUT);
			 // fclose(STDERR);		

		// Set directory
			 // if ((chdir("/")) < 0)
				 // exit(EXIT_FAILURE);

		// Create objects and pools	 
			$prefixManager = new PrefixManager();
			$customerManager = new CustomerManager();

//			$lock_fh = fopen(RELOAD_LOCK_FILE, 'w+');
//			flock($lock_fh, LOCK_EX);
//			$unlock_fh = fopen(RELOAD_UNLOCK_FILE, 'w+');
//			flock($unlock_fh, LOCK_EX);

			// create pools
				$workerManager = new Pool(4, 'WorkerTemplate', array($prefixManager , $customerManager));
				$adminPool = new Pool(1, 'ReloadTemplate', array($prefixManager , $customerManager, $logfh));
			
			// init config for prefix and customer manager
				$adminPool->submit( new reloadConfiguration() );
			
			// declarate signals
				include 'signal_handler.php';
			
		while (true) {
			pcntl_signal_dispatch();

			$socketRead = array($socket);
			$socketWrite = array();
			$socketExcept = array();

			if (socket_select($socketRead, $socketWrite, $socketExcept, 0, 10000) > 0) {
				$clientConnection = socket_accept($socket);
				$workerManager->submit( new ClientConnection($clientConnection) );
			}
		}

//		flock($lock_fh, LOCK_UN);
//		fclose($lock_fh);
//		flock($unlock_fh, LOCK_UN);
//		fclose($unlock_fh);

		socket_close($socket);
		fclose($logfh);
		exit;
	}

?>
