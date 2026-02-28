<?php
define('BASE_DIR', '/home/');
define('PROJECT_DIR', '/srv/project');
define('UPLOAD_QUOTA', 200 * 1024 * 1024);
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024);
define('ADMIN_USERS', ['aditya', 'pitsnas']);

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '55M');

function get_role($username)
{
    return in_array($username, ADMIN_USERS) ? 'admin' : 'collaborator';
}

function get_user_dir($username)
{
    $role = get_role($username);
    if ($role === 'admin') {
        return PROJECT_DIR;
    }
    return BASE_DIR . $username;
}

function dir_size($dir)
{
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
        $size += $file->getSize();
    }
    return $size;
}

function sanitize_filename($name)
{
    $name = basename($name);
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    $name = ltrim($name, '.');
    return $name ?: 'unnamed_file';
}

function is_safe_path($filepath, $basedir)
{
    $real_base = realpath($basedir);
    $real_file = realpath($filepath);
    if ($real_base === false || $real_file === false)
        return false;
    return strpos($real_file, $real_base . DIRECTORY_SEPARATOR) === 0;
}
