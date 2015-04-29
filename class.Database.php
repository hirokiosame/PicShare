<?php

class Database {
	static protected $instance;

	/**
	* @var PDO
	*/
	protected $connection;

	protected function __construct($host, $user) {
		// create pdo instance and assign to $this->pdo

		$this->db = new PDO( sprintf('pgsql:host=%s;user=%s', $host, $user), null, null, array(
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		) );
	}

	public static function getInstance() {
		if(!self::$instance) {
			// get the arguments to the constructor from configuration somewhere
			self::$instance = new self("localhost", "Hiroki");
		}

		return self::$instance;
	}

	// proxy calls to non-existant methods on this class to PDO instance
	public function __call($method, $args) {
		$callable = array($this->db, $method);

		if(is_callable($callable)) {
			return call_user_func_array($callable, $args);
		}
	}
}
?>