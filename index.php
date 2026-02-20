<?php
session_start();
if (!empty($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PITS - Login</title>
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
            <h1>Login</h1>
            <div class="subtitle">Sign in to continue</div>
            <?php if ($error === '1'): ?>
                <div class="error-msg">Invalid username or password.</div>
            <?php elseif ($error === '2'): ?>
                <div class="error-msg">Too many login attempts. Try again in 10 minutes.</div>
            <?php endif; ?>
            <form action="auth.php" method="POST">
                <div class="input-group">
                    <label>LOGIN ID</label>
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="input-group">
                    <label>PASSWORD</label>
                    <input type="password" name="password" placeholder="******" required>
                </div>
                <button type="submit" class="btn-submit">Login</button>
            </form>
        </div>
    </div>
</body>

</html>