#!/bin/sh

/usr/bin/python /var/www/cron2/updateCurStats.py;
/usr/bin/python /var/www/cron2/updateRanking.py;
/usr/bin/python /var/www/worldofhackers.eu/python/rank_generator.py;
