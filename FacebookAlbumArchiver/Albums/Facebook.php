<!DOCTYPE html>
<?php
/**
 * This file is part of the Symfony2-coding-standard (phpcs standard)
 *
 * PHP version 5.4
 *
 * @category PHP
 * @package  Facebook
 * @author   Authors <pateldevik@gmail.com>
 * @license  MIT License
 * @link     https://github.com/DevikVekariya/FacebookAlbumsArchiver
 */
require_once __DIR__.'../../fbConfig.php';
require_once __DIR__.'../../User.php';
require_once __DIR__.'../../Album.php';

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

    // Getting user facebook profile info
    try {
        $fields = 'name,first_name,last_name,email,link,gender,locale,picture';
        $profileRequest = $fb->get('/me?fields='.$fields);
        $fbUserProfile = $profileRequest->getGraphNode()->asArray();
    } catch (FacebookResponseException $e) {
        echo 'Graph returned an error: ' . $e->getMessage();
        session_destroy();
        // Redirect user back to app login page
        header("Location: ./");
        exit;
    } catch (FacebookSDKException $e) {
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }
    // Initialize User class
    $user = new User();
    // Insert or update user data to the database
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
    // Put user data into session

    $_SESSION['userData'] = $userData;
} else {
    echo '<script type="text/javascript">
           window.location = "./../index.php"
      </script>';
}
?>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Facebok Album Archiver</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="./../bootstrap-3.3.7/css/bootstrap.min.css"/>
        <script type="text/javascript" src="./../libs/js/jquery-3.2.1.min.js">
        </script>
        <script src="./../bootstrap-3.3.7/js/bootstrap.min.js"></script>
        <link rel="stylesheet" href="./../libs/SliderTheme/responsiveslides.css">
        <link rel="stylesheet" href="./../libs/SliderTheme/themes.css">
        <script src="./../libs/SliderTheme/responsiveslides.min.js"></script>
        <script>
        </script>

    </head>
    <body>
        <nav class="navbar navbar-inverse navbar-fixed-top">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" 
                            data-toggle="collapse" data-target="#navbar" 
                            aria-expanded="false" aria-controls="navbar">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                <div id="navbar" class="collapse navbar-collapse ">
                    <ul class="nav navbar-nav">              
                        <li><a href="index.php">Home</a></li>
                        <li class="active"><a href="Facebook.php">
                                Facebok Profile
                        </a></li>
                        <li><a href="ArchivedImages.php">Archived Albums</a></li>
                        <li><a href="./../logout.php">Logout</a></li>
                    </ul>
                </div><!--/.nav-collapse -->
            </div>
        </nav>

        <div class="container"><br/><br/><br/><br/>
            <div class="well " style="padding: 15px 0 0px 0;">
                <div  style="margin-left: 20px;">
                    <?php echo $userData['first_name'] . ' ' . 
                            $userData['last_name'] . '\'s Facebook Profile'; ?>
                </div><br/>
                <div class="bg-info">
                    <div class="col-lg-12 col-sm-12 col-xs-12 bg-info">
                        <div class="col-lg-6 col-sm-8 col-xs-12">
                            <br/>
                            <div class="col-lg-2 col-sm-3 col-xs-5">
                                <img src="<?php echo $userData['picture']; ?>" 
                                     class="img-circle img-responsive">
                            </div>
                            <div class="col-lg-4 col-sm-5 col-xs-7 text-capitalize 
                                 text-primary text-left" 
                                 style="font-size:19px;margin-top:10px;">
                                <b>
                                    <?php echo $userData['first_name'] . ' ' . 
                                            $userData['last_name']; ?>
                                </b>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12 col-sm-12 col-xs-12 bg-info">
                        <div class="col-lg-6 col-sm-8 col-xs-12">
                            <div class="col-lg-2 col-sm-3 col-xs-5"><br/>
                                <b>Profile:</b>
                            </div>
                            <div class="col-lg-4 col-sm-5 col-xs-7"><br/>
                                <a href="<?php echo $userData['link']; ?>" class="">
                                    Facebook Link
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12 col-sm-12 col-xs-12 bg-info">
                        <div class="col-lg-6 col-sm-8 col-xs-12">
                            <div class="col-lg-2 col-sm-3 col-xs-5">
                                <b>FB ID:</b>
                            </div>
                            <div class="col-lg-4 col-sm-5 col-xs-7">
                                <?php echo $userData['oauth_uid']; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12 col-sm-12 col-xs-12 bg-info">
                        <div class="col-lg-6 col-sm-8 col-xs-12">
                            <div class="col-lg-2 col-sm-3 col-xs-5">
                                <b>Email:</b>
                            </div>
                            <div class="col-lg-4 col-sm-5 col-xs-7">
                                <?php echo $userData['email']; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12 col-sm-12 col-xs-12 bg-info">
                    <div class="col-lg-6 col-sm-8 col-xs-12">
                        <div class="col-lg-2 col-sm-3 col-xs-5">
                            <b>Gender:</b>
                        </div>
                        <div class="col-lg-4 col-sm-5 col-xs-7">
                            <?php echo $userData['gender']; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12 col-sm-12 col-xs-12 bg-info">
                    <div class="col-lg-6 col-sm-8 col-xs-12">
                        <div class="col-lg-2 col-sm-3 col-xs-5">
                            <b>Locale:</b>
                        </div>
                        <div class="col-lg-4 col-sm-5 col-xs-7">
                            <?php echo $userData['locale']; ?>
                        </div>
                    </div>
                    <br/><br/>
                </div>
            </div>
        </div>
    </body>
</html>
