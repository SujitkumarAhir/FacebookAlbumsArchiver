<?php

/**
 * This file is part of the Symfony2-coding-standard (phpcs standard)
 *
 * PHP version 5.4
 *
 * @category PHP
 * @package  Albums
 * @author   Authors <pateldevik@gmail.com>
 * @license  MIT
 * @link     https://github.com/DevikVekariya/FacebookAlbumsArchiver
 */
require_once __DIR__ . '../../fbConfig.php';
require_once __DIR__ . '../../User.php';
require_once __DIR__ . '../../Album.php';
require_once __DIR__ . '/googleConfig.php';
require_once __DIR__ . '../../Processes.php';

$accessToken = $argv[1];

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
//    print_r($userData);
} else {
    // Get login url
    echo "Please Login";
    die;
}
echo '<br><br>Google : ' . $argv[2];
$_SESSION['access_token']['access_token'] = (string) $argv[2];
$_SESSION['access_token']['token_type'] = (string) $argv[3];
$_SESSION['access_token']['expires_in'] = (int) $argv[4];
$_SESSION['access_token']['created'] = (int) $argv[5];
$albumId = $argv[6];
$albumName = $argv[7];
$offset = $argv[8];
$count = $argv[9];
$pfolderid = $argv[10];
$running = NULL;
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client = getClient();
    $service = new Google_Service_Drive($client);

    if ($offset == 0) {
        $ID = $albumId;
        $i = 0;
        $arr = array();
        $files = "";
        $profileRequest = $fb->get('/' . $ID . '/photos?fields=id,source&limit=100');
        $tmp = $profileRequest->getGraphEdge();
        do {
            $fbPhotosProfile = $tmp->asArray();
            foreach ($fbPhotosProfile as $key1) {
                $arr[$i] = $key1['source'];
                $i = $i + 1;
            }
        } while ($tmp = $fb->next($tmp));
        file_put_contents($ID . '.bin', serialize($arr));
    }


    $i = $offset;
    $flag = 0;
    $limit = $offset + 50;
    if ($count <= $limit) {
        $limit = $count;
        $flag = 1;
    }

    $Process = new Processes();

    $array = unserialize(file_get_contents($albumId . '.bin'));
    for ($k = $i; $k < $limit; $k++) {
        $content = file_get_contents($array[$k]);
        $fileMetadata = new Google_Service_Drive_DriveFile(
                array(
            'parents' => array($pfolderid),
            'name' => $i . '.jpg')
        );
        $file = $service->files->create(
                $fileMetadata, array(
            'data' => $content,
            'mimeType' => 'image/jpeg',
            'uploadType' => 'multipart',
            'fields' => 'id')
        );
        $Process->updateProcess($userData['id'], $albumId, $i, 0, '-1', 0);
        $i = $i + 1;
    }

    if ($flag == 1) {
        $Process->updateProcess($userData['id'], $albumId, $limit, 1, '0', 0);
        unlink($albumId . '.bin');
        die;
    }

    $offset = $i;
    if ($offset < $count) {
        require 'BackgroundProcess.php';
        $proc = new BackgroundProcess();
        $proc->setCmd("php BackgroundProcess_SingleAlbumMove.php " . $_SESSION['facebook_access_token'] . " " . $_SESSION['access_token']['access_token'] . " " . $_SESSION['access_token']['token_type'] . " " . $_SESSION['access_token']['expires_in'] . " " . $_SESSION['access_token']['created'] . " " . $albumId . " '" . $albumName . "' " . $offset . " " . $count . " " . $pfolderid);
        $proc->start();
        $procid = $proc->getProcessId();
    }
    $Process->updateProcess($userData['id'], $albumId, $limit, 0, $procid, 0);





    echo "<h5>Your album is moved to your Google Drive. find directory named "
    . "<b>facebook_" . $userData['first_name'] . "_" .
    $userData['last_name'] . "_albums</b> in root "
    . "directory of your google drive.</h5>";
    die;
} else {
    header('Location: Google.php');
}

?>