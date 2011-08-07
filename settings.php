<?php
// Username and password for login
// Password should be md5 encoded, use `php -r "echo md5('mypasswordhere')"`
define('NEED_LOGIN', false);
define('LOGIN_NAME', 'user');
define('LOGIN_PASSWORD', '5f4dcc3b5aa765d61d8327deb882cf99'); // password

// This is a simple matcher for the default Common Log Format used by Nginx
define('LOG_FORMAT', '/(?<remoteaddr>.*?) (?<host>.*?) (?<remoteuser>.*?) \[(?<time>.*?)\] "(?<request>.*?)" (?<status>\d+) (?<bytessent>\d+) "(?<referer>.*?)" "(?<useragent>.*?)"( "(?<requesttime>.*?)" "(?<gzipratio>.*?)")?/');

// List of files to parse, separated by ':'
define('LOG_PATHS', '/home/zanea/access.log');

// Default time to filter
define('FILTER_FROM', '1 day ago');
define('FILTER_UNTIL', 'now');

// List of hosts we're interested in seeing, separated by ':'
// Leave blank to display all
define('FILTER_HOSTS', 'www.host1.com:www.host2.com');

// Time format used wherever a time is printed
define('TIME_FORMAT', '%A, <b>%I:%M</b> %P');

// A regex to match bad browsers and highlight them
define('BAD_BROWSER_FORMAT', '/IE [6|7]/');