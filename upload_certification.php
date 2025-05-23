<?php
// the goat for error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("connection.php");


// check if the form was submitted
if($_SERVER["REQUEST_METHOD"] == "POST")
{

    // check if the file uploaded without errors
    if(isset($_FILES["certFile"]) && $_FILES["certFile"]["error"] == 0)
    {
        // directory where we want to store the certification 
        $targetDir = "upload/";

        // get user id from session
        $user_id = $_SESSION['user_id'];

        // get file name
        $fileName = basename($_FILES["certFile"]["name"]);

        $targetFilePath = $targetDir.$fileName;

        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        // allow only PDF format 
        $allowedTypes = array('pdf');

        if(in_array($fileType, $allowedTypes))
        {

            // move the uploaded file to the directory
            if(move_uploaded_file($_FILES["certFile"]["tmp_name"], $targetFilePath))
            {

                // insert certification details into certdb 
                $query = "INSERT INTO certdb (user_id, filename, file, uploaded, approved) VALUES (:user_id, :filename, :file, 1, 1)";

                // put data in array
                $data = array(
                    ':user_id' => $user_id,
                    ':filename' => $fileName,
                    ':file' => $targetFilePath
                );

                $stmt = $conn->prepare($query);
                $query_execute = $stmt->execute($data);

                if($query_execute)
                {
                    // check user type and update submission stage 
                    if($_SESSION['pfType'] == 'travelnursesdb')
                    {
                        // update submission stage to approved in travel nurses db
                        $query = "UPDATE travelnursesdb SET stage = 'Approved' WHERE user_id = :user_id";
                    }
                    
                    else if($_SESSION['pfType'] == 'propertyownersdb')
                    {
                        // update submission stage to approved in property owners db
                        $query = "UPDATE propertyownersdb SET stage = 'Approved' WHERE user_id = :user_id";

                    }

                    // execute the query
                    $data = array(':user_id' => $user_id);
                    $stmt = $conn->prepare($query);
                    $stmt->execute($data);

                    // set submission stage to "Approved" (for now)
                    $_SESSION['submission_stage'] = "Approved";

                    // redirect to the respective profile page based on user type
                    if($_SESSION['pfType'] == 'travelnursesdb')
                    {
                        header("Location: nurse-profile.php");
                    }
                    else if($_SESSION['pfType'] == 'propertyownersdb')
                    {
                        header("Location: propertyOwner-profile.php");
                    }
                    
                }
                   
            }

            else
            {
                // for error handling
                echo "Sorry, there was an error uploading your file.";
                
            }
        }
        else
        {
            echo "Sorry, only PDF files are allowed.";
        }
    }
    else 
    {
        // Check if the file upload encountered an error
        switch ($_FILES["certFile"]["error"]) {
            case 1:
            case 2:
                echo "Sorry, the uploaded file exceeds the maximum file size limit.";
                break;
            case 3:
                echo "Sorry, the uploaded file was only partially uploaded.";
                break;
            case 4:
                echo "Sorry, no file was uploaded.";
                break;
            case 6:
                echo "Sorry, missing temporary folder.";
                break;
            case 7:
                echo "Sorry, failed to write file to disk.";
                break;
            case 8:
                echo "Sorry, a PHP extension stopped the file upload.";
                break;
            
            // debugging
            default:
             echo "Unexpected error code: ";
             var_dump($_FILES["certFile"]["error"]);
             echo "Sorry, an unknown error occurred during upload.";
             break;
        }
}}
    else
    {
        if($_SESSION['pfType'] == 'travelnursesdb')
        {
            header("Location: nurse-profile.php");
        }
        else if($_SESSION['pfType'] == 'propertyownersdb')
        {
            header("Location: propertyOwner-profile.php");
        }
    }
