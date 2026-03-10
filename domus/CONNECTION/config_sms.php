<?php
// config_sms.php - Configuration SMS TextBee

$textbee_api_key = '64f33a16-11a6-42d7-a549-b23c358ead94';
$textbee_device_id = '68f433e86a418a16ecb72532';
$textbee_base_url = 'https://api.textbee.dev/api/v1';

/**
 * Formate le numéro de téléphone pour la Côte d'Ivoire
 */
function formatPhoneForTextBee($phone) {
    // Nettoyer le numéro
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Formater pour Côte d'Ivoire
    if (substr($phone, 0, 2) === '07' || substr($phone, 0, 2) === '05') {
        $phone = '+225' . substr($phone, 1);
    } elseif (substr($phone, 0, 3) === '225') {
        $phone = '+' . $phone;
    } elseif (substr($phone, 0, 1) === '0') {
        $phone = '+225' . substr($phone, 1);
    } elseif (substr($phone, 0, 1) !== '+') {
        $phone = '+' . $phone;
    }
    
    return $phone;
}

/**
 * Envoie un SMS via TextBee (VRAI ENVOI)
 */
function sendSMSviaTextBee($phone, $message) {
    global $textbee_api_key, $textbee_device_id, $textbee_base_url;
    
    $formatted_phone = formatPhoneForTextBee($phone);
    
    // Log pour déboguer
    error_log("=== TENTATIVE D'ENVOI SMS RÉEL ===");
    error_log("Numéro formaté: $formatted_phone");
    error_log("Message: $message");
    
    // Données selon l'API TextBee
    $data = [
        'recipients' => [$formatted_phone],
        'message' => $message
    ];
    
    // URL complète
    $url = $textbee_base_url . '/gateway/devices/' . $textbee_device_id . '/send-sms';
    
    error_log("URL: $url");
    
    // Configuration cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $textbee_api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Exécution
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Log du résultat
    error_log("HTTP Code: $httpCode");
    error_log("Réponse: " . substr($response, 0, 500));
    
    if ($error) {
        error_log("cURL Error: $error");
    }
    
    // Code 200 ou 201 = SUCCÈS
    if ($httpCode === 200 || $httpCode === 201) {
        error_log(" SMS ENVOYÉ AVEC SUCCÈS !");
        return ['success' => true, 'message' => 'SMS envoyé avec succès'];
    } else {
        error_log(" ÉCHEC DE L'ENVOI SMS");
        return [
            'success' => false, 
            'error' => "Erreur d'envoi SMS",
            'http_code' => $httpCode,
            'response' => $response
        ];
    }
}

/**
 * Génère un code OTP à 6 chiffres
 */
function generateOTP($length = 6) {
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}
?>