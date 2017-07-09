<?php
require_once 'core.php';

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Calendar($client);

//List calendar avaiables
$calendarList = $service->calendarList->listCalendarList();
foreach ($calendarList->getItems() as $calendar) {
	if (isset($calendar->id)) {
		print("calendarId: " . $calendar->id . "\n");
		print("Summary: " . $calendar->getSummary() . "\n\n");
	}
}
