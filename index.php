<?php
//
// Comanche
// A Simple and Pretty Apache Log Viewer.
// The parser and login class may or may not be reusable.
//
// By Zane Ashby (http://github.com/ZaneA/Comanche)
//

//
// We start by including the settings file and setting up the needed classes
//

include 'settings.php';

if (NEED_LOGIN) {
  include 'login.php';
  $login = new \Comanche\Login(LOGIN_NAME, LOGIN_PASSWORD);
  $login->check();
}

include 'parser.php';
$parser = new \Comanche\Parser(LOG_FORMAT);

// Add defined paths to parser
foreach (explode(':', LOG_PATHS) as $path) {
  $parser->addPath($path);
}

//
// Here we define all of our filters to transform the log file
//

// Parse GET field for "from" and "until"
$filter_from = (isset($_GET['from']) && !empty($_GET['from'])) ? $_GET['from'] : FILTER_FROM;
$filter_until = (isset($_GET['until']) && !empty($_GET['until'])) ? $_GET['until'] : FILTER_UNTIL;

// Filter between those times
$parser->addFilter($parser->filterBetween($filter_from, $filter_until));

// Turn the logged time into something nicer
$parser->addFilter(function ($data) {
  $data->time = strftime(TIME_FORMAT, strtotime($data->time));
});

// Remove GET and HTTP stuff from request and add 'url'.
$parser->addFilter(function ($data) {
  $parts = explode(' ', $data->request);
  $data->request = $parts[1];
  $data->url = htmlentities($data->host . $data->request);
});

// Filter out hosts we aren't interested in
$parser->addFilter(function ($data) {
  if (FILTER_HOSTS && !in_array($data->host, explode(':', FILTER_HOSTS))) {
    return false;
  }
});

// Turn the referrer into a link
$parser->addFilter(function ($data) {
  if ($data->referer != '-') {
    $data->referer = '<a href="' . htmlentities($data->referer) . '">' . htmlentities(parse_url($data->referer, PHP_URL_HOST)) . '</a>';
  }
});

// Parse the Useragent into something nice
$parser->addFilter(function ($data) {
  include_once 'Browscap.php';
  $agent = new Browscap('.');
  $browser = $agent->getBrowser($data->useragent);
  if (property_exists($browser, 'Parent'))
    $data->useragent = '<b>' . $browser->Parent . '</b> (' . $browser->Platform . ')';
  else
    $data->useragent = '<b>' . $browser->Browser . '</b> (' . $browser->Platform . ')';

  if (preg_match(BAD_BROWSER_FORMAT, $data->useragent)) {
    $data->useragent = '<span class="bad">' . $data->useragent . '</span>';
  }
});

// Add GeoIP country name to remoteaddr
$parser->addFilter(function ($data) {
  $data->remoteaddr = geoip_country_name_by_name($data->remoteaddr) . ' (<b>' . $data->remoteaddr . '</b>)';
});

// Sort by Timestamp
$parser->sort($parser->sortByTime());

// Make our visitors array
$visitors = array();

// Dump everything into it in a nice format for presentation
$parser->each(function ($data) use (&$visitors) {
  $visitors[$data->host][$data->remoteaddr][] = $data;
});

// This is where the page is finally output
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <title>Comanche - Simple and Pretty Apache Log Viewer</title>
    <link rel="stylesheet" href="style.css" />
  </head>
  <body>
     <div id="header">
       <h1>Comanche</h1>
       <h2>Simple and Pretty Apache Log Viewer</h2>
     </div>

     <div id="date-range">
       <form action="" method="get">
         From <input type="text" name="from" placeholder="<?php echo htmlentities($filter_from) ?>" autofocus />
         Until <input type="text" name="until" placeholder="<?php echo htmlentities($filter_until) ?>" />
         <input type="submit" value="Display" />
       </form>
     </div>

     <div id="results">
<?php
foreach ($visitors as $host => $persons) {
  $count = count($persons);

  echo "<h3>{$host} <span>&mdash; {$count} Uniques</span></h3>\n";

  foreach ($persons as $person => $visits) {
    $arrive = time();
    $leave = 0;
    foreach ($visits as $data) {
      if ($data->timestamp < $arrive) $arrive = $data->timestamp;
      if ($data->timestamp > $leave) $leave = $data->timestamp;
    }
?>
        <div class="visitor">
          <div class="part-time-spent"><i><?php echo round(abs($leave - $arrive) / 60) ?></i> Min</div>
          <table>
            <tr><td>Who</td><td class="part-ip"><?php echo $visits[0]->remoteaddr ?></td></tr>
            <tr><td>Arrived At</td><td class="part-time-arrived"><?php echo strftime(TIME_FORMAT, $arrive) ?></td></tr>
            <tr><td>Left At</td><td class="part-time-leave"><?php echo strftime(TIME_FORMAT, $leave) ?></td></tr>
            <tr><td>Browser</td><td class="part-browser"><?php echo $visits[0]->useragent ?></td></tr>
            <tr><td>Page Views</td><td class="part-views"><i><?php echo count($visits) ?></i> Page Views</td></tr>
            <tr><td>Came From</td><td class="part-referrer"><?php echo $visits[0]->referer ?></td></tr>
          </table>
        </div>
<?php
  }
}
?>
     </div>
  </body>
</html>