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
  'maxResults' => 20,
  'orderBy' => 'startTime',
  'singleEvents' => TRUE,
  'timeMin' => date('c'),
);
$results = $service->events->listEvents($calendarId, $optParams);	

if (count($results->getItems()) == 0) {
  print "No upcoming events found.\n";
} else {
  print "Upcoming Location events:\n\n";
  foreach ($results->getItems() as $event) {
  	
  	if ($event->location) {

	    $start = $event->start->dateTime;
	    if (empty($start)) {
	      $start = $event->start->date;
	    }
	    printf("Event Name: %s (%s)\n", $event->getSummary(), $start);

  		$params = '&origins=' . urlencode($event->location);
    	$params .= '&destinations=' . urlencode($destination);

	    printf("\tNow we're looking up the distance between \n" .
	    		"\t'$destination' and \n" .
	    		"\t'" . $event->location . "\n");
	    $element = getMatrixDistance($params);

	    if (isset($element) && $element->status == "OK") {
			$distance = $element->distance->text;
			$duration = $element->duration->text;

	    	printf("\tDistance: " . $distance . "\n");
	    	printf("\tDuration: " . $duration . "\n");
	    } else {
	    	printf("\tNo information found! \n");
	    }

  	}

  }
}