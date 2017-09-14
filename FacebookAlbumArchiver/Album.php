<?php
/**
 * Album File Doc Comment
 * 
 * PHP version 5.4
 *
 * @category Class
 * @package  Album
 * @author   Authors <pateldevik@gmail.com>
 * @license  No License
 * @link     https://github.com/DevikVekariya/FacebookAlbumsArchiver
 */
/**
 * Album Class Doc Comment
 * 
 * PHP version 5.4
 *
 * @category Album
 * @package  Album
 * @author   Authors <pateldevik@gmail.com>
 * @license  No License
 * @link     https://github.com/DevikVekariya/FacebookAlbumsArchiver
 */
class Album
{

    private $_dbHost = "localhost";
    private $_dbUsername = "db1testuser";
    private $_dbPassword = "db1test";
    private $_dbName = "db1test";
    private $_downloadTbl = 'albumdownloads';

    /**
     * Constructor to initialize the Album class object with some initial values.
     */
    function __construct()
    {
        if (!isset($this->db)) {
            $conn = new mysqli($this->_dbHost, $this->_dbUsername, $this->_dbPassword, $this->_dbName);
            if ($conn->connect_error) {
                die("Failed to connect with MySQL: " . $conn->connect_error);
            } else {
                $this->db = $conn;
            }
        }
    }

    /**
     * Function to enter albums zip file name into database
     * 
     * @param Array $userData used to pass user informations to function
     *
     * @return none
     */
    function setAlbum($userData = array())
    {
        if (!empty($userData)) {
            $date = date('Y-m-d H:i:s');
            $query = "INSERT INTO " . $this->_downloadTbl . 
                    " SET user_id = '" . $userData['user_id'] . 
                    "', zip_name = '" . $userData['zip_name'] . 
                    "',date_time='" . $date . "'";
            $insert = $this->db->query($query);
        }
    }

    /**
     * Function to get albums zip file-name from database
     *
     * @param Int $UserId used to pass user id of perticuler user
     *
     * @return array
     */
    function getAlbums($UserId)
    {
        $query = "SELECT * FROM " . $this->_downloadTbl . 
                " WHERE user_id = '" . $UserId . "'  ORDER BY id DESC";
        $insert = $this->db->query($query);
        return $insert;
    }

}
?>
