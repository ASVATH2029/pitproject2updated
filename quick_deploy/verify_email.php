<?php
/*
 * verify_email.php — Email OTP verification page for new account signup
 */
require_once __DIR__ . '/config.php';
session_start();

if (!empty($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}
session_write_close();

// No pending registration → back to signup
if (empty($_SESSION['pending_reg'])) {
    header('Location: signup.php');
    exit;
}

$reg      = $_SESSION['pending_reg'];
$email    = $reg['email'];
$masked   = preg_replace('/(?<=.{2}).(?=.*@)/u', '*', $email);
$error    = $_GET['error'] ?? '';
$left     = (int)($_GET['left'] ?? 5);
$otp_at   = $reg['otp_at'] ?? time();
$remaining_secs = max(0, OTP_EXPIRY_SECS - (time() - $otp_at));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PITS — Verify Email</title>
    <meta name="description" content="Verify your email to create your PITS account">
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

        body {
            min-height: 100vh;
            color: var(--text-primary);
            font-family: var(--font-label);
            overflow-x: hidden;
        }

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

        .icon-shield {
            width: 64px; height: 64px; margin: 0 auto 20px;
            background: rgba(160, 200, 140, 0.1);
            border: 1px solid rgba(160, 200, 140, 0.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        .icon-shield svg { width: 30px; height: 30px; fill: rgba(160,200,140,0.9); }

        h1 {
            font-family: var(--font-heading); font-weight: 400; font-size: 2rem;
            color: var(--text-primary); text-align: center; margin-bottom: 8px;
        }

        .sub {
            text-align: center; font-size: 0.88rem; color: var(--text-muted);
            margin-bottom: 8px; line-height: 1.6;
        }
        .sub strong { color: var(--text-cream); font-weight: 500; }

        .divider { border: none; border-top: 1px solid rgba(255,255,255,0.06); margin: 24px 0; }

        /* OTP input row */
        .otp-row {
            display: flex; gap: 10px; justify-content: center; margin: 28px 0 20px;
        }
        .otp-row input {
            width: 52px; height: 64px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 10px;
            font-family: monospace; font-size: 1.6rem; font-weight: 700;
            color: var(--text-primary); text-align: center; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .otp-row input:focus {
            border-color: rgba(160,200,140,0.5);
            box-shadow: 0 0 0 3px rgba(160,200,140,0.1);
        }

        .btn-submit {
            display: block; width: 100%;
            padding: 14px 48px;
            background: var(--btn-bg); color: var(--btn-text);
            border: none; border-radius: var(--radius-pill);
            font-family: var(--font-label); font-weight: 600; font-size: 0.9rem;
            cursor: pointer; letter-spacing: 0.3px; text-align: center;
            transition: transform 0.2s, box-shadow 0.2s, background 0.3s;
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(0,0,0,0.35); }
        .btn-submit:active { transform: scale(0.96) !important; }
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .resend-row {
            display: flex; justify-content: center; align-items: center;
            gap: 8px; margin-top: 18px; font-size: 0.82rem; color: var(--text-muted);
        }
        #resendBtn {
            background: none; border: none; cursor: pointer;
            color: var(--text-primary); font-weight: 700; font-size: 0.82rem;
            font-family: var(--font-label); text-decoration: underline; padding: 0;
        }
        #resendBtn:disabled { opacity: 0.4; cursor: not-allowed; text-decoration: none; }

        #countdown { font-variant-numeric: tabular-nums; }

        .timer-bar-wrap {
            height: 3px; background: rgba(255,255,255,0.06); border-radius: 2px;
            margin: 16px 0 0; overflow: hidden;
        }
        #timerBar {
            height: 100%; background: rgba(160,200,140,0.6); border-radius: 2px;
            transition: width 1s linear;
        }

        .error-msg {
            color: #e74c3c; font-size: 0.82rem; margin-bottom: 1rem;
            background: rgba(231,76,60,0.1); padding: 10px 14px;
            border-radius: 8px; border: 1px solid rgba(231,76,60,0.25);
        }
        .info-msg {
            color: #7dcea0; font-size: 0.82rem; margin-bottom: 1rem;
            background: rgba(125,206,160,0.1); padding: 10px 14px;
            border-radius: 8px; border: 1px solid rgba(125,206,160,0.25); display: none;
        }

        .back-link {
            display: block; text-align: center; margin-top: 22px;
            font-size: 0.8rem; color: var(--text-muted); text-decoration: none;
        }
        .back-link:hover { color: var(--text-primary); }


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

            <div class="icon-shield">
                <svg viewBox="0 0 24 24">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/>
                </svg>
            </div>

            <h1>Verify Your Email</h1>
            <p class="sub">We sent a 6-digit code to<br><strong><?= htmlspecialchars($masked) ?></strong></p>

            <hr class="divider">

            <?php if ($error === 'wrong'): ?>
                <div class="error-msg">
                    Incorrect code.
                    <?= ($left > 0) ? "$left attempt" . ($left === 1 ? '' : 's') . " remaining." : 'No attempts left — please sign up again.' ?>
                </div>
            <?php endif; ?>
            <div class="info-msg" id="resendOk">A new code has been sent!</div>

            <form method="POST" action="verify_email_action.php" id="otpForm">
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

                <button type="submit" class="btn-submit" id="submitBtn">Verify &amp; Create Account</button>
            </form>

            <div class="resend-row">
                <span>Didn't get it?</span>
                <button id="resendBtn" disabled>Resend in <span id="countdown">60</span>s</button>
            </div>

            <a href="signup.php" class="back-link">← Start over</a>
        </div>
    </div>

    <script>
        // ── DateTime ──────────────────────────────────────────────────────
        function updateDateTime() {
            var now = new Date();
            var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            document.getElementById('currentDate').textContent = months[now.getMonth()] + ' ' + now.getDate() + ', ' + now.getFullYear();
            var h = now.getHours(), m = now.getMinutes(), ap = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            document.getElementById('currentTime').textContent = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ap;
        }
        updateDateTime(); setInterval(updateDateTime, 1000);


        // ── OTP digit inputs — auto-advance & paste support ───────────────
        var digits = document.querySelectorAll('.otp-row input[type=text]');
        var hidden = document.getElementById('otpHidden');

        function syncHidden() {
            hidden.value = Array.from(digits).map(function(d){ return d.value; }).join('');
        }

        digits.forEach(function(inp, idx) {
            inp.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g,'').slice(-1);
                syncHidden();
                if (this.value && idx < digits.length - 1) digits[idx + 1].focus();
            });
            inp.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && idx > 0) {
                    digits[idx - 1].focus();
                }
            });
            inp.addEventListener('paste', function(e) {
                e.preventDefault();
                var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0, 6);
                pasted.split('').forEach(function(ch, i) { if (digits[idx + i]) digits[idx + i].value = ch; });
                syncHidden();
                var next = Math.min(idx + pasted.length, digits.length - 1);
                digits[next].focus();
            });
        });

        // Form submit — build hidden OTP value
        document.getElementById('otpForm').addEventListener('submit', function() {
            syncHidden();
        });

        // ── Resend countdown ──────────────────────────────────────────────
        var TOTAL_EXPIRY = <?= (int)$remaining_secs ?>;
        var RESEND_DELAY = 60;
        var bar = document.getElementById('timerBar');
        bar.style.width = '100%';

        var expiryLeft = TOTAL_EXPIRY;
        var resendLeft = RESEND_DELAY;
        var btn = document.getElementById('resendBtn');
        var cd  = document.getElementById('countdown');

        var timer = setInterval(function() {
            expiryLeft--;
            resendLeft--;

            // Expiry bar
            var pct = Math.max(0, expiryLeft / <?= OTP_EXPIRY_SECS ?> * 100);
            bar.style.width = pct + '%';
            if (expiryLeft <= 0) { clearInterval(timer); window.location.href = 'signup.php?error=expired'; return; }

            // Resend button countdown
            if (resendLeft > 0) {
                cd.textContent = resendLeft;
            } else {
                btn.disabled = false;
                btn.textContent = 'Resend code';
            }
        }, 1000);

        // ── Resend AJAX ───────────────────────────────────────────────────
        btn.addEventListener('click', function() {
            btn.disabled = true;
            btn.textContent = 'Sending…';
            fetch('resend_otp.php')
                .then(function(r){ return r.json(); })
                .then(function(data) {
                    if (data.ok) {
                        document.getElementById('resendOk').style.display = 'block';
                        resendLeft = 60;
                        cd.textContent = 60;
                        btn.innerHTML = 'Resend in <span id="countdown">60</span>s';
                        cd = document.getElementById('countdown');
                    } else {
                        btn.textContent = data.msg || 'Error. Try again.';
                        setTimeout(function(){ btn.disabled = false; btn.textContent = 'Resend code'; }, 3000);
                    }
                })
                .catch(function() {
                    btn.disabled = false; btn.textContent = 'Resend code';
                });
        });
    </script>
</body>

</html>
