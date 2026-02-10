<?php

/**
 * Debug Configuration Script
 * 
 * Use this to verify that your credentials are being loaded correctly
 * 
 * Access: http://localhost/examples/php-vanilla/debug.php
 */

// Load configuration
$config = require_once __DIR__ . '/config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Gateway - Configuration Debug</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .container { margin-top: 2rem; margin-bottom: 2rem; }
        .config-box { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        code { color: #d63384; background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .masked { color: #999; font-style: italic; }
        pre { background: #f5f5f5; padding: 1rem; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <h1>🔍 Configuration Debug</h1>
                <p class="text-muted">Check if your credentials are being loaded correctly</p>

                <!-- PayPal Configuration -->
                <div class="config-box">
                    <h3>PayPal Configuration</h3>
                    
                    <div class="mb-3">
                        <strong>Mode:</strong> 
                        <span class="status-ok"><?php echo htmlspecialchars($config['paypal']['mode'] ?? 'MISSING'); ?></span>
                    </div>

                    <div class="mb-3">
                        <strong>Client ID:</strong>
                        <?php if (!empty($config['paypal']['client_id'])): ?>
                            <?php if (strpos($config['paypal']['client_id'], 'YOUR_') === 0): ?>
                                <span class="status-error">❌ Using placeholder credentials!</span>
                                <p><small>You need to replace <code>YOUR_PAYPAL_CLIENT_ID</code> with your real credentials from PayPal Developer Dashboard</small></p>
                            <?php else: ?>
                                <span class="status-ok">✅ Loaded from environment</span>
                                <p><code class="masked"><?php echo substr($config['paypal']['client_id'], 0, 10); ?>...<?php echo substr($config['paypal']['client_id'], -10); ?></code></p>
                                <p><small>Length: <?php echo strlen($config['paypal']['client_id']); ?> characters</small></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="status-error">❌ MISSING!</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <strong>Client Secret:</strong>
                        <?php if (!empty($config['paypal']['client_secret'])): ?>
                            <?php if (strpos($config['paypal']['client_secret'], 'YOUR_') === 0): ?>
                                <span class="status-error">❌ Using placeholder credentials!</span>
                                <p><small>You need to replace <code>YOUR_PAYPAL_CLIENT_SECRET</code> with your real credentials</small></p>
                            <?php else: ?>
                                <span class="status-ok">✅ Loaded from environment</span>
                                <p><code class="masked"><?php echo substr($config['paypal']['client_secret'], 0, 10); ?>...<?php echo substr($config['paypal']['client_secret'], -10); ?></code></p>
                                <p><small>Length: <?php echo strlen($config['paypal']['client_secret']); ?> characters</small></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="status-error">❌ MISSING!</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Environment Variables -->
                <div class="config-box">
                    <h3>Environment Variables (from getenv())</h3>
                    <pre><code><?php 
$vars = ['PAYPAL_CLIENT_ID', 'PAYPAL_CLIENT_SECRET', 'PAYPAL_MODE'];
foreach ($vars as $var) {
    $value = getenv($var);
    if ($value === false) {
        echo "$var = NOT SET\n";
    } else {
        echo "$var = " . (strlen($value) < 50 ? $value : substr($value, 0, 20) . "..." . substr($value, -20)) . "\n";
    }
}
                    ?></code></pre>
                </div>

                <!-- .env File -->
                <div class="config-box">
                    <h3>.env File Status</h3>
                    <?php 
                    $envPath = __DIR__ . '/../../.env';
                    if (file_exists($envPath)): 
                    ?>
                        <p><span class="status-ok">✅ .env file found</span> at <code><?php echo $envPath; ?></code></p>
                        <p>File size: <?php echo filesize($envPath); ?> bytes</p>
                        <p>Last modified: <?php echo date('Y-m-d H:i:s', filemtime($envPath)); ?></p>
                        
                        <h5 style="margin-top: 1.5rem;">PayPal Settings in .env:</h5>
                        <pre><code><?php
                        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        foreach ($lines as $line) {
                            // Show only PayPal-related lines
                            if (stripos($line, 'paypal') !== false && strpos(trim($line), '#') !== 0) {
                                // Mask sensitive values
                                if (stripos($line, 'secret') !== false) {
                                    echo preg_replace('/=.*/', '=***MASKED***', $line) . "\n";
                                } else {
                                    echo $line . "\n";
                                }
                            }
                        }
                        ?></code></pre>
                    <?php else: ?>
                        <p><span class="status-error">❌ .env file NOT found</span> at <code><?php echo $envPath; ?></code></p>
                        <p>You need to create a <code>.env</code> file in the project root with your PayPal credentials.</p>
                        <p><strong>Create <code>.env</code> with this content:</strong></p>
                        <pre><code>PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_actual_client_id_here
PAYPAL_CLIENT_SECRET=your_actual_secret_here</code></pre>
                    <?php endif; ?>
                </div>

                <!-- Next Steps -->
                <div class="config-box">
                    <h3>📋 Next Steps</h3>
                    <?php 
                    $clientId = $config['paypal']['client_id'] ?? '';
                    $clientSecret = $config['paypal']['client_secret'] ?? '';
                    $isPlaceholder = (strpos($clientId, 'YOUR_') === 0 || strpos($clientSecret, 'YOUR_') === 0);
                    $fileExists = file_exists(__DIR__ . '/../../.env');
                    ?>
                    
                    <?php if (!$fileExists): ?>
                        <ol>
                            <li><strong>Create .env file</strong> in project root (not in examples/php-vanilla/)</li>
                            <li>Go to <a href="https://developer.paypal.com" target="_blank">PayPal Developer Dashboard</a></li>
                            <li>Get your Client ID and Secret</li>
                            <li>Copy them into the .env file</li>
                            <li>Reload this page to verify</li>
                        </ol>
                    <?php elseif ($isPlaceholder): ?>
                        <ol>
                            <li>Go to <a href="https://developer.paypal.com" target="_blank">PayPal Developer Dashboard</a></li>
                            <li>Sign in with your account</li>
                            <li>Click "Apps & Credentials" at the top</li>
                            <li>Make sure "Sandbox" is selected (top right)</li>
                            <li>Find your app under "REST API apps section</li>
                            <li>Click to expand and copy:
                                <ul>
                                    <li><strong>Client ID</strong> - copy this value</li>
                                    <li><strong>Secret</strong> - click "Show" and copy this value</li>
                                </ul>
                            </li>
                            <li>Edit your <code>.env</code> file and replace the placeholder values:
                                <pre><code>PAYPAL_CLIENT_ID=paste_client_id_here
PAYPAL_CLIENT_SECRET=paste_secret_here
PAYPAL_MODE=sandbox</code></pre>
                            </li>
                            <li>Save the file</li>
                            <li>Reload this page to verify the credentials are loaded</li>
                            <li>Go back to <a href="checkout.php">checkout.php</a> and try a payment</li>
                        </ol>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <strong>✅ Credentials appear to be configured correctly!</strong>
                            <p style="margin: 0.5rem 0;">
                                If you're still getting a 404 error, the credentials might be:
                            </p>
                            <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                                <li>Expired or revoked</li>
                                <li>For the wrong PayPal account</li>
                                <li>Not activated in the PayPal dashboard</li>
                            </ul>
                            <p style="margin: 0.5rem 0;">
                                Try generating new credentials from the PayPal Developer Dashboard and updating your .env file.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Testing -->
                <div class="config-box">
                    <h3>🧪 Test Your Configuration</h3>
                    <?php if (!$isPlaceholder && $fileExists): ?>
                        <p>Once you've verified your credentials above, go to:</p>
                        <p><a href="checkout.php" class="btn btn-primary">→ Payment Checkout Form</a></p>
                    <?php else: ?>
                        <p><em>Complete the configuration steps above first, then come back to test.</em></p>
                    <?php endif; ?>
                </div>

                <!-- Troubleshooting -->
                <div class="config-box">
                    <h3>🐛 Still Getting 404 Error?</h3>
                    <p><strong>Common causes:</strong></p>
                    <ul>
                        <li>Your PayPal credentials are <strong>expired</strong> - generate new ones in the dashboard</li>
                        <li>You're using credentials from the <strong>wrong environment</strong> - make sure PAYPAL_MODE matches (sandbox vs live)</li>
                        <li>The credentials have been <strong>revoked</strong> or <strong>disabled</strong></li>
                        <li>Your PayPal account has <strong>restrictions</strong> or activity issues</li>
                    </ul>
                    
                    <p style="margin-top: 1rem;"><strong>How to verify your credentials work:</strong></p>
                    <ol>
                        <li>Go to <a href="https://developer.paypal.com/dashboard/" target="_blank">PayPal Developer Dashboard</a></li>
                        <li>Make sure you're in the right app</li>
                        <li>Verify the "Client ID" and "Secret" match your .env file exactly</li>
                        <li>If they look different or are greyed out, generate new credentials</li>
                        <li>Copy the NEW credentials to your .env file</li>
                        <li>Try again</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
