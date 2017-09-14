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

$Process = new Processes();


$ids = $albumId;
$names = $albumName;

if ($offset == 0) {

    $i = 0;
    $id = explode("/", $ids);
    $name = explode("/", $names);
    $l = count($id);
    $j = 0;
    $arr = array();
    for ($i = 1; $i < $l; $i = $i + 1) {
        $c = 0;
        $profileRequest = $fb->get('/' . $id[$i] . '/photos?fields=id,source&limit=100');
        $tmp = $profileRequest->getGraphEdge();
        do {
            $fbPhotosProfile = $tmp->asArray();
            foreach ($fbPhotosProfile as $key1) {
                $arr[$j] = array();
                $arr[$j]['image_source'] = $key1['source'];
                $arr[$j]['id'] = $c;
                $arr[$j]['album_name'] = $name[$i];
                $j = $j + 1;
                $c = $c + 1;
            }
        } while ($tmp = $fb->next($tmp));
    }
    file_put_contents($fileName . '.bin', serialize($arr));
}

$masterInd = 0;
$i = $offset;
$flag = 0;
$limit = $offset + 250;
if ($count <= $limit) {
    $limit = $count;
    $flag = 1;
}




$array = unserialize(file_get_contents($fileName . '.bin'));
$zipname = $fileName . '.zip';
$zip = new ZipArchive;
$zip->open('photos/' . $zipname, ZipArchive::CREATE);
for ($k = $i; $k < $limit; $k++) {
    $content = file_get_contents($array[$masterInd]['image_source']);
    $zip->addFromString($array[$masterInd]['album_name'] . "/" . $array[$masterInd]['id'] . ".jpg", $content);
    $content = null;
    $Process->updateProcess($userId, $fileName, $k, 0, '-1', 1);
    $masterInd = $masterInd + 1;
}
$zip->close();
if ($flag == 1) {
    $Process->updateProcess($userId, $fileName, $count, 1, '0', 1);
    unlink($fileName . '.bin');
    die;
}
$offset = $limit;

$j = 0;
$arr = array();
for ($i = $masterInd; $i < $count; $i = $i + 1) {
    $arr[$j] = array();
    $arr[$j]['image_source'] = $array[$i]['image_source'];
    $arr[$j]['id'] = $array[$i]['id'];
    $arr[$j]['album_name'] = $array[$i]['album_name'];
    $j = $j + 1;
}
unlink($fileName . '.bin');
file_put_contents($fileName . '.bin', serialize($arr));



if ($offset < $count) {
    $proc->setCmd("php BackgroundProcess_MultipleAlbumDownload.php " . $ids . " '" . $names . "' " . $offset . " " . $count . " " . $userId . " " . $fileName . " " . $_SESSION['facebook_access_token']);
    $proc->start();
    $procid = $proc->getProcessId();
}
$Process->updateProcess($userId, $fileName, $limit, 0, $procid, 1);

?>