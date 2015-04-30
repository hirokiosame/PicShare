<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: album.php
// Description: Page to show the album of photos

require_once("class.PicShare.php");

// Page viewable by anyone
$PicShare = new PicShare(0);


// Get album

// If no particular album is specified
if( !isset($_GET['id']) ){

	// And is logged in
	if( isset($PicShare->account->userId) ){

		// Create a new, unpublished album
		$album = $PicShare->account->getUnpublishedAlbum();
		$owned = true;
	}

	// If now, redirect them home
	else{
		header("Location: /");
	}
}else{

	// Get requested album
	$album = new Album( $_GET['id'] );

	// Check if the viewer owns the album
	$owned = isset($PicShare->account->userId) && $PicShare->account->userId === $album->owner->userId;
}


// If the album is owned by the viewer
if( $owned ){

	// If requested to delte
	if( isset($_POST['delete']) ){

		// Delete album
		if( $album->delete() ){

			// If delete is successful, redirect
			header("Location: /");
		}
	}

	// If requested to upload a photo -- only accessed by AJAX
	if( isset($_FILES['file']) && $validateImage = getimagesize($_FILES['file']['tmp_name']) ){

		// Add the photo to the album
		$photoId = $album->addPhoto($_FILES['file']['tmp_name'], $_FILES['file']['name']);

		// If the photo is added, show photo for the ajax request to fetch
		if( $photoId ){

			// Fetch
			print( Template::view("gridBox", [
				'/photo.php?id=' . $photoId,
				'/image.php?id=' . $photoId,
				':name:'
			]) );
		}

		// Do not continue since this is an ajax request
		die();
	}

	// If there is a request for an album name
	if( validateInputs($_POST, ['albumName']) ){

		// Update album name
		$album->updateName($_POST['albumName']);
	}

	// If there is a request to publish the album
	if( isset($_POST['publish']) ){

		// Publish the album and make it public
		$album->publish();

		// Redirect the album to the page
		header("Location: /album.php?id=" . $album->albumId);
	}

	// Create page title
	$title = '<div class="left">
		<form method="post">
			<input type="text" id="albumName" name="albumName" value="'. $album->name .'" placeholder="Album name">
		</form>
	</div>';

	$photos = Template::view("dropBox", ["#"]);


	// If the album is published
	if( $album->published ){

		// Print created dated
		@$date = date("m/d, Y", strtotime( $album->creationDate ) );
		$rightSide = ' Created on ' . $date;
	}

	// Album is not published
	else{

		// Show publish button
		$rightSide = '<form method="post"><input id="publishAlbum" type="submit" name="publish" value="Publish"></form>';
	}

	// Show button to delte the album
	$rightSide .= '<form method="post"><input type="submit" name="delete" value="Delete album"></form>';

}

// If the album is not owned by viewer
else{

	// Print album name
	$title = '<div class="left"><a href="/album.php?id=' . $album->albumId . '">' . $album->name . '</a> by <a href="/profile.php?id='. $album->owner->userId .'">' . $album->owner->getName() .'</a></div>';

	// Aggregate varible
	$photos = '';

	// No options
	$rightSide = '';
}

// Append options to title (since its next to it)
$title .= '<div class="right">' . $rightSide . '</div>';


// Get all photos from album
$gotPhotos = $album->getPhotos();

// Presentation layer to render photo
function viewPhoto($photo){

	return Template::view("gridBox", [
		'/photo.php?id=' . $photo['photoId'],
		'/image.php?id=' . $photo['photoId'],
		$photo['caption']
	]);
}

// Map all photos be passed into "viewPhoto" to get HTML
$photos .= implode(array_map('viewPhoto', $gotPhotos));


// Print HTML
print( $PicShare->createPage( Template::view("album", [
	$title,
	$photos
]) ) );

?>