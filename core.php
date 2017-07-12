<?php
require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

define('APPLICATION_NAME', 'Geolocated Restricted Events');
define('CREDENTIALS_PATH', __DIR__ . '/user_credentials.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
define('MATRIX_CACHE', __DIR__ . '/matrix_distance.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/calendar-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Calendar::CALENDAR)
));

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
  $client->setApprovalPrompt('force');

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

  // Get Google Api Token
  $clientSecretPath = expandHomeDirectory(CLIENT_SECRET_PATH);
  if (file_exists($clientSecretPath)) {
    $clientJson = json_decode(file_get_contents($clientSecretPath), true);
    define('CALENDAR_ID', $clientJson['calendarId']);
    define('CALENDAR_EMAIL', $clientJson['email']);
  } else {
    die("{ success:0, msg: 'Define calendarId!' }");
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

/**
 * Search for source x destionation distance matrix
 * @param string $params with source and destination.
 * @return body object.
 */
function getMatrixDistance($from, $to) {
  //Init Guzzle
  $guzzleClient = new Client();

  // Sort destinations
  $locations = [];
  array_push($locations, $from);
  array_push($locations, $to);
  sort($locations);
  $from = $locations[0];
  $to = $locations[1];

  // Build param url
  $params = '&origins=' . urlencode($from);
  $params .= '&destinations=' . urlencode($to);

  // Get Google Api Token
  $google_key = "";
  $clientSecretPath = expandHomeDirectory(CLIENT_SECRET_PATH);
  if (file_exists($clientSecretPath)) {
    $clientJson = json_decode(file_get_contents($clientSecretPath), true);
    $google_key = $clientJson['api'];
  } else {
    die("{ success:0, msg: 'Create Api Token!' }");
  }

  //Set Google Distance Matrix Api URL with ApiKey
  $gmatrix = 'https://maps.googleapis.com/maps/api/distancematrix/json?key=' . $google_key;
  $gmatrix .= '&mode=driving';
  $hash = hash("md5",$params);

  $matrixCachePath = expandHomeDirectory(MATRIX_CACHE);
  if (file_exists($matrixCachePath)) {
    $matrixCache = json_decode(file_get_contents($matrixCachePath), true);
  } else {
    $matrixCache = [];
  }

  // Find cache or Request
  if (isset($matrixCache[$hash])) {

    $element = $matrixCache[$hash];
    return json_decode(json_encode($element), FALSE);

  } else {

    $response = $guzzleClient->request('GET', $gmatrix . $params);
    //echo "Log: Matrix Distance Request! \n";
    $body = json_decode($response->getBody());

    $matrixCache[$hash] = $body->rows[0]->elements[0];
    file_put_contents('matrix_distance.json', json_encode($matrixCache));

    return $matrixCache[$hash];
  }

}