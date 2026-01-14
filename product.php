<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Firebase project details
$projectId = "verdantra-grows";
$collection = "products";

// Firestore REST API URL
$url = "https://firestore.googleapis.com/v1/projects/$projectId/databases/(default)/documents/$collection";

// Call Firestore API
$response = file_get_contents($url);

if ($response === FALSE) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch data from Firebase"
    ]);
    exit;
}

$data = json_decode($response, true);

// Convert Firestore format → clean JSON
$products = [];

if (isset($data["documents"])) {
    foreach ($data["documents"] as $doc) {
        $fields = $doc["fields"];
        $product = [];

        foreach ($fields as $key => $value) {
            $product[$key] = parseFirestoreValue($value);
        }

        $products[] = $product;
    }
}

// Helper function
function parseFirestoreValue($value) {
    if (isset($value["stringValue"])) return $value["stringValue"];
    if (isset($value["integerValue"])) return (int)$value["integerValue"];
    if (isset($value["doubleValue"])) return (float)$value["doubleValue"];
    if (isset($value["booleanValue"])) return (bool)$value["booleanValue"];

    if (isset($value["arrayValue"])) {
        $arr = [];
        if (isset($value["arrayValue"]["values"])) {
            foreach ($value["arrayValue"]["values"] as $v) {
                $arr[] = parseFirestoreValue($v);
            }
        }
        return $arr;
    }

    if (isset($value["mapValue"])) {
        $obj = [];
        foreach ($value["mapValue"]["fields"] as $k => $v) {
            $obj[$k] = parseFirestoreValue($v);
        }
        return $obj;
    }

    return null;
}

// Final JSON response
echo json_encode([
    "success" => true,
    "count" => count($products),
    "data" => $products
], JSON_PRETTY_PRINT);
