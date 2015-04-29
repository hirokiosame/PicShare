<?php

require_once("class.PicShare.php");

$PicShare = new PicShare(-1);

if( validateInputs($_POST, ["email", "password"]) ){

	// Check if login is successful
	if( $PicShare->account->login( $_POST["email"], $_POST["password"] ) ){
		header("Location: /");
	}else{
		print("Failed to login!");
	}
}

ob_start();
?>
<div class="page white">
	<div class="title">Sign in</div>
	<form id="signIn" method="post" action="signin.php">
		<fieldset>
			<input type="text" name="email" placeholder="Email" required>
			<input type="password" name="password" placeholder="Password" required>
			<input type="submit" name="login" value="Sign in">
		</fieldset>
	</form>
</div>
<?php
	print( $PicShare->createPage(ob_get_clean()) );
?>
