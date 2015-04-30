<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: class.Album.php
// Description: Album class -- an abstraction over a row in the Albums table

class Album{

	// Public indicator of whether album was fetched from the database
	public $fetched = false;

	// Constructor -- takes in an album ID
	public function __construct ($albumId){

		// Get database connection
		$this->db = Database::getInstance();

		// Fetch the album by album Id
		$getAlbum = $this->db->prepare('
			SELECT	*
			FROM	Albums
			WHERE	Albums."albumId" = :albumId
		');

		$getAlbum->execute([ "albumId" => $albumId ]);

		// If the album was successfully fetched
		if( $album = $getAlbum->fetch() ){

			// Add every attribute as a property of this instance
			foreach( $album as $key => $val ){
				$this->{$key} = $val;
			}

			// Fetch the owner of the album
			$this->owner = new Account($this->userId);
		}

		// Throw error if album not found
		else{
			die("Invalid album Id");
		}
	}


	// Public function to update album name
	public function updateName($newName){

		// Update query to change the album name
		$stmt = $this->db->prepare('
			UPDATE	Albums
			SET		name = :name
			WHERE	"albumId" = :albumId
		');

		// If successful
		if( $stmt->execute([
			"name" => $newName,
			"albumId" => $this->albumId
		]) ){
			// Change property "name"
			$this->name = $newName;

			// Return successful
			return true;
		}

		return false;
	}


	// Public function to publish the album
	public function publish(){

		// Update query to mark album as published
		$stmt = $this->db->prepare('
			UPDATE	Albums
			SET		published = true
			WHERE	"albumId" = :albumId
		');

		// If successful
		if( $stmt->execute([
			"albumId" => $this->albumId
		]) ){

			// Change property "published"
			$this->published = true;

			// Return successful
			return true;
		}
		return false;
	}


	// Public function to get the photos of an album
	public function getPhotos(){

		// SELECT query to get all photos associated with the album Id
		$getPhotos = $this->db->prepare('
			SELECT	Photos."photoId", Photos."caption"
			FROM	Photos
			WHERE	Photos."albumId" = :albumId
		');

		$getPhotos->execute([
			"albumId" => $this->albumId
		]);

		// Return all photos
		return $getPhotos->fetchAll();
	}


	// Public function to add a photo to the album
	public function addPhoto($file, $caption){


		// Read image
		$img = fopen($file, 'rb') or die("cannot read image\n");

		// Get the image size
		$imgData = getimagesize($file);

		// Insertion query to insert the photo
		$addPhoto = $this->db->prepare('
			INSERT INTO Photos ("albumId", "imgType", "caption", "data")
			VALUES (:albumid, :imgType, :caption, :data)
		');


		// Bind attributes to the prepared query
		$addPhoto->bindParam(":albumid", $this->albumId); // Add album Id
		$addPhoto->bindParam(":imgType", $imgData['mime']); // Add image type
		$addPhoto->bindParam(":caption", $caption); // Add caption to image
		$addPhoto->bindParam(":data", $img, PDO::PARAM_LOB); // Store image as a binary


		// Insert the photo in a transaction since the image could be large
		$this->db->beginTransaction();
		$inserted = $addPhoto->execute();
		$this->db->commit();


		// If the image is successfully inserted
		if( $inserted ){

			// Fetch
			return $this->db->lastInsertID('"photos_photoId_seq"');
		}else{
			return false;
		}
	}

	// Public function to delete the album
	public function delete(){

		// Delete query to delete the row from table
		$delAlbum = $this->db->prepare('
			DELETE FROM Albums
			WHERE Albums."albumId" = :albumId
		');

		return $delAlbum->execute([
			"albumId" => $this->albumId
		]);
	}
}

?>