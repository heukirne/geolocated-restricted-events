<?php
require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

$_DEBUG = (php_sapi_name() == 'cli');
$start = microtime(true);

define('APPLICATION_NAME', 'Geolocated Restricted Events');
define('CREDENTIALS_PATH', __DIR__ . '/user_credentials.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
define('MATRIX_CACHE', __DIR__ . '/matrix_distance.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/calendar-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Calendar::CALENDAR)
));

// Get Google Api Token
$clientSecretPath = expandHomeDirectory(CLIENT_SECRET_PATH);
$clientJson = [];
if (file_exists($clientSecretPath)) {
  $clientJson = json_decode(file_get_contents($clientSecretPath), true);
} else {
  die("{ success:0, msg: 'Create cclient_secret.json!' }");
}

$timeZone = new DateTimeZone('America/Sao_Paulo');

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfig(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');
  $client->setApprovalPrompt('force');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = json_decode(file_get_contents($credentialsPath), true);
  } else {
    die("{ success:0, msg: 'Run generate_credential.php first!' }");
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
  }

  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

/**
 * Search for source x destionation distance matrix
 * @param string $params with source and destination.
 * @return body object.
 */
function getMatrixDistance($from, $to) {
  //Init Guzzle
  $guzzleClient = new Client();

  // Sort destinations
  $locations = [];
  array_push($locations, $from);
  array_push($locations, $to);
  sort($locations);
  $from = $locations[0];
  $to = $locations[1];

  // Build param url
  $params = '&origins=' . urlencode($from);
  $params .= '&destinations=' . urlencode($to);

  // Get Google Api Token
  $google_key = "";
  $clientSecretPath = expandHomeDirectory(CLIENT_SECRET_PATH);
  if (file_exists($clientSecretPath)) {
    $clientJson = json_decode(file_get_contents($clientSecretPath), true);
    $google_key = $clientJson['api'];
  } else {
    die("{ success:0, msg: 'Create Api Token!' }");
  }

  //Set Google Distance Matrix Api URL with ApiKey
  $gmatrix = 'https://maps.googleapis.com/maps/api/distancematrix/json?key=' . $google_key;
  $gmatrix .= '&mode=driving';
  $hash = hash("md5",$params);

  $matrixCachePath = expandHomeDirectory(MATRIX_CACHE);
  if (file_exists($matrixCachePath)) {
    $matrixCache = json_decode(file_get_contents($matrixCachePath), true);
  } else {
    $matrixCache = [];
  }

  // Find cache or Request
  if (isset($matrixCache[$hash])) {

    $element = $matrixCache[$hash];
    return json_decode(json_encode($element), FALSE);

  } else {

    $response = $guzzleClient->request('GET', $gmatrix . $params);
    //echo "Log: Matrix Distance Request! \n";
    $body = json_decode($response->getBody());

    $matrixCache[$hash] = $body->rows[0]->elements[0];
    file_put_contents('matrix_distance.json', json_encode($matrixCache));

    return $matrixCache[$hash];
  }

}

function belongsToInterval($date, $start, $end) {
  return (($date >= $start) && ($date <= $end));
}

/**
 * Build basic schedule based on config file
 * @param string $dateString date.
 * @return schedule time list.
 */
function basicSchedule($dateString, $events, $timeCost) {
  global $clientJson, $timeZone;

  $timeStartLunch = DateTime::createFromFormat('Y-m-d H:i', $dateString .' '. $clientJson['lunchTime']['start'], $timeZone);
  $timeEndLunch = DateTime::createFromFormat('Y-m-d H:i', $dateString .' '. $clientJson['lunchTime']['end'], $timeZone);

  $timeStartJob = DateTime::createFromFormat('Y-m-d H:i', $dateString .' '. $clientJson['avaiableTime']['start'], $timeZone);
  $timeEndJob = DateTime::createFromFormat('Y-m-d H:i', $dateString .' '. $clientJson['avaiableTime']['end'], $timeZone);

  $dateInterval = new DateInterval($clientJson['timeInterval']);

  $schedule = [];

  for ($date = $timeStartJob; 
        $date <= $timeEndJob; 
        $date->add($dateInterval)) {

      $dateEnd = clone $date;
      $dateEnd->add($timeCost);

      $lunchStartOverlap = belongsToInterval($date, $timeStartLunch, $timeEndLunch);
      $lunchEndOverlap = belongsToInterval($dateEnd, $timeStartLunch, $timeEndLunch);
      $lunchOverlap = $lunchStartOverlap || $lunchEndOverlap;

      $eventOverlap = false;

      foreach ($events as $event) {
        if (!empty($event->location) && !empty($event->end->dateTime)) {
          $startTimeDatetime = new DateTime($event->start->dateTime);
          $endTimeDatetime = new DateTime($event->end->dateTime);
          
          $startBelongs = belongsToInterval($date, $startTimeDatetime, $endTimeDatetime);
          $endBelongs = belongsToInterval($dateEnd, $startTimeDatetime, $endTimeDatetime);

          if ($startBelongs || $endBelongs) {
            $eventOverlap = true;
          }
        }
      }

      if (!$lunchOverlap && !$eventOverlap) {
        $schedule[] = $date->format('H:i');
      }
  }

  return $schedule;
}

