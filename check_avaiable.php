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
$dateString = isset($_GET['date']) ? $_GET['date'] : date_format(new DateTime('NOW'), 'd/m/Y');

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

// Build day schedule
$eventDuration = '00:44';
$schedule = [
              '9:00',
              '9:45',
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


// Remove conflicted events
$schedule = array_diff($schedule, []); // convert to real array
if (count($results->getItems()) > 0) {

  foreach ($results->getItems() as $event) {
    
    if (!empty($event->location) && !empty($event->end->dateTime)) {
      $startTimeDatetime = new DateTime($event->start->dateTime);
      $startTime = $startTimeDatetime->format('H:i');

      $key = array_search($startTime, $schedule);
      unset($schedule[$key]);
    }
  }
}

// Build json response
$scheduleAvaiable = [];
foreach($schedule as $eventTime) {
      $eventDateTime = DateTime::createFromFormat('d/m/Y  H:i', $dateString.' '.$eventTime);
      $scheduleAvaiable[] = [ 
        'key' =>  $eventDateTime->format('c'), 
        'val' =>  $eventDateTime->format('d/m/Y H:i')
      ];
}

echo json_encode($scheduleAvaiable);

// Ref: https://developers.google.com/optimization/routing/tsp/vehicle_routing_time_windows
// Travel Time Minimization based on Google Matrix Distance