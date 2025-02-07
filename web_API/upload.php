<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require "../database.php";
date_default_timezone_set('America/Los_Angeles');

// Define your Google API key
define('GOOGLE_API_KEY', 'AIzaSyAqEAIRnEJU7aSOYLtJvuwGmzUcy2-CVvY');

// Function to get email from user ID
function getEmailFromUserId($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT Email_Address FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ? $user['Email_Address'] : null;
}

// Function to call Google Geocoding API and get address components
function getAddressComponents($latitude, $longitude) {
    $apiKey = GOOGLE_API_KEY;
    $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$latitude,$longitude&key=$apiKey";
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if ($data['status'] == 'OK' && !empty($data['results'])) {
        $addressComponents = $data['results'][0]['address_components'];
        $address = [
            'street' => '',
            'zip' => '',
            'city' => '',
            'county' => '',
            'state' => ''
        ];
        
        $streetNumber = '';
        $route = '';
        
        foreach ($addressComponents as $component) {
            if (in_array('street_number', $component['types'])) {
                $streetNumber = $component['long_name'];
            }
            if (in_array('route', $component['types'])) {
                $route = $component['long_name'];
            }
            if (in_array('postal_code', $component['types'])) {
                $address['zip'] = $component['long_name'];
            }
            if (in_array('locality', $component['types'])) {
                $address['city'] = $component['long_name'];
            }
            if (in_array('administrative_area_level_2', $component['types'])) {
                $address['county'] = $component['long_name'];
            }
            if (in_array('administrative_area_level_1', $component['types'])) {
                $address['state'] = $component['short_name'];
            }
        }
        
        $address['street'] = trim($streetNumber . ' ' . $route);
        return $address;
    }
    return null;
}

// Function to collect all features and generate GeoJSON data
function generateGeoJSON($pdo) {
    $sql = 'SELECT * from images';
    $results = $pdo->query($sql);

    $mainGeojson = array(
        'type' => 'FeatureCollection',
        'name' => 'Updated_Images_CSV_with_Coordinates_2',
        'crs' => array(
            'type' => 'name',
            'properties' => array(
                'name' => 'urn:ogc:def:crs:OGC:1.3:CRS84'
            )
        ),
        'features' => array()
    );

    $stateGeojsonFiles = [];
    $countyGeojsonFiles = [];
    $cityGeojsonFiles = [];

    while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
        $feature = array(
            'type' => 'Feature',
            'properties' => array(
                'id' => (float)$row['id'],
                'image_url' => $row['image_url'],
                'description' => $row['description'],
                'severity' => $row['severity'],
                'upload_date' => $row['upload_date'],
                'tag' => isset($row['tag']) ? (float)$row['tag'] : null,
                'status' => $row['status'],
                'latitude' => isset($row['latitude']) ? (float)$row['latitude'] : 0,
                'longitude' => isset($row['longitude']) ? (float)$row['longitude'] : 0,
                'street' => $row['street'],
                'zip' => $row['zip'],
                'city' => $row['city'],
                'county' => $row['county'],
                'state' => $row['state']
            ),
            'geometry' => array(
                'type' => 'Point',
                'coordinates' => array(
                    isset($row['longitude']) ? (float)$row['longitude'] : 0,
                    isset($row['latitude']) ? (float)$row['latitude'] : 0
                )
            )
        );
        array_push($mainGeojson['features'], $feature);

        // Create state-specific GeoJSON data
        $state = $row['state'];
        if (!isset($stateGeojsonFiles[$state])) {
            $stateGeojsonFiles[$state] = array(
                'type' => 'FeatureCollection',
                'name' => $state,
                'crs' => array(
                    'type' => 'name',
                    'properties' => array(
                        'name' => 'urn:ogc:def:crs:OGC:1.3:CRS84'
                    )
                ),
                'features' => array()
            );
        }
        array_push($stateGeojsonFiles[$state]['features'], $feature);

        // Create county-specific GeoJSON data
        $county = $row['county'];
        if (!isset($countyGeojsonFiles[$state])) {
            $countyGeojsonFiles[$state] = [];
        }
        if (!isset($countyGeojsonFiles[$state][$county])) {
            $countyGeojsonFiles[$state][$county] = array(
                'type' => 'FeatureCollection',
                'name' => $county,
                'crs' => array(
                    'type' => 'name',
                    'properties' => array(
                        'name' => 'urn:ogc:def:crs:OGC:1.3:CRS84'
                    )
                ),
                'features' => array()
            );
        }
        array_push($countyGeojsonFiles[$state][$county]['features'], $feature);

        // Create city-specific GeoJSON data
        $city = $row['city'];
        if (!isset($cityGeojsonFiles[$state])) {
            $cityGeojsonFiles[$state] = [];
        }
        if (!isset($cityGeojsonFiles[$state][$city])) {
            $cityGeojsonFiles[$state][$city] = array(
                'type' => 'FeatureCollection',
                'name' => $city,
                'crs' => array(
                    'type' => 'name',
                    'properties' => array(
                        'name' => 'urn:ogc:def:crs:OGC:1.3:CRS84'
                    )
                ),
                'features' => array()
            );
        }
        array_push($cityGeojsonFiles[$state][$city]['features'], $feature);
    }

    return [
        'mainGeojson' => $mainGeojson,
        'stateGeojsonFiles' => $stateGeojsonFiles,
        'countyGeojsonFiles' => $countyGeojsonFiles,
        'cityGeojsonFiles' => $cityGeojsonFiles
    ];
}

