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
$date = isset($_GET['date']) ? $_GET['date'] : date_format(new DateTime('NOW'), 'd/m/Y');

// Print the next 10 events on the user's calendar.
$calendarId = 'primary';
$optParams = array(
  'maxResults' => 20,
  'orderBy' => 'startTime',
  'singleEvents' => TRUE,
  'timeMin' => date('c'),
);
$results = $service->events->listEvents($calendarId, $optParams);	

$schedule = [];

if (count($results->getItems()) > 0) {

  foreach ($results->getItems() as $event) {
  	
  	if (!empty($event->location) && !empty($event->end->dateTime)) {
  		$endDatetime = new DateTime($event->end->dateTime);
  		$schedule[] = [ 
  			'key' =>  $event->end->dateTime, 
  			'val' =>  $endDatetime->format('d/m/Y H:i')
  		];
  	}

  }

  echo json_encode($schedule);
}