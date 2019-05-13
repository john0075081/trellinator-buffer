#!/bin/sh
/usr/bin/beanstalkd -b /beanstalkd/ -z 10485760 >> /var/log/beanstalkd/beanstalkd.log 2>&1
