<?php
/**
 * This file is part of the Symfony2-coding-standard (phpcs standard)
 *
 * PHP version 5.4
 *
 * @category PHP
 * @package  FacebookAlbumsArchive
 * @author   Authors <pateldevik@gmail.com>
 * @license  MIT License
 * @link     https://github.com/DevikVekariya/FacebookAlbumsArchiver
 */
require_once 'fbConfig.php';
// Remove access token from session
unset($_SESSION['facebook_access_token']);

// Remove user data from session
unset($_SESSION['userData']);
unset($_SESSION['access_token']);
// Redirect to the homepage
header("Location:index.php");
?>
