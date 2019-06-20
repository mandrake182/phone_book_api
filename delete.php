<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: DELETE");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require 'database.php';
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// GET DATA FORM REQUEST
$msg = array();
if($_SERVER['REQUEST_METHOD'] == 'DELETE'){

    parse_str(file_get_contents("php://input"),$post_vars);    

    //CHECKING, IF ID AVAILABLE ON $post_vars
    if(isset($post_vars['id'])){
                
        $error_deletion = 0;    
        $person_id = $post_vars['id'];        
        //DELETE POST BY ID FROM DATABASE
        $delete_person = "DELETE FROM `persons` WHERE id=:person_id";
        $delete_person_stmt = $conn->prepare($delete_person);
        $delete_person_stmt->bindValue(':person_id', $person_id,PDO::PARAM_INT);
        
        if($delete_person_stmt->execute()){

            //DELETE RELATED PHONES AND EMAILS
            $delete_person_phone = "DELETE FROM `phones` WHERE person_id=:person_id";
            $delete_person_phone_stmt = $conn->prepare($delete_person_phone);
            $delete_person_phone_stmt->bindValue(':person_id', $person_id,PDO::PARAM_INT);
            $delete_person_phone_stmt->execute();

            $delete_person_email = "DELETE FROM `emails` WHERE person_id=:person_id";
            $delete_person_email_stmt = $conn->prepare($delete_person_email);
            $delete_person_email_stmt->bindValue(':person_id', $person_id,PDO::PARAM_INT);
            $delete_person_email_stmt->execute();

            $msg['message'] = 'Contact deleted successfully';        
            $msg['code']    =  200;
            $msg['result']  = 'success';

        }else{

            $msg['message'] = 'Contact Not Deleted';
            $msg['code']    =  400;
            $msg['result']  = 'error';
        }
                    
    }else{

        $msg['message'] = 'Invalid parameters';
        $msg['code']    =  400;
        $msg['result']  = 'error';
    }

}else{

    $msg['message'] = 'Method not allowed';
    $msg['code']    = 405;
    $msg['result']  = 'error';
}


echo  json_encode($msg);

?>