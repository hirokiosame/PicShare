<?php

require_once("class.PicShare.php");

$PicShare = new PicShare(0);

// Get album
if( !isset($_GET['id']) ){
	$album = $PicShare->account->getUnpublishedAlbum();
	$owned = true;
}else{
	$album = new Album( $_GET['id'] );
	$owned = isset($PicShare->account->userId) && $PicShare->account->userId === $album->owner->userId;
}


// Build Title
if( $owned ){

	// Action: Delete album
	if( isset($_POST['delete']) ){
		if( $album->delete() ){
			header("Location: /");
		}
	}

	// Action: Upload photo
	if( isset($_FILES['file']) && $validateImage = getimagesize($_FILES['file']['tmp_name']) ){

		$photoId = $album->addPhoto($_FILES['file']['tmp_name'], $_FILES['file']['name']);

		if( $photoId ){

			// Fetch
			print( Template::view("gridBox", [
				'/photo.php?id=' . $photoId,
				'/image.php?id=' . $photoId,
				':name:'
			]) );
		}
		die();
	}

	// Action: Update title
	if( validateInputs($_POST, ['albumName']) ){
		$album->updateName($_POST['albumName']);
	}

	// Action: Publish album
	if( isset($_POST['publish']) ){
		$album->publish();
		header("Location: /album.php?id=" . $album->albumId);
	}


	$title = '<div class="left">
		<form method="post">
			<input type="text" id="albumName" name="albumName" value="'. $album->name .'" placeholder="Album name">
		</form>
	</div>';

	$photos = Template::view("dropBox", ["#"]);


	if( $album->published ){
		// print(strtotime( $album->creationDate ));
		@$date = date("m/d, Y", strtotime( $album->creationDate ) );
		$rightSide = ' Created on ' . $date;
	}else{
		$rightSide = '<form method="post"><input id="publishAlbum" type="submit" name="publish" value="Publish"></form>';
	}

	$rightSide .= '<form method="post"><input type="submit" name="delete" value="Delete album"></form>';

}else{
	$title = '<div class="left"><a href="/album.php?id=' . $album->albumId . '">' . $album->name . '</a> by <a href="/profile.php?id='. $album->owner->userId .'">' . $album->owner->getName() .'</a></div>';

	$photos = '';

	$rightSide = '';
}

$title .= '<div class="right">' . $rightSide . '</div>';


// Build content
$gotPhotos = $album->getPhotos();
function viewPhoto($photo){

	return Template::view("gridBox", [
		'/photo.php?id=' . $photo['photoId'],
		'/image.php?id=' . $photo['photoId'],
		$photo['caption']
	]);
}

$photos .= implode(array_map('viewPhoto', $gotPhotos));



ob_start();
print(Template::view("album", [
	$title,
	$photos
]));
print( $PicShare->createPage(ob_get_clean()) );

?>