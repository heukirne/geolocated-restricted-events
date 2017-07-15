<?php
	require_once 'core.php';

	$msg = '';
	try {
		$client = getClient();
		$service = new Google_Service_Calendar($client);
		$calendarList = $service->calendarList->listCalendarList(); //try api
	} catch (Exception $e) {
		$msg = 'Erro ao acessar calendario! :(';
	}


	if (!empty($_POST)) {

		$idx = $_POST['idx'];
		$calendarId = $clientJson['calendarId'][$idx];

		$dateNow = DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d') . ' 23:59:59');

		$dateStart = new DateTime($_POST['horario']);

		if ( $dateStart < $dateNow ) {
			$msg = "Horário ultrapassado, tente outro horário!";
		} else {

			$dateEnd = new DateTime($_POST['horario']);
			$dateEnd->add(new DateInterval('PT'.$_POST['tempo'].'M'));

			// Load the next 20 events on the user's calendar.
			$optParams = array(
			  'maxResults' => 1,
			  'orderBy' => 'startTime',
			  'singleEvents' => TRUE,
			  'timeMin' => $dateStart->format('c'),
			  'timeMax' => $dateEnd->format('c'),
			);
			$results = $service->events->listEvents($calendarId, $optParams);

			if (count($results) > 0) {
				$msg = "Horário indisponível, tente outro horário!";
			} else {

				// Send event to Calendar

				$dateStart = new DateTime($_POST['horario']);

				$dateEnd = new DateTime($_POST['horario']);
				$dateEnd->add(new DateInterval('PT'.$_POST['tempo'].'M'));

				$description = "";
				foreach($_POST as $field => $value) {
					$description .= ucfirst($field) . ": $value \n";
				}

				// Create Vent with Notification
				$event = new Google_Service_Calendar_Event([
				  'summary' => $_POST['predio'],
				  'location' => $_POST['address'],
				  'description' => $description,
				  'start' => [
				    'dateTime' => $dateStart->format('c'),
				    'timeZone' => 'America/Sao_Paulo',
				  ],
				  'end' => [
				    'dateTime' => $dateEnd->format('c'),
				    'timeZone' => 'America/Sao_Paulo',
				  ],
				  'attendees' => [
				    ['email' => $clientJson['emailAdmin']],
				  ],
				]);

		        $optParams = [ 'sendNotifications' => true ];
				$calendarEvent = $service->events->insert($calendarId, $event, $optParams);


				// Add more attendees without notification

				$eventUpdate = new Google_Service_Calendar_Event([
				  'attendees' => [
				  	['email' => $clientJson['emailAdmin']],
				    ['email' => $clientJson['emailPhotographer'][$idx]],
				    ['email' => $_POST['email']],
				  ],
				]);

				$optParams = [ 'sendNotifications' => false ];
				$service->events->patch($calendarId, $calendarEvent->id, $eventUpdate);

				$msg = "Solicitação de agendamento enviada com sucesso! Aguarde confirmação.";

			}

		}

	} else {
		$msg = 'Dados de agendamento faltantes! :(';
	}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Agendamento de Fotos - Agendado!</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <link rel="shortcut icon" href="favicon.png">
</head>
<body>

<div class="container">
  <h2><?=htmlentities($msg)?></h2>
</div>

</body>
</html>