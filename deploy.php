<?php
/**
 * Automated Deployment Script for Beach Houses in Toco
 *
 * cPanel Implementation Guide:
 * 1. Upload this file to your public_html root (where index.html is)
 * 2. Set file permissions to 644 via cPanel File Manager
 * 3. Update REMOTE_REPOSITORY constant below with your GitHub repo URL
 * 4. Add GitHub webhook: https://yourdomain.com/deploy.php
 * 5. Use SECRET_KEY below in GitHub webhook secret field
 *
 * SECURITY: This script will DISCARD ALL LOCAL CHANGES!
 */

// ==================== CONFIGURATION ====================
// UPDATE THIS with your actual GitHub repository URL
define('REMOTE_REPOSITORY', 'https://github.com/yourusername/toco-beach-houses.git');

// Secret key for webhook validation (use this in GitHub webhook settings)
define('SECRET_KEY', '7f9a3e2d8c1b6f4a5e9d7c3b2a1f8e6d4c9b7a5f3e1d9c7b5a3f1e9d7c5b3a1f');

// Branch to deploy (usually 'master' or 'main')
define('BRANCH', 'master');

// Target directory (current directory where this script is located)
define('TARGET_DIR', __DIR__);

// Log file location (will be created in same directory)
define('LOG_FILE', __DIR__ . '/deployment.log');
// ======================================================

/**
 * Log deployment activity with timestamp
 */
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}\n";
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
    echo $logEntry;
}

/**
 * Validate webhook secret from GitHub/GitLab
 */
function validateSecret() {
    $headers = getallheaders();

    // GitHub webhook signature validation (X-Hub-Signature-256)
    if (isset($headers['X-Hub-Signature-256'])) {
        $payload = file_get_contents('php://input');
        $hash = 'sha256=' . hash_hmac('sha256', $payload, SECRET_KEY);

        if (!hash_equals($hash, $headers['X-Hub-Signature-256'])) {
            logMessage('ERROR: Invalid GitHub webhook signature');
            http_response_code(403);
            die(json_encode(['status' => 'error', 'message' => 'Forbidden: Invalid signature']));
        }
        logMessage('GitHub webhook validated successfully');
        return true;
    }

    // GitLab webhook token validation (X-Gitlab-Token)
    if (isset($headers['X-Gitlab-Token'])) {
        if ($headers['X-Gitlab-Token'] !== SECRET_KEY) {
            logMessage('ERROR: Invalid GitLab webhook token');
            http_response_code(403);
            die(json_encode(['status' => 'error', 'message' => 'Forbidden: Invalid token']));
        }
        logMessage('GitLab webhook validated successfully');
        return true;
    }

    // Manual deployment trigger via query parameter (for testing only)
    if (isset($_GET['secret']) && $_GET['secret'] === SECRET_KEY) {
        logMessage('WARNING: Manual deployment triggered via query parameter');
        return true;
    }

    // No valid authentication found
    logMessage('ERROR: No valid authentication method found');
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Forbidden: Missing or invalid credentials']));
}

/**
 * Execute the deployment process
 * Returns: true on success, false on failure
 */
