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
	private S3Manager $s3Manager;

	public function __construct()
	{
		$this->dbManager = DBManager::getInstance();
		$this->s3Manager = S3Manager::getInstance();
	}

	/**
	 * @param array{name:string,tmp_name:string}
	 */
	public function add(string $name, string $localFile, string $localPath): int
	{
		$result = $this->getByLocalFile($localPath);
        if ($result) {
			$this->dbManager->getDB()->query($this->dbManager->getDB()->prepare("UPDATE {$this->dbManager->getTable()} SET `count` = `count` + 1 WHERE `id` = %d", $result->id));

            return $result->id;
        }

		$storage = $this->s3Manager->getStorage();
		
		$dotPosition = strrpos($name, '.');
		$extention = $dotPosition !== false ? substr($name, $dotPosition) : '';
		$name = md5_file($localFile);

		if (!preg_match("/^([0-9a-f]+)(?:\.[a-zA-Z0-9]+)?$/", $name, $matches)) {
            throw new \Exception('your file name is not a valid hash');
        }

		$parts = [];
        for ($x = 0; $x < 2; ++$x) {
            $parts[] = substr($matches[1], $x * 2, 2);
        }

		$stream = fopen($localFile, 'r');

		$storage->writeStream(implode('/', $parts).'/'.$name.$extention, $stream, []);

        if (is_resource($stream)) {
            fclose($stream);
        }

		$result = $this->dbManager->getDB()->insert(
			$this->dbManager->getTable(),
			[
				'md5' => $name,
				'local_file' => $localPath,
				'remote_file' => $this->s3Manager->getUrl(implode('/', $parts).'/'.$name.$extention),
				'count' => 1,
			]
		);
		if (!$result) {
			throw new \Exception('Can not insert new attachment');
		}

		return $this->dbManager->getDB()->insert_id;
	}

	public function delete(string $remoteFile): void
	{
		$result = $this->getByRemoteFile($remoteFile);
        if ($result) {
            if ($result->count > 1) {
				$this->dbManager->getDB()->query($this->dbManager->getDB()->prepare("UPDATE {$this->dbManager->getTable()} SET `count` = `count` - 1 WHERE `id` = %d", $result->id));
            } else {
                $url = parse_url($remoteFile);
                $url['path'] = substr($url['path'], strlen('/public'));

                $storage = $this->s3Manager->getStorage();
				$storage->delete($url['path']);

				$this->dbManager->getDB()->query($this->dbManager->getDB()->prepare("DELETE FROM {$this->dbManager->getTable()} WHERE `id` = %d", $result->id));
            }
        }
	}

	public function getByID(int $id)
	{
		return $this->dbManager->getDB()->get_row(
			$this->dbManager->getDB()->prepare("SELECT * FROM {$this->dbManager->getTable()} WHERE `id` = %d", $id),
			OBJECT
		);
	}

	public function getByLocalFile(string $localFile)
	{
		return $this->dbManager->getDB()->get_row(
			$this->dbManager->getDB()->prepare("SELECT * FROM {$this->dbManager->getTable()} WHERE `local_file` = %s", $localFile),
			OBJECT
		);
	}

	public function getByRemoteFile(string $remoteFile)
	{
		return $this->dbManager->getDB()->get_row(
			$this->dbManager->getDB()->prepare("SELECT * FROM {$this->dbManager->getTable()} WHERE `remote_file` = %s", $remoteFile),
			OBJECT
		);
	}
}
