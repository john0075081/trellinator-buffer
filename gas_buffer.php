#!/usr/bin/php
<?php
    require_once('pheanstalk/pheanstalk_init.php');
    require_once('vendor/autoload.php');
    use Symfony\Component\Process\Process;
    use Symfony\Component\Process\Exception\ProcessFailedException;
    $p         = new Pheanstalk_Pheanstalk('127.0.0.1');
    $processes = array();
    define('SCRIPT_MAX',5);
    define('PROCESS_MAX',50);
echo 'SCRIPT START'.PHP_EOL;

    while(1)
    {
        if(count($processes) < PROCESS_MAX)
        {
            $to_execute = array();
            
            foreach($p->listTubes() as $tube)
            {
                try
                {
                    $p->peekReady($tube);
                    if(preg_match("/^trellinator-(.+)-[0-9]+$/",$tube,$matches))
                    {
                        $script_id = $matches[1];
                        
                        if(!isset($to_execute[$script_id]))
                            $to_execute[$script_id] = array();
                        
                        $to_execute[$script_id][] = $tube;
                    }
                }
                
                catch(Exception $e)
                {
                }
            }
    
            foreach($to_execute as $script_id => $tubes)
            {
                $select_rand = (SCRIPT_MAX < count($tubes)) ? SCRIPT_MAX:count($tubes);
                $keys = array_rand($tubes,$select_rand);
                
                if(!is_array($keys))
                    $keys = array($keys);
    
                if(!isset($processes[$script_id]))
                    $processes[$script_id] = array();
    
                shuffle($keys);
                foreach($keys as $key)
                {
                    if(
                          (count($processes[$script_id]) < SCRIPT_MAX)
                          //&&(!isset($processes[$script_id][$tubes[$key]]))
                      )
                    {
                        $cmd     = 'php gas_cmd_line.php "'.$tubes[$key].'"';
echo 'executing: '.$cmd.PHP_EOL;
                        $process = new Process($cmd);
                        $process->start();
                        //$processes[$script_id][$tubes[$key]] = $process;
                        $processes[$script_id][] = $process;
                    }
                    
                    else
                    {
                        echo "not executing: ".$script_id." because too many".PHP_EOL;
                    }
                }
            }
        }

        foreach($processes as $script_id => $procs)
        {
            foreach($procs as $tube => $proc)
            {
                if(!$proc->isRunning())
                {
                    unset($processes[$script_id][$tube]);
                    echo "finished for: ".$script_id.PHP_EOL;
                    $output = trim($proc->getOutput());

                    if(stripos($output,'deleted') === FALSE)
                    {
                        if($script_id != 'AKfycbxjRKxf7iwVLFYKIVGaUNyRe1cdIejUQQhvsoFCC7UXqUCBNSM')
                        {
                            echo 'FAILED '.$script_id.PHP_EOL;
                            echo $output.PHP_EOL;
                        }
                    }
                    
                    else
                        echo 'successfully closed or kicked: '.$script_id.PHP_EOL;
                }
            }

            if(count($processes[$script_id]) == 0)
            {
                echo "unsetting: ".$script_id.PHP_EOL;
                unset($processes[$script_id]);
            }
        }
        
        usleep(100000);
    }
