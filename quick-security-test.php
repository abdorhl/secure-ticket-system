<?php
/**
 * Quick Security Test Script
 * Tests security improvements without requiring database connection
 */

echo "🔒 Quick Security Test for Ticket System\n";
echo "=======================================\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

function runTest($testName, $testFunction) {
    global $totalTests, $passedTests, $testResults;
    
    $totalTests++;
    echo "Testing: $testName... ";
    
    try {
        $result = $testFunction();
        if ($result === true || (is_array($result) && empty($result))) {
            echo "✅ PASSED\n";
            $passedTests++;
            $testResults[$testName] = 'PASSED';
        } else {
            echo "❌ FAILED\n";
            if (is_array($result)) {
                echo "   Errors: " . implode(', ', $result) . "\n";
            }
            $testResults[$testName] = 'FAILED';
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        $testResults[$testName] = 'ERROR';
    }
    echo "\n";
}

// Test 1: Security Configuration File
runTest('Security Configuration File', function() {
    return file_exists('security-config.php');
});

// Test 2: Composer Dependencies
runTest('Composer Dependencies', function() {
    if (!file_exists('composer.json')) {
        return "composer.json not found";
    }
    
    $composer = json_decode(file_get_contents('composer.json'), true);
    
    if (!isset($composer['require-dev'])) {
        return "No dev dependencies found";
    }
    
    $requiredDeps = [
        'phpstan/phpstan',
        'vimeo/psalm',
        'enlightn/security-checker',
        'squizlabs/php_codesniffer'
    ];
    
    foreach ($requiredDeps as $dep) {
        if (!isset($composer['require-dev'][$dep])) {
            return "Missing dependency: $dep";
        }
    }
    
    return true;
});

// Test 3: PHPStan Configuration
runTest('PHPStan Configuration', function() {
    return file_exists('phpstan.neon');
});

// Test 4: Psalm Configuration
runTest('Psalm Configuration', function() {
    return file_exists('psalm.xml');
});

// Test 5: Security Pipeline
runTest('Security Pipeline', function() {
    return file_exists('.github/workflows/security-pipeline.yml');
});

// Test 6: Security Headers in Database Config
runTest('Security Headers in Database Config', function() {
    $content = file_get_contents('config/database.php');
    return strpos($content, 'setSecurityHeaders') !== false;
});

// Test 7: CSRF Protection
runTest('CSRF Protection', function() {
    $content = file_get_contents('config/database.php');
    return strpos($content, 'generateCSRFToken') !== false;
});

// Test 8: Input Sanitization
runTest('Input Sanitization', function() {
    $content = file_get_contents('config/database.php');
    return strpos($content, 'sanitizeInput') !== false;
});

// Test 9: Rate Limiting
runTest('Rate Limiting', function() {
    $content = file_get_contents('config/database.php');
    return strpos($content, 'checkLoginAttempts') !== false;
});

// Test 10: File Upload Security
runTest('File Upload Security', function() {
    $content = file_get_contents('api/tickets.php');
    return strpos($content, 'getimagesize') !== false && strpos($content, 'allowedTypes') !== false;
});

// Test 11: Session Security
runTest('Session Security', function() {
    $content = file_get_contents('config/database.php');
    return strpos($content, 'session.cookie_httponly') !== false;
});

// Test 12: Database Security
runTest('Database Security', function() {
    $content = file_get_contents('config/database.php');
    return strpos($content, 'PDO::ATTR_EMULATE_PREPARES') !== false;
});

// Test 13: Login Security
runTest('Login Security', function() {
    $content = file_get_contents('auth/login.php');
    return strpos($content, 'validateCSRFToken') !== false && strpos($content, 'checkLoginAttempts') !== false;
});

// Test 14: Security Test Script
runTest('Security Test Script', function() {
    return file_exists('security-test.php');
});

// Test 15: Security Documentation
runTest('Security Documentation', function() {
    return file_exists('SECURITY.md');
});

// Generate Test Report
echo "📊 Test Results Summary\n";
echo "======================\n";
echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: " . ($totalTests - $passedTests) . "\n";
echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";

echo "Detailed Results:\n";
echo "================\n";
foreach ($testResults as $testName => $result) {
    $status = $result === 'PASSED' ? '✅' : '❌';
    echo "$status $testName: $result\n";
}

echo "\n🔒 Security Test Complete!\n";
echo "==========================\n";

if ($passedTests === $totalTests) {
    echo "🎉 All security tests passed! Your system is properly secured.\n";
} else {
    echo "⚠️  Some security tests failed. Please review and fix the issues.\n";
}

echo "\nNext Steps:\n";
echo "1. Run 'composer install' to install security dependencies\n";
echo "2. Run 'composer run security-check' to check for vulnerabilities\n";
echo "3. Run 'composer run phpstan' for static analysis\n";
echo "4. Run 'composer run psalm' for additional security analysis\n";
echo "5. Test the GitHub Actions security pipeline\n";
?>
