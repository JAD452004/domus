<?php
session_start();
require_once "data.php";

if (!isset($_SESSION['user_id'])) { 
    header("Location: ../CONNECTION/connexionUser.php"); 
    exit(); 
}

$id_rdv = isset($_GET['rdv']) ? intval($_GET['rdv']) : 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Vérifier que l'utilisateur a accès à ce rendez-vous
if ($role === 'client') {
    $check_sql = "SELECT r.*, m.titre FROM rendez_vous r 
                  JOIN maison m ON r.id_maison = m.id_maison 
                  WHERE r.id_rdv = ? AND r.id_client = ?";
} else {
    $check_sql = "SELECT r.*, m.titre FROM rendez_vous r 
                  JOIN maison m ON r.id_maison = m.id_maison 
                  WHERE r.id_rdv = ? AND m.id_pro = ?";
}

$check_stmt = $db->prepare($check_sql);
$check_stmt->bind_param("ii", $id_rdv, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$rdv = $result->fetch_assoc();

if(!$rdv) {
    die("Accès non autorisé à ce rendez-vous.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussion Privée - DOMUS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0f172a;
            --secondary: #2563eb;
            --secondary-dark: #1d4ed8;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --text-muted: #64748b;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Poppins', sans-serif;
        }

        body { 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, var(--bg-light) 0%, #e6f0ff 100%);
            position: relative;
            overflow: hidden;
        }

        /* Formes flottantes */
        .floating-shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.5;
        }
        
        .shape-1 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, var(--secondary), #3b82f6);
            top: -150px;
            left: -150px;
            animation: float1 20s infinite alternate ease-in-out;
        }
        
        .shape-2 {
            width: 350px;
            height: 350px;
            background: linear-gradient(225deg, var(--secondary-dark), #60a5fa);
            bottom: -100px;
            right: -100px;
            animation: float2 18s infinite alternate ease-in-out;
        }
        
        .shape-3 {
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, #3b82f6, #93c5fd);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: float3 22s infinite alternate ease-in-out;
        }

        @keyframes float1 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(50px, 60px) scale(1.1); }
        }
        
        @keyframes float2 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(-40px, -30px) scale(1.2); }
        }
        
        @keyframes float3 {
            0% { transform: translate(-50%, -50%) scale(1); }
            100% { transform: translate(-45%, -45%) scale(1.15); }
        }

        /* Conteneur principal */
        .discussion-container { 
            width: 100%;
            max-width: 480px; 
            height: 85vh;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 32px;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.08),
                0 8px 24px rgba(37, 99, 235, 0.15);
            display: flex; 
            flex-direction: column; 
            overflow: hidden; 
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.7);
        }

        /* Header */
        .header { 
            padding: 20px 24px; 
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid rgba(37, 99, 235, 0.1);
            z-index: 10;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .back-btn { 
            color: var(--secondary); 
            text-decoration: none; 
            font-size: 1.2rem;
            background: white;
            width: 40px; 
            height: 40px;
            border-radius: 14px;
            display: flex; 
            align-items: center; 
            justify-content: center;
            transition: all 0.2s ease;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }
        
        .back-btn:hover { 
            background: var(--secondary); 
            color: white;
            transform: translateX(-3px);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.25);
        }

        .header-info h2 { 
            margin: 0; 
            font-size: 1.1rem; 
            color: var(--primary); 
            font-weight: 600;
            letter-spacing: -0.3px;
        }
        
        .header-info p { 
            margin: 4px 0 0; 
            font-size: 0.75rem; 
            color: var(--text-muted); 
            display: flex; 
            align-items: center; 
            gap: 6px; 
        }
        
        .status-dot { 
            width: 8px; 
            height: 8px; 
            background: var(--success); 
            border-radius: 50%;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Bouton PDF */
        .pdf-btn-glass {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            letter-spacing: -0.2px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .pdf-btn-glass:hover { 
            transform: translateY(-3px) scale(1.02); 
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.35);
        }

        /* Zone des messages */
        #messages-box { 
            flex: 1; 
            padding: 20px; 
            overflow-y: auto; 
            position: relative;
            background: rgba(248, 250, 252, 0.4);
        }

        #messages-box::-webkit-scrollbar { 
            width: 4px; 
        }
        
        #messages-box::-webkit-scrollbar-track {
            background: transparent;
        }
        
        #messages-box::-webkit-scrollbar-thumb { 
            background: var(--secondary); 
            border-radius: 20px;
        }

        .logo-watermark {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.04;
            pointer-events: none;
            z-index: 0;
            width: 180px;
        }

        .messages-container {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Animation des messages */
        @keyframes messageAppear {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-wrapper {
            animation: messageAppear 0.3s ease-out;
        }

        /* Message du vendeur - BLANC (inchangé) */
        .vendeur-message {
            background: #ffffff;
            color: #1e293b;
            border-radius: 18px 18px 18px 4px;
            padding: 12px 16px;
            max-width: 80%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            word-wrap: break-word;
            position: relative;
        }

        /* Message du client - BLEU FONCÉ DOMUS */
        .client-message {
            background: linear-gradient(135deg, var(--secondary), var(--secondary-dark));
            color: white;
            border-radius: 18px 18px 4px 18px;
            padding: 12px 16px;
            max-width: 80%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            word-wrap: break-word;
            position: relative;
        }

        /* Indicateur de chargement */
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 12px 16px;
            background: white;
            border-radius: 20px;
            align-self: flex-start;
            margin-top: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .typing-indicator span {
            width: 6px;
            height: 6px;
            background: var(--secondary);
            border-radius: 50%;
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
            30% { transform: translateY(-8px); opacity: 1; }
        }

        /* Indicateur de chargement des messages */
        .loading-messages {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
        }

        .loading-messages i {
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Zone de saisie */
        .input-area { 
            padding: 16px 20px; 
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(37, 99, 235, 0.1);
            display: flex; 
            flex-direction: column;
            gap: 10px; 
            position: relative;
            z-index: 5;
        }

        /* Prévisualisation fichier */
        .file-preview {
            display: none;
            background: white;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 8px;
            border: 1px solid #e2e8f0;
            position: relative;
            animation: slideIn 0.3s ease;
        }

        .file-preview.active {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .file-preview-img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid var(--secondary);
        }

        .file-preview-info {
            flex: 1;
            font-size: 0.85rem;
            color: var(--primary);
        }

        .file-preview-name {
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--primary);
        }

        .file-preview-size {
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        .file-preview-remove {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .file-preview-remove:hover {
            background: #fee2e2;
        }

        /* Ligne d'input */
        .input-row {
            display: flex;
            gap: 8px;
            align-items: center;
            position: relative;
        }

        .input-wrapper {
            flex: 1;
            background: white;
            border-radius: 30px;
            padding: 4px 4px 4px 20px;
            display: flex;
            align-items: center;
            box-shadow: 
                inset 0 2px 5px rgba(0, 0, 0, 0.02),
                0 4px 15px rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.2s;
        }

        .input-wrapper:focus-within {
            border-color: var(--secondary);
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.15);
        }

        .input-area input { 
            flex: 1;
            border: none;
            outline: none;
            font-size: 0.95rem;
            background: transparent;
            font-family: 'Poppins', sans-serif;
            padding: 12px 0;
            color: var(--primary);
        }

        .input-area input::placeholder {
            color: var(--text-muted);
            font-weight: 300;
        }

        /* Bouton attachement */
        .attach-btn {
            background: white;
            border: 1px solid #e2e8f0;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--secondary);
            font-size: 1.1rem;
            transition: all 0.2s;
            position: relative;
            z-index: 20;
        }

        .attach-btn:hover {
            background: var(--secondary);
            color: white;
            border-color: var(--secondary);
        }

        /* Bouton envoi */
        .send-btn { 
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            color: white; 
            border: none; 
            width: 52px; 
            height: 52px; 
            border-radius: 50%; 
            cursor: pointer; 
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
            transition: all 0.3s ease;
            font-size: 1.2rem;
            position: relative;
            z-index: 20;
        }

        .send-btn:hover { 
            transform: scale(1.1) rotate(5deg); 
            box-shadow: 0 12px 25px rgba(37, 99, 235, 0.35);
        }
        
        .send-btn:active {
            transform: scale(0.95);
        }

        .send-btn.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .send-btn.loading i {
            animation: spin 1s linear infinite;
        }

        /* Menu des types de fichiers */
        .file-menu {
            display: none;
            position: absolute;
            bottom: 70px;
            left: 0;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            padding: 8px;
            z-index: 1000;
            border: 1px solid #e2e8f0;
            min-width: 200px;
        }

        .file-menu.active {
            display: block;
            animation: menuAppear 0.2s ease-out;
        }

        @keyframes menuAppear {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .file-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            cursor: pointer;
            border-radius: 12px;
            transition: all 0.2s;
        }

        .file-menu-item:hover {
            background: var(--bg-light);
        }

        .file-menu-item i {
            width: 24px;
            text-align: center;
            font-size: 1.2rem;
        }

        .file-menu-item span {
            font-weight: 500;
            color: var(--primary);
            font-size: 0.9rem;
        }

        .image-icon { color: var(--success); }
        .pdf-icon { color: var(--danger); }
        .doc-icon { color: var(--secondary); }
        .other-icon { color: var(--text-muted); }

        #file-input {
            display: none;
        }

        /* Message d'accueil */
        .welcome-message {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin: 20px 0;
            font-style: italic;
        }

        /* Indicateurs de vue */
        .message-status {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
            font-size: 0.65rem;
            margin-top: 4px;
            opacity: 0.8;
        }

        .status-sent {
            color: #94a3b8;
        }

        .status-delivered {
            color: #f59e0b;
        }

        .status-seen {
            color: var(--secondary);
        }

        /* Overlay pour le menu */
        .menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 999;
        }

        .menu-overlay.active {
            display: block;
        }

        /* Responsive */
        @media (max-width: 500px) {
            .discussion-container { 
                height: 100vh; 
                max-width: 100%;
                border-radius: 0; 
                border: none;
            }
            
            .file-menu {
                left: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Overlay pour fermer le menu -->
    <div id="menu-overlay" class="menu-overlay" onclick="closeFileMenu()"></div>

    <!-- Formes flottantes -->
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>

    <div class="discussion-container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <a href="javascript:history.back()" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>
                <div class="header-info">
                    <h2><?php echo htmlspecialchars($rdv['titre']); ?></h2>
                    <p><span class="status-dot"></span> Discussion en direct</p>
                </div>
            </div>
            <a href="generer_recu.php?id_rdv=<?php echo $id_rdv; ?>" target="_blank" class="pdf-btn-glass">
                <i class="fa-solid fa-file-invoice"></i> Reçu
            </a>
        </div>
        
        <!-- Zone des messages -->
        <div id="messages-box">
            <img src="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" class="logo-watermark" alt="DOMUS">
            <div class="messages-container" id="messages-container">
                <div class="loading-messages">
                    <i class="fa-solid fa-circle-notch"></i> Chargement des messages...
                </div>
            </div>
        </div>

        <!-- Zone de saisie -->
        <div class="input-area">
            <!-- Prévisualisation du fichier -->
            <div id="file-preview" class="file-preview">
                <img id="preview-image" class="file-preview-img" src="" alt="Aperçu">
                <div class="file-preview-info">
                    <div id="preview-name" class="file-preview-name"></div>
                    <div id="preview-size" class="file-preview-size"></div>
                </div>
                <button class="file-preview-remove" onclick="clearFileSelection()">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>

            <div class="input-row">
                <!-- Bouton d'attachement -->
                <button class="attach-btn" onclick="toggleFileMenu(event)">
                    <i class="fa-solid fa-paperclip"></i>
                </button>

                <!-- Menu des types de fichiers -->
                <div id="file-menu" class="file-menu">
                    <div class="file-menu-item" onclick="selectFileType('image/*')">
                        <i class="fa-solid fa-image image-icon"></i>
                        <span>Image</span>
                    </div>
                    <div class="file-menu-item" onclick="selectFileType('application/pdf')">
                        <i class="fa-solid fa-file-pdf pdf-icon"></i>
                        <span>PDF</span>
                    </div>
                    <div class="file-menu-item" onclick="selectFileType('.doc,.docx')">
                        <i class="fa-solid fa-file-word doc-icon"></i>
                        <span>Document</span>
                    </div>
                    <div class="file-menu-item" onclick="selectFileType('*/*')">
                        <i class="fa-solid fa-file other-icon"></i>
                        <span>Autre fichier</span>
                    </div>
                </div>

                <!-- Champ de texte -->
                <div class="input-wrapper">
                    <input type="text" id="msg_input" placeholder="Écrivez votre message..." autocomplete="off">
                </div>
                
                <!-- Bouton d'envoi -->
                <button class="send-btn" id="send_btn">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Input file caché -->
    <input type="file" id="file-input" style="display: none;">

    <script>
        const idRdv = <?php echo $id_rdv; ?>;
        const userId = <?php echo $user_id; ?>;
        const userRole = '<?php echo $role; ?>';
        
        const messagesBox = document.getElementById('messages-box');
        const messagesContainer = document.getElementById('messages-container');
        const msgInput = document.getElementById('msg_input');
        const sendBtn = document.getElementById('send_btn');
        
        let lastMessageCount = 0;
        let selectedFile = null;
        let isLoading = false;
        let lastMessageId = 0;
        let updateInterval;

        // Éléments pour la prévisualisation et le menu
        const filePreview = document.getElementById('file-preview');
        const previewImage = document.getElementById('preview-image');
        const previewName = document.getElementById('preview-name');
        const previewSize = document.getElementById('preview-size');
        const fileMenu = document.getElementById('file-menu');
        const fileInput = document.getElementById('file-input');
        const menuOverlay = document.getElementById('menu-overlay');

        // Fonctions pour la gestion des fichiers
        function toggleFileMenu(event) {
            if (event) {
                event.stopPropagation();
            }
            fileMenu.classList.toggle('active');
            menuOverlay.classList.toggle('active');
        }

        function closeFileMenu() {
            fileMenu.classList.remove('active');
            menuOverlay.classList.remove('active');
        }

        function selectFileType(accept) {
            fileInput.accept = accept;
            fileInput.click();
            closeFileMenu();
        }

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                selectedFile = file;
                
                // Afficher la prévisualisation
                previewName.textContent = file.name;
                previewSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
                
                // Si c'est une image, afficher l'aperçu
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        previewImage.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewImage.style.display = 'none';
                }
                
                filePreview.classList.add('active');
            }
        });

        function clearFileSelection() {
            selectedFile = null;
            fileInput.value = '';
            filePreview.classList.remove('active');
        }

        // Charger les messages (uniquement les nouveaux)
        function loadNewMessages() {
            if (isLoading) return;
            
            fetch(`get_new_messages.php?id_rdv=${idRdv}&last_id=${lastMessageId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.messages && data.messages.length > 0) {
                        // Ajouter les nouveaux messages
                        data.messages.forEach(msg => {
                            appendMessage(msg);
                            if (msg.id > lastMessageId) {
                                lastMessageId = msg.id;
                            }
                        });
                        
                        // Marquer comme vus
                        markMessagesAsSeen();
                        
                        // SCROLL AUTOMATIQUE VERS LE BAS À CHAQUE NOUVEAU MESSAGE
                        scrollToBottom();
                    }
                    
                    lastMessageCount = data.total;
                })
                .catch(error => console.error('Erreur:', error));
        }

        // Fonction pour scroller en bas
        function scrollToBottom() {
            setTimeout(() => {
                messagesBox.scrollTo({
                    top: messagesBox.scrollHeight,
                    behavior: 'smooth' // Défilement fluide
                });
            }, 100);
        }

        // Fonction pour ajouter un message au DOM
        function appendMessage(msg) {
            const isVendeur = (msg.role_exp === 'vendeur');
            const isMe = (msg.id_expediteur == userId);
            
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message-wrapper';
            messageDiv.dataset.messageId = msg.id;
            messageDiv.style.display = 'flex';
            messageDiv.style.flexDirection = 'column';
            messageDiv.style.alignItems = isVendeur ? 'flex-start' : 'flex-end';
            messageDiv.style.marginBottom = '16px';
            messageDiv.style.width = '100%';
            
            // Nom de l'expéditeur
            const nameSpan = document.createElement('span');
            nameSpan.style.fontSize = '0.7rem';
            nameSpan.style.color = isVendeur ? '#64748b' : '#2563eb';
            nameSpan.style.fontWeight = '700';
            nameSpan.style.marginBottom = '4px';
            nameSpan.style.textTransform = 'uppercase';
            nameSpan.innerHTML = isVendeur ? 
                '<i class="fa-solid fa-store"></i> ' + (msg.nom_vendeur || 'Vendeur') : 
                (msg.nom_client || 'Client') + ' <i class="fa-solid fa-user"></i>';
            
            // Bulle de message
            const bubbleDiv = document.createElement('div');
            bubbleDiv.className = isVendeur ? 'vendeur-message' : 'client-message';
            
            // Contenu du message (avec sauts de ligne)
            const contentP = document.createElement('div');
            contentP.style.whiteSpace = 'pre-wrap';
            contentP.style.wordBreak = 'break-word';
            contentP.innerHTML = msg.message;
            bubbleDiv.appendChild(contentP);
            
            // Fichier joint si présent
            if (msg.type_fichier && msg.chemin_fichier) {
                const fileDiv = document.createElement('div');
                fileDiv.style.marginTop = '8px';
                
                if (msg.type_fichier === 'image') {
                    fileDiv.innerHTML = `<a href="${msg.chemin_fichier}" target="_blank">
                        <img src="${msg.chemin_fichier}" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 2px solid rgba(255,255,255,0.2);">
                    </a>`;
                } else {
                    const icon = msg.type_fichier === 'pdf' ? 'fa-file-pdf' : 
                                (msg.type_fichier === 'word' ? 'fa-file-word' : 'fa-file');
                    const iconColor = msg.type_fichier === 'pdf' ? '#ef4444' : 
                                     (msg.type_fichier === 'word' ? '#2563eb' : 'inherit');
                    
                    fileDiv.innerHTML = `<a href="${msg.chemin_fichier}" target="_blank" style="display: flex; align-items: center; gap: 8px; color: ${isVendeur ? '#2563eb' : 'white'}; text-decoration: none; background: rgba(0,0,0,0.1); padding: 8px 12px; border-radius: 8px;">
                        <i class="fa-solid ${icon}" style="color: ${iconColor};"></i>
                        <span style="flex: 1;">${msg.nom_fichier_original || msg.chemin_fichier.split('/').pop()}</span>
                        <i class="fa-solid fa-download"></i>
                    </a>`;
                }
                bubbleDiv.appendChild(fileDiv);
            }
            
            // Pied du message (heure + statut)
            const footerDiv = document.createElement('div');
            footerDiv.style.display = 'flex';
            footerDiv.style.alignItems = 'center';
            footerDiv.style.justifyContent = 'flex-end';
            footerDiv.style.gap = '4px';
            footerDiv.style.marginTop = '4px';
            footerDiv.style.fontSize = '0.65rem';
            footerDiv.style.opacity = '0.8';
            
            const timeSpan = document.createElement('span');
            const date = new Date(msg.date_envoi);
            timeSpan.textContent = date.toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'});
            footerDiv.appendChild(timeSpan);
            
            // Statut pour mes messages
            if (isMe) {
                const statusSpan = document.createElement('span');
                let statusIcon = '';
                let statusClass = '';
                
                switch(msg.statut) {
                    case 'vu':
                        statusIcon = 'fa-solid fa-check-double';
                        statusClass = 'status-seen';
                        break;
                    case 'delivre':
                        statusIcon = 'fa-solid fa-check-double';
                        statusClass = 'status-delivered';
                        break;
                    default:
                        statusIcon = 'fa-solid fa-check';
                        statusClass = 'status-sent';
                }
                
                statusSpan.innerHTML = `<i class="${statusIcon}"></i>`;
                statusSpan.className = statusClass;
                footerDiv.appendChild(statusSpan);
            }
            
            bubbleDiv.appendChild(footerDiv);
            messageDiv.appendChild(nameSpan);
            messageDiv.appendChild(bubbleDiv);
            
            messagesContainer.appendChild(messageDiv);
        }

        // Charger tous les messages au démarrage
        function loadAllMessages() {
            isLoading = true;
            
            fetch(`recuperer_messages.php?id_rdv=${idRdv}`)
                .then(res => res.text())
                .then(data => {
                    if (data.trim() === '') {
                        messagesContainer.innerHTML = '<div class="welcome-message">Aucun message pour l\'instant. Soyez le premier à écrire !</div>';
                    } else {
                        messagesContainer.innerHTML = data;
                        
                        // Trouver le dernier ID
                        const lastMsg = messagesContainer.querySelector('.message-wrapper:last-child');
                        if (lastMsg) {
                            const msgId = lastMsg.dataset.messageId;
                            if (msgId) lastMessageId = parseInt(msgId);
                        }
                        
                        lastMessageCount = messagesContainer.children.length;
                    }
                    
                    // Marquer comme vus
                    setTimeout(markMessagesAsSeen, 500);
                    
                    // Scroll en bas au chargement
                    scrollToBottom();
                    isLoading = false;
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    messagesContainer.innerHTML = '<div class="welcome-message">Erreur de chargement des messages</div>';
                    isLoading = false;
                });
        }

        // Envoyer un message
        function sendMessage() {
            let msg = msgInput.value.trim();
            
            if (msg === "" && !selectedFile) return;

            // Ajouter l'indicateur de chargement
            sendBtn.classList.add('loading');
            sendBtn.innerHTML = '<i class="fa-solid fa-circle-notch"></i>';
            sendBtn.disabled = true;
            
            if (selectedFile) {
                // Envoi avec fichier
                const formData = new FormData();
                formData.append('id_rdv', idRdv);
                formData.append('message', msg);
                formData.append('fichier', selectedFile);

                fetch('upload_chat.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        msgInput.value = "";
                        clearFileSelection();
                        // Attendre un peu puis charger les nouveaux messages
                        setTimeout(() => {
                            loadNewMessages();
                            // SCROLL AUTOMATIQUE APRÈS ENVOI
                            scrollToBottom();
                        }, 500);
                    } else {
                        alert('Erreur: ' + data.error);
                    }
                    
                    // Restaurer le bouton
                    sendBtn.classList.remove('loading');
                    sendBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
                    sendBtn.disabled = false;
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    sendBtn.classList.remove('loading');
                    sendBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
                    sendBtn.disabled = false;
                });
            } else {
                // Envoi de texte seul
                fetch('envoyer_message.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `id_rdv=${idRdv}&message=${encodeURIComponent(msg)}`
                })
                .then(() => {
                    msgInput.value = "";
                    // Attendre un peu puis charger les nouveaux messages
                    setTimeout(() => {
                        loadNewMessages();
                        // SCROLL AUTOMATIQUE APRÈS ENVOI
                        scrollToBottom();
                    }, 500);
                    
                    // Restaurer le bouton
                    sendBtn.classList.remove('loading');
                    sendBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
                    sendBtn.disabled = false;
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    sendBtn.classList.remove('loading');
                    sendBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
                    sendBtn.disabled = false;
                });
            }
        }

        // Fonction pour marquer les messages comme vus
        function markMessagesAsSeen() {
            if (document.hidden) return;
            
            fetch('marquer_messages_vus.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id_rdv=${idRdv}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && (data.vu > 0 || data.delivre > 0)) {
                    // Mettre à jour les statuts visuellement
                    updateMessageStatus();
                }
            })
            .catch(error => console.error('Erreur:', error));
        }

        // Mettre à jour les statuts des messages
        function updateMessageStatus() {
            // Recharger les messages pour voir les statuts mis à jour
            loadNewMessages();
        }

        // Événements
        sendBtn.onclick = sendMessage;
        msgInput.onkeypress = (e) => { 
            if(e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage(); 
            }
        };

        // Fermer le menu fichier en cliquant sur l'overlay
        menuOverlay.onclick = closeFileMenu;

        // Empêcher la propagation des clics dans le menu
        fileMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Charger tous les messages au démarrage
        loadAllMessages();
        
        // Démarrer l'actualisation automatique (toutes les 3 secondes)
        updateInterval = setInterval(loadNewMessages, 3000);

        // Marquer les messages comme vus quand la page est visible
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                markMessagesAsSeen();
            }
        });

        // Marquer les messages comme vus quand l'utilisateur fait défiler
        let scrollTimeout;
        messagesBox.addEventListener('scroll', function() {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                markMessagesAsSeen();
            }, 500);
        });

        // Nettoyer l'intervalle quand on quitte la page
        window.addEventListener('beforeunload', function() {
            clearInterval(updateInterval);
        });
    </script>
</body>
</html>