function deploy() {
    logMessage('==========================================================');
    logMessage('=== DEPLOYMENT STARTED ===');
    logMessage('==========================================================');

    // Check if git is available on the server
    exec('git --version 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        logMessage('ERROR: Git is not installed or not in PATH');
        logMessage('cPanel Note: Contact your hosting provider to enable Git');
        return false;
    }
    logMessage('Git check: ' . implode(' ', $output));

    // Change to target directory
    if (!chdir(TARGET_DIR)) {
        logMessage('ERROR: Cannot change to target directory: ' . TARGET_DIR);
        return false;
    }
    logMessage('Working directory: ' . getcwd());

    // STEP 1: Stash any local changes (backup in case of accidents)
    logMessage('--- STEP 1: Stashing local changes ---');
    exec('git stash --include-untracked 2>&1', $output, $returnCode);
    logMessage('Stash output: ' . implode("\n", $output));

    // STEP 2: Discard all local changes (staged and unstaged)
    logMessage('--- STEP 2: Discarding local changes (git reset --hard) ---');
    exec('git reset --hard HEAD 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        logMessage('WARNING: Git reset --hard failed');
        logMessage('Output: ' . implode("\n", $output));
    } else {
        logMessage('Local changes discarded successfully');
    }

    // STEP 3: Remove all untracked files and directories
    logMessage('--- STEP 3: Removing untracked files (git clean -fd) ---');
    exec('git clean -fd 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        logMessage('WARNING: Git clean -fd failed');
        logMessage('Output: ' . implode("\n", $output));
    } else {
        logMessage('Untracked files cleaned: ' . implode("\n", $output));
    }

    // STEP 4: Fetch latest changes from remote repository
    logMessage('--- STEP 4: Fetching from remote repository ---');
    exec('git fetch origin ' . BRANCH . ' 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        logMessage('ERROR: Git fetch failed');
        logMessage('Output: ' . implode("\n", $output));
        logMessage('Possible causes: Network issue, repository URL incorrect, authentication failed');
        return false;
    }
    logMessage('Fetch completed: ' . implode("\n", $output));

    // STEP 5: Force reset to match remote branch exactly
    logMessage('--- STEP 5: Force reset to origin/' . BRANCH . ' ---');
    exec('git reset --hard origin/' . BRANCH . ' 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        logMessage('ERROR: Git reset to remote failed');
        logMessage('Output: ' . implode("\n", $output));
        return false;
    }
    logMessage('Force reset completed: ' . implode("\n", $output));

    // STEP 6: Final aggressive cleanup (removes ignored files too)
    logMessage('--- STEP 6: Final cleanup (git clean -fdx) ---');
    exec('git clean -fdx 2>&1', $output, $returnCode);
    logMessage('Final cleanup: ' . implode("\n", $output));

    // STEP 7: Verify current branch and commit
    logMessage('--- STEP 7: Verifying deployment ---');

    exec('git branch --show-current 2>&1', $branchOutput, $returnCode);
    $currentBranch = trim($branchOutput[0] ?? 'unknown');
    logMessage('Current branch: ' . $currentBranch);

    exec('git rev-parse --short HEAD 2>&1', $commitOutput, $returnCode);
    $commitHash = trim($commitOutput[0] ?? 'unknown');
    logMessage('Current commit hash: ' . $commitHash);

    exec('git log -1 --pretty=format:"%s" 2>&1', $commitMsgOutput, $returnCode);
    $commitMessage = trim($commitMsgOutput[0] ?? 'No commit message');
    logMessage('Latest commit message: ' . $commitMessage);

    exec('git log -1 --pretty=format:"%an <%ae>" 2>&1', $authorOutput, $returnCode);
    $author = trim($authorOutput[0] ?? 'Unknown');
    logMessage('Commit author: ' . $author);

    // STEP 8: Verify repository is clean
    logMessage('--- STEP 8: Repository status verification ---');
    exec('git status --porcelain 2>&1', $statusOutput, $returnCode);
    if (empty($statusOutput)) {
        logMessage('Repository status: CLEAN ✓ (no local modifications)');
    } else {
        logMessage('WARNING: Repository has local changes after deployment:');
        logMessage(implode("\n", $statusOutput));
    }

    logMessage('==========================================================');
    logMessage('=== DEPLOYMENT COMPLETED SUCCESSFULLY ===');
    logMessage('==========================================================');

    return true;
}

/**
 * Send deployment notification
 * Future: Integrate with n8n webhook for Google Sheets logging
 */
function sendDeploymentNotification($status, $message) {
    logMessage("Notification: [{$status}] {$message}");

    // TODO: Uncomment and configure when n8n is ready
    /*
    $webhookUrl = 'https://your-n8n-instance.com/webhook/deployment';
    $data = [
        'status' => $status,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => 'production',
        'project' => 'beach-houses-toco',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown'
    ];

    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);

    logMessage('n8n notification sent: ' . $response);
    */
}

// ==================== MAIN EXECUTION ====================
try {
    // Validate webhook authentication
    validateSecret();

    // Execute deployment
    $success = deploy();

    if ($success) {
        sendDeploymentNotification('success', 'Deployment completed successfully');
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Deployment completed successfully',
            'timestamp' => date('Y-m-d H:i:s'),
            'branch' => BRANCH
        ]);
    } else {
        sendDeploymentNotification('error', 'Deployment failed');
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Deployment failed - check deployment.log for details',
            'timestamp' => date('Y-m-d H:i:s'),
            'log_file' => LOG_FILE
        ]);
    }

} catch (Exception $e) {
    logMessage('FATAL EXCEPTION: ' . $e->getMessage());
    logMessage('Stack trace: ' . $e->getTraceAsString());
    sendDeploymentNotification('error', 'Deployment exception: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
