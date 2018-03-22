const bs = require('nodestalker');
var request = require('request');


function forwardJobs()
{
    console.log("about to forward");
    var client = bs.Client('127.0.0.1:11300');
    client.listTubes().onSuccess(function(tubes)
    {
       console.log(tubes);
       
       for(var key in tubes)
       {
           if(/trellinator.*/.test(tubes[key]))
           {
               client.watch(tubes[key]).onSuccess(function(tube) {
                   console.log("reserving: "+tube);

                   client.reserve_with_timeout(1).onSuccess(function(job) {
                       client.bury(job.id);
                       client.ignore(tubes[key]);
                       console.log('reserved', job);

                        request({
                            url: job.url,
                            method: "POST",
                            json: true,   // <--Very important!!!
                            body: job.post
                        }, function (error, response, body){
                        //    console.log(error+" "+response+" "+body);
                        
                            if(/.*Processed Notification.*/.test(body))
                            {
                                client.deleteJob(job.id).onSuccess(function(del_msg) {
                                    console.log('deleted', job);
                                    console.log('message', del_msg);
                                });
                            }
                                
                            else
                            {
                                console.log("Kicking");
                                client.pause_tube(tubes[key],5);
                                client.use(tubes[key]).onSuccess(function(data)
                                {
                                    client.kick(10)
                                    client.ignore(tubes[key]);
                                });
                            }
                        });
                   })
               });
           }
           
           else
               console.log("ignoring: "+tubes[key]);
       }
    });
}

setInterval(forwardJobs,2000);
