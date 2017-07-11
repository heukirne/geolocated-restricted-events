<?php
require_once 'core.php';

$_DEBUG = (php_sapi_name() == 'cli');
$start = microtime(true);

// Get the API client and construct the service object.
try {
	$client = getClient();
	$service = new Google_Service_Calendar($client);
	$calendarList = $service->calendarList->listCalendarList(); //try api
} catch (Exception $e) {
	echo json_encode([[ 'key' => 'erro', 'val' => 'Erro ao acessar calendario! :(' ]]);
	exit();
}

$address = '';
$dateString = '';

if ($_DEBUG) {
  $address = 'Avenida Ipiranga, 7200 - Jardim Botânico, Porto Alegre - RS, 91530-000, Brasil';
  $dateString = '13/07/2017';
} else {
  $address = isset($_GET['address']) ? $_GET['address'] : '';
  $dateString = isset($_GET['date']) ? $_GET['date'] :  '';
}

if (empty($address) || empty($dateString)) {
  echo json_encode([[ 'key' => 'erro', 'val' => 'Erro ao acessar calendario! :(' ]]);
  exit();
}

$dateMin = DateTime::createFromFormat('Y-m-d  H:i:s', $dateString . ' 00:00:00');
$dateMax = DateTime::createFromFormat('Y-m-d  H:i:s', $dateString . ' 23:59:59');

// Print the next 10 events on the user's calendar.
$calendarId = CALENDAR_ID;
$optParams = array(
  'maxResults' => 20,
  'orderBy' => 'startTime',
  'singleEvents' => TRUE,
  'timeMin' => $dateMin->format('c'),
  'timeMax' => $dateMax->format('c'),
);
$results = $service->events->listEvents($calendarId, $optParams);	

// Build location array
$locations = [];
array_push($locations, $address);

// Build booked array
$scheduleBooked = [];

if (count($results->getItems()) > 0) {

  foreach ($results->getItems() as $event) {
    
    if (!empty($event->location) && !empty($event->end->dateTime)) {
      $startTimeDatetime = new DateTime($event->start->dateTime);
      
      array_push($scheduleBooked, $startTimeDatetime->format('H:i'));
      array_push($locations, $event->location);
    }
  }
}

$executionTime = microtime(true) - $start;
if ($_DEBUG) { echo "$executionTime ms (events and locations) \n"; print_r($locations); }
$start = microtime(true);

// TRAVEL TIME MINIMIZATION

// 1- Build a Distance Matrix (but with Time value)
$locationTimeMatrix = [[]];
foreach($locations as $keyFrom => $from){
  foreach($locations as $keyTo => $to){

      $locationTimeMatrix[$keyFrom][$keyTo] = 24 * 60 * 60;
      if ($keyFrom != $keyTo) {
        // Get Time Distance from Google Matrix
        $element = getMatrixDistance($from,$to);
        if (isset($element) && $element->status == "OK") {
          $locationTimeMatrix[$keyFrom][$keyTo] = $element->duration->value;
          $locationTimeMatrix[$keyFrom][$keyTo] = $element->duration->value;
        }
      } else {
        $locationTimeMatrix[$keyFrom][$keyTo] = 0;
      }

  }
}

$executionTime = microtime(true) - $start;
if ($_DEBUG) { echo "$executionTime ms (matrix) \n"; print_r($locationTimeMatrix); }
$start = microtime(true);

// 2- Shortest Path Problem: Like Travelling Salesman Problem
// Brute Force: Optimal Solution
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

$executionTime = microtime(true) - $start;
if ($_DEBUG) { echo "$executionTime ms (all routes) \n"; print_r($allRoutes); }
$start = microtime(true);

// 3- Compute all routes time cost
$routeCost = [];
foreach($allRoutes as $routeKey => $route){
  $routeCost[$routeKey] = 0;
  for ($key=0; $key < count($route)-1; $key++) {
    $fromKey = $route[$key];
    $toKey = $route[$key+1];
    $routeCost[$routeKey] += $locationTimeMatrix[$fromKey][$toKey];
  }
}

$executionTime = microtime(true) - $start;
if ($_DEBUG) { echo "$executionTime ms (route cost) \n"; print_r($routeCost); }
$start = microtime(true);

// COMPUTE DEPENDENCY SCHEDULE COST

// Build day schedule
$eventDuration = '00:44';
$schedule = [
              '09:00',
              '09:45',
              '10:30',
              '11:15',
              '12:00',
              '12:45',
              '13:30',
              '14:15',
              '15:00',
              '15:45',
              '16:30',
              '17:15',
              '18:00'
            ];
$schedule = array_diff($schedule, []); // convert to real array

$executionTime = microtime(true) - $start;
if ($_DEBUG) { echo "$executionTime ms (base schedule) \n"; print_r($scheduleBooked); }
$start = microtime(true);

// Add cost per schedule location
$scheduleCost = [];

foreach($schedule as $timeKey => $startTime){
  if (in_array($startTime, $scheduleBooked)){
      array_shift($routeCost); // remove route
  } else {
    $values = array_values($routeCost);
    $scheduleCost[$startTime] = array_shift($values);
  }
}

asort($scheduleCost);

$executionTime = (microtime(true) - $start);
if ($_DEBUG) { echo "$executionTime ms (schedule cost) \n";  print_r($scheduleCost); }
$start = microtime(true);

// Build json response
$scheduleAvaiable = [];
$suggestTime = " (recomendado)";
$previousCost = 24 * 60 * 60;
foreach($scheduleCost as $eventTime => $eventCost) {
      // Take care about suggested time
      if ($previousCost < $eventCost) {
        $suggestTime = "";
      }

      $eventDateTime = DateTime::createFromFormat('Y-m-d  H:i', $dateString.' '.$eventTime);
      $scheduleAvaiable[] = [ 
        'key' =>  $eventDateTime->format('c'), 
        'val' =>  $eventDateTime->format('d/m/Y H:i') . $suggestTime
      ];
      $previousCost = $eventCost;
}

$executionTime = microtime(true) - $start;
if ($_DEBUG) { echo "$executionTime ms (schedule avaiable) \n"; }
echo json_encode($scheduleAvaiable);