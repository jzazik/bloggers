#!/usr/bin/env bash
ssh user@jzazik.com "cd /var/www/html/bloggers/; sudo git fetch; sudo git stash; sudo git stash drop; sudo git checkout master; sudo git pull;"