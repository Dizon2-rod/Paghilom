<?php
/**
 * Design Consistency Automation Script
 * Applies unified #2A5618 green theme across all files
 */

require __DIR__ . '/config.php';
require_admin();

$updates = [];
$errors = [];
$files_processed = 0;

// Define replacements
$replacements = [
    // Button classes
    'btn-dark' => 'btn-primary',
    'btn-success' => 'btn-primary',
    'btn btn-outline-dark' => 'btn btn-outline-primary',
    'btn btn-outline-success' => 'btn btn-outline-primary',
    
    // Background classes  
    'bg-dark' => 'bg-primary',
    'navbar-dark bg-dark' => 'navbar-dark bg-primary',
    
    // Badge classes
    'badge bg-success' => 'badge bg-primary',
    'text-bg-success' => 'bg-primary',
    
    // Old gradient colors (from stat cards)
    '#667eea' => '#2A5618',
    '#764ba2' => '#1f4012',
    '#f093fb' => '#2A5618',
    '#f5576c' => '#1f4012',
    '#4facfe' => '#2A5618',
    '#00f2fe' => '#1f4012',
    '#43e97b' => '#2A5618',
    '#38f9d7' => '#1f4012',
    
    // Old green colors
    '#2e8b57' => '#2A5618',
    '#1e3d1f' => '#1f4012',
    '#047857' => '#2A5618',
    '#059669' => '#2A5618',
    '#10b981' => '#2A5618',
    '#2d5016' => '#2A5618',
    '#4a7c2c' => '#3a7020',
    
    // Text colors
    'text-success' => 'text-primary',
];

// Directories to scan
$directories = [
    __DIR__ . '/admin',
    __DIR__ . '/pos',
    __DIR__ . '/rewards',
    __DIR__ . '/user',
    __DIR__ // Root directory
];

// File extensions to process
$extensions = ['php', 'html'];

function scanDirectory($dir, &$files, $extensions) {
    if (!is_dir($dir)) return;
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $dir . '/' . $item;
        
        if (is_dir($path) && !in_array(basename($path), ['vendor', 'node_modules', 'uploads'])) {
            scanDirectory($path, $files, $extensions);
        } elseif (is_file($path)) {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if (in_array($ext, $extensions)) {
                $files[] = $path;
            }
        }
    }
}

function applyReplacements($content, $replacements) {
    $changes = 0;
    foreach ($replacements as $search => $replace) {
        $count = 0;
        $content = str_replace($search, $replace, $content, $count);
        $changes += $count;
    }
    return ['content' => $content, 'changes' => $changes];
}

// DRY RUN MODE
$dry_run = !isset($_POST['confirm_apply']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$dry_run) {
    // Collect all files
    $all_files = [];
    foreach ($directories as $dir) {
        scanDirectory($dir, $all_files, $extensions);
    }
    
    // Process each file
    foreach ($all_files as $file) {
        try {
            $content = file_get_contents($file);
            $original_content = $content;
            
            $result = applyReplacements($content, $replacements);
            
            if ($result['changes'] > 0) {
                // Backup original file
                $backup_dir = __DIR__ . '/backups_' . date('Ymd_His');
                if (!is_dir($backup_dir)) {
                    mkdir($backup_dir, 0755, true);
                }
                
                $backup_path = $backup_dir . '/' . basename($file);
                file_put_contents($backup_path, $original_content);
                
                // Write updated content
                file_put_contents($file, $result['content']);
                
                $updates[] = [
                    'file' => str_replace(__DIR__, '', $file),
                    'changes' => $result['changes']
                ];
                $files_processed++;
            }
        } catch (Exception $e) {
            $errors[] = "Error processing " . basename($file) . ": " . $e->getMessage();
        }
    }
}

include __DIR__ . '/partials/header.php';
?>

<style>
.preview-card {
    background: white;
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-left: 4px solid var(--primary);
}

.stat-box {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary));
    color: white;
    padding: 2rem;
    border-radius: var(--radius-xl);
    text-align: center;
}

.stat-number {
    font-size: 3rem;
    font-weight: 700;
}

