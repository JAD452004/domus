<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOMUS - Connexion & Inscription</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" type="image/png">

    <style>
        :root {
            --primary: #2563eb; 
            --primary-hover: #1d4ed8;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --danger: #ef4444;
            --success: #10b981;
            --cat-blue: #3b82f6;
            --cat-dark-blue: #1e40af;
            --cat-light-blue: #93c5fd;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--bg-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }

        /* ALERTS */
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            animation: slideInRight 0.4s ease;
            width: 90%;
            max-width: 350px;
            pointer-events: none;
        }

        .alert-container .alert { pointer-events: auto; }

        .alert {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            border-left:5px solid #ccc;
        }

        .alert.error { border-left-color: var(--danger); color: var(--danger); }
        .alert.success { border-left-color: #10b981; color: #10b981; }
        .alert.info { border-left-color: var(--primary); color: var(--primary); }
        .alert span { color: #333; font-size: 14px; font-weight: 500; }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* PERSONNAGE CHAT BLEU */
        .cat-container {
            position: fixed;
            bottom: 20px;
            left: 20px;
            width: 120px;
            height: 120px;
            z-index: 9999;
            pointer-events: auto;
            filter: drop-shadow(0 10px 15px rgba(37, 99, 235, 0.3));
            animation: catAppear 0.8s ease-out forwards;
            cursor: pointer;
        }

        @keyframes catAppear {
            0% {
                transform: translateX(-50px) translateY(50px) scale(0);
                opacity: 0;
            }
            100% {
                transform: translateX(0) translateY(0) scale(1);
                opacity: 1;
            }
        }

        .cat {
            width: 100%;
            height: 100%;
            transition: all 0.3s ease;
        }

        .cat:hover {
            transform: scale(1.1);
            filter: drop-shadow(0 15px 20px rgba(37, 99, 235, 0.5));
        }

        .cat-body { animation: catBreathe 3s ease-in-out infinite; }

        @keyframes catBreathe {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.03); }
        }

        .cat-head {
            animation: catLook 8s ease-in-out infinite;
            transform-origin: center center;
        }

        @keyframes catLook {
            0%, 100% { transform: rotate(0deg); }
            20% { transform: rotate(5deg); }
            40% { transform: rotate(-5deg); }
            60% { transform: rotate(3deg); }
            80% { transform: rotate(-3deg); }
        }

        .cat-ears { animation: earsTwitch 4s ease-in-out infinite; }

        @keyframes earsTwitch {
            0%, 100% { transform: rotate(0deg); }
            10% { transform: rotate(8deg); }
            20% { transform: rotate(-8deg); }
            30% { transform: rotate(0deg); }
        }

        .cat-tail {
            animation: tailSway 3s ease-in-out infinite;
            transform-origin: left;
        }

        @keyframes tailSway {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(15deg); }
            75% { transform: rotate(-15deg); }
        }

        .cat-eye-left, .cat-eye-right { animation: catBlink 5s ease-in-out infinite; }

        @keyframes catBlink {
            0%, 45%, 55%, 100% { r: 6; }
            50% { r: 2; }
        }

        .cat-paw-left, .cat-paw-right { animation: pawPat 4s ease-in-out infinite; }

        @keyframes pawPat {
            0%, 100% { transform: translateY(0); }
            25% { transform: translateY(-2px); }
            75% { transform: translateY(1px); }
        }

        .cat-speech {
            position: absolute;
            top: -50px;
            left: 50px;
            background: white;
            padding: 10px 18px;
            border-radius: 25px 25px 25px 5px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            color: var(--text-dark);
            border: 3px solid var(--primary);
            opacity: 1;
            transition: all 0.3s ease;
            pointer-events: none;
            animation: speechPop 0.5s ease-out;
            z-index: 30;
        }

        .cat-speech::before {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 20px;
            width: 0;
            height: 0;
            border-left: 12px solid transparent;
            border-right: 5px solid transparent;
            border-top: 12px solid white;
        }

        .cat-speech::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 20px;
            width: 0;
            height: 0;
            border-left: 12px solid transparent;
            border-right: 5px solid transparent;
            border-top: 12px solid var(--primary);
            z-index: -1;
        }

        .cat-thought {
            position: absolute;
            top: -50px;
            left: 50px;
            background: #f1f5f9;
            padding: 10px 18px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            color: var(--text-dark);
            border: 3px solid var(--cat-dark-blue);
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
            z-index: 30;
        }

        .cat-thought::before {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 20px;
            width: 0;
            height: 0;
            border-left: 12px solid transparent;
            border-right: 5px solid transparent;
            border-top: 12px solid #f1f5f9;
        }

        @keyframes speechPop {
            0% { transform: scale(0); opacity: 0; }
            80% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }

        .cat-paw-cover-left, .cat-paw-cover-right {
            opacity: 0;
            transition: all 0.3s ease;
        }

        .cat-paw-cover-left.covering, .cat-paw-cover-right.covering {
            opacity: 1;
            animation: pawCover 0.3s ease-out;
        }

        @keyframes pawCover {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .cat-eye-closed-left, .cat-eye-closed-right {
            opacity: 0;
            transition: all 0.3s ease;
        }

        .cat-eye-closed-left.closed, .cat-eye-closed-right.closed {
            opacity: 1;
        }

        /* CONTENEUR PRINCIPAL */
        .main-container {
            display: flex;
            width: 100%;
            max-width: 1100px;
            height: 85vh;
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
            overflow: hidden;
            position: relative;
            z-index: 1;
            margin: 20px;
        }

        /* CÔTÉ GAUCHE */
        .left-panel {
            width: 50%;
            background: linear-gradient(135deg, var(--primary), #60a5fa);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: 40px;
            text-align: center;
            overflow: hidden;
        }

        .floating-shape {
            position: absolute;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            z-index: 1;
        }

        .shape-1 { 
            width: 120px; 
            height: 120px; 
            top: 20%; 
            left: 15%; 
            transform: rotate(15deg); 
            animation: floatShape1 8s ease-in-out infinite; 
        }
        
        .shape-2 { 
            width: 80px; 
            height: 80px; 
            bottom: 20%; 
            right: 20%; 
            transform: rotate(-15deg); 
            animation: floatShape2 9s ease-in-out infinite reverse; 
        }
        
        .shape-3 { 
            width: 40px; 
            height: 40px; 
            top: 30%; 
            right: 30%; 
            border-radius: 50%; 
            background: rgba(255, 255, 255, 0.2);
            animation: floatShape3 7s ease-in-out infinite 1s; 
        }
        
        .shape-4 { 
            width: 300px; 
            height: 300px; 
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            border: none;
            filter: blur(40px);
            z-index: 0;
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%);
            animation: pulseGlow 4s ease-in-out infinite;
        }

        .house-icon {
            position: absolute;
            font-size: 24px;
            color: rgba(255,255,255,0.2);
            z-index: 1;
        }

        .house-1 { top: 15%; left: 20%; animation: floatIcon 6s ease-in-out infinite; }
        .house-2 { bottom: 25%; right: 15%; animation: floatIcon 7s ease-in-out infinite 0.5s; }
        .house-3 { top: 40%; left: 10%; animation: floatIcon 8s ease-in-out infinite 1s; }

        @keyframes floatShape1 {
            0%, 100% { transform: translateY(0) rotate(15deg); }
            50% { transform: translateY(-30px) rotate(20deg); }
        }

        @keyframes floatShape2 {
            0%, 100% { transform: translateY(0) rotate(-15deg); }
            50% { transform: translateY(-25px) rotate(-20deg); }
        }

        @keyframes floatShape3 {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-20px) scale(1.2); }
        }

        @keyframes floatIcon {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(5deg); }
        }

        @keyframes pulseGlow {
            0%, 100% { opacity: 0.3; transform: translate(-50%, -50%) scale(1); }
            50% { opacity: 0.6; transform: translate(-50%, -50%) scale(1.2); }
        }

        .left-panel::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            opacity: 0.3;
            animation: movePattern 60s linear infinite;
            z-index: 1;
        }

        @keyframes movePattern {
            from { transform: translate(0, 0); }
            to { transform: translate(-50%, -50%); }
        }

        .logo-container {
            position: relative;
            z-index: 10;
            margin-bottom: 30px;
            filter: drop-shadow(0 20px 30px rgba(0,0,0,0.3));
            animation: breathe 4s ease-in-out infinite;
        }

        .left-panel img {
            width: 140px;
            height: auto;
            position: relative;
            z-index: 10;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.2));
            transition: all 0.3s ease;
            animation: logoFloat 6s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% { 
                transform: translateY(0) scale(1) rotate(0deg); 
                filter: drop-shadow(0 10px 20px rgba(0,0,0,0.2)) brightness(1);
            }
            20% { 
                transform: translateY(-8px) scale(1.02) rotate(1deg); 
                filter: drop-shadow(0 15px 25px rgba(255,255,255,0.3)) brightness(1.1);
            }
            40% { 
                transform: translateY(-15px) scale(1.05) rotate(2deg); 
                filter: drop-shadow(0 20px 30px rgba(255,255,255,0.4)) brightness(1.2);
            }
            60% { 
                transform: translateY(-10px) scale(1.03) rotate(-1deg); 
                filter: drop-shadow(0 15px 25px rgba(255,255,255,0.3)) brightness(1.1);
            }
            80% { 
                transform: translateY(-5px) scale(1.01) rotate(-2deg); 
                filter: drop-shadow(0 12px 22px rgba(0,0,0,0.2)) brightness(1);
            }
        }

        @keyframes breathe {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        .glow-ring {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            z-index: 5;
            animation: ringPulse 3s ease-in-out infinite;
        }

        .glow-ring-2 {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border-radius: 50%;
            border: 2px dashed rgba(255,255,255,0.2);
            z-index: 4;
            animation: ringRotate 10s linear infinite;
        }

        @keyframes ringPulse {
            0%, 100% { opacity: 0.3; transform: translate(-50%, -50%) scale(1); }
            50% { opacity: 0.8; transform: translate(-50%, -50%) scale(1.1); }
        }

        @keyframes ringRotate {
            from { transform: translate(-50%, -50%) rotate(0deg); }
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .left-panel h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            position: relative;
            z-index: 10;
            animation: slideInText 1s ease-out, glowText 3s ease-in-out infinite;
        }

        .left-panel p {
            font-size: 15px;
            opacity: 0.9;
            line-height: 1.6;
            position: relative;
            z-index: 10;
            animation: slideInText 1.2s ease-out;
        }

        @keyframes slideInText {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes glowText {
            0%, 100% { text-shadow: 0 0 10px rgba(255,255,255,0.2); }
            50% { text-shadow: 0 0 20px rgba(255,255,255,0.5); }
        }

        /* CÔTÉ DROIT */
        .right-panel {
            width: 50%;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow-y: auto;
        }

        .tabs {
            display: flex;
            margin-bottom: 35px;
            border-bottom: 2px solid #e2e8f0;
        }

        .tab-btn {
            flex: 1;
            padding: 15px;
            background: none;
            border: none;
            font-size: 16px;
            font-weight: 600;
            color: var(--text-light);
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .tab-btn.active {
            color: var(--primary);
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary);
            border-radius: 3px 3px 0 0;
        }

        .forms-wrapper {
            position: relative;
            min-height: 400px;
        }

        .form-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            opacity: 0;
            visibility: hidden;
            transform: translateX(20px);
            transition: all 0.4s ease;
        }

        .form-content.active {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
            position: relative;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group input, 
        .input-group select {
            width: 100%;
            padding: 14px 48px 14px 48px; 
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            background: #fff;
            transition: var(--transition);
            outline: none;
            color: var(--text-dark);
        }

        .input-group .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            transition: var(--transition);
            pointer-events: none;
        }

        .input-group input:focus ~ .input-icon,
        .input-group select:focus ~ .input-icon {
            color: var(--primary);
        }

        .input-group input:focus, 
        .input-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }

        .input-group input.input-error {
            border-color: var(--danger) !important;
            animation: shake 0.3s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #cbd5e1;
            cursor: pointer;
            transition: var(--transition);
            z-index: 2;
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        .file-upload {
            position: relative;
            display: flex;
            align-items: center;
        }

        .file-upload input[type="file"] { display: none; }

        .file-label {
            flex: 1;
            padding: 14px;
            padding-left: 48px;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            cursor: pointer;
            color: var(--text-light);
            font-size: 13px;
            transition: var(--transition);
            display: flex;
            align-items: center;
        }

        .file-label i {
            position: absolute;
            left: 16px;
            color: #cbd5e1;
            font-size: 18px;
        }

        .file-label:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .file-label:hover i { color: var(--primary); }

        .validation-message {
            font-size: 12px;
            margin-top: -10px;
            margin-bottom: 15px;
            padding-left: 5px;
            display: none;
            align-items: center;
            gap: 6px;
        }

        .validation-message.error {
            display: flex;
            color: var(--danger);
            animation: fadeIn 0.3s ease;
        }

        .validation-message.success {
            display: flex;
            color: #10b981;
        }

        .validation-message.info {
            display: flex;
            color: var(--primary);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .links {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
        }

        .links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        /* MODALE OTP */
        .forgot-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(8px);
            z-index: 50;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease;
        }

        .forgot-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .forgot-box {
            background: white;
            padding: 40px;
            width: 90%;
            max-width: 450px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            text-align: center;
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .forgot-overlay.active .forgot-box { transform: scale(1); }

        .forgot-box h2 { color: var(--text-dark); margin-bottom: 10px; }
        .forgot-box p { color: var(--text-light); font-size: 14px; margin-bottom: 25px; }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--text-light);
            font-size: 13px;
            text-decoration: none;
            border-bottom: 1px dashed transparent;
            transition: 0.3s;
            cursor: pointer;
        }

        .back-link:hover { color: var(--primary); border-bottom-color: var(--primary); }

        .otp-input {
            letter-spacing: 8px;
            font-size: 24px;
            text-align: center;
            font-weight: 600;
        }

        .resend-link {
            margin-top: 15px;
            font-size: 13px;
            color: var(--primary);
            cursor: pointer;
            text-decoration: underline;
        }

        .resend-link.disabled {
            color: var(--text-light);
            cursor: not-allowed;
            text-decoration: none;
            opacity: 0.6;
        }

        /* LOADER AMÉLIORÉ - PLUS VISIBLE */
        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 100000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            border-radius: 0;
        }

        .loader-overlay.show { 
            opacity: 1; 
            visibility: visible; 
        }

        .loader-content {
            background: white;
            padding: 30px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid #e2e8f0;
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px auto;
        }

        @keyframes spin { 
            0% { transform: rotate(0deg); } 
            100% { transform: rotate(360deg); } 
        }

        .loader-text { 
            color: var(--text-dark); 
            font-weight: 600; 
            font-size: 18px;
            margin-bottom: 10px;
        }

        .loader-subtext {
            color: var(--text-light);
            font-size: 14px;
        }

        /* MODE MOBILE */
        @media (max-width: 900px) {
            .main-container { 
                height: 95vh; 
                width: 95%; 
                margin: 10px;
            }
            .right-panel { padding: 30px; }
            .cat-container { 
                width: 90px; 
                height: 90px; 
                bottom: 10px; 
                left: 10px;
            }
            .cat-speech, .cat-thought { 
                font-size: 11px; 
                padding: 8px 14px; 
                top: -45px;
                left: 30px;
                white-space: nowrap;
            }
        }

        @media (max-width: 768px) {
            body {
                align-items: flex-start;
                padding: 0;
            }
            
            .main-container {
                flex-direction: column;
                width: 100%;
                height: auto;
                min-height: 100vh;
                border-radius: 0;
                max-width: 100%;
                overflow: visible;
                margin: 0;
            }
            
            .left-panel { 
                width: 100%; 
                padding: 30px 20px; 
                flex: 0 0 auto; 
                min-height: 180px; 
                order: -1; 
            }
            
            .left-panel img { 
                width: 90px; 
            }
            
            .right-panel {
                width: 100%; 
                padding: 30px 20px 80px 20px; 
                border-radius: 25px 25px 0 0; 
                margin-top: -15px;
                background: var(--white); 
                box-shadow: 0 -10px 30px rgba(0,0,0,0.05); 
                overflow-y: visible;
            }
            
            .tabs { margin-bottom: 25px; }
            
            .forms-wrapper { min-height: auto; }
            
            .input-group input, 
            .input-group select, 
            .file-label { 
                padding: 12px 42px 12px 42px; 
                font-size: 15px; 
            }
            
            .input-group .input-icon { left: 14px; }
            .toggle-password { right: 14px; }
            
            .cat-container { 
                width: 80px; 
                height: 80px; 
                bottom: 10px; 
                left: 10px;
                z-index: 10000;
            }
            
            .cat-speech, .cat-thought { 
                font-size: 10px; 
                padding: 5px 10px; 
                top: -35px;
                left: 25px;
                white-space: nowrap;
                border-width: 2px;
            }
            
            .right-panel { padding-bottom: 100px; }
            
            .loader-content {
                padding: 20px 25px;
            }
            
            .spinner {
                width: 50px;
                height: 50px;
                border-width: 4px;
            }
            
            .loader-text {
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .cat-container { 
                width: 70px; 
                height: 70px; 
                bottom: 5px; 
                left: 5px;
            }
            
            .cat-speech, .cat-thought { 
                font-size: 9px; 
                padding: 4px 8px; 
                top: -30px;
                left: 20px;
            }
            
            .right-panel { padding-bottom: 90px; }
        }
    </style>
</head>
<body>

    <!-- ALERTS -->
    <div class="alert-container" id="alertContainer"></div>

    <?php if (isset($_GET['error'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showAlert('error', '<?php echo htmlspecialchars($_GET['error']); ?>');
            });
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showAlert('success', '<?php echo htmlspecialchars($_GET['success']); ?>');
            });
        </script>
    <?php endif; ?>

    <!-- PERSONNAGE CHAT BLEU -->
    <div class="cat-container" id="catContainer">
        <div class="cat-speech" id="catSpeech">
            <i class="fas fa-paw" style="color: #2563eb; margin-right: 5px;"></i>
            Bienvenue chez DOMUS !
        </div>
        <div class="cat-thought" id="catThought">
            <i class="fas fa-eye-slash" style="color: #1e40af; margin-right: 5px;"></i>
            Je regarde pas, promis !
        </div>
        <svg class="cat" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
            <ellipse class="cat-body" cx="100" cy="130" rx="45" ry="40" fill="var(--cat-blue)" stroke="var(--cat-dark-blue)" stroke-width="3"/>
            
            <g class="cat-head">
                <circle cx="100" cy="70" r="40" fill="var(--cat-blue)" stroke="var(--cat-dark-blue)" stroke-width="3"/>
                <circle cx="115" cy="70" r="12" fill="var(--cat-light-blue)"/>
                <circle cx="85" cy="70" r="12" fill="var(--cat-light-blue)"/>
                <circle cx="100" cy="83" r="6" fill="var(--cat-blue)"/>
                <circle cx="100" cy="78" r="4" fill="#fca5a5" stroke="#ef4444" stroke-width="1.5"/>
                <line x1="125" y1="74" x2="150" y2="70" stroke="white" stroke-width="2"/>
                <line x1="125" y1="82" x2="150" y2="82" stroke="white" stroke-width="2"/>
                <line x1="75" y1="74" x2="50" y2="70" stroke="white" stroke-width="2"/>
                <line x1="75" y1="82" x2="50" y2="82" stroke="white" stroke-width="2"/>
            </g>
            
            <g class="cat-ears">
                <path d="M65 35 L40 5 L70 25" fill="var(--cat-blue)" stroke="var(--cat-dark-blue)" stroke-width="3"/>
                <path d="M135 35 L160 5 L130 25" fill="var(--cat-blue)" stroke="var(--cat-dark-blue)" stroke-width="3"/>
            </g>
            
            <path d="M60 30 L45 15 L65 25" fill="var(--cat-light-blue)"/>
            <path d="M140 30 L155 15 L135 25" fill="var(--cat-light-blue)"/>
            
            <circle class="cat-eye-left" cx="85" cy="60" r="6" fill="white"/>
            <circle class="cat-eye-right" cx="115" cy="60" r="6" fill="white"/>
            <circle cx="85" cy="60" r="3" fill="black"/>
            <circle cx="115" cy="60" r="3" fill="black"/>
            <circle cx="82" cy="57" r="1.5" fill="white" opacity="0.8"/>
            <circle cx="112" cy="57" r="1.5" fill="white" opacity="0.8"/>
            
            <path class="cat-eye-closed-left" d="M78 60 L92 60" stroke="black" stroke-width="5" stroke-linecap="round" style="opacity:0;"/>
            <path class="cat-eye-closed-right" d="M108 60 L122 60" stroke="black" stroke-width="5" stroke-linecap="round" style="opacity:0;"/>
            
            <ellipse class="cat-paw-left" cx="70" cy="150" rx="15" ry="12" fill="var(--cat-blue)" stroke="var(--cat-dark-blue)" stroke-width="2"/>
            <ellipse class="cat-paw-right" cx="130" cy="150" rx="15" ry="12" fill="var(--cat-blue)" stroke="var(--cat-dark-blue)" stroke-width="2"/>
            
            <g class="cat-paw-cover-left" id="pawCoverLeft" style="opacity:0;">
                <ellipse cx="75" cy="57" rx="18" ry="16" fill="var(--cat-blue)" stroke="var(--cat-dark-blue)" stroke-width="2" transform="rotate(-15 75 57)"/>
                <circle cx="66" cy="50" r="3.5" fill="var(--cat-light-blue)"/>
                <circle cx="80" cy="54" r="3.5" fill="var(--cat-light-blue)"/>
            </g>
            
            <g class="cat-paw-cover-right" id="pawCoverRight" style="opacity:0;">
                <ellipse cx="125" cy="57" rx="18" ry="16" fill="var(--cat-blue)" stroke="var(--cat-dark-blue)" stroke-width="2" transform="rotate(15 125 57)"/>
                <circle cx="116" cy="50" r="3.5" fill="var(--cat-light-blue)"/>
                <circle cx="134" cy="54" r="3.5" fill="var(--cat-light-blue)"/>
            </g>
            
            <path class="cat-tail" d="M145 140 Q170 125 185 150 Q190 170 165 165 Q155 160 145 140" fill="var(--cat-blue)" stroke="var(--cat-dark-blue)" stroke-width="3"/>
            
            <rect x="85" y="118" width="30" height="8" fill="white" rx="4" stroke="var(--cat-dark-blue)" stroke-width="1.5"/>
            <text x="92" y="125" font-size="5" fill="var(--primary)" font-weight="bold" font-family="Poppins">DOMUS</text>
        </svg>
    </div>

    <!-- MAIN CONTAINER -->
    <div class="main-container">
        
        <!-- GAUCHE -->
        <div class="left-panel">
            
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
            <div class="floating-shape shape-4"></div>
            
            <i class="fas fa-home house-icon house-1"></i>
            <i class="fas fa-building house-icon house-2"></i>
            <i class="fas fa-key house-icon house-3"></i>
            
            <div class="logo-container">
                <div class="glow-ring"></div>
                <div class="glow-ring-2"></div>
                <img src="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" alt="DOMUS Logo">
            </div>
            
            <h2>Bienvenue chez DOMUS</h2>
            <p>La plateforme de référence pour l'immobilier.</p>
        </div>

        <!-- DROITE -->
        <div class="right-panel">
            
            <div class="tabs">
                <button class="tab-btn active" id="tab-signin">Connexion</button>
                <button class="tab-btn" id="tab-signup">Inscription</button>
            </div>

            <div class="forms-wrapper">
                
                <!-- FORMULAIRE CONNEXION -->
                <form id="form-signin" class="form-content active" method="POST" action="../PHP/connexion.php">
                    <div class="input-group">
                        <i class="fas fa-phone-alt input-icon"></i>
                        <input type="tel" name="numero" placeholder="Numéro de téléphone" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="code" id="login_pass" placeholder="Mot de passe" required minlength="6" maxlength="16">
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('login_pass', this)"></i>
                    </div>

                    <div class="links">
                        <a id="forgot-link">Mot de passe oublié ?</a>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" name="connecter" class="btn-submit">
                            <span>Se connecter</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>

                <!-- FORMULAIRE INSCRIPTION -->
                <form id="form-signup" class="form-content" method="POST" action="../PHP/inscription.php" enctype="multipart/form-data">
                    
                    <div class="input-group">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="nom" placeholder="Nom complet" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" placeholder="Adresse Email" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" name="telephone" placeholder="Numéro de téléphone" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-briefcase input-icon"></i>
                        <select name="role" required>
                            <option value="" disabled selected>Vous êtes ?</option>
                            <option value="client">Acheteur / Locataire</option>
                            <option value="vendeur">Propriétaire / Agence</option>
                        </select>
                    </div>

                    <div class="file-upload input-group">
                        <input type="file" id="cin_file" name="cin" onchange="updateFileName(this)">
                        <label for="cin_file" class="file-label" id="file_label">
                            <i class="fas fa-id-card"></i>
                            <span style="margin-left: 20px;">CNI (Carte Nationale d'Identité)</span>
                        </label>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="reg_pass" placeholder="Mot de passe" required minlength="6" maxlength="16">
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('reg_pass', this)"></i>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="confirm_password" id="reg_confirm_pass" placeholder="Confirmer le mot de passe" required minlength="6" maxlength="16">
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('reg_confirm_pass', this)"></i>
                    </div>
                    
                    <div id="password_match_message" class="validation-message">
                        <i class="fas fa-info-circle"></i> <span>Les mots de passe ne correspondent pas.</span>
                    </div>

                    <div style="margin-top: 10px;">
                        <button type="submit" name="inscrit" class="btn-submit">
                            <span>S'inscrire</span>
                            <i class="fas fa-user-plus"></i>
                        </button>
                    </div>
                </form>

            </div>
        </div>

        <!-- MODALE OTP - ÉTAPE 1 : SAISIE NUMÉRO -->
        <div class="forgot-overlay" id="forgot-overlay">
            <div class="forgot-box" id="forgot-step-1">
                <i class="fas fa-key" style="font-size: 40px; color: var(--primary); margin-bottom: 20px;"></i>
                <h2>Réinitialisation</h2>
                <p>Entrez votre numéro de téléphone pour recevoir un code de vérification.</p>
                
                <form id="form-forgot-request">
                    <div class="input-group">
                        <i class="fas fa-phone-alt input-icon"></i>
                        <input type="tel" name="telephone_forgot" id="telephone_forgot" placeholder="Numéro de téléphone" required>
                    </div>
                    <button type="submit" class="btn-submit" id="send-otp-btn">
                        <span>Envoyer le code</span>
                    </button>
                </form>

                <div class="back-link" id="close-forgot">
                    <i class="fas fa-arrow-left"></i> Retour à la connexion
                </div>
            </div>

            <!-- MODALE OTP - ÉTAPE 2 : SAISIE CODE -->
            <div class="forgot-box" id="forgot-step-2" style="display: none;">
                <i class="fas fa-lock" style="font-size: 40px; color: var(--primary); margin-bottom: 20px;"></i>
                <h2>Vérification</h2>
                <p>Un code à 6 chiffres a été envoyé au <strong id="display-phone"></strong></p>
                
                <form id="form-forgot-verify">
                    <div class="input-group">
                        <i class="fas fa-qrcode input-icon"></i>
                        <input type="text" name="otp_code" id="otp_code" class="otp-input" placeholder="000000" maxlength="6" required pattern="[0-9]{6}" inputmode="numeric">
                    </div>
                    <p style="font-size: 12px; color: var(--text-light); margin-bottom: 15px;">Code valable 10 minutes</p>
                    <button type="submit" class="btn-submit" id="verify-otp-btn">
                        <span>Vérifier</span>
                    </button>
                </form>

                <div class="resend-link" id="resend-code">
                    <i class="fas fa-redo"></i> Renvoyer le code
                </div>

                <div class="back-link" id="back-to-phone">
                    <i class="fas fa-arrow-left"></i> Modifier le numéro
                </div>
            </div>

            <!-- MODALE OTP - ÉTAPE 3 : NOUVEAU MOT DE PASSE -->
            <div class="forgot-box" id="forgot-step-3" style="display: none;">
                <i class="fas fa-check-circle" style="font-size: 40px; color: #10b981; margin-bottom: 20px;"></i>
                <h2>Nouveau mot de passe</h2>
                <p>Choisissez un nouveau mot de passe sécurisé.</p>
                
                <form id="form-forgot-reset">
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="new_password" id="new_password" placeholder="Nouveau mot de passe" required minlength="6" maxlength="16">
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('new_password', this)"></i>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="confirm_new_password" id="confirm_new_password" placeholder="Confirmer le mot de passe" required minlength="6" maxlength="16">
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_new_password', this)"></i>
                    </div>
                    
                    <div id="new_password_match_message" class="validation-message">
                        <i class="fas fa-info-circle"></i> <span>Les mots de passe ne correspondent pas.</span>
                    </div>

                    <button type="submit" class="btn-submit" id="reset-password-btn">
                        <span>Changer le mot de passe</span>
                    </button>
                </form>

                <div class="back-link" id="back-to-login">
                    <i class="fas fa-arrow-left"></i> Retour à la connexion
                </div>
            </div>
        </div>

        <!-- OVERLAY DE LOADING AMÉLIORÉ -->
        <div class="loader-overlay" id="loader">
            <div class="loader-content">
                <div class="spinner"></div>
                <p class="loader-text" id="loader-text">Traitement en cours...</p>
                <p class="loader-subtext">Veuillez patienter quelques instants</p>
            </div>
        </div>

    </div>

    <script>
        // ==================== FONCTIONS UTILITAIRES ====================
        
        // Afficher une alerte
        function showAlert(type, message) {
            const alertContainer = document.getElementById('alertContainer');
            
            const alert = document.createElement('div');
            alert.className = `alert ${type}`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'} fa-lg"></i>
                <span>${message}</span>
            `;
            
            alertContainer.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Afficher le loader avec effet VISIBLE
        function showLoader(message = 'Traitement en cours...') {
            const loader = document.getElementById('loader');
            const loaderText = document.getElementById('loader-text');
            
            loaderText.textContent = message;
            loader.classList.add('show');
        }

        // Masquer le loader
        function hideLoader() {
            const loader = document.getElementById('loader');
            loader.classList.remove('show');
        }

        // Basculer l'affichage du mot de passe
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                icon.style.color = "var(--primary)";
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                icon.style.color = "#cbd5e1";
            }
        }

        // Mettre à jour le nom du fichier
        function updateFileName(input) {
            const labelSpan = document.querySelector('#file_label span');
            const labelIcon = document.querySelector('#file_label i');
            if (input.files && input.files.length > 0) {
                labelSpan.innerText = input.files[0].name;
                document.getElementById('file_label').style.borderColor = "#2563eb";
                labelIcon.style.color = "#2563eb";
            }
        }

        // ==================== GESTION DES TABS ====================
        
        const tabSignin = document.getElementById('tab-signin');
        const tabSignup = document.getElementById('tab-signup');
        const formSignin = document.getElementById('form-signin');
        const formSignup = document.getElementById('form-signup');

        tabSignin.addEventListener('click', () => {
            tabSignin.classList.add('active');
            tabSignup.classList.remove('active');
            formSignup.classList.remove('active');
            setTimeout(() => { formSignin.classList.add('active'); }, 100);
            updateCatMessage("connect");
        });

        tabSignup.addEventListener('click', () => {
            tabSignup.classList.add('active');
            tabSignin.classList.remove('active');
            formSignin.classList.remove('active');
            setTimeout(() => { formSignup.classList.add('active'); }, 100);
            updateCatMessage("register");
        });

        // ==================== GESTION DU MOT DE PASSE (INSCRIPTION) ====================
        
        const regPass = document.getElementById('reg_pass');
        const regConfirmPass = document.getElementById('reg_confirm_pass');
        const passMessage = document.getElementById('password_match_message');
        const passMessageText = passMessage.querySelector('span');
        const passMessageIcon = passMessage.querySelector('i');

        function checkPasswordMatch() {
            if (!regPass.value || !regConfirmPass.value) {
                passMessage.className = 'validation-message';
                regConfirmPass.classList.remove('input-error');
                return false;
            }

            if (regPass.value === regConfirmPass.value) {
                passMessage.className = 'validation-message success';
                passMessageText.innerText = "Les mots de passe correspondent.";
                passMessageIcon.className = "fas fa-check-circle";
                regConfirmPass.classList.remove('input-error');
                return true;
            } else {
                passMessage.className = 'validation-message error';
                passMessageText.innerText = "Les mots de passe ne correspondent pas.";
                passMessageIcon.className = "fas fa-exclamation-circle";
                regConfirmPass.classList.add('input-error');
                return false;
            }
        }

        if (regPass && regConfirmPass) {
            regPass.addEventListener('input', checkPasswordMatch);
            regConfirmPass.addEventListener('input', checkPasswordMatch);
        }

        // ==================== GESTION DES FORMULAIRES PRINCIPAUX ====================
        
        const loader = document.getElementById('loader');
        const forms = document.querySelectorAll('form:not(#form-forgot-request):not(#form-forgot-verify):not(#form-forgot-reset)');

        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                
                if (this.id === 'form-signup') {
                    if (!checkPasswordMatch()) {
                        e.preventDefault();
                        regConfirmPass.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        regConfirmPass.focus();
                        return;
                    }
                }

                if (!form.checkValidity()) return;

                e.preventDefault();
                showLoader();

                const submitBtn = this.querySelector('button[type="submit"]');
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = submitBtn.name;
                hiddenInput.value = '1';
                this.appendChild(hiddenInput);

                setTimeout(() => {
                    this.submit(); 
                }, 1500);
            });
        });

        // ==================== GESTION DU CHAT ====================
        
        const catSpeech = document.getElementById('catSpeech');
        const catThought = document.getElementById('catThought');
        const pawLeft = document.getElementById('pawCoverLeft');
        const pawRight = document.getElementById('pawCoverRight');
        const eyeLeft = document.querySelector('.cat-eye-left');
        const eyeRight = document.querySelector('.cat-eye-right');
        const eyeClosedLeft = document.querySelector('.cat-eye-closed-left');
        const eyeClosedRight = document.querySelector('.cat-eye-closed-right');
        
        const messages = {
            welcome: [
                "Bienvenue chez DOMUS !",
                "Hello ! Bienvenue ! 🏠",
                "Envie d'une belle maison ?",
                "DOMUS, votre agent immo !"
            ],
            connect: [
                "Connecte-toi vite !",
                "Tu as déjà un compte ?",
                "Regarde par ici ! ",
                "Connecte-toi !"
            ],
            register: [
                "Inscris-toi ! C'est gratuit !",
                "Rejoins la famille DOMUS ! 🏡",
                "Nouveau membre ? Bienvenue !",
                "Inscris-toi maintenant !"
            ],
            password: [
                "Je regarde pas, promis !",
                "Personne ne regarde ton mot de passe.",
                "Mes yeux sont cachés !",
                "Tu peux le mettre, je regarde pas !"
            ]
        };
        
        // ==================== MESSAGES OTP STATIQUES ====================
        const otpMessages = {
            etape1: "🔐 Mot de passe oublié ? Entre ton numéro de téléphone, je t'envoie un code !",
            etape1_sans_numero: "📞 Il faut entrer ton numéro d'abord !",
            envoi_en_cours: "📤 Envoi du SMS en cours... Patientez...",
            envoi_reussi: "📲 SMS envoyé ! Regarde tes messages, le code est là !",
            envoi_echoue: "😕 Échec de l'envoi. Vérifie ton numéro et réessaie !",
            saisie_code: "🔢 Tape le code à 6 chiffres que tu as reçu par SMS",
            verification_en_cours: "⏳ Vérification du code en cours... Patientez...",
            code_valide: "✅ Code valide ! Maintenant, choisis ton nouveau mot de passe",
            code_invalide: "❌ Code incorrect. Vérifie et réessaie !",
            renvoi_en_cours: "📨 Renvoi du code en cours... Patientez...",
            nouveau_mdp: "🔐 Choisis un nouveau mot de passe sécurisé (min 6 caractères)",
            mdp_non_correspondant: "⚠️ Les mots de passe ne correspondent pas !",
            modification_en_cours: "🔄 Modification du mot de passe en cours... Patientez...",
            modification_reussie: "🎉 BRAVO ! Mot de passe changé avec succès ! Ne l'oublie pas !",
            erreur_technique: "😿 Erreur technique. Réessaie dans un instant !"
        };

        let etapeOTP = 0; // 0: fermé, 1: saisie numéro, 2: saisie code, 3: nouveau mot de passe

        // Fonction pour afficher un message STATIQUE qui ne change pas
        function afficherMessageOTP(etape) {
            let message = "";
            
            switch(etape) {
                case 1:
                    message = otpMessages.etape1;
                    break;
                case 2:
                    message = otpMessages.saisie_code;
                    break;
                case 3:
                    message = otpMessages.nouveau_mdp;
                    break;
                default:
                    return;
            }
            
            catSpeech.style.animation = 'none';
            catSpeech.innerHTML = `<i class="fas fa-paw" style="color: #2563eb; margin-right: 5px;"></i> ${message}`;
            catSpeech.offsetHeight;
            catSpeech.style.animation = 'speechPop 0.5s ease-out';
            catSpeech.style.opacity = '1';
            catThought.style.opacity = '0';
        }

        // Fonction pour afficher un message temporaire (pour les actions)
        function afficherMessageTemporaire(message, duree = 3000) {
            catSpeech.style.animation = 'none';
            catSpeech.innerHTML = `<i class="fas fa-paw" style="color: #2563eb; margin-right: 5px;"></i> ${message}`;
            catSpeech.offsetHeight;
            catSpeech.style.animation = 'speechPop 0.5s ease-out';
            
            // Revenir au message statique après la durée
            if (duree > 0 && etapeOTP > 0) {
                setTimeout(() => {
                    afficherMessageOTP(etapeOTP);
                }, duree);
            }
        }
        
        let currentMessageIndex = 0;
        
        function updateCatMessage(type, customMessage = null) {
            let messageArray;
            
            if (customMessage) {
                catSpeech.innerHTML = `<i class="fas fa-paw" style="color: #2563eb; margin-right: 5px;"></i> ${customMessage}`;
            } else {
                messageArray = messages[type] || messages.welcome;
                currentMessageIndex = (currentMessageIndex + 1) % messageArray.length;
                catSpeech.innerHTML = `<i class="fas fa-paw" style="color: #2563eb; margin-right: 5px;"></i> ${messageArray[currentMessageIndex]}`;
            }
            
            catSpeech.style.animation = 'none';
            catSpeech.offsetHeight;
            catSpeech.style.animation = 'speechPop 0.5s ease-out';
        }
        
        function hideEyes() {
            if (eyeLeft) eyeLeft.style.display = 'none';
            if (eyeRight) eyeRight.style.display = 'none';
            
            eyeClosedLeft.style.opacity = '1';
            eyeClosedRight.style.opacity = '1';
            
            pawLeft.style.opacity = '1';
            pawRight.style.opacity = '1';
            
            catSpeech.style.opacity = '0';
            catThought.style.opacity = '1';
            
            const messageArray = messages.password;
            const randomMsg = messageArray[Math.floor(Math.random() * messageArray.length)];
            catThought.innerHTML = `<i class="fas fa-eye-slash" style="color: #1e40af; margin-right: 5px;"></i> ${randomMsg}`;
        }
        
        function openEyes() {
            if (eyeLeft) eyeLeft.style.display = 'block';
            if (eyeRight) eyeRight.style.display = 'block';
            
            eyeClosedLeft.style.opacity = '0';
            eyeClosedRight.style.opacity = '0';
            
            pawLeft.style.opacity = '0';
            pawRight.style.opacity = '0';
            
            catSpeech.style.opacity = '1';
            catThought.style.opacity = '0';
        }

        // Animation de surprise (yeux grands ouverts)
        function surpriseEyes() {
            if (eyeLeft && eyeRight) {
                eyeLeft.style.transition = 'r 0.2s ease';
                eyeRight.style.transition = 'r 0.2s ease';
                
                eyeLeft.setAttribute('r', '8');
                eyeRight.setAttribute('r', '8');
                
                setTimeout(() => {
                    eyeLeft.setAttribute('r', '6');
                    eyeRight.setAttribute('r', '6');
                }, 300);
            }
        }

        // Animation de contentement
        function happyEyes() {
            hideEyes();
            setTimeout(() => {
                openEyes();
            }, 200);
        }
        
        setTimeout(() => {
            updateCatMessage("welcome");
        }, 500);
        
        setInterval(() => {
            if (catSpeech.style.opacity === '1' && etapeOTP === 0) {
                const activeTab = document.querySelector('.tab-btn.active');
                if (activeTab && activeTab.id === 'tab-signin') {
                    updateCatMessage("connect");
                } else {
                    updateCatMessage("register");
                }
            }
        }, 5000);
        
        const passwordFields = [
            document.getElementById('login_pass'),
            document.getElementById('reg_pass'),
            document.getElementById('reg_confirm_pass'),
            document.getElementById('new_password'),
            document.getElementById('confirm_new_password')
        ];
        
        passwordFields.forEach(field => {
            if (field) {
                field.addEventListener('focus', hideEyes);
                field.addEventListener('blur', openEyes);
                field.addEventListener('input', function() {
                    if (this.value.length > 0) {
                        hideEyes();
                    }
                });
            }
        });
        
        document.querySelector('.cat')?.addEventListener('click', () => {
            if (etapeOTP === 0) {
                const activeTab = document.querySelector('.tab-btn.active');
                if (activeTab && activeTab.id === 'tab-signin') {
                    updateCatMessage("connect");
                } else {
                    updateCatMessage("register");
                }
                
                document.querySelector('.cat').style.transform = 'translateY(-8px)';
                setTimeout(() => {
                    document.querySelector('.cat').style.transform = 'translateY(0)';
                }, 200);
            }
        });

        // ==================== GESTION OTP AVEC TEXTBEE ====================
        
        const forgotLink = document.getElementById('forgot-link');
        const closeForgot = document.getElementById('close-forgot');
        const forgotOverlay = document.getElementById('forgot-overlay');
        const forgotStep1 = document.getElementById('forgot-step-1');
        const forgotStep2 = document.getElementById('forgot-step-2');
        const forgotStep3 = document.getElementById('forgot-step-3');
        const displayPhone = document.getElementById('display-phone');
        const formForgotRequest = document.getElementById('form-forgot-request');
        const formForgotVerify = document.getElementById('form-forgot-verify');
        const formForgotReset = document.getElementById('form-forgot-reset');
        const backToPhone = document.getElementById('back-to-phone');
        const resendCode = document.getElementById('resend-code');
        const backToLogin = document.getElementById('back-to-login');

        let currentPhone = '';
        let resendTimer = 60;
        let resendInterval;
        const MINIMUM_LOADING_TIME = 2000; // 2 secondes minimum pour les chargements

        // Ouvrir la modale - ÉTAPE 1
        if (forgotLink) {
            forgotLink.addEventListener('click', (e) => {
                e.preventDefault();
                forgotOverlay.classList.add('active');
                
                // Définir l'étape et afficher le message statique
                etapeOTP = 1;
                afficherMessageOTP(1);
                surpriseEyes();
            });
        }

        // Fermer la modale
        if (closeForgot) {
            closeForgot.addEventListener('click', (e) => {
                e.preventDefault();
                forgotOverlay.classList.remove('active');
                resetForgotModals();
                etapeOTP = 0;
                
                setTimeout(() => {
                    const activeTab = document.querySelector('.tab-btn.active');
                    if (activeTab && activeTab.id === 'tab-signin') {
                        updateCatMessage("connect");
                    } else {
                        updateCatMessage("register");
                    }
                }, 300);
            });
        }

        // Fermer en cliquant à l'extérieur
        if (forgotOverlay) {
            forgotOverlay.addEventListener('click', (e) => {
                if (e.target === forgotOverlay) {
                    forgotOverlay.classList.remove('active');
                    resetForgotModals();
                    etapeOTP = 0;
                    
                    setTimeout(() => {
                        const activeTab = document.querySelector('.tab-btn.active');
                        if (activeTab && activeTab.id === 'tab-signin') {
                            updateCatMessage("connect");
                        } else {
                            updateCatMessage("register");
                        }
                    }, 300);
                }
            });
        }

        // Réinitialiser les modales
        function resetForgotModals() {
            forgotStep1.style.display = 'block';
            forgotStep2.style.display = 'none';
            forgotStep3.style.display = 'none';
            clearInterval(resendInterval);
            document.getElementById('telephone_forgot').value = '';
            document.getElementById('otp_code').value = '';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_new_password').value = '';
            if (resendCode) {
                resendCode.style.pointerEvents = 'auto';
                resendCode.style.opacity = '1';
                resendCode.innerHTML = '<i class="fas fa-redo"></i> Renvoyer le code';
            }
        }

        // Démarrer le timer de renvoi
        function startResendTimer() {
            if (resendCode) {
                resendCode.style.pointerEvents = 'none';
                resendCode.style.opacity = '0.5';
                
                resendTimer = 60;
                resendInterval = setInterval(() => {
                    resendTimer--;
                    resendCode.innerHTML = `<i class="fas fa-redo"></i> Renvoyer (${resendTimer}s)`;
                    
                    if (resendTimer <= 0) {
                        clearInterval(resendInterval);
                        resendCode.style.pointerEvents = 'auto';
                        resendCode.style.opacity = '1';
                        resendCode.innerHTML = '<i class="fas fa-redo"></i> Renvoyer le code';
                    }
                }, 1000);
            }
        }

        // Fonction pour gérer le chargement avec temps minimum
        async function withMinimumLoading(promise, loaderMessage, callback) {
            const startTime = Date.now();
            
            // Afficher le loader
            showLoader(loaderMessage);
            
            try {
                // Exécuter la promesse
                const result = await promise;
                
                // Calculer le temps écoulé
                const elapsedTime = Date.now() - startTime;
                const remainingTime = Math.max(0, MINIMUM_LOADING_TIME - elapsedTime);
                
                // Attendre le temps restant avant d'exécuter le callback
                setTimeout(() => {
                    hideLoader();
                    callback(result);
                }, remainingTime);
                
            } catch (error) {
                // En cas d'erreur, attendre aussi le temps minimum
                const elapsedTime = Date.now() - startTime;
                const remainingTime = Math.max(0, MINIMUM_LOADING_TIME - elapsedTime);
                
                setTimeout(() => {
                    hideLoader();
                    throw error;
                }, remainingTime);
            }
        }

        // ÉTAPE 1 : Demande de code OTP
        if (formForgotRequest) {
            formForgotRequest.addEventListener('submit', async (e) => {
                e.preventDefault();
                const telephone = document.getElementById('telephone_forgot').value;
                
                if (!telephone) {
                    showAlert('error', 'Veuillez entrer votre numéro de téléphone');
                    afficherMessageTemporaire(otpMessages.etape1_sans_numero, 2000);
                    return;
                }
                
                currentPhone = telephone;
                
                // Afficher le message d'envoi en cours (temporaire)
                afficherMessageTemporaire(otpMessages.envoi_en_cours, 0);
                
                surpriseEyes();

                try {
                    const formData = new FormData();
                    formData.append('telephone', telephone);

                    // Utiliser la fonction avec temps minimum
                    await withMinimumLoading(
                        fetch('send_otp_domus.php', {
                            method: 'POST',
                            body: formData
                        }).then(res => res.json()),
                        '📤 Envoi du SMS en cours...',
                        (data) => {
                            if (data.success) {
                                displayPhone.textContent = telephone;
                                forgotStep1.style.display = 'none';
                                forgotStep2.style.display = 'block';
                                
                                // Passer à l'étape 2
                                etapeOTP = 2;
                                
                                startResendTimer();
                                
                                // Afficher le message de succès (temporaire) puis revenir au message statique étape 2
                                afficherMessageTemporaire(otpMessages.envoi_reussi, 3000);
                                setTimeout(() => {
                                    afficherMessageOTP(2);
                                }, 3000);
                                
                                happyEyes();
                                
                                if (data.debug_code) {
                                    showAlert('info', `Code de test: ${data.debug_code}`);
                                }
                                
                                showAlert('success', 'Code envoyé par SMS');
                            } else {
                                showAlert('error', data.message);
                                // Afficher le message d'échec (temporaire) puis revenir au message statique étape 1
                                afficherMessageTemporaire(otpMessages.envoi_echoue, 3000);
                                setTimeout(() => {
                                    afficherMessageOTP(1);
                                }, 3000);
                            }
                        }
                    );

                } catch (error) {
                    console.error('Erreur:', error);
                    hideLoader();
                    showAlert('error', 'Erreur lors de l\'envoi du code');
                    
                    // Afficher le message d'erreur (temporaire) puis revenir au message statique étape 1
                    afficherMessageTemporaire(otpMessages.erreur_technique, 3000);
                    setTimeout(() => {
                        afficherMessageOTP(1);
                    }, 3000);
                }
            });
        }

        // Retour à l'étape 1
        if (backToPhone) {
            backToPhone.addEventListener('click', (e) => {
                e.preventDefault();
                forgotStep2.style.display = 'none';
                forgotStep1.style.display = 'block';
                clearInterval(resendInterval);
                
                // Revenir à l'étape 1
                etapeOTP = 1;
                afficherMessageOTP(1);
            });
        }

        // Focus sur le champ code - on reste sur le message statique étape 2
        const otpInput = document.getElementById('otp_code');
        if (otpInput) {
            otpInput.addEventListener('focus', () => {
                // On ne change pas le message, on reste sur le message statique
            });
            
            otpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            });
        }

        // ÉTAPE 2 : Vérification du code OTP
        if (formForgotVerify) {
            formForgotVerify.addEventListener('submit', async (e) => {
                e.preventDefault();
                const code = document.getElementById('otp_code').value;

                if (!/^\d{6}$/.test(code)) {
                    showAlert('error', 'Le code doit contenir 6 chiffres');
                    afficherMessageTemporaire(otpMessages.saisie_code, 2000);
                    return;
                }

                // Afficher le message de vérification en cours (temporaire)
                afficherMessageTemporaire(otpMessages.verification_en_cours, 0);
                
                surpriseEyes();

                try {
                    const formData = new FormData();
                    formData.append('code', code);

                    // Utiliser la fonction avec temps minimum (2 secondes comme pour l'envoi)
                    await withMinimumLoading(
                        fetch('verify_otp_domus.php', {
                            method: 'POST',
                            body: formData
                        }).then(res => res.json()),
                        '🔍 Vérification du code en cours...',
                        (data) => {
                            if (data.success) {
                                forgotStep2.style.display = 'none';
                                forgotStep3.style.display = 'block';
                                clearInterval(resendInterval);
                                
                                // Passer à l'étape 3
                                etapeOTP = 3;
                                
                                // Afficher le message de succès (temporaire) puis revenir au message statique étape 3
                                afficherMessageTemporaire(otpMessages.code_valide, 3000);
                                setTimeout(() => {
                                    afficherMessageOTP(3);
                                }, 3000);
                                
                                happyEyes();
                                
                                showAlert('success', 'Code vérifié avec succès');
                            } else {
                                showAlert('error', data.message);
                                document.getElementById('otp_code').classList.add('input-error');
                                
                                // Afficher le message d'erreur (temporaire) puis revenir au message statique étape 2
                                afficherMessageTemporaire(otpMessages.code_invalide, 3000);
                                setTimeout(() => {
                                    afficherMessageOTP(2);
                                }, 3000);
                                
                                setTimeout(() => {
                                    document.getElementById('otp_code').classList.remove('input-error');
                                }, 1000);
                            }
                        }
                    );

                } catch (error) {
                    console.error('Erreur:', error);
                    hideLoader();
                    showAlert('error', 'Erreur lors de la vérification');
                    
                    // Afficher le message d'erreur (temporaire) puis revenir au message statique étape 2
                    afficherMessageTemporaire(otpMessages.erreur_technique, 3000);
                    setTimeout(() => {
                        afficherMessageOTP(2);
                    }, 3000);
                }
            });
        }

        // Renvoyer le code
        if (resendCode) {
            resendCode.addEventListener('click', async (e) => {
                e.preventDefault();
                
                if (resendTimer > 0 && resendTimer < 60) return;
                
                // Afficher le message de renvoi en cours (temporaire)
                afficherMessageTemporaire(otpMessages.renvoi_en_cours, 0);
                
                surpriseEyes();

                try {
                    const formData = new FormData();
                    formData.append('telephone', currentPhone);

                    // Utiliser la fonction avec temps minimum
                    await withMinimumLoading(
                        fetch('send_otp_domus.php', {
                            method: 'POST',
                            body: formData
                        }).then(res => res.json()),
                        '📨 Renvoi du code...',
                        (data) => {
                            if (data.success) {
                                startResendTimer();
                                showAlert('success', 'Nouveau code envoyé');
                                
                                // Afficher le message de succès (temporaire) puis revenir au message statique étape 2
                                afficherMessageTemporaire(otpMessages.envoi_reussi, 3000);
                                setTimeout(() => {
                                    afficherMessageOTP(2);
                                }, 3000);
                                
                                happyEyes();
                                
                                if (data.debug_code) {
                                    showAlert('info', `Code de test: ${data.debug_code}`);
                                }
                            } else {
                                showAlert('error', data.message);
                                
                                // Afficher le message d'échec (temporaire) puis revenir au message statique étape 2
                                afficherMessageTemporaire(otpMessages.envoi_echoue, 3000);
                                setTimeout(() => {
                                    afficherMessageOTP(2);
                                }, 3000);
                            }
                        }
                    );

                } catch (error) {
                    console.error('Erreur:', error);
                    hideLoader();
                    showAlert('error', 'Erreur lors du renvoi');
                    
                    // Afficher le message d'erreur (temporaire) puis revenir au message statique étape 2
                    afficherMessageTemporaire(otpMessages.erreur_technique, 3000);
                    setTimeout(() => {
                        afficherMessageOTP(2);
                    }, 3000);
                }
            });
        }

        // ÉTAPE 3 : Focus sur les champs de mot de passe
        const newPass = document.getElementById('new_password');
        const confirmNewPass = document.getElementById('confirm_new_password');
        
        if (newPass) {
            newPass.addEventListener('focus', () => {
                // On reste sur le message statique étape 3
            });
        }

        // ÉTAPE 3 : Vérification du nouveau mot de passe
        const newPassMessage = document.getElementById('new_password_match_message');

        function checkNewPasswordMatch() {
            if (!newPass?.value || !confirmNewPass?.value) {
                if (newPassMessage) {
                    newPassMessage.className = 'validation-message';
                }
                if (confirmNewPass) confirmNewPass.classList.remove('input-error');
                return false;
            }

            if (newPass.value === confirmNewPass.value) {
                if (newPassMessage) {
                    newPassMessage.className = 'validation-message success';
                    newPassMessage.querySelector('span').innerText = "Les mots de passe correspondent.";
                    newPassMessage.querySelector('i').className = "fas fa-check-circle";
                }
                if (confirmNewPass) confirmNewPass.classList.remove('input-error');
                return true;
            } else {
                if (newPassMessage) {
                    newPassMessage.className = 'validation-message error';
                    newPassMessage.querySelector('span').innerText = "Les mots de passe ne correspondent pas.";
                    newPassMessage.querySelector('i').className = "fas fa-exclamation-circle";
                }
                if (confirmNewPass) confirmNewPass.classList.add('input-error');
                afficherMessageTemporaire(otpMessages.mdp_non_correspondant, 2000);
                return false;
            }
        }

        if (newPass && confirmNewPass) {
            newPass.addEventListener('input', checkNewPasswordMatch);
            confirmNewPass.addEventListener('input', checkNewPasswordMatch);
        }

        // Soumission du nouveau mot de passe
        if (formForgotReset) {
            formForgotReset.addEventListener('submit', async (e) => {
                e.preventDefault();

                if (!checkNewPasswordMatch()) {
                    if (confirmNewPass) {
                        confirmNewPass.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        confirmNewPass.focus();
                    }
                    return;
                }

                // Afficher le message de modification en cours (temporaire)
                afficherMessageTemporaire(otpMessages.modification_en_cours, 0);
                
                surpriseEyes();

                try {
                    const formData = new FormData();
                    formData.append('new_password', newPass.value);
                    formData.append('confirm_password', confirmNewPass.value);

                    // Utiliser la fonction avec temps minimum
                    await withMinimumLoading(
                        fetch('reset_password_domus.php', {
                            method: 'POST',
                            body: formData
                        }).then(res => res.json()),
                        '🔄 Modification du mot de passe...',
                        (data) => {
                            if (data.success) {
                                forgotOverlay.classList.remove('active');
                                resetForgotModals();
                                
                                // Afficher le message BRAVO
                                etapeOTP = 0;
                                afficherMessageTemporaire(otpMessages.modification_reussie, 4000);
                                
                                happyEyes();
                                
                                showAlert('success', 'Mot de passe modifié avec succès');
                                
                                // Retour au message normal après 4 secondes
                                setTimeout(() => {
                                    const activeTab = document.querySelector('.tab-btn.active');
                                    if (activeTab && activeTab.id === 'tab-signin') {
                                        updateCatMessage("connect");
                                    } else {
                                        updateCatMessage("register");
                                    }
                                }, 4000);
                            } else {
                                showAlert('error', data.message);
                                
                                // Afficher le message d'erreur (temporaire) puis revenir au message statique étape 3
                                afficherMessageTemporaire(otpMessages.erreur_technique, 3000);
                                setTimeout(() => {
                                    afficherMessageOTP(3);
                                }, 3000);
                            }
                        }
                    );

                } catch (error) {
                    console.error('Erreur:', error);
                    hideLoader();
                    showAlert('error', 'Erreur lors de la modification');
                    
                    // Afficher le message d'erreur (temporaire) puis revenir au message statique étape 3
                    afficherMessageTemporaire(otpMessages.erreur_technique, 3000);
                    setTimeout(() => {
                        afficherMessageOTP(3);
                    }, 3000);
                }
            });
        }

        // Retour à la connexion
        if (backToLogin) {
            backToLogin.addEventListener('click', (e) => {
                e.preventDefault();
                forgotOverlay.classList.remove('active');
                resetForgotModals();
                etapeOTP = 0;
                setTimeout(() => {
                    updateCatMessage("connect");
                }, 300);
            });
        }

    </script>
</body>
</html>