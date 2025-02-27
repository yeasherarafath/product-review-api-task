<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

$data = json_decode(file_get_contents('php://input'), true);

function validateInput( $data )
{
    if (
        isset($data['product_id'], $data['user_id'], $data['review_text']) &&
        !empty($data['product_id']) && !empty($data['user_id']) && !empty($data['review_text']) &&
        is_numeric($data['product_id']) && is_numeric($data['user_id'])
    ) {
        return true;
    }
    return false;
}

function connectDatabase()
{
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "product_reviews";
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ( $conn->connect_error ) {
        http_response_code(500);
        return (json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]));
    }

    return $conn;
}

function insertReview( $conn, $data )
{
    $stmt = $conn->prepare("SELECT review_id FROM reviews WHERE product_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $data['product_id'], $data['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if( $result->num_rows > 0 ) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Review already exists']);
        return;
    }
    
    $data['review_text'] = htmlspecialchars(strip_tags($data['review_text']));
    $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, review_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $data['product_id'], $data['user_id'], $data['review_text']);

    if ( $stmt->execute() ) {
        http_response_code(201);
        echo json_encode(['status' => 'success', 'message' => 'Review submitted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit review']);
    }

    $stmt->close();
}

if ( validateInput($data) ) {
    $conn = connectDatabase();
    insertReview($conn, $data);
    $conn->close();
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input, Invalid input. Check your data"','data' => $data]);
}
?>
