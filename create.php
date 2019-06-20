<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require 'database.php';
require 'validation.php';
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// GET DATA FORM REQUEST//
if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $data = $_POST;
    $msg = array();

    if(isset($data['name']) && isset($data['first_name']) && isset($data['last_name']) && isset($data['email']) && isset($data['phone'])){

        if(!empty($data['name']) && !empty($data['first_name']) && !empty($data['last_name']) && !empty($data['email']) && !empty($data['phone'])){
            
            $error_upload = false;                    
            $add_file_bind_value = false;
            
            // SAVING PERSON PICTURE //
            if(isset($_FILES)){            
                if(is_array($_FILES) && count($_FILES)>0){            
                    if(($_FILES['picture']['name']!="")){
                        $target_dir = "pictures/";
                        $file = $_FILES['picture']['name'];
                        $path = pathinfo($file);
                        $filename = $path['filename'];
                        $ext = $path['extension'];
                        $temp_name = $_FILES['picture']['tmp_name'];
                        $path_filename_ext = $target_dir.$filename.".".$ext;                    
                        $moved_file = move_uploaded_file($temp_name,$path_filename_ext);             
                        if($moved_file){
                            $add_file_bind_value = true;                                                                
                        }else{
                            $error_upload = true;
                        }            
                    }    
                }    
            } 
            
            if($error_upload==false){
                
                //SAVING PERSON DATA //
                if($add_file_bind_value == true){
                    $insert_query_user = "INSERT INTO `persons`(name,first_name,last_name,picture)VALUES(:name,:first_name,:last_name,:picture)";
                }else{
                    $insert_query_user = "INSERT INTO `persons`(name,first_name,last_name)VALUES(:name,:first_name,:last_name)";
                }

                $insert_stmt_user = $conn->prepare($insert_query_user);        
                $insert_stmt_user->bindValue(':name', htmlspecialchars(strip_tags($data['name'])),PDO::PARAM_STR);
                $insert_stmt_user->bindValue(':first_name', htmlspecialchars(strip_tags($data['first_name'])),PDO::PARAM_STR);
                $insert_stmt_user->bindValue(':last_name', htmlspecialchars(strip_tags($data['last_name'])),PDO::PARAM_STR);
                if($add_file_bind_value == true){
                    $insert_stmt_user->bindValue(':picture', htmlspecialchars(strip_tags($path_filename_ext)),PDO::PARAM_STR);    
                }

                $insert_stmt_user->execute();
                $idNewUser  = $conn->lastInsertId();

                if($idNewUser!='' && !is_null($idNewUser)){

                    $error_insert = 0;
                    
                    //SAVING PERSON EMAILS//
                    $emails = explode(',', $data['email']);
                    
                    if(count($emails)>0){
                        
                        foreach ($emails as $email) {
                            if(!empty($email) && !is_null($email)){

                                if(validate_email($email)!==false){

                                    $insert_query_email = "INSERT INTO `emails`(email,person_id) VALUES(:email,:person_id)";
                                    $insert_stmt_email = $conn->prepare($insert_query_email);                            
                                    $insert_stmt_email->bindValue(':email', htmlspecialchars(strip_tags($email)),PDO::PARAM_STR);
                                    $insert_stmt_email->bindValue(':person_id', htmlspecialchars(strip_tags($idNewUser)),PDO::PARAM_INT);
                                    $insert_stmt_email->execute();
                                }                            
                            }                            
                        }
                    }

                    //SAVING PERSON PHONES//
                    $phones = explode(',', $data['phone']);
                    
                    if(count($phones)>0){

                        foreach ($phones as $phone) {
                            if(!empty($phone) && !is_null($phone)){
                                
                                if(validate_phone($phone)){

                                    $insert_query_phone = "INSERT INTO `phones`(phone,person_id) VALUES(:phone,:person_id)";
                                    $insert_stmt_phone = $conn->prepare($insert_query_phone);                            
                                    $insert_stmt_phone->bindValue(':phone', htmlspecialchars(strip_tags($phone)),PDO::PARAM_STR);
                                    $insert_stmt_phone->bindValue(':person_id', htmlspecialchars(strip_tags($idNewUser)),PDO::PARAM_INT);
                                    $insert_stmt_phone->execute();    
                                }
                                
                            }                            
                        }
                    }
                                                    
                    $msg['message'] = 'Contact added successfully';
                    $msg['code']    = 201;
                    $msg['result']  = 'success';

                }else{

                    $msg['message'] = 'Error, Couldn´t save person info';
                    $msg['code']    = 400;
                    $msg['result']  = 'error';
                }

            }else{

                $msg['message'] = 'Error, Couldn´t save person picture';
                $msg['code']    = 400;
                $msg['result']  = 'error';
            }
            
        }else{

            $msg['message'] = 'Please fill all the fields';
            $msg['code']    = 400;
            $msg['result']  = 'error';
        }

    }else{

        $msg['message'] = 'Please fill all the fields | name, first_name, last_name, phone(comma separated for multiple), email(comma separated for multiple)';
        $msg['code']    = 400;
        $msg['result']  = 'error';
    }

}else{

    $msg['message'] = 'Method not allowed';
    $msg['code']    = 405;
    $msg['result']  = 'error';

}

echo  json_encode($msg);
?>