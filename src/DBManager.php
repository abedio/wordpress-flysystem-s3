<?php

namespace Abedi\WPFlysystemS3;

class DBManager
{
	public static function getInstance(): self
	{
		return self::$instance ?: self::$instance = new self();
	}

	private static ?self $instance = null;

	private $db = null;

	public function __construct()
	{
	}

	/**
	 * @return stdclass Instance of wp-includes/class-wpdb.php reterned.
	 */
	public function getDB()
	{
		if (!$this->db) {
			global $wpdb;
			$this->db = $wpdb;
		}

		return $this->db;
	}

	public function getTable(): string
	{
		return "{$this->getDB()->base_prefix}fs_s3_files";
	}
}