/**
 * 1- Build a Distance Matrix (but with Time value)
 * @param array $locations address.
 * @return matrix $locationTimeMatrix time-cost distance
 */
function buildTimeMatrix($locations) {
  $locationTimeMatrix = [[]];
  foreach($locations as $keyFrom => $from){
    foreach($locations as $keyTo => $to){

        $locationTimeMatrix[$keyFrom][$keyTo] = 24 * 60 * 60;
        if ($keyFrom != $keyTo) {
          // Get Time Distance from Google Matrix
          $element = getMatrixDistance($from,$to);
          if (isset($element) && $element->status == "OK") {
            $locationTimeMatrix[$keyFrom][$keyTo] = round($element->duration->value / 60,2);
            $locationTimeMatrix[$keyFrom][$keyTo] = round($element->duration->value / 60,2);
          }
        } else {
          $locationTimeMatrix[$keyFrom][$keyTo] = 0;
        }

    }
  }
  return $locationTimeMatrix;
}

/**
 * 2- Shortest Path Problem: Like Travelling Salesman Problem
 *    Brute Force: Optimal Solution
 * @param array $locations address, 
 * @param matrix $locationTimeMatrix with time distance.
 * @return array $routeCost route time-cost distance.
 */
function cheapestPath($scheduleBooked, $locations, $locationTimeMatrix) {
  global $_DEBUG, $clientJson;

  $allRoutes = [];
  $keys = array_keys($locations);

  foreach($keys as $key){
    // first route is dummy
    if ($key == 0) {
      array_push($allRoutes, $keys); 
      continue;
    }

    // following routes
    $route = array_slice($keys, 1, $key);
    array_push($route, 0);
    $route_last = array_slice($keys, $key+1);
    foreach($route_last as $newkey) { array_push($route, $newkey); }

    array_push($allRoutes, $route);
  }

  if ($_DEBUG) { echo "(all routes) \n"; print_r($allRoutes); }

  // 3- Compute all routes time cost
  $routeCost = [];
  foreach($allRoutes as $routeKey => $route){
    $timeKey = $scheduleBooked[$routeKey];
    $routeCost[$timeKey] = 0;
    for ($key=0; $key < count($route)-1; $key++) {
      $fromKey = $route[$key];
      $toKey = $route[$key+1];
      $routeCost[$timeKey] += $locationTimeMatrix[$fromKey][$toKey];
    }
  }

  $basicRouteCost = 0;
  if (count($locationTimeMatrix)>1) {
    $basicRouteCost = $routeCost[$scheduleBooked[0]] - $locationTimeMatrix[0][1];
  }

  $routeCostMinute = array_map(function($value) use ($basicRouteCost){
    return $value - $basicRouteCost;
  }, $routeCost);

  return $routeCostMinute;
}

/**
 * Add cost per schedule time
 * @param array $schedule address, 
 * @param array $routeCost route time-cost distance
 * @return array $scheduleCost schedule order by cheapest routes.
 */
function buildScheduleCost($schedule, $routeCost) {
  $scheduleCost = [];

  foreach($schedule as $timeKey => $startTime){
    @$nextBook = array_keys($routeCost)[1]; // care about next book
    if ($startTime > $nextBook && count($routeCost) > 1){
        array_shift($routeCost); // remove route
    }
    $values = array_values($routeCost)[0];
    $scheduleCost[$startTime] = $values;
  }

  // Sort cheapest routes
  // asort($scheduleCost);

  return $scheduleCost;
}