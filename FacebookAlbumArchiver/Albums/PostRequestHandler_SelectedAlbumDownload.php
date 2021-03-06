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
require_once __DIR__ . '../../libs/google/vendor/autoload.php';
require_once __DIR__ . '../../Processes.php';

define('APPLICATION_NAME', 'rtCampAssignment');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_id.json');


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
if (isset($_POST["NoOfAlbums"]) && isset($_POST["ActionType"]) && $_POST["ActionType"] == "DownloadSelected") {
    $no = $_POST["NoOfAlbums"];
    $str = generateRandomString();
    $zipname = $str . '.zip';
    $albums = "";
    $albumstr = "";
    $ids = "";
    $ids = "";
    $masterCount = 0;
    require 'BackgroundProcess.php';

    $proc = new BackgroundProcess();
    for ($i = 0; $i < $no; $i++) {
        if ($_POST["album" . $i] == "true") {
            $ID = $_POST["albumsDownloadId" . $i];
            $zipname = $ID . '.zip';
            $folderName = $_POST["AlbumNames" . $i];
            $count = $_POST["Count" . $i];
            $masterCount = $masterCount + $count;
            $albums = $albums . '/' . $folderName;
            $albumstr = $albumstr . '"' . $folderName . '" ';
            $ids = $ids . '/' . $ID;
            $offset = 0;
        }
    }

    $Process = new Processes();
    $res = $Process->isRunningProcess($userData['id'], 1, $albumstr);
    $running = $res['count'];
    if ($running > 0) {
        echo "<h5 class='text-danger'><b>Background process already inititated / running</b></h5>";
        die;
    } else {
        $Process->deleteOldProcess($userData['id'], $albumstr, 1);
    }

    $proc->setCmd("php BackgroundQueueProcess_MultipleAlbumDownload.php " . $ids . " '" . $albums . "' " . $offset . " " . $masterCount . " " . $userData['id'] . " " . $str . " " . $_SESSION['facebook_access_token']);
    $proc->start();
    $pid = $proc->getProcessId();

    $res = $Process->getRunningProcesses($userData['id'], 1, 0); //uid,type,status
    $running = $res['count'];
    $status = 3;
    if ($running >= 1) {
        $status = 2;
    }
    $pData = array(
        'user_id' => $userData['id'],
        'album_id' => $str,
        'album_name' => $albumstr,
        'count' => '0',
        'total' => $masterCount,
        'status' => $status,
        'type' => '1',
        'background_process_id' => $pid
    );
    $Process->deleteOldProcess($userData['id'], $albumstr, 1);
    $Process->setProcess($pData);

    echo "<h5>Background process initiated <b>";
    die;
}

/**
 * Function to enter albums zip file name into database
 * 
 * @param Int $length used to pass user informations to function
 *
 * @return Srting
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
?>



