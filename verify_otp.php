<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = $_POST['otp'];

    if ($enteredOtp == $_SESSION['otp']) {
        // OTP verified, register user in Keycloak
        $tokenUrl = 'http://localhost:8080/realms/secupass/protocol/openid-connect/token';
        $clientId = 'php';
        $clientSecret = 'nu9m7gb8bzpST8j5nKeEX05F5QhaCZLv';
        $registerUrl = 'http://localhost:8080/admin/realms/secupass/users';

        // Retrieve user details from session
        $userDetails = $_SESSION['user_details'];

        // Get admin access token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'openid',
        ]));

        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);

        if (isset($data['access_token'])) {
            $adminToken = $data['access_token'];

            // Register new user in Keycloak
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $registerUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $adminToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'username' => $userDetails['username'],
                'email' => $userDetails['email'],
                'firstName' => $userDetails['first_name'],
                'lastName' => $userDetails['last_name'],
                'enabled' => true,
                'emailVerified' => true,  // Set email verified to true
                'credentials' => [
                    [
                        'type' => 'password',
                        'value' => $userDetails['password'],
                    ]
                ]
            ]));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code
            curl_close($ch);

            if ($httpCode == 201) {
                // Register successful, now save to SQL
                $pdo = new PDO('mysql:host=localhost;dbname=appid', 'root', '');
                $stmt = $pdo->prepare('INSERT INTO users (username, app_id) VALUES (:username, :app_id)');
                $appId = uniqid(); // Generate a unique app ID
                $stmt->execute([
                    ':username' => $userDetails['username'],
                    ':app_id' => $appId
                ]);

                echo 'Registration successful and email verified.';

                // Clear session and redirect to login
                session_destroy();
                header("Location: login.php");
                exit();
            } else {
                echo 'Failed to register user. HTTP Code: ' . $httpCode;
            }
        } else {
            echo 'Failed to obtain admin access token.';
        }
    } else {
        echo 'Invalid OTP.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
</head>
<body>
    <h2>Verify OTP</h2>
    <form method="POST">
        <input type="text" name="otp" placeholder="OTP" required>
        <button type="submit">Verify OTP</button>
    </form>
</body>
</html>
