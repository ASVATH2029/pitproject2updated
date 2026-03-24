<?php
session_start();
if (!empty($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}
session_write_close();
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PITS — Sign Up</title>
    <meta name="description" content="Create a PITS Student Archival System account">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="background.css">
    <style>
        :root {
            --bg-dark: #0a100a;
            --text-primary: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.65);
            --text-cream: #e8e0d0;
            --glass-bg: rgba(55, 70, 50, 0.30);
            --glass-border: rgba(255, 255, 255, 0.06);
            --glass-blur: 20px;
            --input-bg: rgba(25, 35, 22, 0.75);
            --input-border: rgba(255, 255, 255, 0.08);
            --btn-bg: rgba(225, 218, 200, 0.85);
            --btn-text: #2a2a2a;
            --font-heading: 'Libre Baskerville', Georgia, serif;
            --font-label: 'DM Sans', 'Helvetica Neue', sans-serif;
            --radius-card: 16px;
            --radius-inner: 12px;
            --radius-pill: 30px;
            --fs-h1: 2.6rem;
            --fs-subtitle: 0.95rem;
            --fs-label: 0.8rem;
            --fs-input: 0.92rem;
            --fs-button: 0.9rem;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            color: var(--text-primary);
            font-family: var(--font-label);
            overflow-x: hidden;
        }

        .top-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 28px 44px;
            z-index: 100;
            font-family: var(--font-heading);
            font-size: 1rem;
            color: var(--text-cream);
            transition: all 0.45s cubic-bezier(0.16, 1, 0.3, 1);
            animation: topSlide 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes topSlide {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 90px 3vw 50px;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 60px 52px 52px;
            width: 100%;
            max-width: min(540px, 94vw);
            animation: cardFloat 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
            transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.35s cubic-bezier(0.22, 1, 0.36, 1), border-color 0.35s ease;
        }

        @keyframes cardFloat {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
                filter: blur(4px);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
                filter: blur(0);
            }
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            margin-bottom: 2.4rem;
            padding-bottom: 1.6rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .card-logo {
            width: 170px;
            height: 170px;
            object-fit: contain;
            flex-shrink: 0;
            filter: invert(1);
            mix-blend-mode: screen;
        }

        .card-title-block {
            flex: 1;
            text-align: left;
        }

        .glass-card h1 {
            font-family: var(--font-heading);
            font-weight: 400;
            font-size: var(--fs-h1);
            color: var(--text-primary);
            margin-bottom: 6px;
            letter-spacing: 0.3px;
        }

        .glass-card .subtitle {
            font-family: var(--font-heading);
            font-size: var(--fs-subtitle);
            font-weight: 400;
            color: var(--text-muted);
        }

        .card-title-block .subtitle {
            margin-bottom: 0;
        }

        .glass-card .subtitle a {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 700;
            font-family: var(--font-label);
            font-size: 0.92rem;
        }

        .glass-card .subtitle a:hover {
            text-decoration: underline;
        }

        .input-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }

        .input-group label {
            display: block;
            font-family: var(--font-label);
            font-size: var(--fs-label);
            font-weight: 500;
            letter-spacing: 2.2px;
            text-transform: uppercase;
            color: var(--text-cream);
            margin-bottom: 10px;
        }

        .input-group input {
            width: 100%;
            padding: 14px 18px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: var(--radius-inner);
            font-family: var(--font-label);
            font-size: var(--fs-input);
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.25s, background 0.25s, box-shadow 0.25s;
        }

        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.35);
        }

        .input-group input:focus {
            border-color: rgba(160, 200, 140, 0.4) !important;
            background: rgba(25, 35, 22, 0.9);
            box-shadow: 0 0 0 3px rgba(160, 200, 140, 0.08), 0 0 20px rgba(160, 200, 140, 0.06);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 44px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            opacity: 0.45;
            transition: opacity 0.2s;
        }

        .toggle-password:hover {
            opacity: 1;
        }

        .toggle-password svg {
            width: 18px;
            height: 18px;
            fill: var(--text-primary);
        }

        .password-hint {
            font-size: 0.68rem;
            color: rgba(255, 255, 255, 0.3);
            margin-top: 5px;
        }

        .btn-submit {
            display: block;
            width: 100%;
            margin-top: 1.8rem;
            padding: 14px 48px;
            background: var(--btn-bg);
            color: var(--btn-text);
            border: none;
            border-radius: var(--radius-pill);
            font-family: var(--font-label);
            font-weight: 600;
            font-size: var(--fs-button);
            cursor: pointer;
            letter-spacing: 0.3px;
            text-align: center;
            transition: transform 0.2s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.2s ease, background 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.35);
        }

        .btn-submit:active {
            transform: scale(0.96) !important;
        }

        .error-msg {
            color: #e74c3c;
            font-size: 0.82rem;
            margin-bottom: 1rem;
            background: rgba(231, 76, 60, 0.1);
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid rgba(231, 76, 60, 0.25);
        }

        .success-msg {
            color: #7dcea0;
            font-size: 0.82rem;
            margin-bottom: 1rem;
            background: rgba(125, 206, 160, 0.1);
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid rgba(125, 206, 160, 0.25);
        }

        .particles {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(160, 200, 140, 0.15), transparent 70%);
            animation: floatParticle linear infinite;
        }

        @keyframes floatParticle {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                transform: translateY(-10vh) rotate(360deg);
                opacity: 0;
            }
        }

        @media(max-width:768px) {
            .top-bar {
                padding: 16px 20px;
            }

            .glass-card {
                padding: 34px 26px;
            }

            .glass-card h1 {
                font-size: 2rem;
            }
        }

        @media(max-width:600px) {
            .top-bar {
                top: 12px;
                left: 50%;
                right: auto;
                transform: translateX(-50%);
                width: 90%;
                max-width: 400px;
                padding: 10px 20px;
                background: rgba(10, 18, 10, 0.85);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 50px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.04);
                font-size: 0.85rem;
                justify-content: space-between;
                animation: none;
            }
            .page-wrapper {
                padding: 90px 4vw 60px;
            }
            .glass-card {
                padding: 24px 18px;
                border-radius: 16px;
                width: 94%;
                margin: 0 auto;
            }
            .glass-card h1 {
                font-size: 1.5rem;
            }
            .input-group input, .btn-submit {
                padding: 12px 16px;
                font-size: 0.85rem;
            }
        }

        @media(max-width:420px) {
            .top-bar {
                padding: 12px 16px;
            }

            .page-wrapper {
                padding: 64px 6px 65px;
            }

            .glass-card {
                padding: 24px 16px;
            }
        }
    </style>
