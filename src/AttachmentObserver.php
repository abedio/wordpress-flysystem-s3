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
     * @return array{file:string,url:string,type:string}
     */
	public function created($default, array $file, string $new_file): ?bool
	{
		$result = $this->attachmentManager->getByLocalFile($new_file);
        if ($result) {
			$this->attachmentManager->add($result->id);

            return true;
        }

		$storage = $this->s3Manager->getStorage();

		$dotPosition = strrpos($file['name'], '.');
		$extention = $dotPosition !== false ? substr($file['name'], $dotPosition) : '';
		$name = md5_file($file['tmp_name']);

		if (!preg_match("/^([0-9a-f]+)(?:\.[a-zA-Z0-9]+)?$/", $name, $matches)) {
            throw new \Exception('your file name is not a valid hash');
        }

		$parts = [];
        for ($x = 0; $x < 2; ++$x) {
            $parts[] = substr($matches[1], $x * 2, 2);
        }

		$stream = fopen($file['tmp_name'], 'r');

		$storage->writeStream(implode('/', $parts).'/'.$name.$extention, $stream, []);

        if (is_resource($stream)) {
            fclose($stream);
        }



		$this->attachmentManager->add(null, [
			'md5' => $name,
			'local_file' => $new_file,
			'remote_file' => $this->s3Manager->getUrl(implode('/', $parts).'/'.$name.$extention),
			'count' => 1,
		]);

		return true;
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

        $name = basename($post->guid);
        $dotPosition = strpos($name, '.');
        $name = $dotPosition !== false ? substr($name, 0, $dotPosition) : $name;

		$result = $this->attachmentManager->getByMD5($name);

        if ($result) {
            if ($result->count > 1) {
				$this->attachmentManager->delete($result->id);
            } else {
                $url = parse_url($post->guid);
                $url['path'] = substr($url['path'], strlen('/public'));

                $storage = $this->s3Manager->getStorage();
    
                $storage->delete($url['path']);

				$this->attachmentManager->delete($result->id, true);
            }
            
        }

        return null;
	}

}
