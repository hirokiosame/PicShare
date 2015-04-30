<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: search.php
// Description: Search page -- allows searching by tag for photos or by keyword for users


require_once("class.PicShare.php");

// Page is viewable by anyone
$PicShare = new PicShare(0);


// If no search type is set, or no query, ignore and redirect
if( !isset($_GET['type']) || !isset($_GET['q']) ){ header("Location: /"); }


$content = '';

// If searching by tag, search for photos
if( $_GET['type'] === "tag" ){

	// Set title
	$title = 'Photos tagged <u>' . $_GET['q'] . '</u>';

	// Build select query to fetch all photos with the tag
	$query = '
		SELECT Photos."photoId", Photos.caption
		FROM Tags, TaggedPhotos, Photos, Albums
		WHERE	Tags.tag = :tag AND
				Tags."tagId" = TaggedPhotos."tagId" AND
				TaggedPhotos."photoId" = Photos."photoId" AND
				Photos."albumId" = Albums."albumId"
	';

	$parameters = [ "tag" => $_GET['q'] ];


	// If searching for photos by a specific user
	if( isset($_GET['user']) ){
		$byUser = new Account($_GET['user']);

		// Extend title
		$title .= ' by ' . $byUser->getName();

		// Extend query
		$query .= ' AND Albums."userId" = :userId';

		// Add to parameters
		$parameters['userId'] = $byUser->userId;
	}

	// Prepare & execute query
	$getPhotos = $PicShare->db->prepare($query);
	$getPhotos->execute($parameters);


	// Render each photo with template
	while( $photo = $getPhotos->fetch() ){
		$content .= Template::view("gridBox", [
			'/photo.php?id=' . $photo['photoId'],
			'/image.php?id=' . $photo['photoId'],
			$photo['caption']
		]);
	}
}


// If searching by user, accept keyword
elseif( $_GET['type'] === 'user' ){

	$title = 'Users that matched "'. $_GET['q'].'"';

	// Find users where the keyword match email, firstname, or lastname
	$getUsers = $PicShare->db->prepare('
		SELECT	*
		FROM	Users
		WHERE 	email ILIKE :q OR
				"firstName" ILIKE :q OR
				"lastName" ILIKE :q
	');

	$getUsers->execute([ "q" => '%'.$_GET['q'].'%' ]);


	// Accmulate HTML
	$content .= '<table>
		<tr>
			<th>First name</th>
			<th>Last name</th>
			<th>Gender</th>
			<th></th>
		</tr>
	';

	// For each user found
	while( $user = $getUsers->fetch() ){

		// If logged in and not self, list option to add as friend
		if( isset($PicShare->account->userId) && $user['userId'] !== $PicShare->account->userId ){
			$addFriend = '<form method="post" action="/profile.php?id='. $user['userId'] .'"><input type="submit" name="addFriend" value="Add friend"></form>';
		}else{
			$addFriend = '';
		}

		// Render profile as table-row
		$content .= '<tr>
			<td><a href="/profile.php?id='. $user['userId'] .'">'. $user['firstName'] .'</a></td>
			<td><a href="/profile.php?id='. $user['userId'] .'">'. $user['lastName'] .'</a></td>
			<td>'. $user['gender'] .'</td>
			<td>'. $addFriend .'</td>
		</tr>';
	}

	$content .= '</table>';
}


// Print HTML
ob_start();
?>
<div class="page white">
	<div class="title"><?=$title?></div>
	<?=$content?>
</div>
<?php
	print( $PicShare->createPage(ob_get_clean()) );
?>