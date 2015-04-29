<?php

require_once("class.PicShare.php");

$PicShare = new PicShare(0);

if( !isset($_GET['id']) ){ header("Location: /"); }


// Get Photo info
$photo = new Photo($_GET['id']);
$isOwner = isset($PicShare->account->userId) && $photo->owner->userId === $PicShare->account->userId;



// Action: Like photo
if( isset($_POST['likePhoto']) ){

	if( isset($PicShare->account->userId) ){

		$photo->likePhoto($PicShare->account->userId);
	}else{
		print("Must be logged in");
	}
}

$caption = $photo->caption;


$likesThis = $photo->getLikes();

$alreadyLiked = isset($PicShare->account->userId) ? $photo->likedBy($PicShare->account->userId) : false;


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

}else{

	// Action: write comment
	if( validateInputs($_POST, ['comment']) ){

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

if( $isOwner ){
	$caption = '<form method="post"><textarea name="caption">' . $caption . '</textarea><input type="submit" value="Save"></form>';
	$caption .= '<form method="post"><input type="submit" name="deletePhoto" value="Delete Photo"></form>';


	$tagCs .= '<form method="post"><input type="text" name="tag" placeholder="Tag name"> <input type="submit" value="Add Tag"></form>';

	if( count($tags) > 1 ){

		$tagCs .= '<h4>Tag suggestions</h4><ul>';

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
		// print_r($suggestions->fetchAll());
		while( $suggestion = $suggestions->fetch() ){
			$tagCs .= '<li><a href="/search.php?type=tag&q=' . $suggestion['tag'] . '">'. $suggestion['tag'] .' ('. $suggestion['freq'] .')</a></li>';
		}
		$tagCs .= '</ul>';
	}


	$comments .= '<form method="post"><textarea placeholder="You cannot comment on your own photo" disabled></textarea></form>';

}else{

	$comments .= '<form method="post"><textarea name="comment" placeholder="Write comment..."></textarea><input type="submit" value="Comment"></form>';
}

ob_start();
print( Template::view("viewPhoto", [
	// Photo by
	$photo->owner->userId, $photo->owner->getName(),

	// Return to album
	$photo->albumId,

	// Image URL
	$photo->photoId,

	$alreadyLiked ? "Unlike" : "Like",
	count($likesThis) > 0 ? implode(", ", $likesThis) . " likes this photo. (" . count($likesThis) ." likes)" : "",

	$comments,

	$caption,
	$tagCs

]) );

print( $PicShare->createPage(ob_get_clean()) );
?>