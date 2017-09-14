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

$accessToken = $argv[7];

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

$albumId = $argv[1];
$albumName = $argv[2];
$offset = $argv[3];
$count = $argv[4];
$userId = $argv[5];
$fileName = $argv[6];
$running = NULL;
require 'BackgroundProcess.php';
$proc = new BackgroundProcess();

$ids = $albumId;
$names = $albumName;

$Process = new Processes();

$res = $Process->getRunningProcesses($userId, 1, 0); //uid,type,status
$running = $res['count'];


if ($running > 0) {
//wait

    for ($i = 0; $i < 60; $i++) {
        sleep(10);
        $res = $Process->getRunningProcesses($userId, 1, 0); //uid,type,status
        $running = $res['count'];
        if ($running == 0) {
            $proc->setCmd("php BackgroundProcess_MultipleAlbumDownload.php " . $ids . " '" . $names . "' " . $offset . " " . $count . " " . $userId . " " . $fileName . " " . $_SESSION['facebook_access_token']);
            $proc->start();
            $procid = $proc->getProcessId();
            $Process->updateProcess($userId, $fileName, 0, 0, $procid, 1);
            die;
        }
    }
    $proc->setCmd("php BackgroundQueueProcess_MultipleAlbumDownload.php " . $ids . " '" . $names . "' " . $offset . " " . $count . " " . $userId . " " . $fileName . " " . $_SESSION['facebook_access_token']);
    $proc->start();
    $procid = $proc->getProcessId();
    $Process->updateProcess($userId, $fileName, 0, 2, $procid, 1);
} else {
//run
    $proc->setCmd("php BackgroundProcess_MultipleAlbumDownload.php " . $ids . " '" . $names . "' " . $offset . " " . $count . " " . $userId . " " . $fileName . " " . $_SESSION['facebook_access_token']);
    $proc->start();
    $procid = $proc->getProcessId();
    $Process->updateProcess($userId, $fileName, 0, 0, $procid, 1);
}
?>