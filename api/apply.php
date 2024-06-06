<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? null;
    $age = $_POST['age'] ?? null;
    $birthday = $_POST['birthday'] ?? null;
    $address = $_POST['address'] ?? null;
    $position = $_POST['position'] ?? null;
    $public_key = $_POST['public_key'] ?? null;
    $private_key = $_POST['private_key'] ?? null;

    // Handling file uploads
    $id_picture = isset($_FILES['id_picture']) ? file_get_contents($_FILES['id_picture']['tmp_name']) : null;
    $resume = isset($_FILES['resume']) ? file_get_contents($_FILES['resume']['tmp_name']) : null;
    $signature = $_POST['signature'] ?? null;
    $rsa_signature = $_POST['rsa_signature'] ?? null;

    if ($name && $age && $birthday && $address && $position && $id_picture && $resume && $signature && $public_key && $private_key && $rsa_signature) {
        // Save the user data to the database
        $stmt = $conn->prepare("INSERT INTO users (name, age, birthday, address, position, id_picture, resume, public_key, private_key) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("sisssssss", $name, $age, $birthday, $address, $position, $id_picture, $resume, $public_key, $private_key);
        $stmt->execute();

        if ($stmt->error) {
            die("Error executing statement: " . $stmt->error);
        }
        
        $user_id = $stmt->insert_id;

        // Close the previous statement
        $stmt->close();

        // Save the signed document data to the database
        $stmt = $conn->prepare("INSERT INTO signed_documents (user_id, signature, rsa_signature) VALUES (?, ?, ?)");
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("iss", $user_id, $signature, $rsa_signature);
        $stmt->execute();
        
        if ($stmt->error) {
            die("Error executing statement: " . $stmt->error);
        }

        // Close the statement
        $stmt->close();

        // Save audit log
        $action = 'User application submitted';
        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("is", $user_id, $action);
        $stmt->execute();
        
        if ($stmt->error) {
            die("Error executing statement: " . $stmt->error);
        }

        // Close the statement
        $stmt->close();

        echo json_encode(['message' => 'Application submitted successfully.']);
    } else {
        echo json_encode(['message' => 'Incomplete form data.']);
    }
} else {
    echo json_encode(['message' => 'Invalid request method.']);
}
?>
