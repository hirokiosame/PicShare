<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: class.Photo.php
// Description: Photo class - abstraction for a row from the photo row

class Photo{

	// Indicator for whether it was actually fetched from the DB
	public $fetched = false;

	// Constructor
	public function __construct ($photoId){

		// Get database connection
		$this->db = Database::getInstance();

		// Fetch photo from "Photos" with the photoId
		$getPhoto = $this->db->prepare('
			SELECT	"photoId", "albumId", "imgType", "caption"
			FROM	Photos
			WHERE	Photos."photoId" = :photoId
		');

		$getPhoto->execute([ "photoId" => $photoId ]);

		$photo = $getPhoto->fetch();

		// Set attributes as properties of the class
		foreach( $photo as $key => $val ){
			$this->{$key} = $val;
		}

		// Fetch the owner and store it as an attribute of the class
		$this->owner = $this->getOwner();
	}

	// Private function to fetch the owner of the photo
	private function getOwner(){

		// Selecct the owner 
		$getOwner = $this->db->prepare('
			SELECT	Users."userId"
			FROM	Photos, Albums, Users
			WHERE	Photos."photoId" = :photoId AND
					Photos."albumId" = Albums."albumId" AND
					Albums."userId" = Users."userId"
		');

		$getOwner->execute([
			"photoId" => $this->photoId
		]);

		$owner = $getOwner->fetch();

		// Return the user wrapped in Account class abstraction
		return new Account($owner['userId']);
	}

	// Public function to update the caption of the photo
	public function updateCaption($newCaption){

		// Update SQL
		$stmt = $this->db->prepare('
			UPDATE	Photos
			SET		caption = :caption
			WHERE	"photoId" = :photoId
		');

		// If execution is successful
		if( $stmt->execute([
			"caption" => $newCaption,
			"photoId" => $this->photoId
		]) ){

			// Update caption attribute
			$this->caption = $newCaption;

			return true;
		}
	}

	// Public function to get all tags of the photo
	public function getTags(){

		// Build SQL query to fetch all tags associated with photo
		$getTags = $this->db->prepare('
			SELECT	Tags.tag
			FROM	TaggedPhotos, Tags
			WHERE	TaggedPhotos."photoId" = :photoId AND
					TaggedPhotos."tagId" = Tags."tagId"
		');

		$getTags->execute([
			"photoId" => $this->photoId
		]);

		$tags = array();

		// Iterate over tags and wrap them in links that lead to the search by that tag
		while( $tag = $getTags->fetch() ){
			$tags[] = '<a href="/search.php?type=tag&q='. $tag['tag'] .'">'. $tag['tag'] .'</a>';
		}

		return $tags;
	}

	// Public function to add a tag to the photo
	public function addTag($tag){

		// Select the tag to see if its already in the database
		// GET the Tag Id if it exists
		$getTag = $this->db->prepare('
			SELECT	Tags."tagId"
			FROM	Tags
			WHERE	Tags.tag = :tag
		');

		$getTag->execute([ "tag" => $tag ]);

		$tagId = $getTag->fetch();

		// If the tag exists, get the ID
		if($tagId){
			$tagId = $tagId['tagId'];
		}else{

			// Insert the new tag
			$insTag = $this->db->prepare("INSERT INTO Tags (tag) VALUES (:tag)");
			$insTag->execute([ "tag" => $tag ]);

			// Get the ID of the new tag
			$tagId = $this->db->lastInsertID('"tags_tagId_seq"');
		}

		// Insert the relation between the Tag and the Photo by their IDs
		$insTag = $this->db->prepare('
			INSERT INTO TaggedPhotos ("tagId", "photoId")
			VALUES (:tagId, :photoId)
		');

		return $insTag->execute([ "tagId" => $tagId, "photoId" => $this->photoId ]);
	}


	// Public function that checks if a particular User liked the photo
	public function likedBy($userId){

		// SELECT to see if the user liked it
		$checkLiked = $this->db->prepare('
			SELECT *
			FROM 	Likes
			WHERE	"userId" = :userId AND
					"photoId" = :photoId
		');

		$checkLiked->execute([
			"userId" => $userId,
			"photoId" => $this->photoId
		]);

		// Return the row counts that matched
		return $checkLiked->rowCount();
	}

	// Public function that gets all the users that liked the photo
	public function getLikes(){

		// SELECT Users that liked the photo
		$stmt = $this->db->prepare('
			SELECT	Users."userId", Users."firstName", Users."lastName"
			FROM	Likes, Users
			WHERE	Likes."photoId" = :photoId AND
					Likes."userId" = Users."userId"
		');

		$stmt->execute([ "photoId" => $this->photoId ]);

		$likedThis = array();

		// For every person, wrap into a link and add to new array
		while( $likes = $stmt->fetch() ){
			$likedThis[] = '<a href="/profile.php?id='. $likes['userId'] .'">'. $likes['firstName'] .' '. $likes['lastName'] .'</a>';
		}

		return $likedThis;
	}


	// Public function to like/unlike the photo by a particular user
	public function likePhoto($userId){

		// Check if the user already liked the photo
		$checkLiked = $this->likedBy($userId);

		// If the user already liked the photo, unlike it
		if( $checkLiked ){

			// Prepare SQL to Delete the like
			$likeStmt = $this->db->prepare('
				DELETE FROM Likes
				WHERE	"userId" = :userId AND
						"photoId" = :photoId
			');
		}else{

			// Prepare SQL to Like the photo
			$likeStmt = $this->db->prepare('
				INSERT INTO Likes ("userId", "photoId")
				VALUES(:userId, :photoId)
			');
		}

		return $likeStmt->execute([
			"userId" => $userId,
			"photoId" => $this->photoId
		]);
	}


	// Public function to get all of the photos
	public function getComments(){

		// SELECT query to get all of the comments
		$stmt = $this->db->prepare('
			SELECT	Comments."commentId",
					Comments."comment",
					Comments."commentDate",
					Users."userId",
					Users."firstName",
					Users."lastName"
			FROM Comments
			LEFT JOIN Users
			ON Comments."userId" = Users."userId"
			WHERE Comments."photoId" = :photoId
		');

		$stmt->execute([ "photoId" => $this->photoId ]);

		$comments = "";

		// For every comment, format and append to array
		while( $comment = $stmt->fetch() ){

			// Format date
			@$date = date("g:i:s A m/d, Y", strtotime( $comment['commentDate'] ) );

			// Add formatted comment to string
			$comments .= Template::view("comment", [
				$comment['userId'],
				isset($comment['userId']) ? ($comment['firstName'] . " " . $comment['lastName']) : "Anonymous",
				$date,
				$comment['comment']
			]);
		}

		return $comments;
	}

	// Public function for a particular use to comment on the photo
	public function commentPhoto($userId, $comment){

		// Prepare sql statement to add the comment to the photo
		$commentStmt = $this->db->prepare('
			INSERT INTO Comments ("photoId", "userId", comment)
			VALUES(:photoId, :userId, :comment)
		');

		return $commentStmt->execute([
			"photoId" => $this->photoId,
			"userId" => $userId,
			"comment" => $comment
		]);
	}

	// Public function to delete the photo
	public function deletephoto(){

		// Prepare sql to delete the photo from the table
		$delPhotos = $this->db->prepare('
			DELETE FROM Photos
			WHERE Photos."photoId" = :photoId
		');

		return $delPhotos->execute([
			"photoId" => $this->photoId
		]);
	}
}
?>