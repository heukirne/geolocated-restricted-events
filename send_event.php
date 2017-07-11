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

	if (empty($msg)) {
		$createdEvent = $service->events->quickAdd(
	    CALENDAR_ID,
	    'Appointment at Somewhere on July 11th 10pm-10:25pm');

		$msg = "Solicitação de agendamento enviada com sucesso!";
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