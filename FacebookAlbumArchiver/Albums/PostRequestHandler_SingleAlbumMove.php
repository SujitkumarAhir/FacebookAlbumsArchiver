<?php

/**
 * This file is part of the Symfony2-coding-standard (phpcs standard)
 *
 * PHP version 5.4
 *
 * @category PHP
 * @package  PostRequestHandler_SingleAlbumMove
 * @author   Authors <pateldevik@gmail.com>
 * @license  MIT License
 * @link     https://github.com/DevikVekariya/FacebookAlbumsArchiver
 */
require_once __DIR__ . '../../fbConfig.php';
require_once __DIR__ . '../../User.php';
require_once __DIR__ . '../../Album.php';
require_once __DIR__ . './googleConfig.php';


if (isset($accessToken)) {
    if (isset($_SESSION['facebook_access_token'])) {
        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    } else {
        // Put short-lived access token in session
        $_SESSION['facebook_access_token'] = (string) $accessToken;

        // OAuth 2.0 client handler helps to manage access tokens
        $oAuth2Client = $fb->getOAuth2Client();

        // Exchanges a short-lived access token for a long-lived one
        $longLivedAccessToken = $oAuth2Client->
                getLongLivedAccessToken($_SESSION['facebook_access_token']);
        $_SESSION['facebook_access_token'] = (string) $longLivedAccessToken;

        // Set default access token to be used in script
        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    }

    if (isset($_GET['code'])) {
        header('Location: ./');
    }

    try {
        $fields = 'name,first_name,last_name,email,link,gender,locale,picture';
        $profileRequest = $fb->get('/me?fields=' . $fields);
        $fbUserProfile = $profileRequest->getGraphNode()->asArray();
    } catch (FacebookResponseException $e) {
        echo 'Graph returned an error: ' . $e->getMessage();
        session_destroy();
        header("Location: ./");
        exit;
    } catch (FacebookSDKException $e) {
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }
    $user = new User();
    $fbUserData = array(
        'oauth_provider' => 'facebook',
        'oauth_uid' => $fbUserProfile['id'],
        'first_name' => $fbUserProfile['first_name'],
        'last_name' => $fbUserProfile['last_name'],
        'email' => $fbUserProfile['email'],
        'gender' => $fbUserProfile['gender'],
        'locale' => $fbUserProfile['locale'],
        'picture' => $fbUserProfile['picture']['url'],
        'link' => $fbUserProfile['link']
    );
    $userData = $user->checkUser($fbUserData);
    $_SESSION['userData'] = $userData;
} else {
    // Get login url
    echo '<script type="text/javascript">
           window.location = "./../index.php"
      </script>';
}

if (isset($_POST["moveId"]) && isset($_POST["AlbumName"])) {
    if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
        $client = getClient();

        $service = new Google_Service_Drive($client);
        $folderName = "facebook_" . $userData['first_name'] . "_" . 
                $userData['last_name'] . "_albums";
        $i = 0;
        $MainFolderId = "";
        $search = "mimeType = 'application/vnd.google-apps.folder' "
                . "AND trashed != true";
        $parameters = array("q" => $search);
        $results = $service->files->listFiles($parameters);
        if (count($results->getFiles()) > 0) {
            foreach ($results->getFiles() as $file) {
                if ($folderName == $file->getName()) {
                    $MainFolderId = $file->getId();
                    $i = 1;
                    break;
                }
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
            $MainFolderId = $file->id;
        }
        $folderName = $_POST["AlbumName"];
        $search = "mimeType = 'application/vnd.google-apps.folder' "
                . "AND trashed != true";
        $parameters = array("q" => $search);
        $results = $service->files->listFiles($parameters);
        $i = 0;
        $albumFolder = "";
        if (count($results->getFiles()) > 0) {
            foreach ($results->getFiles() as $file) {
                if ($folderName == $file->getName()) {
                    $albumFolder = $file->getId();
                    $i = 1;
                    break;
                }
            }
        }
        if ($i == 1) {
            $service->files->delete($albumFolder);
        }
        $ParentfolderId = $MainFolderId;
        $fileMetadata = new Google_Service_Drive_DriveFile(
            array(
            'parents' => array($ParentfolderId),
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder')
        );
        $file = $service->files->create(
            $fileMetadata, array(
            'fields' => 'id')
        );
        $albumFolder = $file->id;
        $ParentfolderId = $albumFolder;
        $ID = $_POST["moveId"];
        $profileRequest = $fb->get('/' . $ID . '?fields=photos.limit(1000){id}');
        $fbPhotosProfile = $profileRequest->getGraphNode()->asArray();
        $i = 0;
        foreach ($fbPhotosProfile['photos'] as $key1) {
            $fields='?fields=images.limit(1000)';
            $photo_request = $fb->get('/' . $key1['id'] . $fields);
            $photo = $photo_request->getGraphNode()->asArray();
            if (isset($photo['images'][2]['source'])) {
                $content = file_get_contents($photo['images'][2]['source']);
            } else if (isset($photo['images'][1]['source'])) {
                $content = file_get_contents($photo['images'][1]['source']);
            } else {
                $content = file_get_contents($photo['images'][0]['source']);
            }
            $fileMetadata = new Google_Service_Drive_DriveFile(
                array(
                'parents' => array($ParentfolderId),
                'name' => $i . '.jpg')
            );
            $file = $service->files->create(
                $fileMetadata, array(
                'data' => $content,
                'mimeType' => 'image/jpeg',
                'uploadType' => 'multipart',
                'fields' => 'id')
            );
            $i = $i + 1;
        }
        echo "<h5>Your album is moved to your Google Drive. find directory named "
        . "<b>facebook_" . $userData['first_name'] . "_" . 
                $userData['last_name'] . "_albums</b> in root "
                . "directory of your google drive.</h5>";
        die;
    } else {
        header('Location: Google.php');
    }
}
?>
