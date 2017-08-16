<?php

/**
 * This file is part of the Symfony2-coding-standard (phpcs standard)
 *
 * PHP version 5.4
 *
 * @category PHP
 * @package  PostRequestHandler_SelectedAlbumMove
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
if (isset($_POST["NoOfAlbums"]) && isset($_POST["ActionType"]) && $_POST["ActionType"] == "MoveSelected") {
    $no = $_POST["NoOfAlbums"];
    if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
        $client = getClient();
        $service = new Google_Service_Drive($client);
        $folderName = "facebook_" . $userData['first_name'] . "_" .
                $userData['last_name'] . "_albums";
        $i = 0;
        $MainFolderId = "";
        $search = "mimeType = 'application/vnd.google-apps.folder'"
                . " AND trashed != true";
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
        for ($i = 0; $i < $no; $i++) {
            if ($_POST["album" . $i] == "true") {
                $folderName = $_POST["AlbumNames" . $i];
                $search = "mimeType = 'application/vnd.google-apps.folder' "
                        . "AND trashed != true";
                $parameters = array("q" => $search);
                $results = $service->files->listFiles($parameters);
                $j = 0;
                $albumFolder = "";
                if (count($results->getFiles()) > 0) {
                    foreach ($results->getFiles() as $file) {
                        if ($folderName == $file->getName()) {
                            $albumFolder = $file->getId();
                            $j = 1;
                            break;
                        }
                    }
                }
                if ($j == 1) {
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
                $ID = $_POST["albumsDownloadId" . $i];


                $arr = array();
                $profileRequest = $fb->get('/' . $ID . '/photos?fields=id,source');
                $tmp = $profileRequest->getGraphEdge();
                $j = 0;
                do {
                    $fbPhotosProfile = $tmp->asArray();
                    foreach ($fbPhotosProfile as $key1) {
                        $arr[$j] = $key1['source'];
                        $j = $j + 1;
                    }
                } while ($tmp = $fb->next($tmp));

                $res = array();
                $res = multiple_threads_request($arr);

                $j = 0;
                foreach ($res as $photo) {
                    $fileMetadata = new Google_Service_Drive_DriveFile(
                            array(
                        'parents' => array($ParentfolderId),
                        'name' => $j . '.jpg')
                    );
                    $file = $service->files->create(
                            $fileMetadata, array(
                        'data' => $photo,
                        'mimeType' => 'image/jpeg',
                        'uploadType' => 'multipart',
                        'fields' => 'id')
                    );

                    $j = $j + 1;
                }
            }
        }
        echo "<h5>Your albums are moved to your Google Drive. "
        . "find directory named <b>facebook_" . $userData['first_name'] .
        "_" . $userData['last_name'] . "_albums</b> in root"
        . " directory of your google drive.</h5>";
        die;
    } else {
        header('Location: Google.php');
    }
}

function multiple_threads_request($nodes) {
    $mh = curl_multi_init();
    $curl_array = array();
    foreach ($nodes as $i => $url) {
        $curl_array[$i] = curl_init($url);
        curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, true);
        curl_multi_add_handle($mh, $curl_array[$i]);
    }
    $running = NULL;
    do {
        curl_multi_exec($mh, $running);
    } while ($running > 0);

    $res = array();
    foreach ($nodes as $i => $url) {
        $res[$url] = curl_multi_getcontent($curl_array[$i]);
    }

    foreach ($nodes as $i => $url) {
        curl_multi_remove_handle($mh, $curl_array[$i]);
    }
    curl_multi_close($mh);
    return $res;
}

?>
