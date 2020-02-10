#!/bin/bash

for ((i=16; i<=31; i++)); do
	/usr/bin/php   app/console daily:cron:run $i/05/2016  
done
