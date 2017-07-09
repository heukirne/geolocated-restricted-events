<?php
require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

define('APPLICATION_NAME', 'Google Calendar API PHP Quickstart');
define('CREDENTIALS_PATH', __DIR__ . '/user_credentials.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/calendar-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Calendar::CALENDAR_READONLY)
));

// Get Google Api Token
$google_key = "";
$clientSecretPath = expandHomeDirectory(CLIENT_SECRET_PATH);
if (file_exists($clientSecretPath)) {
	$clientJson = json_decode(file_get_contents($clientSecretPath), true);
	$google_key = $clientJson['api'];
} else {
	die("{ success:0, msg: 'Create Api Token!' }");
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfig(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = json_decode(file_get_contents($credentialsPath), true);
  } else {
    die("{ success:0, msg: 'Run generate_credential.php first!' }");
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

//Get destination address
$destination = isset($_GET['destination']) ? $_GET['destination'] : 'Avenida Ipiranga, 7200 - Jardim Botânico, Porto Alegre - RS, 91530-000, Brasil';

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Calendar($client);

//Set Google Distance Matrix Api URL with ApiKey
$gmatrix = 'https://maps.googleapis.com/maps/api/distancematrix/json?key=' . $google_key;

// Print the next 10 events on the user's calendar.
$calendarId = 'primary';
$optParams = array(
  'maxResults' => 10,
  'orderBy' => 'startTime',
  'singleEvents' => TRUE,
  'timeMin' => date('c'),
);
$results = $service->events->listEvents($calendarId, $optParams);

//Init Guzzle
$client = new Client();	

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
	    $response = $client->request('GET', $gmatrix . $params);
	    $body = json_decode($response->getBody());
	    
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