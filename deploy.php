<?php
/**
 * Automated Deployment Script for Beach Houses in Toco
 *
 * Based on Mayaro deployment approach: Repository + Manual Copy
 * This is simpler to set up and maintain than in-place deployment.
 */

// ==================== CONFIGURATION ====================
// Security token (use this in GitHub webhook URL)
$secret_token = '7f9a3e2d8c1b6f4a5e9d7c3b2a1f8e6d4c9b7a5f3e1d9c7b5a3f1e9d7c5b3a1f';

// Paths configuration
$repo_path = '/home/trinorui/repositories/Beach-Houses-In-Toco-Website';
$deploy_path = '/home/trinorui/public_html/Beach-Houses-In-Toco-Website';
$log_file = '/home/trinorui/deployment.log';

// Branch to deploy
$branch = 'master';
// =======================================================

// Get the webhook payload
$payload = file_get_contents('php://input');

// Verify the token from URL parameter
if (isset($_GET['token']) && $_GET['token'] === $secret_token) {

    // Log the deployment start
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] Deployment triggered\n", FILE_APPEND);

    // Commands to execute
    $commands = [
        // Navigate to repository and fetch latest changes
        "cd $repo_path && git fetch origin $branch 2>&1",

        // Discard any local changes and match remote exactly
        "cd $repo_path && git reset --hard origin/$branch 2>&1",

        // Copy all HTML files
        "cp $repo_path/index.html $deploy_path/",
        "cp $repo_path/blog.html $deploy_path/",

        // Copy SEO and configuration files
        "cp $repo_path/robots.txt $deploy_path/",
        "cp $repo_path/sitemap.xml $deploy_path/",
        "cp $repo_path/.htaccess $deploy_path/",

        // Copy PHP files (including this deploy script for self-updates)
        "cp $repo_path/deploy.php $deploy_path/",

        // Copy directories (recursive)
        "cp -R $repo_path/assets $deploy_path/",
        "cp -R $repo_path/forms $deploy_path/"
    ];

    $output = [];
    foreach ($commands as $command) {
        exec($command, $output);
    }

    // Log results
    file_put_contents($log_file, "[$timestamp] Output: " . implode("\n", $output) . "\n\n", FILE_APPEND);

    // Success response
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Deployment completed',
        'timestamp' => $timestamp
    ]);

} else {
    // Invalid or missing token
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized - Invalid token'
    ]);
}
?>
