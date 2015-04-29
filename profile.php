<?php

require_once("class.PicShare.php");

$PicShare = new PicShare(0);

if( !isset($_GET['id']) || !is_numeric($_GET['id']) ){ header("Location: /"); }


$profile = new Account( $_GET['id'] );


if( isset($_POST['addFriend']) ){
	print("Adding friend!");

	if( $PicShare->account->addFriend( $profile->userId ) ){
		print("Added!");
	}
}


if(
	// Signed in
	isset($PicShare->account->signed) &&

	// Not self
	isset($PicShare->account->userId) && $PicShare->account->userId !== $profile->userId &&

	// Not already friends
	$PicShare->account->checkFriends($profile->userId) === 0
){
	$addFriend = '<form method="post"><input type="submit" name="addFriend" value="Add friend"></form>';
}else{
	$addFriend = '';
}

ob_start();
print( Template::view("profile", [
	$profile->getName(),
	'<tr><th>Birthdate</th><td>'. $profile->birthDate .'</td></tr>'.
	'<tr><th>Gender</th><td>'. $profile->gender .'</td></tr>'.

	'<tr><th>Home city</th><td>'. $profile->homeCity .'</td></tr>'.
	'<tr><th>Home state</th><td>'. $profile->homeState .'</td></tr>'.
	'<tr><th>Home country</th><td>'. $profile->homeCountry .'</td></tr>'.

	'<tr><th>Current city</th><td>'. $profile->currentCity .'</td></tr>'.
	'<tr><th>Current state</th><td>'. $profile->currentState .'</td></tr>'.
	'<tr><th>Current country</th><td>'. $profile->currentCountry .'</td></tr>',

	'<li>'. implode($profile->getFriends(), '</li><li>') .'</li>',
	$addFriend,
	implode($profile->getAlbums(), ""),
	implode($profile->getTagAlbums(), "")
]) );
print( $PicShare->createPage(ob_get_clean()) );
?>