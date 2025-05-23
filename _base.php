<?php
include_once 'config/database.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

// Is GET request?
function is_get() {
    return $_SERVER['REQUEST_METHOD'] == 'GET';
}

// Is POST request?
function is_post() {
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}

// Obtain GET parameter
function get($key, $value = null) {
    $value = $_GET[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Obtain POST parameter
function post($key, $value = null) {
    $value = $_POST[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Obtain REQUEST (GET and POST) parameter
function req($key, $value = null) {
    $value = $_REQUEST[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

function redirect($url = null) {
    $url ??= $_SERVER['REQUEST_URI'];
    header("Location: $url");
    exit();
}

// Set or get temporary session variable
function temp($key, $value = null) {
    if ($value !== null) {
        $_SESSION["temp_$key"] = $value;
    }
    else {
        $value = $_SESSION["temp_$key"] ?? null;
        unset($_SESSION["temp_$key"]);
        return $value;
    }
}

// Return local root path
function root($path = '') {
    return "$_SERVER[DOCUMENT_ROOT]/$path";
}

// Return base url (host + port)
function base($path = '') {
    return "http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]/$path";
}

// ============================================================================
// Database Setups and Functions
// ============================================================================

// Global PDO object

    $_db = new PDO('mysql:host=localhost;dbname=TESTING1', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    ]);


// ============================================================================
// HTML Helpers
// ============================================================================

// Encode HTML special characters
function encode($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Generate <input type='text'>
function html_text($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='text' id='$key' name='$key' value='$value' $attr>";
}

function html_pwd($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='password' id='$key' name='$key' value='$value' $attr>";
}

// Generate <input type='password'>
function html_password($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='password' id='$key' name='$key' value='$value' $attr>";
}

// Generate <input type='radio'> list
function html_radios($key, $items, $br = false, $disabled = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo '<div>';
    foreach ($items as $id => $text) {
        $checked = ($id == $value) ? 'checked' : '';
        $disabledAttr = $disabled ? 'disabled' : ''; // Ensure disabled is applied
        echo "<label><input type='radio' id='{$key}_$id' name='$key' value='$id' $checked $disabledAttr> $text</label>";
        if ($br) {
            echo '<br>';
        }
    }
    echo '</div>';
}

// Generate <select>
function html_select($key, $items, $default = '- Select One -', $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<select id='$key' name='$key' $attr>";
    if ($default !== null) {
        echo "<option value=''>$default</option>";
    }
    foreach ($items as $id => $text) {
        $state = $id == $value ? 'selected' : '';
        echo "<option value='$id' $state>$text</option>";
    }
    echo '</select>';
}

// ============================================================================
// Error Handlings
// ============================================================================

// Global error array
$_err = [];

// Generate <span class='err'>
function err($key) {
    global $_err;
    if ($_err[$key] ?? false) {
        echo "<span class='err'>$_err[$key]</span>";
    }
    else {
        echo '<span></span>';
    }
}

// ============================================================================
// Security
// ============================================================================

// Global user object
session_start();
require_once 'config/database.php';
$_user = $_SESSION['user'] ?? null;

// Login user
function login($user, $url = '/') {
    $_SESSION['user'] = $user;
    redirect($url);
}

// Logout user
// function logout($url = '/') {
//     unset($_SESSION['user']);
//     redirect($url);
// }

// Authorization
function auth(...$roles) {
    global $_user;

    if ($_user) {
        // echo "User is logged in. Role: " . $_user['role'] . "<br>";

        if ($roles) {
            //echo "Roles required: ";
           // print_r($roles); // Display required roles
           // echo "<br>";

            if (in_array($_user->role, $roles)) {
                // echo "✅ User role matches. Access granted.<br>";
                return; // ✅ Allow access
            } else {
                // echo "❌ User role does NOT match. Access denied.<br>";
            }
        } else {
            // echo "✅ No specific role required. Access granted.<br>";
            return; // ✅ Allow access
        }
    } else {
        // echo "❌ User is NOT logged in. Access denied.<br>";
    }

    redirect('/pages/signup_login.php'); 
}


// Check session and 'remember me'
function auth_user() {
    global $_user, $_db; // Use the global $_db object

    // If session exists, keep user logged in
    if (isset($_SESSION['user'])) {
        $_user = $_SESSION['user'];
        return;
    }

    // Check "Remember Me" token
    if (isset($_COOKIE['remember_me'])) {
        $token = $_COOKIE['remember_me'];

        // Use PDO syntax
        $stmt = $_db->prepare("SELECT users.* FROM users INNER JOIN token ON users.user_id = token.user_id WHERE token.token_id = :token AND token.expire > NOW()");
        $stmt->execute([':token' => $token]); // Bind parameter using execute

        // Fetch the user
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC); // Use fetch with PDO::FETCH_ASSOC

        if ($user_data) {
            $_user = $user_data;
            $_SESSION['user'] = $_user; // Restore session
            return;
        }
    }
}

// ============================================================================
// Email Functions
// ============================================================================

// Demo Accounts:
// --------------
// leejs-jm22@student.tarc.edu.ny   gqia feuv ypmk wdet


// Initialize and return mail object
function get_mail() {
    require_once 'lib/PHPMailer.php';
    require_once 'lib/SMTP.php';

    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->SMTPAuth = true;
    $m->Host = 'smtp.gmail.com';
    $m->Port = 587;
    $m->Username = 'leejs-jm22@student.tarc.edu.my';
    $m->Password = 'gqia feuv ypmk wdet';
    $m->CharSet = 'utf-8';
    $m->setFrom($m->Username, 'Admin');

    return $m;
}

