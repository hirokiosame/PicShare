<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: signup.php
// Description: Front-page of the website -- displays all of the albums

require_once("class.PicShare.php");

// Must be logged out to view
$PicShare = new PicShare(-1);


// Make sure the attributes exist in the POST request
if( validateInputs($_POST, [
	"email", "password",
	"firstName", "lastName",
	"month", "day", "year",
	"gender",
	"homeCity", "homeState", "homeCountry",
	"currentCity", "currentState", "currentCountry"
]) ){

	// Prepare SQL statement to insert new account
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

	// Register account by inserting it into the table
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

	// if successfully inserted
	if( $inserted ){

		// Login (create session)
		(new Account($PicShare->db->lastInsertID('"users_userId_seq"')))->login();

		// Redirect to main page
		header("Location: /");
	}
}


// Generate months to print in the signup form in a dropdown/select

// Initialize aggergator
$months = '';

// 12 months
for( $m = 1; $m <= 12; $m++ ){
	// Get the month name
	@$month = date('F', mktime(0, 0, 0, $m, 1, date('Y')));

	// Append the month as an option
	$months .= '<option value="' . $m . '">' . $month . '</option>';
}


// Print HTML 
print( $PicShare->createPage( Template::view("signup", [ $months ]) ) );
?>