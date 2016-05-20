<?php

function detectFileMimeType($filename = '')
{
    $filename = escapeshellcmd($filename);
    $mimeType = mime_content_type($filename);
    return trim($mimeType);
}

$uri = $_SERVER['REQUEST_URI'];
$fname = __DIR__ . $uri;
if (file_exists($fname) && !is_dir($fname)) {
    $mtype = detectFileMimeType($fname);
    if (preg_match('/.([^.]+)$/', $fname, $matches)) {
        switch ($matches[1]) {
            case 'css':
                $mtype = 'text/css';
                break;
            case 'js':
                $mtype = 'text/javascript';
                break;
            case 'jpg':
                $mtype = 'image/jpeg';
                break;
            case 'png':
                $mtype = 'image/png';
                break;
        }
    }
    header('Content-type: ' . $mtype);
    readfile($fname);
} else {
    if (preg_match('%^/v\d\.\d%', $uri)) {
        require_once(__DIR__ . '/api.php');
    } elseif (preg_match('%^/admin%', $uri)) {
        require_once(__DIR__ . '/admin.php');
    } else {
        require_once(__DIR__ . '/index.php');
    }
}
