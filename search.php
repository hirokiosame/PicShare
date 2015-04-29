<?php

require_once("class.PicShare.php");

$PicShare = new PicShare(0);

if( !isset($_GET['type']) || !isset($_GET['q']) ){ header("Location: /"); }


$content = '';

if( $_GET['type'] === "tag" ){

	$title = 'Photos tagged <u>' . $_GET['q'] . '</u>';

	if( isset($_GET['user']) ){
		$byUser = new Account($_GET['user']);
		$title .= ' by ' . $byUser->getName();
	}
	
	$query = '
		SELECT Photos."photoId", Photos.caption
		FROM Tags, TaggedPhotos, Photos, Albums
		WHERE	Tags.tag = :tag AND
				Tags."tagId" = TaggedPhotos."tagId" AND
				TaggedPhotos."photoId" = Photos."photoId" AND
				Photos."albumId" = Albums."albumId"
	';

	$parameters = [ "tag" => $_GET['q'] ];

	if( isset($_GET['user']) ){
		$query .= ' AND Albums."userId" = :userId';
		$parameters['userId'] = $byUser->userId;
	}

	$getPhotos = $PicShare->db->prepare($query);

	$getPhotos->execute($parameters);

	while( $photo = $getPhotos->fetch() ){

		// print_r($row);
		$content .= Template::view("gridBox", [
			'/photo.php?id=' . $photo['photoId'],
			'/image.php?id=' . $photo['photoId'],
			$photo['caption']
		]);
	}
}elseif( $_GET['type'] === 'user' ){

	$title = 'Users that matched "'. $_GET['q'].'"';

	$getUsers = $PicShare->db->prepare('
		SELECT	*
		FROM	Users
		WHERE 	email ILIKE :q OR
				"firstName" ILIKE :q OR
				"lastName" ILIKE :q
	');

	$getUsers->execute([ "q" => '%'.$_GET['q'].'%' ]);


	$content .= '<table>
		<tr>
			<th>First name</th>
			<th>Last name</th>
			<th>Gender</th>
			<th></th>
		</tr>
	';

	while( $user = $getUsers->fetch() ){

		if( isset($PicShare->account->userId) && $user['userId'] !== $PicShare->account->userId ){
			$addFriend = '<form method="post" action="/profile.php?id='. $user['userId'] .'"><input type="submit" name="addFriend" value="Add friend"></form>';
		}else{
			$addFriend = '';
		}
		$content .= '<tr>
			<td><a href="/profile.php?id='. $user['userId'] .'">'. $user['firstName'] .'</a></td>
			<td><a href="/profile.php?id='. $user['userId'] .'">'. $user['lastName'] .'</a></td>
			<td>'. $user['gender'] .'</td>
			<td>'. $addFriend .'</td>
		</tr>';
	}

	$content .= '</table>';
}


ob_start();
?>
<div class="page white">
	<div class="title"><?=$title?></div>
	<?=$content?>
</div>
<?php
	print( $PicShare->createPage(ob_get_clean()) );
?>