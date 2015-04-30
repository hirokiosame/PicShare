<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: photo.php
// Description: Show photo page of an image -- interface to like, comment (or tag, edit, or delete as the owner)

require_once("class.PicShare.php");

// Viewable by anyone
$PicShare = new PicShare(0);

// If a photo is not requested, ignore
if( !isset($_GET['id']) ){ header("Location: /"); }

// Get Photo info
$photo = new Photo($_GET['id']);

$caption = $photo->caption;

$isOwner = isset($PicShare->account->userId) && $photo->owner->userId === $PicShare->account->userId;


// If request to like the photo
if( isset($_POST['likePhoto']) ){

	// Make sure the viewer is logged in
	if( isset($PicShare->account->userId) ){

		// Like the photo
		$photo->likePhoto($PicShare->account->userId);
	}else{
		print("Must be logged in");
	}
}

$likesThis = $photo->getLikes();

$alreadyLiked = isset($PicShare->account->userId) ? $photo->likedBy($PicShare->account->userId) : false;


// If the viewer is the owner of the photo
if( $isOwner ){

	// If deleting photo
	if( isset($_POST['deletePhoto']) ){
		$photo->deletePhoto();
		header("Location: /album.php?id=" . $photo->albumId);
	}

	// If updating caption
	if( isset($_POST['caption']) ){
		$photo->updateCaption($_POST['caption']);
		$caption = $photo->caption;
	}

	// If submitting tag
	if( isset($_POST['tag']) && str_word_count($_POST['tag']) === 1 ){
		$photo->addTag($_POST['tag']);
	}

}

// If not logged in or anyone but the owner
else{

	// Action: write comment (Owners cannot comment but other users and anonymous users can comment)
	if( validateInputs($_POST, ['comment']) ){

		// Post the comment
		$photo->commentPhoto(
			isset($PicShare->account->userId) ? $PicShare->account->userId : null,
			$_POST['comment']
		);
	}
}

// Collect tags
$tags = $photo->getTags();
$tagCs = implode($tags, " ");


// Collect comments
$comments = $photo->getComments();

// Check if owner of the photo
if( $isOwner ){

	// Print form for editing caption
	$caption = '<form method="post"><textarea name="caption">' . $caption . '</textarea><input type="submit" value="Save"></form>';

	// Print delete button for photo
	$caption .= '<form method="post"><input type="submit" name="deletePhoto" value="Delete Photo"></form>';

	// Print "Add Tag" form for photo
	$tagCs .= '<form method="post"><input type="text" name="tag" placeholder="Tag name"> <input type="submit" value="Add Tag"></form>';

	// If there is more than 1 caption, offer tag suggestions
	if( count($tags) > 1 ){

		$tagCs .= '<h4>Tag suggestions</h4><ul>';

		// Get suggestions
		// Find photos that use the same tag and list the other tags in order of frequency
		$suggestions = $PicShare->db->prepare('
			SELECT	Tags."tag", COUNT(TaggedPhotos."tagId") AS freq
			FROM	TaggedPhotos, Tags
			WHERE	TaggedPhotos."photoId" IN (
						SELECT	DISTINCT TaggedPhotos."photoId"
						FROM	TaggedPhotos
						WHERE	TaggedPhotos."photoId" != :photoId AND
								TaggedPhotos."tagId" IN (
									SELECT TaggedPhotos."tagId"
									FROM TaggedPhotos
									WHERE TaggedPhotos."photoId" = :photoId
								)
					) AND
					TaggedPhotos."tagId" NOT IN (
						SELECT TaggedPhotos."tagId"
						FROM TaggedPhotos
						WHERE TaggedPhotos."photoId" = :photoId
					) AND
					TaggedPhotos."tagId" = Tags."tagId"
			GROUP BY TaggedPhotos."tagId", Tags.tag
			ORDER BY freq DESC
		');

		$suggestions->execute([
			"photoId" => $photo->photoId
		]);

		// Render each tag in a list
		while( $suggestion = $suggestions->fetch() ){
			$tagCs .= '<li><a href="/search.php?type=tag&q=' . $suggestion['tag'] . '">'. $suggestion['tag'] .' ('. $suggestion['freq'] .')</a></li>';
		}
		$tagCs .= '</ul>';
	}

	// Not commentable message
	$comments .= '<form method="post"><textarea placeholder="You cannot comment on your own photo" disabled></textarea></form>';

}else{

	// Render comment form
	$comments .= '<form method="post"><textarea name="comment" placeholder="Write comment..."></textarea><input type="submit" value="Comment"></form>';
}


// Print HTML
ob_start();
print( Template::view("viewPhoto", [
	// Photo by
	$photo->owner->userId, $photo->owner->getName(),

	// Return to album
	$photo->albumId,

	// Image URL
	$photo->photoId,

	// Determine whether or not to show "Like" or "Unlike"
	$alreadyLiked ? "Unlike" : "Like",

	// Show who liked the photo
	count($likesThis) > 0 ? implode(", ", $likesThis) . " likes this photo. (" . count($likesThis) ." likes)" : "",

	// Show comments
	$comments,

	// Show caption
	$caption,

	// Show tags
	$tagCs
]) );

print( $PicShare->createPage(ob_get_clean()) );
?>