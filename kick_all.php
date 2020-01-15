#!/usr/bin/php
<?php
    require_once('pheanstalk/pheanstalk_init.php');
    require_once('vendor/autoload.php');
    use Symfony\Component\Process\Process;
    use Symfony\Component\Process\Exception\ProcessFailedException;
    $p         = new Pheanstalk_Pheanstalk('127.0.0.1');
    $processes = array();
    define('SCRIPT_MAX',5);

    while(1)
    {
        $to_execute = array();
        
        foreach($p->listTubes() as $tube)
        {
            try
            {
                $p->useTube($tube)->kick(10);
            }
            
            catch(Exception $e)
            {
                echo $e->getMessage().PHP_EOL;
            }
        }

        echo 'finished sleeping'.PHP_EOL;
        usleep(100000);
    }
