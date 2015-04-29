<?php
class Template{

	public static function view($name, $args = []){
		$opened = file_get_contents("views/" . $name . ".html");
		$filled = call_user_func_array("sprintf", array_merge([$opened], $args));
		return $filled;
	}

	public static function ul($lis = []){
		$list = '<ul>';

		foreach( $lis as $key => $val ){
			$list .= '<li><a href="' . $val . '">' . $key . '</a></li>';
		}

		return $list . '</ul>';
	}




}
?>