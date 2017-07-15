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
$schedule = basicSchedule($dateString);

if ($_DEBUG) { print_r($schedule); }

$executionTime = microtime(true) - $start;
if ($_DEBUG) { echo "$executionTime ms (events and locations) \n"; print_r($locations); }
$start = microtime(true);


// Check if it's overbooked
if (count($scheduleBooked) == count($schedule)) {
  $scheduleAvaiable[] = [ 
    'key' =>  date('c'), 
    'val' =>  'Nenhum horario disponivel para esta data.'
  ];
  echo json_encode($scheduleAvaiable);
  exit();
}

// TRAVEL TIME MINIMIZATION

// 1- Build a Distance Matrix (but with Time value)
$locationTimeMatrix = buildTimeMatrix($locations);

$executionTime = microtime(true) - $start;
if ($_DEBUG) { echo "$executionTime ms (matrix) \n"; print_r($locationTimeMatrix); }
$start = microtime(true);

// 2- Shortest Path Problem: Like Travelling Salesman Problem
// Brute Force: Optimal Solution
$routeCost = cheapestPath($locations, $locationTimeMatrix);

$executionTime = microtime(true) - $start;
if ($_DEBUG) { echo "$executionTime ms (route cost) \n"; print_r($routeCost); }
$start = microtime(true);

// COMPUTE DEPENDENCY SCHEDULE COST
// Add cost per schedule location
$scheduleCost = buildScheduleCost($schedule, $scheduleBooked, $routeCost);

$executionTime = (microtime(true) - $start);
if ($_DEBUG) { echo "$executionTime ms (schedule cost) \n";  print_r($scheduleCost); }
$start = microtime(true);

// Build json response
$suggestTime = " (recomendado)";
$previousCost = 24 * 60 * 60;
foreach($scheduleCost as $eventTime => $eventCost) {
      // Take care about suggested time
      if ($previousCost < $eventCost) {
        $suggestTime = "";
      }

      $eventDateTime = DateTime::createFromFormat('Y-m-d H:i', $dateString.' '.$eventTime, new DateTimeZone('America/Sao_Paulo'));
      $scheduleAvaiable[] = [ 
        'key' =>  $eventDateTime->format('c'), 
        'val' =>  $eventDateTime->format('d/m/Y H:i') . $suggestTime
      ];
      $previousCost = $eventCost;
}

if (count($scheduleAvaiable) == 0) {
  $scheduleAvaiable[] = [ 
    'key' =>  date('c'), 
    'val' =>  'Nenhum horario disponivel para esta data.'
  ];
}

$executionTime = microtime(true) - $start;
if ($_DEBUG) { echo "$executionTime ms (schedule avaiable) \n"; }
echo json_encode($scheduleAvaiable);