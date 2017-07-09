<?php
require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

define('APPLICATION_NAME', 'Google Calendar API PHP Quickstart');
define('CREDENTIALS_PATH', __DIR__ . '/user_credentials.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
define('MATRIX_CACHE', __DIR__ . '/matrix_distance.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/calendar-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Calendar::CALENDAR_READONLY)
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

/**
 * Search for source x destionation distance matrix
 * @param string $params with source and destination.
 * @return body object.
 */
function getMatrixDistance($params) {
  //Init Guzzle
  $guzzleClient = new Client();

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
    if (isset($matrixCache[$hash])) {
      $body = $matrixCache[$hash];
    } else {
      $matrixCache[$hash] = $body;
      file_put_contents('matrix_distance.json', json_encode($matrixCache));

      $response = $guzzleClient->request('GET', $gmatrix . $params);
      $body = json_decode($response->getBody());

    }
  }
  return $body;
}