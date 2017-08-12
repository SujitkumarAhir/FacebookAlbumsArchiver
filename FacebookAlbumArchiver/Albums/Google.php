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
session_start();
require_once __DIR__ . '../../libs/google/vendor/autoload.php';
define('APPLICATION_NAME', 'rtCampAssignment');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_id.json');
define(
    'SCOPES', implode(
        ' ', array(
        Google_Service_Drive::DRIVE)
    )
);
if (isset($_GET['logout'])) {
    unset($_SESSION['access_token']);
}
$client = new Google_Client();
$client->setApplicationName(APPLICATION_NAME);
$client->setScopes(SCOPES);
$client->setAuthConfig(CLIENT_SECRET_PATH);
$service = new Google_Service_Oauth2($client);
if (isset($_GET['code'])) {
    $client->authenticate($_GET['code']);
    $_SESSION['access_token'] = $client->getAccessToken();
    header('Location: index.php');
    exit;
}
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);
} else {
    $authUrl = $client->createAuthUrl();
}
echo '<div style="margin:20px">';
if (isset($authUrl)) {
    //show login url
    echo '<div align="center">';
    echo '<h3>Please click login button to connect to Google and Google Drive</h3>';
    echo '<a class="login" href="' . $authUrl . 
            '"><img src="./../images/google-login-button.png" /></a>';
    echo '</div>';
} else {
    header('Location: index.php');
    $service = new Google_Service_Drive($client);
    $i = 0;
    $folderName = "Invoices";
    $search = "mimeType = 'application/vnd.google-apps.folder' AND trashed != true";
    $parameters = array("q" => $search);
    $results = $service->files->listFiles($parameters);
    if (count($results->getFiles()) > 0) {
        print "Files:\n";
        foreach ($results->getFiles() as $file) {
            printf("%s (%s)\n", $file->getName(), $file->getId());
            if ($folderName == $file->getName()) {
                $i = 1;
                break;
            }
        }
        if ($i == 0) {
            $fileMetadata = new Google_Service_Drive_DriveFile(
                array(
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder')
            );
            $file = $service->files->create(
                $fileMetadata, array(
                'fields' => 'id')
            );
            printf("Folder ID: %s\n", $file->id);
        }
    }
}
echo '</div>';
?>
