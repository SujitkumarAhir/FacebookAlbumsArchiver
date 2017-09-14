<!DOCTYPE html>
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
require_once __DIR__ . '../../Processes.php';
gc_enable();
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
        $profileRequest = $fb->get('/me?fields=name,first_name,last_name,email,link,gender,locale,picture');
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
    //    echo "<script>alert('".$userData['id']."')</script>";
    // Get logout url
    $logoutURL = $helper->getLogoutUrl($accessToken, $redirectURL . 'logout.php');
} else {
    echo '<script type="text/javascript">
           window.location = "./../index.php"
      </script>';
}
?>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Facebook Album Archiver</title>
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
        <div id="main">
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
                        <a class="navbar-brand " href="#">Facebook Album Archiver</a>
                    </div>
                    <div id="navbar" class="collapse navbar-collapse ">
                        <ul class="nav navbar-nav">              
                            <li><a href="index.php">Home</a></li>
                            <li><a href="Facebook.php">Facebok Profile</a></li>
                            <li class="active"><a href="BackGroundProcesses.php">
                                    Background Processes
                                </a></li>
                            <li><a href="./../logout.php">Logout</a></li>
                        </ul>
                    </div><!--/.nav-collapse -->
                </div>
            </nav>

            <div class="container"><br/><br/><br/><br/>
                <div class="well " style="padding: 15px 0 0px 0;">
                    <div  style="margin-left: 20px;">
                        <?php
                        echo $userData['first_name'] . ' ' .
                        $userData['last_name'] . '\'s Background Processes';
                        ?>
                    </div><br/>
                    <div>
                        <?php
                        $processes = new Processes();
                        $res = $processes->getProcesses($userData["id"]);
                        $Album = new Album();
                        $result = $Album->getAlbums($userData["id"]);
                        ?>

                    </div>
                </div>
                <div ID="gif" style="position: fixed;top: 0;left: 0;width: 100%; height:100%;display: none; z-index:1999;background: url('./../images/loading_1.gif') 50% 50% no-repeat" > </div>
                <script>
                    function loadlink() {
                        $('#main').load('BackGroundProcesses.php', function () {
                            $(this).unwrap();
                        });
                    }

                    loadlink(); // This will run on page load
                    setInterval(function () {
                        loadlink() // this will run after every 5 seconds
                    }, 1000000);
                </script>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Album Name(s)</th>
                            <th>Archived Photos</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>ID</th>
                            <th>Abort</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                        </tr>
                        <?php
                        $i = 1;
                        foreach ($res as $key) {
                            if ($key["status"] == 1)
                                $str = "<td class='bg-success text-success'>Finished</td>";
                            else if ($key["status"] == 2)
                                $str = "<td class='bg-warning text-warning '>Pending</td>";
                            else
                                $str = "<td class='bg-danger text-danger'>Running</td>";
                            if ($key["type"] == 0)
                                $type = "Move";
                            else
                                $type = "<a href='/Albums/photos/" . $key["album_id"] . ".zip'>Download</a>";

                            echo "<tr>";
                            echo "<td>" . $i . "</td><td>" . $key["album_name"] .
                            "</td><td>" . $key["count"] .
                            " / " . $key["total"] . "</td>" .
                            "<td>" . $type . "</td>" . $str;
                            if ($key["status"] == 1)
                                echo "<td>finished</td>";
                            else {
                                ?>
                            <td><?php echo $key["background_process_id"]; ?></td>
                            <td>
                                <form name='form<?php echo $i; ?>' id='form<?php echo $i; ?>'>
                                    <input type='hidden' id='Id' name='Id' value='<?php echo $key["process_id"]; ?>' />
                                    <input type='hidden' id='userId' name='userId' value='<?php echo $userData["id"]; ?>' />
                                    <input type='hidden' id='type' name='type' value='<?php echo $key["type"]; ?>' />
                                    <input type='submit' class='btn btn-danger' value='Abort' />
                                </form>
                                <script>
                                    $(document).ready(function () {
                                        $('#form<?php echo $i; ?>').submit(function () {
                                            document.getElementById("gif").style = "position: fixed;top: 0;left: 0;width: 100%; height:100%;display: block; z-index:1999;background: url('./../images/loading_1.gif') 50% 50% no-repeat";
                                            $.ajax({
                                                type: 'post',
                                                url: 'post_stop_service.php',
                                                data: $('#form<?php echo $i; ?>').serialize(),
                                                success: function (data) {
                                                    document.getElementById("gif").style = "position: fixed;top: 0;left: 0;width: 100%; height:100%;display: none; z-index:1999;background: url('./../images/loading_1.gif') 50% 50% no-repeat";
                                                    alert(data);
                                                }
                                            });
                                            //                                        return false;
                                        });
                                    });
                                </script>
                            </td>
                            <?php
                        }
                        echo "</tr>";
                        $i = $i + 1;
                    }
                    ?>
                    </tbody>
                </table>
            </div>        
        </div>
    </body>
</html>

<?php
gc_disable();
?>