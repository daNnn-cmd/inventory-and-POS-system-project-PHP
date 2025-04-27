<?php
session_start();
require 'db_connect.php';

// Initialize the error variable
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Basic validation
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Fetch user from the database
        $stmt = $conn->prepare("SELECT * FROM User WHERE Username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify plain text password
        if ($user && $user['Password'] === $password) {
            session_regenerate_id(true); // Prevent session fixation

            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['role'] = $user['Role'];

            // Redirect user based on role
            if ($user['Role'] == 'Admin') {
                header('Location: dashboard_admin.php');
            } else {
                header('Location: dashboard_cashier.php');
            }
            exit();
        } else {
            $error = 'Invalid username or password.';           }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pet Zoofari</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(to bottom, #001f3f, #003366, #004080);
            position: relative;
        }

        /* Aquarium Background */
        .aquarium {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        /* Fish Animations */
        .fish {
            position: absolute;
            font-size: 40px;
            color: #ffcc00;
            animation: swim linear infinite;
        }

        @keyframes swim {
            0% {
                transform: translateX(-100px) scaleX(1);
            }
            100% {
                transform: translateX(calc(100vw + 100px)) scaleX(1);
            }
        }

        .fish.reverse {
            animation: swimReverse linear infinite;
        }

        @keyframes swimReverse {
            0% {
                transform: translateX(calc(100vw + 100px)) scaleX(-1);
            }
            100% {
                transform: translateX(-100px) scaleX(-1);
            }
        }

        /* Bubbles */
        .bubble {
            position: absolute;
            width: 10px;
            height: 10px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: floatUp 5s infinite ease-in;
        }

        @keyframes floatUp {
            0% {
                transform: translateY(100vh);
                opacity: 0;
            }
            50% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px);
                opacity: 0;
            }
        }

        /* Login Container */
        .login-container {
            background: rgba(28, 28, 28, 0.95);
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 255, 255, 0.3);
            width: 100%;
            max-width: 350px;
            text-align: center;
            border: 1px solid #00cccc;
            position: relative;
            z-index: 2;
            backdrop-filter: blur(10px);
            animation: fadeIn 1.5s ease-in-out;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container h2 {
            color: #00ffff;
            font-size: 30px;
            margin-bottom: 15px;
            text-shadow: 0 0 15px rgba(0, 255, 255, 0.7);
            animation: glow 2s infinite alternate;
        }

        h2{
            margin-bottom: 20px;
            margin-top: -20px;
        }
        

        @keyframes glow {
            0% {
                text-shadow: 0 0 15px rgba(0, 255, 255, 0.7);
            }
            100% {
                text-shadow: 0 0 25px rgba(0, 255, 255, 1);
            }
        }

        .error {
            color: #ff4444;
            font-weight: bold;
            text-shadow: 0 0 10px rgba(255, 68, 68, 0.7);
            display: block;
            text-align: center;
            margin-bottom: 15px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            25% {
                transform: translateX(-10px);
            }
            75% {
                transform: translateX(10px);
            }
        }

        .login-container input {
            width: 80%;
            padding: 12px;
            margin: 10px 0;
            border: 2px solid transparent;
            border-radius: 25px;
            background: #2b2b2b;
            color: #00ffff;
            font-size: 15px;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .login-container input:focus {
            border-color: #00ffff;
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
        }

        .login-container button {
            width: 80%;
            padding: 12px;
            background-color: #00ced1;
            border: none;
            border-radius: 25px;
            color: #0d0d0d;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease, box-shadow 0.3s ease, transform 0.3s ease;
        }

        .login-container button:hover {
            background-color: #20b2aa;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.8);
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Aquarium Background -->
    <div class="aquarium">
        <div class="fish">🐟</div>
        <div class="fish reverse">🐠</div>
        <div class="fish" style="top: 20%; animation-duration: 15s;">🐡</div>
        <div class="fish reverse" style="top: 50%; animation-duration: 20s;">🐟</div>
        <div class="fish" style="top: 70%; animation-duration: 25s;">🐠</div>
        <div class="bubble" style="left: 10%; animation-delay: 0s;"></div>
        <div class="bubble" style="left: 30%; animation-delay: 2s;"></div>
        <div class="bubble" style="left: 50%; animation-delay: 4s;"></div>
        <div class="bubble" style="left: 70%; animation-delay: 6s;"></div>
        <div class="bubble" style="left: 90%; animation-delay: 8s;"></div>
    </div>

    <!-- Login Container -->
    <div class="login-container">
        <h2>Pet Zoofari Login</h2>
        <?php 
        ob_start(); 
        if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif;
        ob_end_flush(); 
        ?>
        <form action="login.php" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>

    <!-- Fish and Bubble Script -->
    <script>
        const aquarium = document.querySelector('.aquarium');

        // Add random fish
        for (let i = 0; i < 10; i++) {
            const fish = document.createElement('div');
            fish.classList.add('fish');
            if (Math.random() > 0.5) fish.classList.add('reverse');
            fish.style.top = `${Math.random() * 100}%`;
            fish.style.animationDuration = `${Math.random() * 15 + 10}s`;
            fish.textContent = Math.random() > 0.5 ? '🐟' : '🐠';
            aquarium.appendChild(fish);
        }

        // Add random bubbles
        for (let i = 0; i < 20; i++) {
            const bubble = document.createElement('div');
            bubble.classList.add('bubble');
            bubble.style.left = `${Math.random() * 100}%`;
            bubble.style.animationDelay = `${Math.random() * 10}s`;
            aquarium.appendChild(bubble);
        }
    </script>
</body>
</html>