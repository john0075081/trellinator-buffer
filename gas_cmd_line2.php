#!/usr/bin/php
<?php
    require_once('pheanstalk/pheanstalk_init.php');
    $tube = $argv[1];
    $p    = new Pheanstalk_Pheanstalk('127.0.0.1');

    if($job = $p->reserveFromTube($tube,1))
    {
        echo "reserved".PHP_EOL;
        $p->bury($job);
        $obj = json_decode(base64_decode($job->getData()));
echo base64_decode($job->getData()).PHP_EOL;
        $success = true;
        $post_fields = $obj->post;
        echo "forward to: ".$obj->url.PHP_EOL;
                    
        $ch = curl_init($obj->url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);                                                                  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);                                                                      
        //https://stackoverflow.com/questions/35359720/php-with-curl-follow-redirect-with-post
        curl_setopt ( $ch, CURLOPT_POSTREDIR, 3);
        curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, TRUE);
//        curl_setopt($ch, CURLOPT_TIMEOUT, 120);                                                                      
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
        'Content-Type: application/json',                                                                                
        'Content-Length: ' . strlen($post_fields))                                                                       
        );                                                                                                                   
                                                                                                                         
        $resp = curl_exec($ch);
echo $resp.PHP_EOL;
        if(
           preg_match("/.*Processed Notification.*/",$resp)||
           preg_match("/.*checkCCBBLib.*/",$resp)||
           preg_match("/.*Roger That.*/",$resp)||
           preg_match("/.*Action not allowed.*/",$resp)||
           preg_match("/.*Sorry, unable to open the file at this time.*/",$resp)||
           preg_match("/.*Authorization is required to perform that action..*/",$resp)||
           preg_match("/.*Sorry, the file you have requested does not exist.*/",$resp)
          )
        {
            echo "deleting".PHP_EOL;
            $p->delete($job);
        }

        else
        {
            echo "kicking and pausing: ".$tube.PHP_EOL;
            echo $resp.PHP_EOL;
            $p->kickJob($job);
        }
    }
    
    else
        echo "timed out on reserve".PHP_EOL;
    
    $p->watchOnly("default");
    exit(0);