.change-item {
    padding: 0.75rem;
    background: var(--background);
    border-radius: var(--radius-md);
    margin-bottom: 0.5rem;
}
</style>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">🎨 Design Consistency Automation</h3>
                    <p class="mb-0 opacity-75">Apply unified #2A5618 green theme across all files</p>
                </div>
                <div class="card-body">
                    
                    <?php if (!$dry_run && count($updates) > 0): ?>
                        <!-- Success Message -->
                        <div class="alert alert-success">
                            <h4 class="alert-heading">✅ Design Updates Applied Successfully!</h4>
                            <p><strong><?= $files_processed ?></strong> files were updated with the unified design system.</p>
                            <hr>
                            <p class="mb-0">Backups saved to: <code>backups_<?= date('Ymd_His') ?>/</code></p>
                        </div>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="stat-box">
                                    <div class="stat-number"><?= $files_processed ?></div>
                                    <div>Files Updated</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-box">
                                    <div class="stat-number"><?= array_sum(array_column($updates, 'changes')) ?></div>
                                    <div>Total Changes</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-box">
                                    <div class="stat-number">✓</div>
                                    <div>Design Unified</div>
                                </div>
                            </div>
                        </div>
                        
                        <h5>Updated Files:</h5>
                        <div class="list-group mb-4">
                            <?php foreach ($updates as $update): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <code><?= htmlspecialchars($update['file']) ?></code>
                                <span class="badge bg-primary rounded-pill"><?= $update['changes'] ?> changes</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-center">
                            <a href="index.php" class="btn btn-primary btn-lg px-5">
                                View Homepage
                            </a>
                            <a href="admin/" class="btn btn-outline-primary btn-lg px-5">
                                Go to Admin
                            </a>
                        </div>
                        
                    <?php else: ?>
                        <!-- Preview Mode -->
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Preview Mode</h5>
                            <p class="mb-0">Review the changes before applying them to your files.</p>
                        </div>
                        
                        <h5 class="mb-3">What Will Be Changed:</h5>
                        
                        <div class="row g-3 mb-4">
                            <?php foreach ($replacements as $old => $new): ?>
                            <div class="col-md-6">
                                <div class="change-item">
                                    <small class="text-muted d-block">Replace:</small>
                                    <code class="text-danger"><?= htmlspecialchars($old) ?></code>
                                    <br>
                                    <small class="text-muted d-block mt-2">With:</small>
                                    <code class="text-success"><?= htmlspecialchars($new) ?></code>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h6>📂 Directories to Scan:</h6>
                                <ul class="mb-0">
                                    <li><code>/admin/</code> - Admin pages</li>
                                    <li><code>/pos/</code> - POS system</li>
                                    <li><code>/rewards/</code> - Rewards pages</li>
                                    <li><code>/user/</code> - User pages</li>
                                    <li><code>/</code> - Root files</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card border-warning mb-4">
                            <div class="card-body">
                                <h6 class="text-warning">⚠️ Important Notes:</h6>
                                <ul class="mb-0">
                                    <li>✅ Automatic backups will be created</li>
                                    <li>✅ Only PHP and HTML files will be modified</li>
                                    <li>✅ Vendor and upload folders will be skipped</li>
                                    <li>✅ All changes can be reverted from backups</li>
                                </ul>
                            </div>
                        </div>
                        
                        <?php if (count($errors) > 0): ?>
                        <div class="alert alert-danger">
                            <h6>Errors:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <form method="post" class="text-center">
                            <button type="submit" name="confirm_apply" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-check-circle me-2"></i>Apply Design Changes
                            </button>
                            <a href="admin/" class="btn btn-outline-secondary btn-lg px-5">
                                Cancel
                            </a>
                        </form>
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <!-- Color Reference -->
            <div class="card mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">🎨 Unified Color Palette</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div style="background: #2A5618; color: white; padding: 2rem; border-radius: 0.5rem; text-align: center;">
                                <strong>#2A5618</strong>
                                <br>Primary Green
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div style="background: #1f4012; color: white; padding: 2rem; border-radius: 0.5rem; text-align: center;">
                                <strong>#1f4012</strong>
                                <br>Primary Dark
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div style="background: #F6FFF6; color: #2A5618; padding: 2rem; border-radius: 0.5rem; text-align: center; border: 2px solid #2A5618;">
                                <strong>#F6FFF6</strong>
                                <br>Background
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
