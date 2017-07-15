<?php
require_once 'core.php';

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
$idx = '';
$calendarId = '';

if ($_DEBUG) {
  $address = 'Avenida Ipiranga, 7200 - Jardim Botânico, Porto Alegre - RS, 91530-000, Brasil';
  $dateString = '2017-07-18';
  $idx = 0;
  $calendarId = $clientJson['calendarId'][$idx];
} else {
  $address = isset($_GET['address']) ? $_GET['address'] : '';
  $dateString = isset($_GET['date']) ? $_GET['date'] :  '';
  $idx = isset($_GET['idx']) ? $_GET['idx'] :  '';
  $calendarId = $clientJson['calendarId'][$idx];
}

if (empty($address) || empty($dateString)) {
  echo json_encode([[ 'key' => 'erro', 'val' => 'Erro ao acessar calendario! :(' ]]);
  exit();
}

try {
  $dateMin = DateTime::createFromFormat('Y-m-d H:i:s', $dateString . ' 00:00:00');
  $dateMax = DateTime::createFromFormat('Y-m-d H:i:s', $dateString . ' 23:59:59');
} catch (Exception $e) {
  echo json_encode([[ 'key' => 'erro', 'val' => 'Data invalida! :(' ]]);
  exit();
}

$dateNow = DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d') . ' 23:59:59');

$scheduleAvaiable = [];

// Check if needs Delorean ;)
if ( $dateMin < $dateNow ) {
  $scheduleAvaiable[] = [ 
    'key' =>  date('c'), 
    'val' =>  'Data ultrapassado, tente outra data!'
  ];
  echo json_encode($scheduleAvaiable);
  exit();
}

// Load the next 20 events on the user's calendar.
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
$timeStartJob = DateTime::createFromFormat('Y-m-d H:i', $dateString .' '. $clientJson['avaiableTime']['start'], $timeZone);
$timeStartJob->sub(new DateInterval('PT1M'));
array_push($scheduleBooked, $timeStartJob->format('H:i'));

if (count($results->getItems()) > 0) {

  foreach ($results->getItems() as $event) {
    
    if (!empty($event->location) && !empty($event->end->dateTime)) {
      $startTimeDatetime = new DateTime($event->start->dateTime);
      
      array_push($scheduleBooked, $startTimeDatetime->format('H:i'));
      array_push($locations, $event->location);
    }
  }
}

// Build basic schedule

$timeCost = new DateInterval('PT'.(55).'M');

$schedule = basicSchedule($dateString, $results->getItems(), $timeCost);

if ($_DEBUG) { print_r($schedule); }

$executionTime = microtime(true) - $start;
if ($_DEBUG) { echo "$executionTime ms (events and locations) \n"; print_r($locations); }
$start = microtime(true);

// TRAVEL TIME MINIMIZATION

// 1- Build a Distance Matrix (but with Time value)
$locationTimeMatrix = buildTimeMatrix($locations);

$executionTime = microtime(true) - $start;
if ($_DEBUG) { echo "$executionTime ms (matrix) \n"; print_r($locationTimeMatrix); }
$start = microtime(true);

// 2- Shortest Path Problem: Like Travelling Salesman Problem
// Brute Force: Optimal Solution
$routeCost = cheapestPath($scheduleBooked, $locations, $locationTimeMatrix);

$executionTime = microtime(true) - $start;
if ($_DEBUG) { echo "$executionTime ms (route cost) \n"; print_r($routeCost); }
$start = microtime(true);

// COMPUTE DEPENDENCY SCHEDULE COST
// Add cost per schedule location
$scheduleCost = buildScheduleCost($schedule, $routeCost);

$executionTime = (microtime(true) - $start);
if ($_DEBUG) { echo "$executionTime ms (schedule cost) \n";  print_r($scheduleCost); }
$start = microtime(true);

// Build json response
$scheduleMsg = " (recomendado)";

// Get minimum route cost
$leastCost = array_values($scheduleCost);
rsort($leastCost);
$leastCost = array_pop($leastCost);

foreach($scheduleCost as $eventTime => $eventCost) {
      // Take care about suggested time
      if ($eventCost == $leastCost) {
        $scheduleMsg = " (recomendado)";
      } else {
        $scheduleMsg = "";
      }
      if ($eventCost > $clientJson['maxMinutesDistance']) {
        $scheduleMsg = " (indisponivel)"; // TODO: NEVER REACH 
      }

      $eventDateTime = DateTime::createFromFormat('Y-m-d H:i', $dateString.' '.$eventTime, $timeZone);
      $scheduleAvaiable[] = [ 
        'key' =>  $eventDateTime->format('c'), 
        'val' =>  $eventDateTime->format('d/m/Y H:i') . $scheduleMsg,
        'cost' => $eventCost,
      ];

}

if (count($scheduleAvaiable) == 0) {
  $scheduleAvaiable[] = [ 
    'key' =>  date('c'), 
    'val' =>  'Nenhum horario disponivel para esta data.'
  ];
}

$executionTime = microtime(true) - $start;
if ($_DEBUG) { echo "$executionTime ms (schedule avaiable) \n"; print_r($scheduleAvaiable); }
else { echo json_encode($scheduleAvaiable); }