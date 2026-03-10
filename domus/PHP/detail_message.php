<?php
session_start();
require_once "data.php";

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

// Vérifier si l'ID est présent
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID manquant");
}

$id_message = intval($_GET['id']);

// Récupérer le message
$sql = "SELECT * FROM contact WHERE id_contact = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $id_message);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Message non trouvé");
}

$message = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message #<?php echo $message['id_contact']; ?> | DOMUS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f8fafc;
            padding: 40px;
            color: #1e293b;
        }
        .message-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .message-header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .message-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 10px;
        }
        .message-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .info-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 1rem;
            font-weight: 500;
            color: #1e293b;
        }
        .message-content {
            line-height: 1.6;
            font-size: 1.1rem;
            color: #334155;
            white-space: pre-wrap;
            background: #f8fafc;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #2563eb;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 30px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .back-button:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="message-container">
        <div class="message-header">
            <h1 class="message-title"><?php echo htmlspecialchars($message['sujet']); ?></h1>
        </div>
        
        <div class="message-info">
            <div class="info-item">
                <span class="info-label">Expéditeur</span>
                <span class="info-value"><?php echo htmlspecialchars($message['nom']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email</span>
                <span class="info-value"><?php echo htmlspecialchars($message['email']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Type</span>
                <span class="info-value"><?php echo ucfirst($message['user_type']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Date d'envoi</span>
                <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?></span>
            </div>
        </div>
        
        <div class="message-content">
            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
        </div>
        
        <button onclick="window.close()" class="back-button">
            <i class="fa-solid fa-arrow-left"></i>
            Fermer
        </button>
    </div>
</body>
</html>
<?php
$stmt->close();
?>