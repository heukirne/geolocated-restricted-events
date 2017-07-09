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
  print "Upcoming Location events:\n";
  foreach ($results->getItems() as $event) {
  	
  	if (!empty($event->location) && !empty($event->end->dateTime)) {

	    print("\nEvent Name: " . $event->getSummary() . "\n");
	    print("End Time: " . $event->end->dateTime . "\n");

  		$params = '&origins=' . urlencode($event->location);
    	$params .= '&destinations=' . urlencode($destination);

	    print("\tNow we're looking up the distance between \n" .
	    		"\t'$destination' and \n" .
	    		"\t'" . $event->location . "\n");
	    $element = getMatrixDistance($params);

	    if (isset($element) && $element->status == "OK") {
			$distance = $element->distance;
			$duration = $element->duration;

	    	print("\tDistance: " . $distance->text . ", " . $distance->value . "\n");
	    	print("\tDuration: " . $duration->text . ", " . $duration->value . "\n");
	    } else {
	    	print("\tNo driving route found! \n");
	    }

  	}

  }
}