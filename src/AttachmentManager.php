<?php

namespace Abedi\WPFlysystemS3;

class AttachmentManager
{
	public static function getInstance(): self
	{
		return self::$instance ?: self::$instance = new self();
	}

	private static ?self $instance = null;

	private DBManager $dbManager;

	public function __construct()
	{
		$this->dbManager = DBManager::getInstance();
	}

	/**
	 * @param array{local_file:string,remote_file:string,md5:string} $data
	 */
	public function add(?int $id = null, array $data = []): int
	{
		if ($id) {
			$this->dbManager->getDB()->query($this->dbManager->getDB()->prepare("UPDATE {$this->dbManager->getTable()} SET `count` = `count` + 1 WHERE `id` = %d", $id));
			
			return $id;
		}

		$result = $this->dbManager->getDB()->insert($this->dbManager->getTable(), $data);
		if (!$result) {
			throw new \Exception('Can not insert new attachment');
		}

		return $this->dbManager->getDB()->insert_id;
	}

	public function delete(int $id, bool $forceDelete = false): void
	{
		if ($forceDelete) {
			$this->dbManager->getDB()->query($this->dbManager->getDB()->prepare("DELETE FROM {$this->dbManager->getTable()} WHERE `id` = %d", $id));
		} else {
			$this->dbManager->getDB()->query($this->dbManager->getDB()->prepare("UPDATE {$this->dbManager->getTable()} SET `count` = `count` - 1 WHERE `id` = %d", $id));
		}
	}

	public function getByLocalFile(string $localFile)
	{
		return $this->dbManager->getDB()->get_row(
			$this->dbManager->getDB()->prepare("SELECT * FROM {$this->dbManager->getTable()} WHERE `local_file` = %s", $localFile),
			OBJECT
		);
	}

	public function getByMD5(string $md5)
	{
		return $this->dbManager->getDB()->get_row(
			$this->dbManager->getDB()->prepare("SELECT * FROM {$this->dbManager->getTable()} WHERE `md5` = %s", $md5),
			OBJECT
		);
	}
}
