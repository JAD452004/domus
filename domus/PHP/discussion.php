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
    <!-- Police Inter plus moderne -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Inter', sans-serif;
        }

        body { 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #f0f5ff 0%, #e6f0ff 100%);
            position: relative;
            overflow: hidden;
        }

        /* Formes flottantes améliorées */
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
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            top: -150px;
            left: -150px;
            animation: float1 20s infinite alternate ease-in-out;
        }
        
        .shape-2 {
            width: 350px;
            height: 350px;
            background: linear-gradient(225deg, #1d4ed8, #60a5fa);
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

        /* Conteneur principal avec design plus moderne */
        .discussion-container { 
            width: 100%;
            max-width: 480px; 
            height: 85vh;
            background: rgba(255, 255, 255, 0.85);
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

        /* Header amélioré */
        .header { 
            padding: 20px 24px; 
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.6);
            border-bottom: 1px solid rgba(37, 99, 235, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .back-btn { 
            color: #2563eb; 
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
            background: #2563eb; 
            color: white;
            transform: translateX(-3px);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.25);
        }

        .header-info h2 { 
            margin: 0; 
            font-size: 1.1rem; 
            color: #1e293b; 
            font-weight: 600;
            letter-spacing: -0.3px;
        }
        
        .header-info p { 
            margin: 4px 0 0; 
            font-size: 0.75rem; 
            color: #64748b; 
            display: flex; 
            align-items: center; 
            gap: 6px; 
        }
        
        .status-dot { 
            width: 8px; 
            height: 8px; 
            background: #10b981; 
            border-radius: 50%;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Bouton PDF plus élégant */
        .pdf-btn-glass {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
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
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            letter-spacing: -0.2px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .pdf-btn-glass:hover { 
            transform: translateY(-3px) scale(1.02); 
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.35);
        }
        
        .pdf-btn-glass i {
            font-size: 1rem;
        }

        /* Zone Messages */
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
            background: #2563eb; 
            border-radius: 20px;
            opacity: 0.5;
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

        /* Bulles de messages améliorées */
        .msg-wrapper {
            display: flex;
            flex-direction: column;
            max-width: 80%;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .msg-name { 
            font-size: 0.7rem; 
            font-weight: 600; 
            color: #64748b; 
            margin-bottom: 4px; 
            margin-left: 8px;
            letter-spacing: -0.2px;
            text-transform: uppercase;
        }

        .bubble {
            padding: 14px 18px;
            border-radius: 24px;
            font-size: 0.95rem;
            line-height: 1.5;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            word-wrap: break-word;
            font-weight: 400;
        }

        /* Message reçu */
        .vendeur-msg { 
            align-self: flex-start; 
        }
        
        .vendeur-msg .bubble {
            background: white;
            color: #1e293b;
            border-bottom-left-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(226, 232, 240, 0.6);
        }

        /* Message envoyé */
        .client-msg { 
            align-self: flex-end; 
            align-items: flex-end; 
        }
        
        .client-msg .bubble {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: white;
            border-bottom-right-radius: 6px;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.2);
        }

        /* Date des messages (optionnelle) */
        .msg-time {
            font-size: 0.65rem;
            color: #94a3b8;
            margin-top: 4px;
            margin-left: 8px;
            margin-right: 8px;
        }

        /* Zone de saisie améliorée */
        .input-area { 
            padding: 20px 24px; 
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(37, 99, 235, 0.1);
            display: flex; 
            gap: 12px; 
            align-items: center;
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
            border-color: #2563eb;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.15);
        }

        .input-area input { 
            flex: 1;
            border: none;
            outline: none;
            font-size: 0.95rem;
            background: transparent;
            font-family: 'Inter', sans-serif;
            padding: 12px 0;
            color: #1e293b;
        }

        .input-area input::placeholder {
            color: #94a3b8;
            font-weight: 300;
        }

        .input-area button { 
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
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
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-size: 1.2rem;
        }

        .input-area button:hover { 
            transform: scale(1.1) rotate(5deg); 
            box-shadow: 0 12px 25px rgba(37, 99, 235, 0.35);
        }
        
        .input-area button:active {
            transform: scale(0.95);
        }

        /* Animation de chargement */
        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 12px 16px;
            background: white;
            border-radius: 20px;
            align-self: flex-start;
            margin-top: 8px;
        }
        
        .typing-indicator span {
            width: 6px;
            height: 6px;
            background: #94a3b8;
            border-radius: 50%;
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
            30% { transform: translateY(-8px); opacity: 1; }
        }

        @media (max-width: 500px) {
            .discussion-container { 
                height: 100vh; 
                max-width: 100%;
                border-radius: 0; 
                border: none;
            }
            
            .shape-1, .shape-2, .shape-3 {
                opacity: 0.3;
            }
        }

        /* Message de bienvenue */
        .welcome-message {
            text-align: center;
            color: #94a3b8;
            font-size: 0.85rem;
            margin: 20px 0;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- Formes flottantes -->
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>

    <div class="discussion-container">
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
        
        <div id="messages-box">
            <img src="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" class="logo-watermark" alt="DOMUS">
            <div class="messages-container" id="messages-container"></div>
        </div>

        <div class="input-area">
            <div class="input-wrapper">
                <input type="text" id="msg_input" placeholder="Écrivez votre message..." autocomplete="off">
            </div>
            <button id="send_btn"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>

    <script>
        // VOTRE JS ORIGINAL - INTACT
        const idRdv = <?php echo $id_rdv; ?>;
        const messagesBox = document.getElementById('messages-box');
        const messagesContainer = document.getElementById('messages-container');
        const msgInput = document.getElementById('msg_input');
        const sendBtn = document.getElementById('send_btn');
        let lastMessageCount = 0;

        function loadMessages() {
            fetch(`recuperer_messages.php?id_rdv=${idRdv}`)
                .then(res => res.text())
                .then(data => {
                    const wasAtBottom = messagesBox.scrollHeight - messagesBox.scrollTop <= messagesBox.clientHeight + 100;
                    messagesContainer.innerHTML = data;
                    
                    const msgCount = messagesContainer.children.length;
                    if (msgCount > lastMessageCount && wasAtBottom) {
                        setTimeout(() => {
                            messagesBox.scrollTop = messagesBox.scrollHeight;
                        }, 100);
                    }
                    lastMessageCount = msgCount;
                })
                .catch(error => console.error('Erreur:', error));
        }

        function sendMessage() {
            let msg = msgInput.value.trim();
            if(msg === "") return;

            sendBtn.disabled = true;
            
            fetch('envoyer_message.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id_rdv=${idRdv}&message=${encodeURIComponent(msg)}`
            })
            .then(() => {
                msgInput.value = "";
                sendBtn.disabled = false;
                loadMessages();
                messagesBox.scrollTop = messagesBox.scrollHeight;
            })
            .catch(error => {
                console.error('Erreur:', error);
                sendBtn.disabled = false;
            });
        }

        sendBtn.onclick = sendMessage;
        msgInput.onkeypress = (e) => { 
            if(e.key === 'Enter') {
                e.preventDefault();
                sendMessage(); 
            }
        };

        loadMessages();
        setInterval(loadMessages, 2000);
    </script>
</body>
</html>