function updateGeoJSONFiles($mainGeojson, $stateGeojsonFiles, $countyGeojsonFiles, $cityGeojsonFiles) {
    $mainGeojsonString = 'var json_Updated_Images_CSV_with_Coordinates_2 = ' . json_encode($mainGeojson, JSON_NUMERIC_CHECK) . ';';

    // Update main GeoJSON file
    $mainGeojsonFilePath = "/Applications/XAMPP/xamppfiles/htdocs/PRS/nimda/pages/qgis/data/Updated_Images_CSV_with_Coordinates_2.js";
    if (file_put_contents($mainGeojsonFilePath, $mainGeojsonString) === false) {
        error_log("Failed to write to main GeoJSON file: " . $mainGeojsonFilePath);
    } else {
        error_log("Successfully wrote to main GeoJSON file: " . $mainGeojsonFilePath);
    }

    // Update state files
    foreach ($stateGeojsonFiles as $state => $geojson) {
        $stateVariableName = 'json_' . $state;
        $stateGeojsonString = 'var ' . $stateVariableName . ' = ' . json_encode($geojson, JSON_NUMERIC_CHECK) . ';';
        $stateDir = "/Applications/XAMPP/xamppfiles/htdocs/PRS/nimda/pages/qgis/data/states/{$state}";
        $stateFilePath = "{$stateDir}/{$state}.js";

        if (!is_dir($stateDir)) {
            mkdir($stateDir, 0775, true);
        }

        if (file_put_contents($stateFilePath, $stateGeojsonString) === false) {
            error_log("Failed to write to state file: " . $stateFilePath);
        } else {
            error_log("Successfully wrote to state file: " . $stateFilePath);
        }
    }

    // Update county files
    foreach ($countyGeojsonFiles as $state => $counties) {
        foreach ($counties as $county => $geojson) {
            $countyGeojsonString = 'var json_' . str_replace(' ', '_', $county) . '_County = ' . json_encode($geojson, JSON_NUMERIC_CHECK) . ';';
            $countyDir = "/Applications/XAMPP/xamppfiles/htdocs/PRS/nimda/pages/qgis/data/states/{$state}/county";
            $countyFilePath = "{$countyDir}/{$county}_County.js";

            if (!is_dir($countyDir)) {
                mkdir($countyDir, 0775, true);
            }

            if (file_put_contents($countyFilePath, $countyGeojsonString) === false) {
                error_log("Failed to write to county file: " . $countyFilePath);
            } else {
                error_log("Successfully wrote to county file: " . $countyFilePath);
            }
        }
    }

    // Update city files
    foreach ($cityGeojsonFiles as $state => $cities) {
        foreach ($cities as $city => $geojson) {
            $cityGeojsonString = 'var json_' . str_replace(' ', '_', $city) . ' = ' . json_encode($geojson, JSON_NUMERIC_CHECK) . ';';
            $cityDir = "/Applications/XAMPP/xamppfiles/htdocs/PRS/nimda/pages/qgis/data/states/{$state}/city";
            $cityFilePath = "{$cityDir}/{$city}.js";

            if (!is_dir($cityDir)) {
                mkdir($cityDir, 0775, true);
            }

            if (file_put_contents($cityFilePath, $cityGeojsonString) === false) {
                error_log("Failed to write to city file: " . $cityFilePath);
            } else {
                error_log("Successfully wrote to city file: " . $cityFilePath);
            }
        }
    }
}

