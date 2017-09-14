<?php

/**
 * Processes File Doc Comment
 * 
 * PHP version 5.4
 *
 * @category Class
 * @package  Processes
 * @author   Authors <pateldevik@gmail.com>
 * @license  MIT
 * @link     https://github.com/DevikVekariya/FacebookAlbumsArchiver
 */

/**
 * Processes Class Doc Comment
 * 
 * PHP version 5.4
 *
 * @category Processes
 * @package  Processes
 * @author   Authors <pateldevik@gmail.com>
 * @license  MIT
 * @link     https://github.com/DevikVekariya/FacebookAlbumsArchiver
 */
class Processes {

    private $_dbHost = "";
    private $_dbUsername = "";
    private $_dbPassword = "";
    private $_dbName = "";
    private $_downloadTbl = '';

    /**
     * Constructor to initialize the Album class object with some initial values.
     */
    function __construct() {
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
    function setProcess($pData = array()) {
        if (!empty($pData)) {
            $date = date('Y-m-d H:i:s');
            $query = "INSERT INTO " . $this->_downloadTbl .
                    " SET user_id = '" . $pData['user_id'] .
                    "', album_id = '" . $pData['album_id'] .
                    "', album_name = '" . $pData['album_name'] .
                    "', count = '" . $pData['count'] .
                    "', total = '" . $pData['total'] .
                    "', status = '" . $pData['status'] .
                    "', type = '" . $pData['type'] .
                    "', background_process_id = '" . $pData['background_process_id'] .
                    "',datetime='" . $date . "'";
            $insert = $this->db->query($query);
        }
    }

    function finishProcess($userId, $type) {
        $query = "UPDATE " . $this->_downloadTbl .
                " SET " .
                " status = '1'" .
                " WHERE user_id = '" . $userId .
                "' AND type='" . $type . "' and count=total";
        $update = $this->db->query($query);
    }

    function UpdateProcess($userId, $albumId, $count, $status, $pid, $type) {
        if ($pid == -1) {
            $query = "UPDATE " . $this->_downloadTbl .
                    " SET count = '" . $count .
                    "', status = '" . $status .
                    "' WHERE user_id = '" . $userId .
                    "' AND album_id = '" . $albumId . "' and type='" . $type . "'";
            $update = $this->db->query($query);
        } else {
            $query = "UPDATE " . $this->_downloadTbl .
                    " SET count = '" . $count .
                    "', status = '" . $status .
                    "', background_process_id = '" . $pid .
                    "' WHERE user_id = '" . $userId .
                    "' AND album_id = '" . $albumId . "' and type='" . $type . "'";
            $update = $this->db->query($query);
        }
    }

    function getRunningProcesses($UserId, $type, $status) {
        $prevQuery = "SELECT count(*) as count FROM " . $this->_downloadTbl .
                " WHERE user_id = '" . $UserId . "' and type='" . $type . "' and status='" . $status . "'";
        $result = $this->db->query($prevQuery);
        $Data = $result->fetch_assoc();
        return $Data;
    }

    function isRunningProcess($UserId, $type, $album_name) {
        $prevQuery = "SELECT count(*) as count FROM " . $this->_downloadTbl .
                " WHERE user_id = '" . $UserId . "' and type='" . $type . "' and album_name='" . $album_name . "' and (status='0' or status='2')";
        $result = $this->db->query($prevQuery);
        $Data = $result->fetch_assoc();

        return $Data;
    }

    function deleteOldProcess($UserId, $album_name, $type) {
        $query = "DELETE FROM " . $this->_downloadTbl .
                " WHERE user_id = '" . $UserId . "' and album_name='" . $album_name . "' and type='" . $type . "'";
        $insert = $this->db->query($query);
    }

    function deleteProcess($ProcessId) {
        $query = "DELETE FROM " . $this->_downloadTbl .
                " WHERE process_id = '" . $ProcessId . "'";
        $insert = $this->db->query($query);
    }

    function getProcess($ProcessId) {
        // Check whether user data already exists in database
        $prevQuery = "SELECT * FROM " . $this->_downloadTbl .
                " WHERE process_id = '" . $ProcessId . "'";
        $result = $this->db->query($prevQuery);
        $Data = $result->fetch_assoc();
        return $Data;
    }

    /**
     * Function to get albums zip file-name from database
     *
     * @param Int $UserId used to pass user id of perticuler user
     *
     * @return array
     */
    function getProcesses($UserId) {
        if ($UserId == -1) {
            $query = "SELECT * FROM " . $this->_downloadTbl .
                    " ORDER BY process_id DESC";
            $insert = $this->db->query($query);
            return $insert;
        } else {
            $query = "SELECT * FROM " . $this->_downloadTbl .
                    " WHERE user_id = '" . $UserId . "'  ORDER BY process_id DESC";
            $insert = $this->db->query($query);
            return $insert;
        }
    }

}

?>
