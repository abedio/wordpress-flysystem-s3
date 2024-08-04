<?php

namespace Abedi\WPFlysystemS3;

class ServiceProvider
{
	public static function getInstance(): self
	{
		return self::$instance ?: self::$instance = new self();
	}

	private static ?self $instance = null;

	public function boot()
	{
		$s3Manager = S3Manager::getInstance();
		if (!$s3Manager->isReady()) {
			return;
		}

		$observer = AttachmentObserver::getInstance();

		add_filter( "pre_move_uploaded_file", [$observer, "created"], 10, 3 );
		add_filter( "wp_handle_upload", [$observer, "uploaded"], 10, 3 );
        add_filter( "wp_get_attachment_url", [$observer, "visited"], 10, 3 );
        add_filter( "pre_delete_attachment", [$observer, "deleted"], 10, 3 );
	}
}
