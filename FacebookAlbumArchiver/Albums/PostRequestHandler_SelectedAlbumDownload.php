<?php

/**
 * This file is part of the Symfony2-coding-standard (phpcs standard)
 *
 * PHP version 5.4
 *
 * @category PHP
 * @package  PostRequestHandler_SelectedAlbumDownload
 * @author   Authors <pateldevik@gmail.com>
 * @license  MIT License
 * @link     https://github.com/DevikVekariya/FacebookAlbumsArchiver
 */
require_once __DIR__ . '../../fbConfig.php';
require_once __DIR__ . '../../User.php';
require_once __DIR__ . '../../Album.php';
require_once __DIR__ . '../../libs/google/vendor/autoload.php';
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
    $zipname = generateRandomString() . '.zip';
    $zip = new ZipArchive;
    $zip->open('photos/' . $zipname, ZipArchive::CREATE);
    for ($i = 0; $i < $no; $i++) {
        if ($_POST["album" . $i] == "true") {
            $ID = $_POST["albumsDownloadId" . $i];
            $j = 0;
            $arr = array();
            $profileRequest = $fb->get('/' . $ID . '/photos?fields=id,source');
            $tmp = $profileRequest->getGraphEdge();
            do {
                $fbPhotosProfile = $tmp->asArray();
                foreach ($fbPhotosProfile as $key1) {
                    $arr[$j] = $key1['source'];
                    $j = $j + 1;
                }
            } while ($tmp = $fb->next($tmp));
            $res = array();
            $res = multiple_threads_request($arr);
            $profileRequest = $fb->get('/' . $ID . '?fields=photos.limit(1000){id}');
            $fbPhotosProfile = $profileRequest->getGraphNode()->asArray();
            $j = 0;
            foreach ($res as $photo) {
                $zip->addFromString($_POST["AlbumNames" . $i] . '/' . $j . ".jpg", $photo);
                $j = $j + 1;
            }
        }
    }
    $zip->close();
    $Album = new Album();
    $fbZipData = array(
        'user_id' => $userData['id'],
        'zip_name' => $zipname
    );
    $Album->setAlbum($fbZipData);
    echo "<h5>You can download a zip fIle from <b>"
    . "<a href='./photos/" . $zipname . "'>here</a></b>.</h5>";
    die;
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



