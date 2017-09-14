<?php

/**
 * This file is part of the Symfony2-coding-standard (phpcs standard)
 *
 * PHP version 5.4
 *
 * @category PHP
 * @package  Google
 * @author   Authors <pateldevik@gmail.com>
 * @license  No License
 * @link     https://github.com/DevikVekariya/FacebookAlbumsArchiver
 */
require_once __DIR__ . '../../fbConfig.php';
require_once __DIR__ . '../../User.php';
require_once __DIR__ . '../../Album.php';
require_once __DIR__ . '/googleConfig.php';
require_once __DIR__ . '../../Processes.php';


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
        require 'BackgroundProcess.php';
        $proc = new BackgroundProcess();

        $Process = new Processes();
        for ($i = 0; $i < $no; $i++) {
            if ($_POST["album" . $i] == "true") {
                $folderName = $_POST["AlbumNames" . $i];



                $res = $Process->isRunningProcess($userData['id'], 0, $folderName);
                $running = $res['count'];
                if ($running > 0) {
//    echo "<h5 class='text-danger'><b>Background process already inititated / running</b></h5>";
                    continue;
                } else {
                    $Process->deleteOldProcess($userData['id'], $folderName, 0);
                }

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
                $count = $_POST["Count" . $i];
                $offset = 0;
                $p = 0;

                $proc->setCmd("php BackgroundQueueProcess_MultipleAlbumMove.php " . $_SESSION['facebook_access_token'] . " " . $_SESSION['access_token']['access_token'] . " " . $_SESSION['access_token']['token_type'] . " " . $_SESSION['access_token']['expires_in'] . " " . $_SESSION['access_token']['created'] . " " . $ID . " '" . $folderName . "' " . $offset . " " . $count . " " . $albumFolder);
                $proc->start();
                $pid = $proc->getProcessId();
                $res = $Process->getRunningProcesses($userData['id'], 0, 0); //uid,type,status
                $running = $res['count'];
                $status = 3;
                if ($running >= 1) {
                    $status = 2;
                }


                $pData = array(
                    'user_id' => $userData['id'],
                    'album_id' => $ID,
                    'album_name' => $folderName,
                    'count' => '0',
                    'total' => $count,
                    'status' => $status,
                    'type' => '0',
                    'background_process_id' => $pid
                );
                $Process->setProcess($pData);

            }
        }
        echo "<h5>Background process initiated. Your albums will be moved to your Google Drive. "
        . "find directory named <b>facebook_" . $userData['first_name'] .
        "_" . $userData['last_name'] . "_albums</b> in root"
        . " directory of your google drive.</h5>";
        die;
    } else {
        header('Location: Google.php');
    }
}

?>
