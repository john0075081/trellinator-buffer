#!/usr/bin/php
<?php
    require_once('pheanstalk/pheanstalk_init.php');
    require_once('vendor/autoload.php');
    use Symfony\Component\Process\Process;
    use Symfony\Component\Process\Exception\ProcessFailedException;
    $p         = new Pheanstalk_Pheanstalk('127.0.0.1');
    $processes = array();
    define('SCRIPT_MAX',5);

        $to_execute = array();
        
        foreach($p->listTubes() as $tube)
        {
            try
            {
echo "trying: ".$tube." containing: ".$argv[1].PHP_EOL;
if(strpos($tube,$argv[1]) !== false)
{
echo "does contain".PHP_EOL;
    while($job = $p->reserveFromTube($tube,1))
    {
        echo "deleting job from ".$tube.PHP_EOL;
        $p->delete($job);
    }
}
            }
            
            catch(Exception $e)
            {
                echo $e->getMessage().PHP_EOL;
            }
        }

        usleep(100000);
