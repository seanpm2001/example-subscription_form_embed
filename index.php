<?php

	$api_url = "";
	$api_key = "";

?>

<script>

	var cf = {}; // custom fields object

</script>

<?php

	define("ACTIVECAMPAIGN_URL", $api_url);
	define("ACTIVECAMPAIGN_API_KEY", $api_key);

	require_once("../../activecampaign-api-php/includes/ActiveCampaign.class.php");
	$ac = new ActiveCampaign(ACTIVECAMPAIGN_URL, ACTIVECAMPAIGN_API_KEY);

	$form_embed_params = array(
		"id" => 1157,
		"action" => "",
		"ajax" => 0,
		"css" => 1,
	);

	// perform sync (or swim? ;)
	// if 0, it does an add/edit
	$sync = 0;

	function dbg($var, $continue = 0, $element = "pre") {
	  echo "<" . $element . ">";
	  echo "Vartype: " . gettype($var) . "\n";
	  if ( is_array($var) ) echo "Elements: " . count($var) . "\n\n";
	  elseif ( is_string($var) ) echo "Length: " . strlen($var) . "\n\n";
	  print_r($var);
	  echo "</" . $element . ">";
		if (!$continue) exit();
	}

	$api_params = array();
	foreach ($form_embed_params as $var => $val) {
		$api_params[] = $var . "=" . $val;
	}

	$form_process = $ac->api("form/process?sync={$sync}");

	if ($form_process && (int)$form_embed_params["ajax"]) {
		// form submitted via ajax
		echo $form_process;
		exit;
	}

	// check for subscriber visiting this page (to preload their data).
	$contact = null;
	if (isset($_GET["hash"])) {
		$hash = $_GET["hash"];
		$contact = $ac->api("contact/view?hash={$hash}");
//dbg($contact);

		if (!$contact->success) {
			$contact = null;
		}
		else {
			echo "<script>";
			foreach ($contact->fields as $field) {
				?>
				cf[<?php echo $field->id; ?>] = "<?php echo $field->val; ?>";
				<?php
			}
			echo "</script>";
		}

	}

?>

<html>

<head>

	<style type="text/css">

		#form_result_message {
			font-weight: bold;
			margin-bottom: 30px;
		}

	</style>

	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>

</head>

<body>

	<div id="form_result_message">

		<?php

			if ($form_process) {
				// form submitted
				$form_process = json_decode($form_process);
				echo $form_process->message;
			}

		?>

	</div>

	<?php

		$form_html = $ac->api("form/embed?" . implode("&", $api_params));
		echo $form_html;

	?>

	<?php
	
		if ($contact) {
	
			?>

			<script>

				$(document).ready(function() {

					$("#_form_<?php echo $form_embed_params["id"]; ?> input[name=fullname]").val('<?php echo $contact->name; ?>');
					$("#_form_<?php echo $form_embed_params["id"]; ?> input[name=email]").val('<?php echo $contact->email; ?>');

					// loop through all custom fields in the form.
					$("#_form_<?php echo $form_embed_params["id"]; ?> *[name^=field]").each(function() {
						// IE: field[148]. just get the number.
						var cfid = $(this).attr("name").match(/[0-9]+/);
						if (typeof(cf[cfid]) != "undefined") {
							$(this).val(cf[cfid]);
						}
					});

				});

			</script>

			<?php

		}

	?>

</body>

</html>