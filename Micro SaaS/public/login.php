<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];

        $stmt = $pdo->prepare("
            SELECT id 
            FROM companies 
            WHERE user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$user['id']]);
        $company = $stmt->fetch();

        if (!$company) {
            die('Empresa não encontrada para este usuário');
        }

        $_SESSION['company_id'] = $company['id'];

        header('Location: dashboard.php');
        exit;
    }

    $error = "Email ou senha incorretos";
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Entrar - FinanceTracker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #eff6ff, #ffffff, #ecfeff);
        }

        /* CONTAINER */

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        /* LOGO */

        .logo-area {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-box {
            width: 64px;
            height: 64px;
            margin: auto;
            border-radius: 16px;
            background: linear-gradient(135deg, #3b82f6, #14b8a6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .logo-title {
            font-size: 28px;
            font-weight: bold;
            margin-top: 15px;
            background: linear-gradient(135deg, #2563eb, #0d9488);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            -webkit-text-fill-color: transparent;
        }

        .logo-sub {
            color: #64748b;
            margin-top: 5px;
        }

        /* CARD */

        .login-card {
            background: white;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        }

        /* INPUT GROUP */

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            font-weight: bold;
            font-size: 14px;
        }

        .input-box {
            position: relative;
            margin-top: 6px;
        }

        .input-box input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            font-size: 14px;
            transition: 0.2s;
        }

        .input-box input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15)
        }

        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.5;
        }

        /* BUTTON */

        .login-btn {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            color: white;
            font-size: 15px;
            cursor: pointer;
            background: linear-gradient(135deg, #3b82f6, #14b8a6);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
            transition: 0.2s;
        }

        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 25px rgba(59, 130, 246, 0.4);
        }

        /* ERROR */

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        /* REGISTER */

        .register {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .register a {
            color: #2563eb;
            font-weight: bold;
            text-decoration: none;
        }

        .register a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="login-container">

        <div class="logo-area">

            <div class="logo-box">
                💰
            </div>

            <div class="logo-title">
                FinanceTracker
            </div>

            <div class="logo-sub">
                Entre para gerenciar suas finanças
            </div>

        </div>


        <div class="login-card">

            <?php if (!empty($error)): ?>
                <div class="error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>


            <form method="POST">

                <div class="input-group">

                    <label>Email</label>

                    <div class="input-box">

                        <span class="input-icon">📧</span>

                        <input type="email" name="email" required placeholder="seu@email.com">

                    </div>

                </div>


                <div class="input-group">

                    <label>Senha</label>

                    <div class="input-box">

                        <span class="input-icon">🔒</span>

                        <input type="password" name="password" required placeholder="••••••••">

                    </div>

                </div>


                <button class="login-btn">
                    Entrar →
                </button>

            </form>


            <div class="register">

                Não tem uma conta?
                <a href="register.php">
                    Cadastre-se grátis
                </a>

            </div>

        </div>

    </div>

</body>

</html>