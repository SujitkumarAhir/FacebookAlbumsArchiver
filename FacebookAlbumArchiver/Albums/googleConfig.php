<?php

/**
 * This file is part of the Symfony2-coding-standard (phpcs standard)
 *
 * PHP version 5.4
 *
 * @category PHP
 * @package  Google
 * @author   Authors <pateldevik@gmail.com>
 * @license  MIT License
 * @link     https://github.com/DevikVekariya/FacebookAlbumsArchiver
 */
require_once __DIR__ . '../../libs/google/vendor/autoload.php';
define('APPLICATION_NAME', 'rtCampAssignment');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_id.json');
define(
    'SCOPES', implode(
        ' ', array(
        Google_Service_Drive::DRIVE)
    )
);

/**
 * Function to get client details
 * 
 * @return Array
 */
function getClient() 
{
    $client = new Google_Client();
    $client->setApplicationName(APPLICATION_NAME);
    $client->setScopes(SCOPES);
    $client->setAuthConfig(CLIENT_SECRET_PATH);
    $client->setAccessType('offline');
    if (isset($_SESSION["access_token"])) {
        $accessToken = $_SESSION["access_token"];
    }
    $client->setAccessToken($accessToken);
    if ($client->isAccessTokenExpired()) {
        unset($_SESSION['access_token']);
        header('Location: Google.php');
    }
    return $client;
}

?>