function sendGeoJSONUpdate($data) {
    $url = 'http://10.242.170.155/PRS/nimda/pages/qgis/data/update_geojson.php';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("Failed to update GeoJSON file: " . curl_error($ch));
    } else {
        error_log("Update successful: " . $result);
    }
    curl_close($ch);
}

/* 
   __MAIN__
*/

// Check if we are not missing any required fields
if (isset($_FILES['image']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $email = getEmailFromUserId($pdo, $user_id);

    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
        exit;
    }

    $description = $_POST['description'];
    $severity = $_POST['severity']; 
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $upload_date = date('Y-m-d H:i:s');
    $filename = basename($_FILES["image"]["name"]);

    // Set the target directory for the user's uploaded files
    $target_dir = "../uploads/$email/";

    // Ensure the directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0775, true);
    }

    $target_file = $target_dir . $filename;
    $uploadOk = 1;

    // Check if the file already exists
    if (file_exists($target_file)) {
        echo json_encode(['success' => false, 'message' => 'Sorry, file already exists.']);
        $uploadOk = 0;
    }

    // Check file size (optional, limit to 5MB for example)
    if ($_FILES["image"]["size"] > 5000000) {
        echo json_encode(['success' => false, 'message' => 'Sorry, your file is too large.']);
        $uploadOk = 0;
    }

    // Allow certain file formats (optional)
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowedFormats = ["jpg", "png", "jpeg", "gif"];
    if (!in_array($imageFileType, $allowedFormats)) {
        echo json_encode(['success' => false, 'message' => 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.']);
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo json_encode(['success' => false, 'message' => 'Sorry, your file was not uploaded.']);
    } else {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Save the image URL and user ID to the database
            $imageURL = 'http://' . $_SERVER['HTTP_HOST'] . '/PRS/uploads/' . $email . '/' . $filename;
            
            // Get the address components using the Google Geocoding API
            $address = getAddressComponents($latitude, $longitude);
            
            if ($address) {
                $stmt = $pdo->prepare('INSERT INTO images (image_url, id, description, severity, upload_date, latitude, longitude, street, zip, city, county, state) VALUES (:image_url, :id, :description, :severity, :upload_date, :latitude, :longitude, :street, :zip, :city, :county, :state)');
                if ($stmt->execute([
                    'image_url' => $imageURL, 
                    'id' => $user_id, 
                    'description' => $description, 
                    'severity' => $severity, 
                    'upload_date' => $upload_date, 
                    'latitude' => $latitude, 
                    'longitude' => $longitude,
                    'street' => $address['street'],
                    'zip' => $address['zip'],
                    'city' => $address['city'],
                    'county' => $address['county'],
                    'state' => $address['state']
                ])) {
                    $geojsonData = generateGeoJSON($pdo);
                    updateGeoJSONFiles($geojsonData['mainGeojson'], $geojsonData['stateGeojsonFiles'], $geojsonData['countyGeojsonFiles'], $geojsonData['cityGeojsonFiles']);

                    echo json_encode(['success' => true, 'message' => 'The file ' . $filename . ' has been uploaded.']);
                } else {
                    $errorInfo = $stmt->errorInfo();
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $errorInfo[2]]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to retrieve address information.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Sorry, there was an error uploading your file.']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file was uploaded.']);
}
?>
