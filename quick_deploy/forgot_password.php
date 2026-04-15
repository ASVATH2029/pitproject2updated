<?php
/*
 * forgot_password.php — Multi-step password reset page
 *
 * Step 1 (default)      : Enter username + email
 * Step 2 (otp)          : Enter the 6-digit OTP sent to email
 * Step 3 (new_password) : Enter + confirm new password
 *
 * Steps are PHP-rendered based on $_SESSION['pw_reset_step'].
 */
require_once __DIR__ . '/config.php';
session_start();

if (!empty($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}
session_write_close();

$step   = $_SESSION['pw_reset_step'] ?? 'email';
$email  = $_SESSION['pw_reset_email'] ?? '';
$masked = $email ? preg_replace('/(?<=.{2}).(?=.*@)/u', '*', $email) : '';

$status = $_GET['status'] ?? '';
$error  = $_GET['error']  ?? '';
$left   = (int)($_GET['left'] ?? 3);

$otp_at       = $_SESSION['pw_reset_otp_at'] ?? time();
$remaining_secs = max(0, OTP_EXPIRY_SECS - (time() - $otp_at));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PITS — Reset Password</title>
    <meta name="description" content="Reset your PITS account password">
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
            --radius-pill: 30px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { min-height: 100vh; color: var(--text-primary); font-family: var(--font-label); overflow-x: hidden; }

        .top-bar {
            position: fixed; top: 0; left: 0; right: 0;
            display: flex; justify-content: space-between; align-items: center;
            padding: 28px 44px; z-index: 100;
            font-family: var(--font-heading); font-size: 1rem; color: var(--text-cream);
            animation: topSlide 0.8s cubic-bezier(0.16,1,0.3,1) both;
        }
        @keyframes topSlide {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .page-wrapper {
            position: relative; z-index: 1; min-height: 100vh;
            display: flex; justify-content: center; align-items: center;
            padding: 90px 3vw 50px;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: 16px; padding: 60px 52px 52px;
            width: 100%; max-width: min(520px, 94vw);
            animation: cardFloat 0.6s cubic-bezier(0.16,1,0.3,1) both;
        }
        @keyframes cardFloat {
            from { opacity: 0; transform: translateY(40px) scale(0.95); filter: blur(4px); }
            to   { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
        }

        .card-header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1.5rem; margin-bottom: 2.4rem; padding-bottom: 1.6rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .card-logo {
            width: 120px; height: 120px; object-fit: contain; flex-shrink: 0;
            filter: invert(1); mix-blend-mode: screen;
        }
        .card-title-block { flex: 1; }
        h1 {
            font-family: var(--font-heading); font-weight: 400; font-size: 2.2rem;
            color: var(--text-primary); margin-bottom: 6px;
        }
        .subtitle { font-family: var(--font-heading); font-size: 0.92rem; color: var(--text-muted); }

        /* Step indicator */
        .steps {
            display: flex; gap: 6px; align-items: center;
            margin-bottom: 24px; justify-content: center;
        }
        .step-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: rgba(255,255,255,0.15); transition: background 0.3s;
        }
        .step-dot.active { background: rgba(160,200,140,0.8); width: 24px; border-radius: 4px; }
        .step-dot.done   { background: rgba(160,200,140,0.4); }

        .input-group { margin-bottom: 1.25rem; text-align: left; }
        .input-group label {
            display: block; font-size: 0.8rem; font-weight: 500;
            letter-spacing: 2.2px; text-transform: uppercase;
            color: var(--text-cream); margin-bottom: 10px;
        }
        .input-group input {
            width: 100%; padding: 14px 18px;
            background: var(--input-bg); border: 1px solid var(--input-border);
            border-radius: 12px; font-family: var(--font-label); font-size: 0.92rem;
            color: var(--text-primary); outline: none;
            transition: border-color 0.25s, background 0.25s, box-shadow 0.25s;
        }
        .input-group input:focus {
            border-color: rgba(160,200,140,0.4) !important;
            background: rgba(25,35,22,0.9);
            box-shadow: 0 0 0 3px rgba(160,200,140,0.08);
        }
        .password-wrapper { position: relative; }
        .password-wrapper input { padding-right: 44px; }
        .toggle-password {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            padding: 4px; opacity: 0.45; transition: opacity 0.2s;
        }
        .toggle-password:hover { opacity: 1; }
        .toggle-password svg { width: 18px; height: 18px; fill: var(--text-primary); }

        /* OTP digit row */
        .otp-row {
            display: flex; gap: 10px; justify-content: center; margin: 20px 0 16px;
        }
        .otp-row input {
            width: 52px; height: 64px;
            background: var(--input-bg); border: 1px solid var(--input-border);
            border-radius: 10px; font-family: monospace;
            font-size: 1.6rem; font-weight: 700;
            color: var(--text-primary); text-align: center; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .otp-row input:focus {
            border-color: rgba(160,200,140,0.5);
            box-shadow: 0 0 0 3px rgba(160,200,140,0.1);
        }

        .timer-bar-wrap { height: 3px; background: rgba(255,255,255,0.06); border-radius: 2px; margin: 12px 0; overflow: hidden; }
        #timerBar { height: 100%; background: rgba(160,200,140,0.6); border-radius: 2px; transition: width 1s linear; }

        .sub { font-size: 0.88rem; color: var(--text-muted); margin-bottom: 4px; line-height: 1.6; }
        .sub strong { color: var(--text-cream); }

        .btn-submit {
            display: block; width: 100%; margin-top: 1.6rem;
            padding: 14px 48px; background: var(--btn-bg); color: var(--btn-text);
            border: none; border-radius: var(--radius-pill);
            font-family: var(--font-label); font-weight: 600; font-size: 0.9rem;
            cursor: pointer; text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(0,0,0,0.35); }
        .btn-submit:active { transform: scale(0.96) !important; }

        .resend-row {
            display: flex; justify-content: center; align-items: center;
            gap: 8px; margin-top: 16px; font-size: 0.82rem; color: var(--text-muted);
        }
        #resendBtn {
            background: none; border: none; cursor: pointer;
            color: var(--text-primary); font-weight: 700; font-size: 0.82rem;
            font-family: var(--font-label); text-decoration: underline; padding: 0;
        }
        #resendBtn:disabled { opacity: 0.4; cursor: not-allowed; text-decoration: none; }

        .error-msg {
            color: #e74c3c; font-size: 0.82rem; margin-bottom: 1rem;
            background: rgba(231,76,60,0.1); padding: 10px 14px;
            border-radius: 8px; border: 1px solid rgba(231,76,60,0.25);
        }
        .success-msg {
            color: #7dcea0; font-size: 0.82rem; margin-bottom: 1rem;
            background: rgba(125,206,160,0.1); padding: 10px 14px;
            border-radius: 8px; border: 1px solid rgba(125,206,160,0.25);
        }
        .info-msg {
            color: #7dcea0; font-size: 0.82rem; margin-bottom: 1rem;
            background: rgba(125,206,160,0.1); padding: 10px 14px;
            border-radius: 8px; border: 1px solid rgba(125,206,160,0.25); display: none;
        }

        .back-link {
            display: block; text-align: center; margin-top: 18px;
            font-size: 0.8rem; color: var(--text-muted); text-decoration: none;
        }
        .back-link:hover { color: var(--text-primary); }

        .password-hint { font-size: 0.68rem; color: rgba(255,255,255,0.3); margin-top: 5px; }


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
            .page-wrapper { padding: 90px 4vw 60px; }
            .glass-card { padding: 24px 18px; border-radius: 16px; width: 94%; margin: 0 auto; }
            h1 { font-size: 1.5rem; }
            .input-group input, .btn-submit { padding: 12px 16px; font-size: 0.85rem; }
            .otp-row input { width: 40px; height: 52px; font-size: 1.3rem; gap: 6px; }
        }
    </style>
</head>

<body>

    <div class="top-bar">
        <span id="currentDate"></span>
        <span id="currentTime"></span>
    </div>

    <div class="page-wrapper">
        <div class="glass-card">

            <!-- Header -->
            <div class="card-header">
                <div class="card-title-block">
                    <?php if ($step === 'email'): ?>
                        <h1>Forgot Password</h1>
                        <div class="subtitle">Enter your details to receive a reset code</div>
                    <?php elseif ($step === 'otp'): ?>
                        <h1>Enter OTP</h1>
                        <div class="subtitle">Check your email for the 6-digit code</div>
                    <?php else: ?>
                        <h1>New Password</h1>
                        <div class="subtitle">Choose a strong new password</div>
                    <?php endif; ?>
                </div>
                <img src="textbox/NEW UI/pitsnew.png" class="card-logo" alt="PITS Logo">
            </div>

            <!-- Step indicator -->
            <div class="steps">
                <div class="step-dot <?= $step==='email' ? 'active' : 'done' ?>"></div>
                <div class="step-dot <?= $step==='otp' ? 'active' : ($step==='new_password' ? 'done' : '') ?>"></div>
                <div class="step-dot <?= $step==='new_password' ? 'active' : '' ?>"></div>
            </div>

            <!-- ── STEP 1: Email form ─────────────────────────────────── -->
            <?php if ($step === 'email'): ?>

                <?php if ($status === 'error' || $error !== ''): ?>
                    <div class="error-msg">No account found with that username and email combination.</div>
                <?php endif; ?>
                <?php if ($status === 'expired'): ?>
                    <div class="error-msg">Your OTP expired or too many failed attempts. Please try again.</div>
                <?php endif; ?>

                <form method="POST" action="reset_request.php">
                    <div class="input-group">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="" autocomplete="username" required>
                    </div>
                    <div class="input-group">
                        <label>Registered Email</label>
                        <input type="email" name="email" placeholder="" autocomplete="email" required>
                    </div>
                    <button type="submit" class="btn-submit">Send Reset Code</button>
                </form>
                <a href="index.php" class="back-link">← Back to Login</a>

            <!-- ── STEP 2: OTP entry ──────────────────────────────────── -->
            <?php elseif ($step === 'otp'): ?>

                <p class="sub">We sent a 6-digit code to <strong><?= htmlspecialchars($masked) ?></strong></p>

                <?php if ($error === 'wrong'): ?>
                    <div class="error-msg">
                        Incorrect code.
                        <?= ($left > 0) ? "$left attempt" . ($left===1?'':'s') . " remaining." : 'No attempts left.' ?>
                    </div>
                <?php endif; ?>
                <div class="info-msg" id="resendOk">A new code was sent to your email!</div>

                <form method="POST" action="reset_verify_otp.php" id="otpForm">
                    <div class="otp-row">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1"
                                   id="d<?= $i ?>" name="d<?= $i ?>" autocomplete="off" required>
                        <?php endfor; ?>
                        <input type="hidden" name="otp" id="otpHidden">
                    </div>

                    <div class="timer-bar-wrap">
                        <div id="timerBar" style="width:100%"></div>
                    </div>

                    <button type="submit" class="btn-submit">Verify Code</button>
                </form>

                <div class="resend-row">
                    <span>Didn't get it?</span>
                    <button id="resendBtn" disabled>Resend in <span id="countdown">60</span>s</button>
                </div>
                <a href="forgot_password.php" class="back-link" onclick="<?php echo "fetch('reset_cancel.php');"; ?>">← Start over</a>

            <!-- ── STEP 3: New password ───────────────────────────────── -->
            <?php else: ?>

                <?php if ($error === 'mismatch'): ?>
                    <div class="error-msg">Passwords do not match.</div>
                <?php elseif ($error === 'short'): ?>
                    <div class="error-msg">Password must be at least 8 characters.</div>
                <?php elseif ($error === 'weak_password'): ?>
                    <div class="error-msg">Password requires 8+ chars with letters, numbers, and symbols.</div>
                <?php elseif ($error !== ''): ?>
                    <div class="error-msg">Something went wrong. Please try again.</div>
                <?php endif; ?>

                <form method="POST" action="reset_password_action.php">
                    <div class="input-group">
                        <label>New Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="newPass"
                                   autocomplete="new-password" required>
                            <button type="button" class="toggle-password"
                                    onclick="togglePassword('newPass',this)" aria-label="Show password">
                                <svg class="eye-open" viewBox="0 0 24 24">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 12.5c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                                <svg class="eye-closed" style="display:none" viewBox="0 0 24 24">
                                    <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46A11.8 11.8 0 001 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                                </svg>
                            </button>
                        </div>
                        <div class="password-hint">Min 8 chars, requires letters, numbers & symbols</div>
                    </div>
                    <div class="input-group">
                        <label>Confirm Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm" id="confPass"
                                   autocomplete="new-password" required>
                            <button type="button" class="toggle-password"
                                    onclick="togglePassword('confPass',this)" aria-label="Show confirm password">
                                <svg class="eye-open" viewBox="0 0 24 24">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 12.5c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                                <svg class="eye-closed" style="display:none" viewBox="0 0 24 24">
                                    <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46A11.8 11.8 0 001 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Change Password</button>
                </form>

            <?php endif; ?>

        </div>
    </div>

    <script>
        function updateDateTime() {
            var now = new Date();
            var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            document.getElementById('currentDate').textContent = months[now.getMonth()] + ' ' + now.getDate() + ', ' + now.getFullYear();
            var h = now.getHours(), m = now.getMinutes(), ap = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            document.getElementById('currentTime').textContent = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ap;
        }
        updateDateTime(); setInterval(updateDateTime, 1000);


        function togglePassword(id, btn) {
            var inp = document.getElementById(id);
            var eo = btn.querySelector('.eye-open'), ec = btn.querySelector('.eye-closed');
            if (inp.type === 'password') { inp.type = 'text'; eo.style.display='none'; ec.style.display='block'; }
            else { inp.type = 'password'; eo.style.display='block'; ec.style.display='none'; }
        }

        <?php if ($step === 'otp'): ?>
        // ── OTP digit inputs ──────────────────────────────────────────────
        var digits = document.querySelectorAll('.otp-row input[type=text]');
        var hidden = document.getElementById('otpHidden');

        function syncHidden() {
            hidden.value = Array.from(digits).map(function(d){ return d.value; }).join('');
        }
        digits.forEach(function(inp, idx) {
            inp.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g,'').slice(-1);
                syncHidden();
                if (this.value && idx < digits.length - 1) digits[idx+1].focus();
            });
            inp.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && idx > 0) digits[idx-1].focus();
            });
            inp.addEventListener('paste', function(e) {
                e.preventDefault();
                var pasted = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
                pasted.split('').forEach(function(ch,i){ if(digits[idx+i]) digits[idx+i].value=ch; });
                syncHidden();
                digits[Math.min(idx+pasted.length, digits.length-1)].focus();
            });
        });
        document.getElementById('otpForm').addEventListener('submit', syncHidden);

        // ── Countdown & expiry bar ────────────────────────────────────────
        var TOTAL = <?= (int)$remaining_secs ?>;
        var bar = document.getElementById('timerBar');
        var expiryLeft = TOTAL; var resendLeft = 60;
        var btn = document.getElementById('resendBtn'); var cd = document.getElementById('countdown');

        var timer = setInterval(function() {
            expiryLeft--; resendLeft--;
            bar.style.width = Math.max(0, expiryLeft / <?= OTP_EXPIRY_SECS ?> * 100) + '%';
            if (expiryLeft <= 0) { clearInterval(timer); window.location.href='forgot_password.php?status=expired'; return; }
            if (resendLeft > 0) { cd.textContent = resendLeft; }
            else { btn.disabled = false; btn.textContent = 'Resend code'; }
        }, 1000);

        // ── Resend AJAX ───────────────────────────────────────────────────
        btn.addEventListener('click', function() {
            btn.disabled = true; btn.textContent = 'Sending…';
            fetch('reset_resend_otp.php')
                .then(function(r){ return r.json(); })
                .then(function(data) {
                    if (data.ok) {
                        document.getElementById('resendOk').style.display='block';
                        resendLeft = 60;
                        btn.innerHTML = 'Resend in <span id="countdown">60</span>s';
                        cd = document.getElementById('countdown');
                    } else {
                        btn.textContent = data.msg || 'Error.';
                        setTimeout(function(){ btn.disabled=false; btn.textContent='Resend code'; }, 3000);
                    }
                })
                .catch(function(){ btn.disabled=false; btn.textContent='Resend code'; });
        });
        <?php endif; ?>
    </script>
</body>

</html>