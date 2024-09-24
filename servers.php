<?php
header('Content-Type: application/json');

// Function to load servers data from JSON file
function load_servers_data() {
    $json_file = 'servers_data.json';
    if (file_exists($json_file)) {
        $data = json_decode(file_get_contents($json_file), true);
        return is_array($data) ? $data : [];
    }
    return [];
}

try {
    // Load existing servers data
    $servers_data = load_servers_data();

    // Prepare the response
    $response = [
        'success' => true,
        'servers' => array_values($servers_data) // Convert associative array to indexed array
    ];

    // Send the response as JSON
    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
