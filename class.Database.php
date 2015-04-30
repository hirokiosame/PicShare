<?php

// Author: Hiroki Osame <hirokio@bu.edu>
// Project: PicShare (CS108 Project)
// Date: April 29, 2015
// File: class.Database.php
// Description: Singleton that connects to the database once and returns it to everyone that wants it

class Database {
	static protected $instance;

	/**
	* @var PDO
	*/
	protected $connection;

	protected function __construct($host, $user) {

		// Create pdo instance and assign to $this->pdo - connect to postgresql
		$this->db = new PDO( sprintf('pgsql:host=%s;user=%s', $host, $user), null, null, array(
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		) );
	}

	// Get instance if already connected, if not, connect
	public static function getInstance() {

		// Check whether or not instance exists
		if(!self::$instance) {

			// get the arguments to the constructor from configuration somewhere
			self::$instance = new self("localhost", "Hiroki");
		}

		// If it exists, return it
		return self::$instance;
	}

	// Proxy calls to non-existant methods on this class to PDO instance
	public function __call($method, $args) {
		$callable = array($this->db, $method);

		if(is_callable($callable)) {
			return call_user_func_array($callable, $args);
		}
	}
}
?>