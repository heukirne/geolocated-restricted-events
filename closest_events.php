<?php
require_once 'core.php';

//Get destination address
$destination = 'Avenida Ipiranga, 7200 - Jardim Botânico, Porto Alegre - RS, 91530-000, Brasil';

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Calendar($client);

// Print the next 10 events on the user's calendar.
$calendarId = $clientJson['calendarId'][0];
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

  		$from = $event->location;
    	$to = $destination;

    	print("\tLooking up distance between: \n \t $from and \n \t $to \n");

	    $element = getMatrixDistance($from,$to);

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