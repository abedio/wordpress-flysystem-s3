<?php

namespace Abedi\WPFlysystemS3;

class AttachmentObserver
{
	public static function getInstance(): self
	{
		return self::$instance ?: self::$instance = new self();
	}

	private static ?self $instance = null;

	private AttachmentManager $attachmentManager;
	private S3Manager $s3Manager;

	public function __construct()
	{
		$this->attachmentManager = AttachmentManager::getInstance();
		$this->s3Manager = S3Manager::getInstance();
	}

    /**
	 * @param mixed|null $default
	 * @param array{name:string,tmp_name:string}
     */
	public function created($default, array $file, string $new_file): ?bool
	{
		return !!$this->attachmentManager->add($file['name'], $file['tmp_name'], $new_file);
	}

    /**
     * @return array{file:string,url:string,type:string}
     */
	public function uploaded(array $upload, string $context): array
	{
		$result = $this->attachmentManager->getByLocalFile($upload['file']);
		
		return $result ? [
			'file' => $result->remote_file,
			'url'  => $result->remote_file,
			'type' => $upload['type'],
		] : $upload;
	}

	public function visited(string $url): string
	{
		if (!preg_match("/^https?:\\/\\/(?:.+)(https?:\\/\\/s3\\..+blufs\\.ir.+)$/", $url, $matches)) {
            return $url;
        }

        return $matches[1];
	}

	/**
	 * @param stdclass $post Post Object
	 * 
	 * @return null
	 */
	public function deleted(?bool $delete, $post)
	{
		if (!preg_match("/^https?:\\/\\/s3\\..+blufs\\.ir/", $post->guid)) {
            return null;
        }

		$this->attachmentManager->delete($post->guid);

        return null;
	}

}
