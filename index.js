function isEmail(email) {
	var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	return regex.test(email);
}

var autocomplete;

$(document).ready(function() {

	var defaultBounds = new google.maps.LatLngBounds(
		new google.maps.LatLng(-29.967808, -51.242466),
		new google.maps.LatLng(-30.157086, -51.013126));

	var options = {
		bounds: defaultBounds,
		types: ['address'],
		componentRestrictions: {
			country: 'br'
		}
	};

	autocomplete = new google.maps.places.Autocomplete($("#address")[0], options);

	google.maps.event.addListener(autocomplete, 'place_changed', function() {
		var place = autocomplete.getPlace();
		console.log(place.address_components);

		place.address_components.forEach(function(item) {
			if (item.types[0].indexOf("postal") == 0) {
				$("#address").val($("#address").val() + ", " + item.short_name)
			}
		})

	});

	$('#calendar_send').submit(function() {
		var isValid = true;

		if ($('[name=infra]:checked').length == 0) {
			isValid = false;
		}
		if ($('[name=metragem]:checked').length == 0) {
			isValid = false;
		}
		if ($('[name=cliente]:checked').length == 0) {
			isValid = false;
		}

		$("input").each(function() {
			var element = $(this);
			if (element.val() == "" && $(this).prop('disabled') == false)  {
				isValid = false;
			}
		});

		if (isValid) {
			return true;
		} else {
			alert('Todos os campos sao obrigatorios.');
			return false;
		}
	});

	$('#checkAvaiable').click(function() {
		var validPlace = false;
		var place = autocomplete.getPlace();

		if (place) {
			place.address_components.forEach(function(item) {
				if (item.short_name.indexOf("RS") == 0) {
					validPlace = true;
				}
			});
		}

		if ($('#address').val() == "" || $('#date').val() == "" || !validPlace) {
			alert('Preencha um endereco valido e uma data!');
			return false;
		}

		$("#loader").show();
		$.getJSON("check_avaiable.php", {
				address: $('#address').val(),
				date: $('#date').val()
			})
			.done(function(data) {

				console.log(data);

				$('#horario').empty();
				$.each(data, function(i, obj) {
					$('#horario').append($('<option>').text(obj.val).attr('value', obj.key));
				});

			})
			.fail(function() {
				$('#horario').empty();
				$('#horario').append($('<option>').text("Erro ao verificar agenda.").attr('value', '-'));
			})
			.always(function() {
				$("#loader").hide();
			});

	});

	$('input[name="cliente"]:radio').change(function() {
		if ($('input[name="cliente"]:checked').val() == "Proprietario") {
			$("#corretor-div").hide();
			$(".corretor-fields").prop('disabled', true);
		} else {
			$("#corretor-div").show();
			$(".corretor-fields").prop('disabled', false);
		}
	});

})