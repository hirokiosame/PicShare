<?php

require_once("class.PicShare.php");

$PicShare = new PicShare(-1);


if( validateInputs($_POST, [
	"email", "password",
	"firstName", "lastName",
	"month", "day", "year",
	"gender",
	"homeCity", "homeState", "homeCountry",
	"currentCity", "currentState", "currentCountry"
]) ){


	$stmt = $PicShare->db->prepare('
		INSERT INTO Users (
			email, password,
			"firstName", "lastName",
			"birthDate", gender,
			"homeCity", "homeState", "homeCountry",
			"currentCity", "currentState", "currentCountry"
		)
		VALUES (
			:email,
			:password,
			:firstName, :lastName,
			:birthDate,
			CAST(:gender AS genders),
			:homeCity, :homeState, :homeCountry,
			:currentCity, :currentState, :currentCountry
		);
	');

	$inserted = $stmt->execute([
		"email" => $_POST['email'],
		"password" => $_POST['password'],
		"firstName" => $_POST['firstName'],
		"lastName" => $_POST['lastName'],
		"birthDate" => $_POST['year'] ."-". $_POST['day'] ."-". $_POST['month'],
		"gender" => $_POST['gender'],
		"homeCity" => $_POST['homeCity'],
		"homeState" => $_POST['homeState'],
		"homeCountry" => $_POST['homeCountry'],
		"currentCity" => $_POST['currentCity'],
		"currentState" => $_POST['currentState'],
		"currentCountry" => $_POST['currentCountry']
	]);


	if( $inserted ){

		(new Account($PicShare->db->lastInsertID('"users_userId_seq"')))->login();

		header("Location: /");
	}
}


	$months = '';
	for( $m = 1; $m <= 12; $m++ ){
		@$month = date('F', mktime(0,0,0,$m, 1, date('Y')));
		$months .= '<option value="' . $m . '">' . $month . '</option>';
	}


ob_start();
print(Template::view("signup", [
	$months
]));
	print( $PicShare->createPage(ob_get_clean()) );
?>