</head>

<body>
    <div class="particles" id="particleContainer"></div>

    <div class="top-bar">
        <span id="currentDate"></span>
        <span id="currentTime"></span>
    </div>

    <div class="page-wrapper">
        <div class="glass-card">
            <div class="card-header">
                <div class="card-title-block">
                    <h1>Sign Up</h1>
                    <div class="subtitle">Already registered? <a href="index.php">Login</a></div>
                </div>
                <img src="textbox/NEW UI/pitsnew.png" class="card-logo" alt="PITS Logo">
            </div>

            <?php if ($error === 'exists'): ?>
                <div class="error-msg">Username already taken. Please choose another.</div>
            <?php elseif ($error === 'short'): ?>
                <div class="error-msg">Password must be at least 8 characters.</div>
            <?php elseif ($error === 'weak_password'): ?>
                <div class="error-msg">Password requires 8+ chars with letters, numbers, and symbols.</div>
            <?php elseif ($error === 'invalid'): ?>
                <div class="error-msg">Invalid username. Use letters, numbers, underscores only.</div>
            <?php elseif ($error === 'invalidemail'): ?>
                <div class="error-msg">Please enter a valid email address.</div>
            <?php elseif ($error === 'mismatch'): ?>
                <div class="error-msg">Passwords do not match. Please try again.</div>
            <?php elseif ($error === 'mailfail'): ?>
                <div class="error-msg">Could not send verification email. Please check your email or try again later.</div>
            <?php elseif ($error === 'expired'): ?>
                <div class="error-msg">Verification code expired or too many failed attempts. Please sign up again.</div>
            <?php elseif ($error === 'fail' || $error !== ''): ?>
                <div class="error-msg">Registration failed. Please try again.</div>
            <?php endif; ?>
            <?php if ($success === '1'): ?>
                <div class="success-msg">Account created! <a href="index.php" style="color:#7dcea0;font-weight:700;">Login
                        here</a></div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="input-group">
                    <label>USERNAME</label>
                    <input type="text" name="username" placeholder="" autocomplete="username" required>
                </div>
                <div class="input-group">
                    <label>EMAIL <span style="font-size:0.65rem;opacity:0.5;text-transform:none;letter-spacing:0;">(used
                            for password recovery)</span></label>
                    <input type="email" name="email" placeholder="" autocomplete="email" required>
                </div>
                <div class="input-group">
                    <label>PASSWORD</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="signupPass" placeholder=""
                            autocomplete="new-password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('signupPass',this)"
                            aria-label="Show password">
                            <svg class="eye-open" viewBox="0 0 24 24" fill="var(--text-primary)">
                                <path
                                    d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 12.5c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                            </svg>
                            <svg class="eye-closed" style="display:none" viewBox="0 0 24 24" fill="var(--text-primary)">
                                <path
                                    d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46A11.8 11.8 0 001 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z" />
                            </svg>
                        </button>
                    </div>
                    <div class="password-hint">Min 8 chars, requires letters, numbers & symbols</div>
                </div>
                <div class="input-group">
                    <label>CONFIRM PASSWORD</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="signupConfirm" placeholder=""
                            autocomplete="new-password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('signupConfirm',this)"
                            aria-label="Show confirm password">
                            <svg class="eye-open" viewBox="0 0 24 24" fill="var(--text-primary)">
                                <path
                                    d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 12.5c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                            </svg>
                            <svg class="eye-closed" style="display:none" viewBox="0 0 24 24" fill="var(--text-primary)">
                                <path
                                    d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46A11.8 11.8 0 001 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Sign Up</button>
            </form>
        </div>
    </div>

    <script>
        function updateDateTime() { var now = new Date(); var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']; document.getElementById('currentDate').textContent = months[now.getMonth()] + ' ' + now.getDate() + ', ' + now.getFullYear(); var h = now.getHours(), m = now.getMinutes(), ap = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12; document.getElementById('currentTime').textContent = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ap; }
        updateDateTime(); setInterval(updateDateTime, 1000);
        (function () { var c = document.getElementById('particleContainer'); for (var i = 0; i < 12; i++) { var p = document.createElement('div'); p.className = 'particle'; var s = Math.random() * 60 + 20; p.style.cssText = 'width:' + s + 'px;height:' + s + 'px;left:' + (Math.random() * 100) + '%;animation-duration:' + (Math.random() * 20 + 15) + 's;animation-delay:' + (Math.random() * 10) + 's'; c.appendChild(p); } })();
        function togglePassword(id, btn) { var inp = document.getElementById(id); var eo = btn.querySelector('.eye-open'), ec = btn.querySelector('.eye-closed'); if (inp.type === 'password') { inp.type = 'text'; eo.style.display = 'none'; ec.style.display = 'block'; } else { inp.type = 'password'; eo.style.display = 'block'; ec.style.display = 'none'; } }
    </script>
</body>

</html>