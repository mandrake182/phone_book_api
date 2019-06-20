<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

require 'database.php';
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// CHECK GET ID PARAMETER OR NOT
if($_SERVER['REQUEST_METHOD'] == 'GET'){

    if(isset($_GET['id']))
    {    
        $person_id = filter_var($_GET['id'], FILTER_VALIDATE_INT,[
            'options' => [
                'default' => 'all_persons',
                'min_range' => 1
            ]
        ]);
    }
    else{
        $person_id = 'all_persons';
    }

    $sql = is_numeric($person_id) ? "SELECT * FROM `persons` WHERE id='$person_id'" : "SELECT * FROM `persons`"; 
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    if($stmt->rowCount() > 0){    
        $persons_array  = [];
        //FORMING OUR ARRAY OF PERSONS WITH LINKED PHONES AND EMAILS//    
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            
            $emails_array   = [];
            $phones_array   = [];

            $phones_sql = "SELECT phone FROM phones where person_id =".$row['id'];
            $stmt_phone = $conn->prepare($phones_sql);
            $stmt_phone->execute();        
            if($stmt_phone->rowCount() > 0){
                while ($row_phones = $stmt_phone->fetch(PDO::FETCH_ASSOC)) {                                
                    array_push($phones_array, $row_phones['phone']);                
                }    
            }

            $emails_sql = "SELECT email FROM emails where person_id =".$row['id'];
            $stmt_email = $conn->prepare($emails_sql);
            $stmt_email->execute();        
            if($stmt_email->rowCount() > 0){
                while ($row_email = $stmt_email->fetch(PDO::FETCH_ASSOC)) {                                
                    array_push($emails_array, $row_email['email']);                
                }    
            }        
            
            $persons_data = [
                'id' => $row['id'],
                'name' => $row['name'],
                'first_name' => html_entity_decode($row['first_name']),
                'last_name' => $row['last_name'],
                'picture' => html_entity_decode($row['picture']),
                'phone' => $phones_array,
                'email' => $emails_array,
            ];
            
            array_push($persons_array, $persons_data);
        }
        
        echo json_encode(['message'=>'Contacts found', 'code'=>200, 'result'=>'success', 'data'=>$persons_array]);
    }
    else{    
        echo json_encode(['message'=>'No contacts found', 'code'=>200, 'result'=>'success']);
    }

}else{
    echo json_encode(['message'=>'Method not allowed', 'code'=>405, 'result'=>'error']);
}

?>