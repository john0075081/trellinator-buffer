#!/usr/bin/env nodejs
var bs = require('nodestalker'),
client = bs.Client('127.0.0.1:11300');
var url = require('url');
var qs = require('querystring');

var http = require('http');
http.createServer(function (req, res) {
  res.writeHead(200, {'Content-Type': 'text/plain'});

    if ((req.method == 'POST') || (req.method == 'GET')) {



        var body = '';

        req.on('data', function (data) {
console.log("data: "+data);
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
            var url_parts = url.parse(req.url, true);
            var parts = /^\/.+\/(.+)$/.exec(url_parts.pathname);
            var script_id = parts[1];
            var timeStamp = Math.floor(Date.now() / 20000);//20 second window
            var use_tube = "trellinator-"+script_id+"-"+timeStamp;
            var new_url = "https://script.google.com/macros/s/"+script_id+"/exec";
            var query = url_parts.query;
            var data = Buffer.from(JSON.stringify({url: new_url,post: post,get: query})).toString('base64');
            
            if([
                "COPY THIS LINE AND PASTE IN A SCRIPT ID TO BLOCK",
                "END BLOCKED SCRIPT IDS"].indexOf(script_id) == -1)
            {
                client.use(use_tube).onSuccess(function(tube)
                {
                    client.put(this.dataAsy).onSuccess(function()
                    {
                        client.ignore(use_tube);
                    });
                }.bind({dataAsy:data}));
            }
        });
    }
    
  res.end('Hello World\n');
}).listen(8082, 'localhost');
console.log('Server running at http://localhost:8082/');
