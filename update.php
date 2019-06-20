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

// GET DATA FORM REQUEST

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $data = $_POST;
    if(isset($data['id'])){
        
        $msg = array();
        $person_id = $data['id'];
        
        //GET POST BY ID FROM DATABASE
        $get_person = "SELECT * FROM `persons` WHERE id=:person_id";
        $get_stmt = $conn->prepare($get_person);
        $get_stmt->bindValue(':person_id', $person_id,PDO::PARAM_INT);
        $get_stmt->execute();
        
        
        //CHECK WHETHER THERE IS ANY POST IN OUR DATABASE
        if($get_stmt->rowCount() > 0){
            
            
            $add_file_bind_value    = false;
            $error_upload           = false;
            
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
                        $path_filename = dirname(__FILE__) .'/'.$path_filename_ext;
                        if(!file_exists($path_filename)){                                                    
                            $moved_file = move_uploaded_file($temp_name,$path_filename_ext);                        
                            if($moved_file){
                                $add_file_bind_value = true;                                                                                        
                            }else{
                                $error_upload = true;
                            }
                        }else{
                            $add_file_bind_value = false;                                                                
                        }                                
                    }    
                }    
            }

            if($error_upload == false){

                // UPDATING PERSON DATA, EMAILS Y PHONES//        
                $row = $get_stmt->fetch(PDO::FETCH_ASSOC);                
                $prev_picture = $row['picture'];            
                $person_name = isset($data['name']) ? $data['name'] : $row['name'];
                $person_first_name = isset($data['first_name']) ? $data['first_name'] : $row['first_name'];
                $person_last_name = isset($data['last_name']) ? $data['last_name'] : $row['last_name'];            
                if($add_file_bind_value == true){
                    $update_query = "UPDATE `persons` SET name = :name, first_name = :first_name, last_name = :last_name, picture = :picture WHERE id = :id";
                }else{
                    $update_query = "UPDATE `persons` SET name = :name, first_name = :first_name, last_name = :last_name 
                    WHERE id = :id";    
                }


                
                $update_stmt = $conn->prepare($update_query);
                        
                $update_stmt->bindValue(':name', htmlspecialchars(strip_tags($person_name)),PDO::PARAM_STR);
                $update_stmt->bindValue(':first_name', htmlspecialchars(strip_tags($person_first_name)),PDO::PARAM_STR);
                $update_stmt->bindValue(':last_name', htmlspecialchars(strip_tags($person_last_name)),PDO::PARAM_STR);
                if($add_file_bind_value == true){
                    $update_stmt->bindValue(':picture',strip_tags($path_filename_ext),PDO::PARAM_STR);    
                }            
                $update_stmt->bindValue(':id', $person_id,PDO::PARAM_INT);
                
                
                if($update_stmt->execute()){
                    if($add_file_bind_value == true){
                        unlink(dirname(__FILE__) .'/'.$prev_picture);    
                    }
                    

                    if(!empty($data['email'])){

                        $delete_person_email = "DELETE FROM `emails` WHERE person_id=:person_id";
                        $delete_person_email_stmt = $conn->prepare($delete_person_email);
                        $delete_person_email_stmt->bindValue(':person_id', $person_id,PDO::PARAM_INT);
                        if($delete_person_email_stmt->execute()){

                            $emails = explode(',', $data['email']);            
                            if(count($emails)>0){
                                
                                foreach ($emails as $email) {
                                    if(!empty($email) && !is_null($email)){
                                        if(validate_email($email)!==false){

                                            $insert_query_email = "INSERT INTO `emails`(email,person_id) VALUES(:email,:person_id)";
                                            $insert_stmt_email  = $conn->prepare($insert_query_email);                                  
                                            $insert_stmt_email->bindValue(':email', htmlspecialchars(strip_tags($email)),PDO::PARAM_STR);
                                            $insert_stmt_email->bindValue(':person_id', htmlspecialchars(strip_tags($person_id)),PDO::PARAM_INT);
                                            $insert_stmt_email->execute();    
                                        }
                                        
                                    }                            
                                }
                            }    
                        }                
                    }

                    if(!empty($data['phone'])){

                        $delete_person_phone = "DELETE FROM `phones` WHERE person_id=:person_id";
                        $delete_person_phone_stmt = $conn->prepare($delete_person_phone);
                        $delete_person_phone_stmt->bindValue(':person_id', $person_id,PDO::PARAM_INT);
                        if($delete_person_phone_stmt->execute()){

                            $phones = explode(',', $data['phone']);
                        
                            if(count($phones)>0){

                                foreach ($phones as $phone) {
                                    if(!empty($phone) && !is_null($phone)){
                                        
                                        if(validate_phone($phone)){

                                            $insert_query_phone = "INSERT INTO `phones`(phone,person_id) VALUES(:phone,:person_id)";
                                            $insert_stmt_phone = $conn->prepare($insert_query_phone);                            
                                            $insert_stmt_phone->bindValue(':phone', htmlspecialchars(strip_tags($phone)),PDO::PARAM_STR);
                                            $insert_stmt_phone->bindValue(':person_id', htmlspecialchars(strip_tags($person_id)),PDO::PARAM_INT);
                                            $insert_stmt_phone->execute();
                                        }
                                    }                               
                                }
                            }

                        }
                    }            
                    
                    $msg['message'] = 'Data updated successfully';
                    $msg['code']    = 201;
                    $msg['result']  = 'success';

                }else{            
                    
                    $msg['message'] = 'Data not updated';
                    $msg['code']    = 400;
                    $msg['result']  = 'error';
                }
            }else{

                $msg['message'] = 'Error, Couldn´t save person picture';
                $msg['code']    = 400;
                $msg['result']  = 'error';
            }           
            
        }else{

            $msg['message'] = 'Invalid ID';
            $msg['code']    = 400;
            $msg['result']  = 'error';
        }  
    }else{

        $msg['message'] = 'Invalid parameters';
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