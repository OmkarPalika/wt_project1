<?php
session_start();

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: index.php");
    exit;
}

require_once "../scripts/db_connect.php";

$username = $password = "";
$username_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter your username.";
    } else {
        $username = trim($_POST["username"]);
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    if (empty($username_err) && empty($password_err)) {
        $sql = "SELECT id, username, password FROM users WHERE username = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            session_regenerate_id(true);

                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;

                            header("Location: index.php");
                            exit;
                        }
                    }
                }
                $login_err = "Invalid username or password.";
            } else {
                echo "We are unable to process your request at the moment. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    mysqli_close($link);
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Circle Up - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="mt-16">
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Sign In to Your Account</h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Or
                    <a href="index.php" class="font-medium text-indigo-600 hover:text-indigo-500"> browse around for now. </a>
                </p>
            </div>
            <form class="mt-8 space-y-6" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="my-8 space-y-6">
                    <div class="flex flex-col">
                        <input id="username" name="username" autocomplete="off"
                            class="appearance-none rounded-md w-full px-3 py-2 sm:text-sm <?php echo (!empty($username_err)) ? 'bg-red-50 border-red-500 text-red-900 placeholder-red-700 focus:ring-red-500' : 'border-gray-300 placeholder-gray-500 text-gray-900 focus:ring-indigo-500'; ?>"
                            value="<?php echo htmlspecialchars($username); ?>" placeholder="Username" />
                        <span class="mt-2 text-sm text-red-600"><?php echo $username_err; ?></span>
                    </div>
                    <div class="flex flex-col">
                        <input id="password" name="password" type="password"
                            class="appearance-none rounded-md w-full px-3 py-2 sm:text-sm <?php echo (!empty($password_err)) ? 'bg-red-50 border-red-500 text-red-900 placeholder-red-700 focus:ring-red-500' : 'border-gray-300 placeholder-gray-500 text-gray-900 focus:ring-indigo-500'; ?>"
                            placeholder="Password" />
                        <span class="mt-2 text-sm text-red-600"><?php echo $password_err; ?></span>
                    </div>
                    <span class="mt-2 text-sm text-red-600"><?php echo $login_err; ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900"> Remember Me </label>
                    </div>

                    <div class="text-sm">
                        <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500"> Forgot your password? </a>
                    </div>
                </div>
                <button type="submit" name="sign_in"
                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                clip-rule="evenodd" />
                        </svg>
                    </span>
                    Sign In
                </button>
            </form>
            <p class="mt-2 text-right text-sm text-gray-600">
                Don't have an account?
                <a href="sign-up.php" class="font-medium text-indigo-600 hover:text-indigo-500"> Sign Up. </a>
            </p>
        </div>
    </div>
</body>

</html>
