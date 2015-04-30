<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: ranking.php
// Description: Front-page of the website -- displays all of the albums

require_once("class.PicShare.php");

// Anyone can view
$PicShare = new PicShare(0);


// Select Top 10 Users with the highest ranking
// Ranking is determiend by number of photo uploads + number of comments
$getUsers = $PicShare->db->prepare('
	SELECT Users.*, SUM(photos.photos + comments.comments) AS score
	FROM
		Users,
		(
			SELECT	Users."userId", COUNT(Photos."photoId") AS photos
			FROM	Users
			LEFT JOIN 	Albums
			ON 			Users."userId" = Albums."userId"
			LEFT JOIN	Photos
			ON 			Albums."albumId" = Photos."albumId"
			GROUP BY Users."userId"
		) AS photos,
		(
			SELECT		Users."userId", COUNT(Comments."commentId") AS comments
			FROM		Users
			LEFT JOIN	Comments
			ON			Users."userId" = Comments."userId"
			GROUP BY Users."userId"
		) AS comments
	WHERE photos."userId" = comments."userId" AND
			Users."userId" = photos."userId"
	GROUP BY Users."userId"
	ORDER BY score DESC
	LIMIT 10
');

$getUsers->execute();


// Collect HTML
$content = '<table>
	<tr>
		<th>First name</th>
		<th>Last name</th>
		<th>Gender</th>
		<th>Score</th>
	</tr>
';

// Print in table format
while( $user = $getUsers->fetch() ){

	$content .= '<tr>
		<td><a href="/profile.php?id='. $user['userId'] .'">'. $user['firstName'] .'</a></td>
		<td><a href="/profile.php?id='. $user['userId'] .'">'. $user['lastName'] .'</a></td>
		<td>'. $user['gender'] .'</td>
		<td>'. $user['score'] .'</td>
	</tr>';
}

$content .= '</table>';


// Print HTML
ob_start();
?>
<div class="page white">
	<div class="title">Top 10 Contributers</div>
	<?=$content?>
</div>
<?php
	print( $PicShare->createPage(ob_get_clean()) );
?>