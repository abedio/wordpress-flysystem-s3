<?php

namespace Abedi\WPFlysystemS3;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter as AwsS3PortableVisibilityConverter;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Visibility;

class S3Manager
{
	public static function getInstance(): self
	{
		return self::$instance ?: self::$instance = new self();
	}

	private static ?self $instance = null;

    private ?Filesystem $storage = null;

    public function isReady(): bool
    {
        return defined('S3_KEY') and
            defined('S3_SECRET') and
            defined('S3_BUCKET') and
            defined('S3_ENDPOINT');
    }

	/**
     * Create an instance of the Amazon S3 driver.
     *
     * @param  array  $config
     * @return Filesystem
     */
    public function build(array $config)
    {
        $s3Config = $this->formatS3Config($config);

        $root = (string) ($s3Config['root'] ?? '');

        $visibility = new AwsS3PortableVisibilityConverter(
            $config['visibility'] ?? Visibility::PUBLIC
        );

        $streamReads = $s3Config['stream_reads'] ?? false;

        $client = new S3Client($s3Config);

        $adapter = new AwsS3V3Adapter($client, $s3Config['bucket'], $root, $visibility, null, $config['options'] ?? [], $streamReads);

		return new Filesystem($adapter);
    }

    /**
     * @return Filesystem
     */
    public function getStorage()
    {
        if (!$this->storage) {
            $this->storage = $this->build([
                'key' => S3_KEY,
                'secret' => S3_SECRET,
                'region' => S3_REGION,
                'bucket' => S3_BUCKET,
                'endpoint' => S3_ENDPOINT,
                'root' => defined('S3_ROOT') ? S3_ROOT : '',
            ]);
        }

        return $this->storage;
    }

    public function getUrl(string $path): string
    {
        $endpoint = rtrim(S3_ENDPOINT, '/');
        $url = $endpoint.'/'.S3_BUCKET;
        if (defined('S3_ROOT')) {
            $url .= '/'.S3_ROOT;
        }
        
        return $url.'/'.trim($path, '/').'/';
    }

    /**
     * Format the given S3 configuration with the default options.
     *
     * @param  array  $config
     * @return array
     */
    private function formatS3Config(array $config)
    {
        $config += ['version' => 'latest'];

        if (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = ['key' => $config['key'], 'secret' => $config['secret']];
        }

        if (! empty($config['token'])) {
            $config['credentials']['token'] = $config['token'];
        }

		unset($config['token']);
        return $config;
    }
}
