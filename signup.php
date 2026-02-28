<?php
session_start();
if (!empty($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PITS - Sign Up</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --bg-color: #ffffff;
            --primary-color: #545454;
            --input-bg-color: rgba(84, 84, 84, 0.35);
            --font-heading: 'Poppins', sans-serif;
            --font-label: 'DM Sans', sans-serif;
            --font-input: 'Poppins', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--primary-color);
            font-family: var(--font-heading);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3rem;
            padding: 2rem;
            position: relative;
        }

        .logo-section {
            position: absolute;
            left: 2rem;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            justify-content: flex-start;
            align-items: center;
        }

        .form-wrapper {
            max-width: 432px;
            width: 100%;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 2.25rem;
            margin-bottom: 0.45rem;
            color: var(--primary-color);
        }

        .subtitle {
            font-family: var(--font-label);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 2.7rem;
            color: var(--primary-color);
        }

        .input-group {
            margin-bottom: 1.35rem;
            text-align: left;
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper input {
            padding-right: 45px;
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
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.6;
            transition: opacity 0.2s;
        }

        .toggle-password:hover {
            opacity: 1;
        }

        .toggle-password svg {
            width: 20px;
            height: 20px;
            fill: var(--primary-color);
        }

        label {
            display: block;
            font-family: var(--font-label);
            font-size: 0.77rem;
            letter-spacing: 0.9px;
            text-transform: uppercase;
            margin-bottom: 0.45rem;
            font-weight: 500;
            color: var(--primary-color);
        }

        input {
            width: 100%;
            padding: 13px;
            background-color: var(--input-bg-color);
            border: none;
            outline: none;
            font-family: var(--font-input);
            font-size: 0.85rem;
            color: #333;
            border-radius: 2px;
        }

        ::placeholder {
            color: #545454;
            opacity: 0.8;
        }

        .btn-submit {
            background: none;
            border: none;
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--primary-color);
            cursor: pointer;
            margin-top: 0.9rem;
            padding: 9px 18px;
        }

        .btn-submit:hover {
            opacity: 0.8;
        }

        form {
            width: 100%;
            max-width: 432px;
        }

        .error-msg {
            color: #c0392b;
            font-family: var(--font-label);
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }

        .success-msg {
            color: #27ae60;
            font-family: var(--font-label);
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }

        .link-text {
            font-family: var(--font-label);
            font-size: 0.8rem;
            margin-top: 1.5rem;
            color: var(--primary-color);
        }

        .link-text a {
            color: var(--primary-color);
            font-weight: 700;
            text-decoration: none;
        }

        .link-text a:hover {
            opacity: 0.7;
        }

        .password-hint {
            font-family: var(--font-label);
            font-size: 0.7rem;
            color: #888;
            margin-top: 0.3rem;
        }

        @media (max-width: 768px) {
            .logo-section {
                position: static;
                transform: none;
                margin-bottom: 2rem;
            }

            .container {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo-section">
            <img src="pits logo.png" alt="PITS Logo" style="width: 120px; height: auto;">
        </div>
        <div class="form-wrapper">
            <h1>Create new Account</h1>
            <div class="subtitle">Already Registered? <a href="index.php"
                    style="color: var(--primary-color); font-weight: 700; text-decoration: none;">Login</a></div>
            <?php if ($error === '1'): ?>
                <div class="error-msg">Username already exists.</div>
            <?php elseif ($error === '2'): ?>
                <div class="error-msg">Invalid username. Use only letters, numbers, and underscores (3–32 characters).</div>
            <?php elseif ($error === '3'): ?>
                <div class="error-msg">Password must be at least 6 characters.</div>
            <?php elseif ($error === '4'): ?>
                <div class="error-msg">Passwords do not match.</div>
            <?php elseif ($error === '5'): ?>
                <div class="error-msg">Failed to create account. Contact the administrator.</div>
            <?php endif; ?>
            <?php if ($success === '1'): ?>
                <div class="success-msg">Account created successfully! You can now log in.</div>
            <?php endif; ?>
            <form action="register.php" method="POST">
                <div class="input-group">
                    <label>USERNAME</label>
                    <input type="text" name="username" placeholder="Choose a username" required>
                </div>
                <div class="input-group">
                    <label>PASSWORD</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" placeholder="******" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password', this)"
                            aria-label="Show password">
                            <svg class="eye-open" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 12.5c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                            </svg>
                            <svg class="eye-closed" style="display:none" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46A11.8 11.8 0 001 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z" />
                            </svg>
                        </button>
                    </div>
                    <div class="password-hint">Minimum 6 characters</div>
                </div>
                <div class="input-group">
                    <label>CONFIRM PASSWORD</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="******"
                            required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)"
                            aria-label="Show password">
                            <svg class="eye-open" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 12.5c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                            </svg>
                            <svg class="eye-closed" style="display:none" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46A11.8 11.8 0 001 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Create Account</button>
            </form>
            <div class="link-text">Already have an account? <a href="index.php">Login</a></div>
            <div class="link-text"><a href="forgot_password.php">Forgot Password?</a></div>
        </div>
    </div>
    <script>
        function togglePassword(inputId, btn) {
            var input = document.getElementById(inputId);
            var eyeOpen = btn.querySelector('.eye-open');
            var eyeClosed = btn.querySelector('.eye-closed');
            if (input.type === 'password') {
                input.type = 'text';
                eyeOpen.style.display = 'none';
                eyeClosed.style.display = 'block';
                btn.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                eyeOpen.style.display = 'block';
                eyeClosed.style.display = 'none';
                btn.setAttribute('aria-label', 'Show password');
            }
        }
    </script>
</body>

</html>