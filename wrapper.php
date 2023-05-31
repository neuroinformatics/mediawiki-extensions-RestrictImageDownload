<?php

declare(strict_types=1);

use MediaWiki\Extension\RestrictImageDownload\ImageFile;

define('MW_ENTRY_POINT', 'restrict_image_download');
require dirname(dirname(__DIR__)).'/includes/WebStart.php';

wfImageWrapperMain();

function wfImageWrapperMain()
{
    global $wgServer;
    global $wgUploadDirectory;

    $file_path = realpath($wgUploadDirectory.($_SERVER['PATH_INFO'] ?? ''));
    if (false === $file_path || 0 !== strpos($file_path, $wgUploadDirectory) || !is_file($file_path)) {
        error403();
    }

    $file = new ImageFile($file_path);
    if (!$file->validate()) {
        error403();
    }

    // refere check
    if (0 !== strpos($_SERVER['HTTP_REFERER'] ?? '', $wgServer)) {
        $file->output_protected_image();
    } else {
        $file->output();
    }
}

function error403()
{
    header('HTTP/1.1 403 Forbidden');
    exit;
}
