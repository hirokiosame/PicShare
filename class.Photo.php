<?php

class Photo{

	public $fetched = false;

	public function __construct ($photoId){

		$this->db = Database::getInstance();


		$getPhoto = $this->db->prepare('
			SELECT	"photoId", "albumId", "imgType", "caption"
			FROM	Photos
			WHERE	Photos."photoId" = :photoId
		');

		$getPhoto->execute([ "photoId" => $photoId ]);

		$photo = $getPhoto->fetch();


		foreach( $photo as $key => $val ){
			$this->{$key} = $val;
		}

		$this->owner = $this->getOwner();
	}

	private function getOwner(){

		// Check if liked
		$getOwner = $this->db->prepare('
			SELECT Users."userId"
			FROM	Photos, Albums, Users
			WHERE	Photos."photoId" = :photoId AND
					Photos."albumId" = Albums."albumId" AND
					Albums."userId" = Users."userId"
		');

		$getOwner->execute([
			"photoId" => $this->photoId
		]);

		$owner = $getOwner->fetch();
		return new Account($owner['userId']);
	}

	public function updateCaption($newCaption){

		// set new creationDate
		$stmt = $this->db->prepare('
			UPDATE	Photos
			SET		caption = :caption
			WHERE	"photoId" = :photoId
		');

		if( $stmt->execute([
			"caption" => $newCaption,
			"photoId" => $this->photoId
		]) ){
			$this->caption = $newCaption;
			return true;
		}
	}

	// Tags
	public function getTags(){

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
		while( $tag = $getTags->fetch() ){
			$tags[] = '<a href="/search.php?type=tag&q='. $tag['tag'] .'">'. $tag['tag'] .'</a>';
		}

		return $tags;
	}

	public function addTag($tag){

		$getTag = $this->db->prepare('
			SELECT	Tags."tagId"
			FROM	Tags
			WHERE	Tags.tag = :tag
		');

		$getTag->execute([ "tag" => $tag ]);

		$tagId = $getTag->fetch();

		if($tagId){
			$tagId = $tagId['tagId'];
		}else{
			$insTag = $this->db->prepare("INSERT INTO Tags (tag) VALUES (:tag)");
			$insTag->execute([ "tag" => $tag ]);

			$tagId = $this->db->lastInsertID('"tags_tagId_seq"');
		}

		$insTag = $this->db->prepare('
			INSERT INTO TaggedPhotos ("tagId", "photoId")
			VALUES (:tagId, :photoId)
		');

		return $insTag->execute([ "tagId" => $tagId, "photoId" => $this->photoId ]);
	}


	// Likes
	public function likedBy($userId){

		// Check if liked
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

		return $checkLiked->rowCount();
	}

	public function getLikes(){

		$stmt = $this->db->prepare('
			SELECT	Users."userId", Users."firstName", Users."lastName"
			FROM	Likes, Users
			WHERE	Likes."photoId" = :photoId AND
					Likes."userId" = Users."userId"
		');

		$stmt->execute([ "photoId" => $this->photoId ]);

		$likedThis = array();
		while( $likes = $stmt->fetch() ){
			$likedThis[] = '<a href="/profile.php?id='. $likes['userId'] .'">'. $likes['firstName'] .' '. $likes['lastName'] .'</a>';
		}
		return $likedThis;
	}

	public function likePhoto($userId){

		$checkLiked = $this->likedBy($userId);

		if( $checkLiked ){
			$likeStmt = $this->db->prepare('
				DELETE FROM Likes
				WHERE	"userId" = :userId AND
						"photoId" = :photoId
			');
		}else{
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


	// Comments
	public function getComments(){

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
		while( $comment = $stmt->fetch() ){
			@$date = date("g:i:s A m/d, Y", strtotime( $comment['commentDate'] ) );

			$comments .= Template::view("comment", [
				$comment['userId'],
				isset($comment['userId']) ? ($comment['firstName'] . " " . $comment['lastName']) : "Anonymous",
				$date,
				$comment['comment']
			]);
		}

		return $comments;
	}

	public function commentPhoto($userId, $comment){

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

	public function deletephoto(){

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