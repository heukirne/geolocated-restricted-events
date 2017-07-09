<?php
require_once 'core.php';

//Get destination address
$destination = isset($_GET['destination']) ? $_GET['destination'] : 'Avenida Ipiranga, 7200 - Jardim Botânico, Porto Alegre - RS, 91530-000, Brasil';

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Calendar($client);


// Print the next 10 events on the user's calendar.
$calendarId = 'primary';
$optParams = array(
  'maxResults' => 10,
  'orderBy' => 'startTime',
  'singleEvents' => TRUE,
  'timeMin' => date('c'),
);
$results = $service->events->listEvents($calendarId, $optParams);	

if (count($results->getItems()) == 0) {
  print "No upcoming events found.\n";
} else {
  print "Upcoming Location events:\n";
  foreach ($results->getItems() as $event) {
  	
  	if ($event->location) {

	    $start = $event->start->dateTime;
	    if (empty($start)) {
	      $start = $event->start->date;
	    }
	    printf("Event Name: %s (%s)\n", $event->getSummary(), $start);

  		$params = '&origins=' . urlencode($event->location);
    	$params .= '&destinations=' . urlencode($destination);



	    printf("Now we're looking up the distance between \n'$destination' and \n'" . $event->location . "\n");
	    $body = getMatrixDistance($params);
	    
	    if (isset($body) && isset($body->rows[0]) && isset($body->rows[0]->elements[0])) {
			$distance = $body->rows[0]->elements[0]->distance->text;
			$duration = $body->rows[0]->elements[0]->duration->text;

	    	printf("Distance: " . $distance . "\n");
	    	printf("Duration: " . $duration . "\n");
	    } else {
	    	printf("No information found!");
	    }

  	}

  }
}