<!DOCTYPE html>
<?php
/**
 * This file is part of the Symfony2-coding-standard (phpcs standard)
 *
 * PHP version 5.4
 *
 * @category PHP
 * @package  Master
 * @author   Authors <pateldevik@gmail.com>
 * @license  MIT License
 * @link     https://github.com/DevikVekariya/FacebookAlbumsArchiver
 */
require_once __DIR__.'/fbConfig.php';
require_once __DIR__.'/User.php';
if (isset($accessToken)) {
    if (isset($_SESSION['facebook_access_token'])) {
        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    } else {
        /* Put short-lived access token in session */
        $_SESSION['facebook_access_token'] = (string) $accessToken;
        /* OAuth 2.0 client handler helps to manage access tokens */
        $oAuth2Client = $fb->getOAuth2Client();
        /* Exchanges a short-lived access token for a long-lived one */
        $longLivedAccessToken = $oAuth2Client->
                getLongLivedAccessToken($_SESSION['facebook_access_token']);
        $_SESSION['facebook_access_token'] = (string) $longLivedAccessToken;
        /* Set default access token to be used in script */
        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    }
    /* Redirect the user back to the same page if url has "code" 
     * parameter in query string */
    if (isset($_GET['code'])) {
        header('Location: ./');
    }
    /* Put user data into session */
    isset($_SESSION['userData']) ? $_SESSION['userData'] = $userData : '';
    /* Get logout url */
    $logoutURL = $helper->getLogoutUrl($accessToken, $redirectURL . 'logout.php');
    echo '<script type="text/javascript">
           window.location = "./Albums/index.php"
      </script>';
} else {
    /* Get login url */
    $loginURL = $helper->getLoginUrl($redirectURL, $fbPermissions);
    /* Render facebook login button */
    $output = '<a href="' . htmlspecialchars($loginURL) . '">Login</a>';
}
?>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Facebok Album Archiver</title>
        <link rel="stylesheet" href="bootstrap-3.3.7/css/bootstrap.min.css"/>
        <link href="bootstrap-3.3.7/css/creative.min.css" rel="stylesheet">
    </head>
<body id="page-top">

    <nav id="mainNav" class="navbar navbar-default navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand page-scroll" href="#page-top">Facebook Archiver</a>
            </div>
        </div>
    </nav>

    <header>
        <div class="header-content">
            <div class="header-content-inner">
                <h1 id="homeHeading">Archive Facebook albums</h1>
                <hr>
                <p>Login to archive your facebook photo albums 
                    to your local drive or your google drive.</p>
                <a href="<?php echo htmlspecialchars($loginURL); ?>">
                    <button class="btn btn-primary">  Login with facebook</button>
                </a>
            </div>
        </div>
    </header>
    </body>
</html>
