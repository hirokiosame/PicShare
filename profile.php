<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: profile.php
// Description: Page that shows the requested users profile

require_once("class.PicShare.php");

// Page is visible to anyone
$PicShare = new PicShare(0);


// If no profile ID is passed in, redirect to home
if( !isset($_GET['id']) || !is_numeric($_GET['id']) ){ header("Location: /"); }


// Fetch the user profile
$profile = new Account( $_GET['id'] );


// If there is a request to add the user as a friend
if( isset($_POST['addFriend']) ){

	print("Adding friend!");

	// Add as friend
	if( $PicShare->account->addFriend( $profile->userId ) ){
		print("Added!");
	}
}


// Only display "add friend" button if the user is...
if(
	// Signed in
	isset($PicShare->account->signed) &&

	// Not viewing self
	isset($PicShare->account->userId) && $PicShare->account->userId !== $profile->userId &&

	// Not already friends
	$PicShare->account->checkFriends($profile->userId) === 0
){
	$addFriend = '<form method="post"><input type="submit" name="addFriend" value="Add friend"></form>';
}else{
	$addFriend = '';
}


// Print HTML
print( $PicShare->createPage( Template::view("profile", [

	// Show Profile
	$profile->getName(),
	'<tr><th>Birthdate</th><td>'. $profile->birthDate .'</td></tr>'.
	'<tr><th>Gender</th><td>'. $profile->gender .'</td></tr>'.

	'<tr><th>Home city</th><td>'. $profile->homeCity .'</td></tr>'.
	'<tr><th>Home state</th><td>'. $profile->homeState .'</td></tr>'.
	'<tr><th>Home country</th><td>'. $profile->homeCountry .'</td></tr>'.

	'<tr><th>Current city</th><td>'. $profile->currentCity .'</td></tr>'.
	'<tr><th>Current state</th><td>'. $profile->currentState .'</td></tr>'.
	'<tr><th>Current country</th><td>'. $profile->currentCountry .'</td></tr>',

	// List friends
	'<li>'. implode($profile->getFriends(), '</li><li>') .'</li>',
	$addFriend,

	// List albums
	implode($profile->getAlbums(), ""),

	// List Tags
	implode($profile->getTagAlbums(), "")
]) ) );
?>