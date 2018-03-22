#!/usr/bin/php
<?php
    require_once('pheanstalk/pheanstalk_init.php');
    require_once('vendor/autoload.php');
    use Symfony\Component\Process\Process;
    use Symfony\Component\Process\Exception\ProcessFailedException;


    $processes = array();
    $cmd = 'php gas_cmd_line.php "trellinator-AKfycbyBiyoIN-LE0OEbfsTBftZ71nDUiL_Mda-YIpTtQEdr1v3GBtl6-76081523"';
    $process = new Process($cmd);
    $process->start();

    while($process->isRunning())
    {
        echo "running".PHP_EOL;
    }

    echo 'out: '.$process->getOutput();
