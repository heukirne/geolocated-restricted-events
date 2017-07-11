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

$address = isset($_GET['address']) ? $_GET['address'] : 'Avenida Ipiranga, 7200 - Jardim Botânico, Porto Alegre - RS, 91530-000, Brasil';
$dateString = isset($_GET['date']) ? $_GET['date'] :  '13/07/2017';

$dateMin = DateTime::createFromFormat('d/m/Y  H:i:s', $dateString . ' 00:00:00');
$dateMax = DateTime::createFromFormat('d/m/Y  H:i:s', $dateString . ' 23:59:59');

// Print the next 10 events on the user's calendar.
$calendarId = 'primary';
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

// Build booked arrau
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

print_r($locationTimeMatrix);

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

print_r($allRoutes);

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

print_r($routeCost);

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

print_r($scheduleBooked);

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
print_r($scheduleCost);

// Build json response
$scheduleAvaiable = [];
$suggestTime = " (recomendado)";
foreach($scheduleCost as $eventTime => $eventCost) {
      $eventDateTime = DateTime::createFromFormat('d/m/Y  H:i', $dateString.' '.$eventTime);
      $scheduleAvaiable[] = [ 
        'key' =>  $eventDateTime->format('c'), 
        'val' =>  $eventDateTime->format('d/m/Y H:i') . $suggestTime
      ];
      $suggestTime = "";
}



echo json_encode($scheduleAvaiable);
echo "\n\n\n";
print_r($locations);
echo "\n\n";