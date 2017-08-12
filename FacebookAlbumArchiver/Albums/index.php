<!DOCTYPE html>
<?php
/**
 * This file is part of the Symfony2-coding-standard (phpcs standard)
 *
 * PHP version 5.4
 *
 * @category PHP
 * @package  Google
 * @author   Authors <pateldevik@gmail.com>
 * @license  MIT License
 * @link     https://github.com/DevikVekariya/FacebookAlbumsArchiver
 */
require_once __DIR__ . '../../fbConfig.php';
require_once __DIR__ . '../../User.php';
require_once __DIR__ . '../../Album.php';
require_once __DIR__ . '../../libs/google/vendor/autoload.php';


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
        <style>
            div.horizontal {
                display: flex;
                justify-content: center;
            }

            div.vertical {
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .frame {
                white-space: nowrap;
                text-align: center; margin: 1em 0;
            }

            .helper {
                display: inline-block;
                height: 100%;
                vertical-align: middle;
            }

        </style>
        <script>
            $(function () {
                $("#slider3").responsiveSlides({
                    auto: true,
                    pager: true,
                    nav: true,
                    speed: 600,
                    namespace: "large-btns"
                });

            });
            $(document).ready(function () {
                $('#close').click(function () {
                    document.getElementById("summary").style = 'display:none;\n\
                    position: absolute; top:0px;left:0px;z-index: 1999;\n\
                    height:100%;width:100%;';
                    document.getElementById("backblack").style = 'display:none;\n\
                    position: absolute; top:0px;left:0px;z-index: 1980;\n\
                    height:100%;width:100%;background: black;opacity: 0.7;';
                });
            });
        </script>

    </head>
    <body>
        <?php
        if (isset($_POST["id"])) {
            $ID = $_POST["id"];
            $profileRequest = $fb->get('/' . $ID . '?fields=photos.limit(1000){id}');
            $fbPhotosProfile = $profileRequest->getGraphNode()->asArray();
            echo "<div class='col-lg-1 col-lg-offset-11 col-sm-1 col-sm-offset-11 col-xs-2 col-xs-offset-10'><a href='#' id='close' class='right' style='color: white;'><i class='glyphicon glyphicon-remove-sign'></i></a></div>";
            echo "<div class='col-lg-12'><div class='rslides_container'>";
            echo "<ul class='rslides' id='slider3' style='display:table;'>";
            $zipname = 'file.zip';
            foreach ($fbPhotosProfile['photos'] as $key1) {
                $photo_request = $fb->get('/' . $key1['id'] . '?fields=images');
                $photo = $photo_request->getGraphNode()->asArray();
                echo "<li style='height:100%;'><div class='horizontal frame'><div class='vertical helper'><img class='img-responsive' src='" . $photo['images'][0]['source'] . "' style='max-height: 570px;width:auto;'  alt=''/></div></div></li>";
            }
            echo "</ul></div></div>";
            die;
        }
        ?>        
        <nav class="navbar navbar-inverse navbar-fixed-top">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                <div id="navbar" class="collapse navbar-collapse ">
                    <ul class="nav navbar-nav">              
                        <li class="active"><a href="index.php">Home</a></li>
                        <li><a href="Facebook.php">Facebok Profile</a></li>
                        <li><a href="ArchivedImages.php">Archived Albums</a></li>
                        <li><a href="./../logout.php">Logout</a></li>
                    </ul>
                </div><!--/.nav-collapse -->
            </div>
        </nav>
        <div ID="gif" style="position: fixed;top: 0;left: 0;width: 100%; height:100%;display: none; z-index:1999;background: url('./../images/loading_1.gif') 50% 50% no-repeat" > </div>

        <div class="container"><br/><br/><br/><br/>
            <div id="FilePath" class="alert-success"></div>
            <div class="well " style="padding: 15px 0 0px 0;">
                <div  style="margin-left: 20px;"><?php echo $userData['first_name'] . ' ' . $userData['last_name'] . '\'s albums'; ?></div><br/>
                <div>
                    <div class="row" style="margin: 0px 10px 0px 10px;">
                        <?php
                        $array = array();
                        $albumInd = 0;
                        $profileRequest = $fb->get('/me/albums?fields=picture{url},name');
                        $fbAlbumProfile = $profileRequest->getGraphEdge()->asArray();
                        foreach ($fbAlbumProfile as $key) {
                            $array[$albumInd] = $key['picture']['url'];
                            ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12 thumb">
                                <a class="thumbnail" style="padding: 0px 0px 0px 0px;">
                                    <div class="img-responsive" style="height:180px;background-size: 100%; background: url('<?php echo $key['picture']['url']; ?>')center no-repeat;">
                                    </div> 
                                    <div class="bg-info" style="height: 30px;padding-top: 2px;">
                                        <div class="col-xs-1">
                                            <input type="checkbox" class="checkbox-inline checkbox" name="album<?php echo $albumInd; ?>" id="album<?php echo $albumInd; ?>"/>
                                        </div>
                                        <div class="col-xs-7">
                                            <form method="post" name="form<?php echo $albumInd; ?>" id="form<?php echo $albumInd; ?>">
                                                <input type="hidden" name="id" value="<?php echo $key['id']; ?>"/>
                                                <input class="col-xs-12" type="submit" name="form<?php echo $albumInd; ?>" value="<?php echo $key['name']; ?>" style="background: transparent;border: none;margin-top: 3px;" title="<?php echo $key['name']; ?>"/>
                                            </form>
                                        </div>
                                        <div class="col-xs-1" style="padding-top: 3px;">
                                            <form method="post" name="form_download<?php echo $albumInd; ?>" id="form_download<?php echo $albumInd; ?>">
                                                <input type="hidden" name="downloadId" id="downloadId" value="<?php echo $key['id']; ?>"/>
                                                <button type="submit" name="form_download<?php echo $albumInd; ?>" value="" style="background: transparent;border: none;margin-top: 3px;" title=""><i class='glyphicon glyphicon-download-alt'></i></button>
                                            </form>
                                        </div>
                                        <div class="col-xs-1" style="padding-top: 1px;">
                                            <form method="post" name="form_move<?php echo $albumInd; ?>" id="form_move<?php echo $albumInd; ?>">
                                                <input type="hidden" name="moveId" value="<?php echo $key['id']; ?>"/>
                                                <input type="hidden" name="AlbumName" value="<?php echo $key['name']; ?>"/>
                                                <button class="" type="submit" name="form_move<?php echo $albumInd; ?>" value="" style="background: transparent;border: none;margin-top: 3px;" title=""><img src="../images/GoogleDrive.ico" height="20px" width="20px"></img></button>
                                            </form>
                                        </div>
                                        <input type="hidden" name="albumsDownloadId<?php echo $albumInd; ?>" id="albumsDownloadId<?php echo $albumInd; ?>" value="<?php echo $key['id']; ?>"/>
                                        <input type="hidden" name="AlbumNames<?php echo $albumInd; ?>" id="AlbumNames<?php echo $albumInd; ?>" value="<?php echo $key['name']; ?>"/>
                                    </div>
                                </a>
                            </div>
                            <script>
                                $(document).ready(function () {
                                    $('#form_download<?php echo $albumInd; ?>').submit(function () {
                                        document.getElementById("gif").style = "position: fixed;top: 0;left: 0;width: 100%; height:100%;display: block; z-index:1999;background: url('./../images/loading_1.gif') 50% 50% no-repeat";
                                        $("#FilePath").html("Preparing zip file of your facebook album to download..........").addClass('alert');
                                        $.ajax({
                                            type: 'post',
                                            url: 'PostRequestHandler_SingleAlbumDownload.php',
                                            data: $('#form_download<?php echo $albumInd; ?>').serialize(),
                                            success: function (data) {
                                                document.getElementById("gif").style = "position: fixed;top: 0;left: 0;width: 100%; height:100%;display: none; z-index:1999;background: url('./../images/loading_1.gif') 50% 50% no-repeat";
                                                $("#FilePath").html(data).addClass('alert');
                                            }
                                        });
                                        return false;
                                    });
                                });
                                $(document).ready(function () {
                                    $('#form_move<?php echo $albumInd; ?>').submit(function () {
                                        document.getElementById("gif").style = "position: fixed;top: 0;left: 0;width: 100%; height:100%;display: block; z-index:1999;background: url('./../images/loading_1.gif') 50% 50% no-repeat";
                                        $("#FilePath").html("Moving your facebook album to your Google Drive..........").addClass('alert');
                                        $.ajax({
                                            type: 'post',
                                            url: 'PostRequestHandler_SingleAlbumMove.php',
                                            data: $('#form_move<?php echo $albumInd; ?>').serialize(),
                                            success: function (data) {
                                                document.getElementById("gif").style = "position: fixed;top: 0;left: 0;width: 100%; height:100%;display: none; z-index:1999;background: url('./../images/loading_1.gif') 50% 50% no-repeat";
                                                $("#FilePath").html(data).addClass('alert');
                                            }
                                        });
                                        return false;
                                    });
                                });
                                $(document).ready(function () {
                                    $('#form<?php echo $albumInd; ?>').submit(function () {
                                        document.getElementById("gif").style = "position: fixed;top: 0;left: 0;width: 100%; height:100%;display: block; z-index:1999;background: url('./../images/loading_1.gif') 50% 50% no-repeat";
                                        $.ajax({
                                            type: 'post',
                                            url: 'index.php',
                                            data: $('#form<?php echo $albumInd; ?>').serialize(),
                                            success: function (data) {
                                                document.getElementById("gif").style = "position: fixed;top: 0;left: 0;width: 100%; height:100%;display: none; z-index:1999;background: url('./../images/loading_1.gif') 50% 50% no-repeat";
                                                $("#summary").removeClass().html(data).addClass('myinfo');
                                                document.getElementById("summary").style = 'position: absolute; top:0px;left:0px;z-index: 1999;height:100%;width:100%;';
                                                document.getElementById("backblack").style = 'position: absolute; top:0px;left:0px;z-index: 1980;height:100%;width:100%;background: black;opacity: 0.7;';
                                            }
                                        });
                                        return false;
                                    });
                                });
                            </script>
                            <?php
                            $albumInd = $albumInd + 1;
                        }
                        ?>
                    </div>
                </div>
                <div class="bg-info" style="min-height:45px;">
                    <div class=" col-lg-2 col-sm-3 col-lg-offset-1 col-sm-offset-1 col-xs-4" style="margin-top: 12px;">
                        <input type="checkbox" class="checkbox-inline col-lg-2 col-sm-2 col-xs-2" id="select_all"/> <div class="col-lg-9 col-sm-10 col-xs-10">Select All</div>
                    </div>
                    <form name="form_master_download" id="form_master_download">
                        <input type="hidden" name="NoOfAlbums" id="NoOfAlbums" value="<?php echo $albumInd; ?>"/>
                        <button class="btn btn-info col-lg-2 col-sm-2 col-lg-offset-1 col-sm-offset-1" name="form_master_download" style="margin-top: 5px;"><i class="glyphicon glyphicon-download-alt"></i> Download</button>
                    </form>
                    <form name="form_master_move" id="form_master_move">
                        <button class="btn btn-info col-lg-2 col-sm-2 col-lg-offset-1 col-sm-offset-1" name="form_master_move" style="margin-top: 5px;"><img src="../images/GoogleDrive.ico" height="20px" width="20px"></img>&nbsp; Move</button>
                    </form>
                </div>
                <div id="backblack" style="display: none; position: absolute; top:0px;left:0px;z-index: 1980;height:100%;width:100%;background: black;opacity: 0.7;"></div>
                <div id="summary" style="display: none; position: absolute; top:0px;left:0px;z-index: 1990;height:100%;width:100%;">
                    <div class="col-lg-1 col-lg-offset-11 col-sm-1 col-sm-offset-11 col-xs-2 col-xs-offset-10">
                        <a href="#" id="close" class="right" style="color: white;"><i class="glyphicon glyphicon-remove-sign"></i></a>
                    </div>
                </div>
            </div>
            <script>
                $("#select_all").change(function () {
                    var status = this.checked;
                    $('.checkbox').each(function () {
                        this.checked = status;
                    });
                });
                $('.checkbox').change(function () {
                    if (this.checked == false) {
                        $("#select_all")[0].checked = false;
                    }
                    if ($('.checkbox:checked').length == $('.checkbox').length) {
                        $("#select_all")[0].checked = true;
                    }
                });
                $(document).ready(function () {
                    $('#form_master_download').submit(function () {
                        $("#FilePath").html("Preparing zip file of your facebook albums to download..........").addClass('alert');
                        document.getElementById("gif").style = "position: fixed;top: 0;left: 0;width: 100%; height:100%;display: block; z-index:1999;background: url('./../images/loading_1.gif') 50% 50% no-repeat";
                        var num_checkboxes = document.getElementById('NoOfAlbums').value;
                        var mydata;
                        mydata = "&ActionType=DownloadSelected&NoOfAlbums=" + num_checkboxes;
                        for (var i = 0; i < num_checkboxes; i++)
                        {
                            mydata +=
                                    "&albumsDownloadId" + i + "=" + document.getElementById("albumsDownloadId" + i).value +
                                    "&AlbumNames" + i + "=" + document.getElementById("AlbumNames" + i).value +
                                    "&album" + i + "=" + document.getElementById("album" + i).checked;
                        }
                        $.ajax({
                            type: 'post',
                            url: 'PostRequestHandler_SelectedAlbumDownload.php',
                            data: mydata,
                            success: function (data) {
                                document.getElementById("gif").style = "position: fixed;top: 0;left: 0;width: 100%; height:100%;display: none; z-index:1999;background: url('./../images/loading_1.gif') 50% 50% no-repeat";
                                $("#FilePath").html(data).addClass('alert');
                            }
                        });
                        return false;
                    });
                });
                $(document).ready(function () {
                    $('#form_master_move').submit(function () {
                        $("#FilePath").html("Moving your facebook albums to your Google Drive..........").addClass('alert');
                        document.getElementById("gif").style = "position: fixed;top: 0;left: 0;width: 100%; height:100%;display: block; z-index:1999;background: url('./../images/loading_1.gif') 50% 50% no-repeat";
                        var num_checkboxes = document.getElementById('NoOfAlbums').value;
                        var mydata;
                        mydata = "&ActionType=MoveSelected&NoOfAlbums=" + num_checkboxes;
                        for (var i = 0; i < num_checkboxes; i++)
                        {
                            mydata +=
                                    "&albumsDownloadId" + i + "=" + document.getElementById("albumsDownloadId" + i).value +
                                    "&AlbumNames" + i + "=" + document.getElementById("AlbumNames" + i).value +
                                    "&album" + i + "=" + document.getElementById("album" + i).checked;
                        }
                        $.ajax({
                            type: 'post',
                            url: 'PostRequestHandler_SelectedAlbumMove.php',
                            data: mydata,
                            success: function (data) {
                                document.getElementById("gif").style = "position: fixed;top: 0;left: 0;width: 100%; height:100%;display: none; z-index:1999;background: url('./../images/loading_1.gif') 50% 50% no-repeat";
                                $("#FilePath").html(data).addClass('alert');
                            }
                        });
                        return false;
                    });

                });
            </script>

        </div>        
    </body>
</html>
