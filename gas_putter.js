#!/usr/bin/env nodejs
var bs = require('nodestalker'),

client = bs.Client('127.0.0.1:11300');
var qs = require('querystring');

function putJob(script_id,data)
{
    var timeStamp = Math.floor(Date.now() / 20000);//20 second window
    var use_tube = "trellinator-"+script_id+"-"+timeStamp;

    client.use(use_tube).onSuccess(function(tube)
    {
        client.put(data).onSuccess(function()
        {
            client.ignore(use_tube);
        });
    });
}

var http = require('http');
http.createServer(function (req, res) {
  res.writeHead(200, {'Content-Type': 'text/plain'});

    if (req.method == 'POST') {
        var body = '';

        req.on('data', function (data) {
            body += data;

            // Too much POST data, kill the connection!
            // 1e6 === 1 * Math.pow(10, 6) === 1 * 1000000 ~~~ 1MB
//            if (body.length > 1e6)
//                request.connection.destroy();
        });

        req.on('end', function () {
            //var post = qs.parse(body);
            var post = body;
            ///forward-gas-service-test/AKfycbzxvwNqwgXXnJEcs0ICFEAZyY67o9P2zSIPjH4mGG4oOEGYoWw
            var script_id = req.url.replace(/^\/.+\/(.+)$/,function(match,p1,offset,string)
            {
                return p1;
            });

            var new_url = "https://script.google.com/macros/s/"+script_id+"/exec";
            putJob(script_id,Buffer.from(JSON.stringify({url: new_url,post: post})).toString('base64'));
        });
    }
    
  res.end('Hello World\n');
}).listen(8082, 'localhost');
console.log('Server running at http://localhost:8082/');
