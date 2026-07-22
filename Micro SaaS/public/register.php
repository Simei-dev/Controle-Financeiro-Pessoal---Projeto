<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $passwordRaw = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($passwordRaw !== $confirm) {
        $error = "As senhas não coincidem";
    } else {

        $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                throw new Exception('Este email já está cadastrado');
            }

            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password) VALUES (?, ?, ?)"
            );
            $stmt->execute([$name, $email, $password]);

            $userId = $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                "INSERT INTO companies (user_id, name) VALUES (?, ?)"
            );
            $stmt->execute([$userId, 'Minha Empresa']);

            $companyId = $pdo->lastInsertId();

            session_regenerate_id(true);

            $_SESSION['user_id']    = $userId;
            $_SESSION['company_id'] = $companyId;

            $pdo->commit();

            header('Location: dashboard.php');
            exit;
        } catch (Exception $e) {

            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Criar conta - FinanceTracker</title>
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

        .container {

            width: 100%;
            max-width: 420px;
            padding: 20px;

        }


        /* LOGO */

        .logo {

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

        .card {

            background: white;

            padding: 30px;

            border-radius: 18px;

            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);

        }


        /* INPUT */

        .group {

            margin-bottom: 18px;

        }

        .group label {

            font-weight: bold;
            font-size: 14px;

        }

        .input {

            position: relative;
            margin-top: 6px;

        }

        .input input {

            width: 100%;
            padding: 12px 12px 12px 40px;

            border-radius: 10px;
            border: 1px solid #e2e8f0;

            font-size: 14px;

        }

        .input input:focus {

            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);

        }

        .icon {

            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.5;

        }


        /* BUTTON */

        .btn {

            width: 100%;

            padding: 13px;

            border: none;

            border-radius: 10px;

            font-weight: bold;

            font-size: 15px;

            color: white;

            cursor: pointer;

            background: linear-gradient(135deg, #3b82f6, #14b8a6);

            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);

        }

        .btn:hover {

            transform: translateY(-1px);
            box-shadow: 0 12px 25px rgba(59, 130, 246, 0.4);

        }


        /* BENEFITS */

        .benefits {

            margin-top: 20px;

        }

        .benefit {

            font-size: 14px;
            color: #475569;
            margin-bottom: 8px;

        }


        /* ERROR */

        .error {

            background: #fee2e2;
            color: #991b1b;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;

        }


        /* LOGIN LINK */

        .login {

            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;

        }

        .login a {

            color: #2563eb;
            font-weight: bold;
            text-decoration: none;

        }
    </style>
</head>

<body>

    <div class="container">

        <div class="logo">

            <div class="logo-box">💰</div>

            <div class="logo-title">FinanceTracker</div>

            <div class="logo-sub">Crie sua conta para começar</div>

        </div>


        <div class="card">

            <?php if (!empty($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>


            <form method="POST">

                <div class="group">
                    <label>Nome</label>
                    <div class="input">
                        <span class="icon">👤</span>
                        <input type="text" name="name" required placeholder="Seu nome">
                    </div>
                </div>


                <div class="group">
                    <label>Email</label>
                    <div class="input">
                        <span class="icon">📧</span>
                        <input type="email" name="email" required placeholder="seu@email.com">
                    </div>
                </div>


                <div class="group">
                    <label>Senha</label>
                    <div class="input">
                        <span class="icon">🔒</span>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>
                </div>


                <div class="group">
                    <label>Confirmar Senha</label>
                    <div class="input">
                        <span class="icon">🔒</span>
                        <input type="password" name="confirm_password" required placeholder="••••••••">
                    </div>
                </div>


                <button class="btn">
                    Criar Conta →
                </button>

            </form>


            <div class="benefits">

                <div class="benefit">✔ Acompanhe receitas e despesas</div>

                <div class="benefit">✔ Visualize padrões de gastos</div>

                <div class="benefit">✔ Gerencie seu orçamento efetivamente</div>

            </div>


            <div class="login">

                Já tem uma conta?
                <a href="login.php">Entrar</a>

            </div>


        </div>

    </div>

</body>

</html>