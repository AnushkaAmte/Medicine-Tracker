<?php
 
/*
 * Following code will create a new medicine connected to a user
 * All product details are read from HTTP Post Request
 * Input:  JSON Format perbottle, dosage, medname, username schedule [day, hour, min, intentID]
 * Output: 'success', 'message'
 */
 
// array for JSON response
$response = array();
 
// Create connection
include_once 'connect.php';

$data = json_decode(file_get_contents('php://input'), true);

// check for required fields
if (isset($data['perbottle']) && isset($data['dosage']) && isset($data['medname']) && isset($data['username']) && isset($data['oldname']) && isset($data['alarms'])) {
 
    //get information 
    $user = $mysqli->real_escape_string($data['username']);
    $perbottle = $mysqli->real_escape_string($data['perbottle']);
    $dose = $mysqli->real_escape_string($data['dosage']);
    $medname = $mysqli->real_escape_string($data['medname']);
    $oldname = $mysqli->real_escape_string($data['oldname']);
    $schedule = $data['schedule'];
    $alarms = $mysqli->real_escape_string($data['alarms']); 
    
    //check if username exist
    $result = $mysqli->query("SELECT * FROM Users WHERE Username='$user'");
    if( $result->num_rows <= 0 ) {
	    $response["success"] = 0;
        $response["message"] = "username doesn't exist!"; 
        echo json_encode($response);
	}
    else {
        $sql = "UPDATE UserMeds SET PerBottle = '$perbottle', MedName = '$medname',  Dosage = '$dose', AlarmCount = '$alarms' WHERE User = '$user' AND MedName = '$oldname' ";
        
        //if the query is successsful, user exists so add medication
        if ($mysqli->query($sql) === true){
            $response["success"] = 1;
            $response["message"] = "Medicine Updated!"; 
        }
        else {
            $response["success"] = 0;
            $response["message"] = "Somethign went wrong! Medicine Table";
        }
        
        $delsql = $mysqli->query("DELETE FROM Schedule WHERE UserN = '$user' AND MedNa = '$medname' ");
        if(!$delsql) {
            $response["success"] = 0;
            $response["message"] = "Couldn't delete Schedule!";
            echo json_encode($response);
            return; 
        }
        
        //insert the schedule readd new id ad schedule 
        foreach( $schedule as $alarm ) {
            $id = $alarm['intentID']; 
            $day = $alarm['day']; 
            $hour = $alarm['hour']; 
            $min = $alarm['min']; 
            $sql = "INSERT INTO `Schedule` (`UserN`, `MedNa`, `Day`, `Hour`, `Min`, `intentID`) VALUES ('$user', '$medname', '$day', '$hour', '$min', '$id')";
            
            if ($mysqli->query($sql) === true){
                $response["success"] = 1;
                
            }
            else {
                $response["success"] = 0;
                $response["message"] = "Couldn't insert schdule please try again!";
                break;
            }
            
        }
        if ($response["success"]  == 0) {
             $sql = "DELETE FROM `UserMeds` WHERE `UserMeds`.`MedName` = '$medname'";
             $mysqli->query($sql);
        }
        echo json_encode($response);
        
    }//end username dup check else
    
} 
else {
    // required field is missing
    $response["success"] = 0;
    $response["message"] = "Field missing?";
 
    // echoing JSON response
    echo json_encode($response);
}

?>	