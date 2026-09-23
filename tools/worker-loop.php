<?php
$root=dirname(__DIR__);$sleep=max(1,(int)(getenv('WORKER_SLEEP_SECONDS')?:3));echo "Pune Mirror worker loop started\n";while(true){passthru(PHP_BINARY.' '.escapeshellarg($root.'/tools/worker.php'),$code);if($code!==0)fwrite(STDERR,"worker cycle failed: $code\n");sleep($sleep);}
