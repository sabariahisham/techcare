<?php
// Direct test of phpCAS without Laravel
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Disable output buffering to see errors immediately
if (ob_get_level()) ob_end_flush();

echo "<h1>Testing phpCAS</h1>";

// Try to load phpCAS
$casPath = dirname(__DIR__) . '/vendor/apereo/phpcas/CAS.php';
echo "<p>CAS.php path: $casPath</p>";

if (file_exists($casPath)) {
    echo "<p style='color:green'>✓ CAS.php file exists</p>";
    
    require_once($casPath);
    
    if (class_exists('phpCAS')) {
        echo "<p style='color:green'>✓ phpCAS class loaded</p>";
        
        if (defined('CAS_VERSION_2_0')) {
            echo "<p style='color:green'>✓ CAS_VERSION_2_0 defined: " . CAS_VERSION_2_0 . "</p>";
        } else {
            echo "<p style='color:red'>✗ CAS_VERSION_2_0 not defined</p>";
        }
        
        // Try to initialize with error handler
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            echo "<p style='color:red'>PHP Error [$errno]: $errstr in $errfile on line $errline</p>";
            return false;
        });
        
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                echo "<p style='color:red'>✗ Fatal Error: {$error['message']}</p>";
                echo "<p style='color:red'>File: {$error['file']} Line: {$error['line']}</p>";
            }
        });
        
        try {
            echo "<p>Attempting to initialize phpCAS...</p>";
            flush();
            
            // Start session before phpCAS
            if (session_status() === PHP_SESSION_NONE) {
                echo "<p>Starting PHP session...</p>";
                flush();
                session_start();
                echo "<p style='color:green'>✓ Session started</p>";
                flush();
            }
            
            if (!phpCAS::isInitialized()) {
                echo "<p>Calling phpCAS::client() with parameters:</p>";
                echo "<ul>";
                echo "<li>Version: " . CAS_VERSION_2_0 . "</li>";
                echo "<li>Hostname: satuid.treasury.gov.my</li>";
                echo "<li>Port: 443</li>";
                echo "<li>URI: /gk</li>";
                echo "<li>Service Base: false</li>";
                echo "</ul>";
                echo "<p><strong>Initializing now...</strong></p>";
                flush();
                
                phpCAS::client(
                    CAS_VERSION_2_0,
                    'satuid.treasury.gov.my',
                    443,
                    '/gk',
                    false
                );
                echo "<p style='color:green'>✓ phpCAS::client() called successfully!</p>";
                flush();
                
                echo "<p>Setting verbose mode...</p>";
                phpCAS::setVerbose(false);
                echo "<p style='color:green'>✓ Verbose set</p>";
                
                echo "<p>Disabling SSL validation...</p>";
                phpCAS::setNoCasServerValidation();
                echo "<p style='color:green'>✓ SSL validation disabled</p>";
                
                echo "<p style='color:green'><strong>✓ phpCAS initialized successfully!</strong></p>";
                flush();
            } else {
                echo "<p style='color:orange'>phpCAS already initialized</p>";
            }
            
            phpCAS::setVerbose(false);
            phpCAS::setNoCasServerValidation();
            
            echo "<p style='color:green'>✓ phpCAS configured</p>";
            
            $isAuth = phpCAS::isAuthenticated();
            echo "<p>Is Authenticated: " . ($isAuth ? 'YES' : 'NO') . "</p>";
            
            if ($isAuth) {
                $user = phpCAS::getUser();
                echo "<p style='color:green'>✓ User ID: $user</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color:red'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p style='color:red'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
            echo "<pre style='background:#fee;padding:10px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        } catch (Throwable $e) {
            echo "<p style='color:red'>✗ Fatal Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p style='color:red'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
            echo "<pre style='background:#fee;padding:10px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
        
    } else {
        echo "<p style='color:red'>✗ phpCAS class not found after require</p>";
    }
} else {
    echo "<p style='color:red'>✗ CAS.php file not found at: $casPath</p>";
}

echo "<hr><p><a href='/dashboard'>Go to Dashboard</a></p>";
