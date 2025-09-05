<?php
/**
 * Security Test Script for Ticket System
 * Run this script to perform basic security checks
 */

echo "🔒 Ticket System Security Test\n";
echo "==============================\n\n";

// Test 1: PHP Version Check
echo "1. PHP Version Check:\n";
$phpVersion = PHP_VERSION;
$minVersion = '8.0.0';
if (version_compare($phpVersion, $minVersion, '>=')) {
    echo "   ✅ PHP version $phpVersion is supported\n";
} else {
    echo "   ❌ PHP version $phpVersion is too old. Minimum required: $minVersion\n";
}

// Test 2: Required Extensions
echo "\n2. Required Extensions Check:\n";
$requiredExtensions = ['pdo', 'pdo_mysql', 'gd', 'zip', 'curl', 'mbstring', 'xml', 'json'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ $ext extension loaded\n";
    } else {
        echo "   ❌ $ext extension missing\n";
    }
}

// Test 3: Session Security
echo "\n3. Session Security Check:\n";
$sessionConfig = [
    'session.cookie_httponly' => ini_get('session.cookie_httponly'),
    'session.cookie_secure' => ini_get('session.cookie_secure'),
    'session.use_strict_mode' => ini_get('session.use_strict_mode'),
    'session.cookie_samesite' => ini_get('session.cookie_samesite')
];

foreach ($sessionConfig as $key => $value) {
    $status = $value ? '✅' : '⚠️';
    echo "   $status $key: " . ($value ? 'Enabled' : 'Disabled') . "\n";
}

// Test 4: Password Hashing
echo "\n4. Password Hashing Test:\n";
$testPassword = 'testpassword123';
$hashed = password_hash($testPassword, PASSWORD_DEFAULT);
if (password_verify($testPassword, $hashed)) {
    echo "   ✅ Password hashing working correctly\n";
} else {
    echo "   ❌ Password hashing failed\n";
}

// Test 5: SQL Injection Protection
echo "\n5. SQL Injection Protection Test:\n";
try {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test prepared statements
    $stmt = $pdo->prepare("SELECT * FROM test WHERE id = ?");
    echo "   ✅ Prepared statements supported\n";
    
    // Test parameter binding
    $testId = 1;
    $stmt->bindParam(1, $testId, PDO::PARAM_INT);
    echo "   ✅ Parameter binding working\n";
    
} catch (Exception $e) {
    echo "   ❌ PDO error: " . $e->getMessage() . "\n";
}

// Test 6: XSS Protection
echo "\n6. XSS Protection Test:\n";
$xssTests = [
    '<script>alert("XSS")</script>',
    'javascript:alert("XSS")',
    '<img src=x onerror=alert("XSS")>',
    '"><script>alert("XSS")</script>'
];

foreach ($xssTests as $test) {
    $escaped = htmlspecialchars($test, ENT_QUOTES, 'UTF-8');
    if ($escaped !== $test) {
        echo "   ✅ XSS protection working for: " . substr($test, 0, 30) . "...\n";
    } else {
        echo "   ❌ XSS protection failed for: " . substr($test, 0, 30) . "...\n";
    }
}

// Test 7: File Upload Security
echo "\n7. File Upload Security Test:\n";
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
$testFiles = [
    'test.jpg' => 'image/jpeg',
    'test.php' => 'application/x-httpd-php',
    'test.exe' => 'application/x-msdownload',
    'test.png' => 'image/png'
];

foreach ($testFiles as $filename => $mimeType) {
    if (in_array($mimeType, $allowedTypes)) {
        echo "   ✅ File type allowed: $filename ($mimeType)\n";
    } else {
        echo "   ❌ File type blocked: $filename ($mimeType)\n";
    }
}

// Test 8: CSRF Token Generation
echo "\n8. CSRF Token Generation Test:\n";
$token1 = bin2hex(random_bytes(32));
$token2 = bin2hex(random_bytes(32));

if ($token1 !== $token2 && strlen($token1) === 64) {
    echo "   ✅ CSRF token generation working\n";
} else {
    echo "   ❌ CSRF token generation failed\n";
}

// Test 9: Input Validation
echo "\n9. Input Validation Test:\n";
$testInputs = [
    'valid@email.com' => filter_var('valid@email.com', FILTER_VALIDATE_EMAIL),
    'invalid-email' => filter_var('invalid-email', FILTER_VALIDATE_EMAIL),
    'http://example.com' => filter_var('http://example.com', FILTER_VALIDATE_URL),
    'not-a-url' => filter_var('not-a-url', FILTER_VALIDATE_URL)
];

foreach ($testInputs as $input => $result) {
    $status = $result ? '✅' : '❌';
    echo "   $status Input validation for: $input\n";
}

// Test 10: Error Reporting
echo "\n10. Error Reporting Check:\n";
$errorReporting = error_reporting();
if ($errorReporting & E_ALL) {
    echo "   ✅ Error reporting enabled\n";
} else {
    echo "   ⚠️ Error reporting may be disabled\n";
}

$displayErrors = ini_get('display_errors');
if (!$displayErrors) {
    echo "   ✅ Display errors disabled (good for production)\n";
} else {
    echo "   ⚠️ Display errors enabled (should be disabled in production)\n";
}

echo "\n🔒 Security Test Complete!\n";
echo "==============================\n";
echo "Review the results above and address any issues marked with ❌ or ⚠️\n";
echo "For production deployment, ensure all security measures are properly configured.\n";
?>
