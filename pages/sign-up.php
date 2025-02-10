<?php
require_once "../scripts/db_connect.php";

$username = $first_name = $last_name = $email = $password = $confirm_password = "";
$username_err = $first_name_err = $last_name_err = $email_err = $password_err = $confirm_password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))) {
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    // Validate first name
    if (empty(trim($_POST["first_name"]))) {
        $first_name_err = "Please enter your first name.";
    } else {
        $first_name = trim($_POST["first_name"]);
    }

    // Validate last name
    if (empty(trim($_POST["last_name"]))) {
        $last_name_err = "Please enter your last name.";
    } else {
        $last_name = trim($_POST["last_name"]);
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email address.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        $sql = "SELECT id FROM users WHERE email = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = trim($_POST["email"]);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $email_err = "This email address is already taken.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm your password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Passwords do not match.";
        }
    }

    // If no errors, insert into database
    if (empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
        $sql = "INSERT INTO users (username, first_name, last_name, email, password) VALUES (?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssss", $param_username, $first_name, $last_name, $param_email, $param_password);

            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT);

            if (mysqli_stmt_execute($stmt)) {
                header("location: sign-in.php");
            } else {
                echo "Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Circle Up - Sign Up</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="mt-16">
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Create a New Account</h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Or
                    <a href="index.php" class="font-medium text-indigo-600 hover:text-indigo-500"> browse around for now. </a>
                </p>
            </div>
            <form class="mt-8 space-y-6" method="POST">
                <div class="my-8 space-y-4">
                    <div class="flex flex-col">
                        <input id="username" name="username" autocomplete="off" class="appearance-none relative block w-full px-3 py-2 focus:z-10 sm:text-sm rounded-md <?php echo (!empty($username_err)) ? 'bg-red-50 border border-red-500 text-red-900 placeholder-red-700 focus:outline-none focus:ring-red-500 focus:border-red-500' : 'border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 '; ?>" value="<?php echo $username; ?>" placeholder="Username" />
                        <span class="mt-2 text-sm text-red-600"><?php echo $username_err; ?></span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col w-50">
                            <input id="first-name" name="first_name" autocomplete="off" class="appearance-none relative block w-full px-3 py-2 focus:z-10 sm:text-sm rounded-md <?php echo (!empty($first_name_err)) ? 'bg-red-50 border border-red-500 text-red-900 placeholder-red-700 focus:outline-none focus:ring-red-500 focus:border-red-500' : 'border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 '; ?>" value="<?php echo $first_name; ?>" placeholder="First Name" />
                            <span class="mt-2 text-sm text-red-600"><?php echo $first_name_err; ?></span>
                        </div>
                        <div class="flex flex-col">
                            <input id="last-name" name="last_name" autocomplete="off" class="appearance-none relative block w-full px-3 py-2 focus:z-10 sm:text-sm rounded-md <?php echo (!empty($last_name_err)) ? 'bg-red-50 border border-red-500 text-red-900 placeholder-red-700 focus:outline-none focus:ring-red-500 focus:border-red-500' : 'border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 '; ?>" value="<?php echo $last_name; ?>" placeholder="Last Name" />
                            <span class="mt-2 text-sm text-red-600"><?php echo $last_name_err; ?></span>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <input id="email" name="email" autocomplete="off" class="appearance-none relative block w-full px-3 py-2 focus:z-10 sm:text-sm rounded-md <?php echo (!empty($email_err)) ? 'bg-red-50 border border-red-500 text-red-900 placeholder-red-700 focus:outline-none focus:ring-red-500 focus:border-red-500' : 'border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 '; ?>" value="<?php echo $email; ?>" placeholder="Email" />
                        <span class="mt-2 text-sm text-red-600"><?php echo $email_err; ?></span>
                    </div>
                    <div class="flex flex-col">
                        <input id="password" name="password" type="password" class="appearance-none relative block w-full px-3 py-2 focus:z-10 sm:text-sm rounded-md <?php echo (!empty($password_err)) ? 'bg-red-50 border border-red-500 text-red-900 placeholder-red-700 focus:outline-none focus:ring-red-500 focus:border-red-500' : 'border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 '; ?>" value="<?php echo $password; ?>" placeholder="Password" />
                        <span class="mt-2 text-sm text-red-600"><?php echo $password_err; ?></span>
                    </div>
                    <div class="flex flex-col">
                        <input id="confirm-password" name="confirm_password" type="password" class="appearance-none relative block w-full px-3 py-2 focus:z-10 sm:text-sm rounded-md <?php echo (!empty($confirm_password_err)) ? 'bg-red-50 border border-red-500 text-red-900 placeholder-red-700 focus:outline-none focus:ring-red-500 focus:border-red-500' : 'border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 '; ?>" value="<?php echo $confirm_password; ?>" placeholder="Confirm Password" />
                        <span class="mt-2 text-sm text-red-600"><?php echo $confirm_password_err; ?></span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900"> Remember Me </label>
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        Sign Up
                    </button>
                </div>
            </form>
            <p class="mt-2 text-right text-sm text-gray-600">
                Already have an account?
                <a href="sign-in.php" class="font-medium text-indigo-600 hover:text-indigo-500"> Sign In. </a>
            </p>
        </div>
    </div>
</body>

</html>