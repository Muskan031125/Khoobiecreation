<?php
/**
 * ============================================================================
 *  Khoobie one-page installer
 *  ----------------------------------------------------------------------------
 *  Run this ONCE on a fresh server to provision Khoobie end-to-end:
 *    1. Verify PHP version + required extensions
 *    2. Verify writable paths
 *    3. Test DB connectivity (and create DB if missing)
 *    4. Import bundled khoobie-db-backup.sql  (or run CI4 migrations + seeders)
 *    5. Write .env with site-specific config (baseURL, mail, gateways, brand)
 *    6. Create a fresh admin user (overrides bundled one if you want)
 *    7. Lock itself with writable/.setup-locked so it can't be re-run
 *
 *  Open  http://your-domain.com/setup.php  in a browser. Walk through 7 steps.
 *
 *  After success: DELETE this file (or leave the lock file; the script refuses
 *  to run a second time). Production safety only — never ship this to a live
 *  customer-facing site without removing it.
 *
 *  Standalone PHP — does NOT bootstrap CodeIgniter. Works on a clean server
 *  where vendor/ isn't even installed yet.
 * ============================================================================
 */

declare(strict_types=1);
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ---------------------------------------------------------------------------
// Paths — setup.php lives in public/, project root is one level up.
// .env, writable/, and khoobie-db-backup.sql all live at the project root.
// ---------------------------------------------------------------------------
$ROOT      = dirname(__DIR__);
$ENV_PATH  = $ROOT . '/.env';
$BACKUP    = $ROOT . '/khoobie-db-backup.sql';
$LOCK_FILE = $ROOT . '/writable/.setup-locked';
$STEP      = (int) ($_GET['step'] ?? $_POST['step'] ?? 1);

// ---------------------------------------------------------------------------
// Hard safety: refuse to run twice
// ---------------------------------------------------------------------------
if (file_exists($LOCK_FILE) && ! isset($_GET['force'])) {
    http_response_code(423);
    echo "<h1>Setup already completed</h1>";
    echo "<p>The installer has already run. To run again, delete <code>" . htmlspecialchars($LOCK_FILE) . "</code> or append <code>?force=1</code> to the URL.</p>";
    echo "<p style='color:#b91c1c;font-weight:bold'>SECURITY: delete <code>public/setup.php</code> from the server now that setup is done.</p>";
    exit;
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
function h(?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

function row(string $label, bool $ok, string $detail = ''): string {
    $cls   = $ok ? 'ok' : 'fail';
    $badge = $ok ? '✓' : '✗';
    return "<tr class='{$cls}'><td><span class='badge'>{$badge}</span></td><td>" . h($label) . "</td><td class='detail'>" . h($detail) . "</td></tr>";
}

function envEscape(string $v): string {
    // Quote values that contain whitespace, # or =. Otherwise leave bare.
    if (preg_match('/[\s#=\']/', $v)) {
        return "'" . str_replace("'", "\\'", $v) . "'";
    }
    return $v;
}

function writeEnv(array $kv, string $path): void
{
    // Preserve any lines we don't override; append + replace by key.
    $lines = file_exists($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];
    $written = [];
    foreach ($lines as $i => $line) {
        if (preg_match('/^\s*([a-zA-Z0-9._-]+)\s*=/', $line, $m) && isset($kv[$m[1]])) {
            $lines[$i] = $m[1] . ' = ' . envEscape((string) $kv[$m[1]]);
            $written[$m[1]] = true;
        }
    }
    foreach ($kv as $k => $v) {
        if (empty($written[$k])) {
            $lines[] = $k . ' = ' . envEscape((string) $v);
        }
    }
    file_put_contents($path, implode("\n", $lines) . "\n");
    @chmod($path, 0640);
}

function ensureDir(string $path, int $mode = 0775): bool
{
    if (! is_dir($path)) @mkdir($path, $mode, true);
    @chmod($path, $mode);
    return is_dir($path) && is_writable($path);
}

// ---------------------------------------------------------------------------
// Step 4: DB import via mysqli (no external mysql binary required)
// ---------------------------------------------------------------------------
function importSqlFile(mysqli $conn, string $file, string $database): array
{
    if (! is_file($file)) {
        return ['ok' => false, 'error' => "Backup file not found: {$file}"];
    }
    // mysqli_multi_query with statement separator handling
    $sql = file_get_contents($file);
    if ($sql === false || strlen($sql) === 0) {
        return ['ok' => false, 'error' => 'Backup file is empty or unreadable.'];
    }
    // mysqli_multi_query handles semicolons cleanly for mysqldump output
    if (! $conn->multi_query($sql)) {
        return ['ok' => false, 'error' => 'Import failed at statement 1: ' . $conn->error];
    }
    $count = 1;
    while ($conn->more_results()) {
        if (! $conn->next_result()) {
            return ['ok' => false, 'error' => "Import failed at statement {$count}: " . $conn->error];
        }
        $count++;
    }
    // Sanity check: table count
    $r = $conn->query("SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = '" . $conn->real_escape_string($database) . "'");
    $tables = $r ? (int) $r->fetch_assoc()['n'] : 0;
    return ['ok' => true, 'statements' => $count, 'tables' => $tables];
}

// ---------------------------------------------------------------------------
// Step 6: Create / overwrite admin user
// ---------------------------------------------------------------------------
function upsertAdmin(mysqli $conn, string $name, string $email, string $phone, string $password): array
{
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $esc  = fn(string $s) => $conn->real_escape_string($s);

    // Find existing
    $r = $conn->query("SELECT id FROM users WHERE email = '" . $esc($email) . "' LIMIT 1");
    $existing = $r ? $r->fetch_assoc() : null;

    if ($existing) {
        $userId = (int) $existing['id'];
        $conn->query("UPDATE users SET name='" . $esc($name) . "', phone='" . $esc($phone) . "', password_hash='" . $esc($hash) . "', status='active' WHERE id={$userId}");
    } else {
        $conn->query("INSERT INTO users (email, phone, name, password_hash, status, created_at, updated_at) VALUES ('" . $esc($email) . "', '" . $esc($phone) . "', '" . $esc($name) . "', '" . $esc($hash) . "', 'active', NOW(), NOW())");
        $userId = (int) $conn->insert_id;
    }

    // Ensure super_admin role exists, then assign
    $conn->query("INSERT IGNORE INTO roles (name, label, created_at, updated_at) VALUES ('super_admin', 'Super Admin', NOW(), NOW())");
    $r = $conn->query("SELECT id FROM roles WHERE name='super_admin' LIMIT 1");
    $roleId = $r ? (int) $r->fetch_assoc()['id'] : 0;
    if ($roleId) {
        $conn->query("INSERT IGNORE INTO user_roles (user_id, role_id, created_at) VALUES ({$userId}, {$roleId}, NOW())");
    }
    return ['ok' => true, 'user_id' => $userId];
}

// ---------------------------------------------------------------------------
// POST handlers (each step that submits)
// ---------------------------------------------------------------------------
$messages = [];
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($STEP === 3) {
        // Test DB connection + create DB if missing
        $host = $_POST['db_host']     ?? '127.0.0.1';
        $port = (int) ($_POST['db_port'] ?? 3306);
        $user = $_POST['db_user']     ?? 'root';
        $pass = $_POST['db_pass']     ?? '';
        $name = $_POST['db_name']     ?? 'khoobie';

        $conn = @new mysqli($host, $user, $pass, '', $port);
        if ($conn->connect_errno) {
            $errors[] = "Connection failed: " . $conn->connect_error;
        } else {
            $conn->set_charset('utf8mb4');
            $conn->query("CREATE DATABASE IF NOT EXISTS `" . $conn->real_escape_string($name) . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $conn->select_db($name);
            $_SESSION['db'] = compact('host', 'port', 'user', 'pass', 'name');
            $messages[] = "✓ Connected to {$host}:{$port} and ensured database <code>{$name}</code> exists.";
            $conn->close();
            $STEP = 4; // advance
        }
    }

    if ($STEP === 4) {
        $mode = $_POST['mode'] ?? 'import';
        $db   = $_SESSION['db'] ?? null;
        if (! $db) { $errors[] = "Lost DB credentials — restart Step 3."; }
        else {
            $conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], (int) $db['port']);
            $conn->set_charset('utf8mb4');

            if ($mode === 'import') {
                if (! is_file($BACKUP)) {
                    $errors[] = "khoobie-db-backup.sql not found in project root.";
                } else {
                    $res = importSqlFile($conn, $BACKUP, $db['name']);
                    if (! $res['ok']) $errors[] = $res['error'];
                    else { $messages[] = "✓ Imported {$res['statements']} SQL statements · {$res['tables']} tables ready."; $STEP = 5; }
                }
            } else {
                // Migrate + seed via spark (requires PHP CLI + composer install already run)
                $sparkOut = shell_exec('php ' . escapeshellarg($ROOT . '/spark') . ' migrate --all 2>&1');
                $messages[] = "<strong>spark migrate output:</strong><pre>" . h($sparkOut ?: '(no output)') . "</pre>";
                $STEP = 5;
            }
            $conn->close();
        }
    }

    if ($STEP === 5) {
        $kv = [
            'CI_ENVIRONMENT'              => 'production',
            'app.baseURL'                 => rtrim($_POST['base_url'] ?? '', '/') . '/',
            'app.indexPage'               => '',
            'app.forceGlobalSecureRequests' => str_starts_with($_POST['base_url'] ?? '', 'https://') ? 'true' : 'false',
            'app.appTimezone'             => 'Asia/Kolkata',
            'database.default.hostname'   => $_SESSION['db']['host'] ?? '127.0.0.1',
            'database.default.database'   => $_SESSION['db']['name'] ?? 'khoobie',
            'database.default.username'   => $_SESSION['db']['user'] ?? 'root',
            'database.default.password'   => $_SESSION['db']['pass'] ?? '',
            'database.default.DBDriver'   => 'MySQLi',
            'database.default.port'       => (int) ($_SESSION['db']['port'] ?? 3306),
            'database.default.DBPrefix'   => '',
            'database.default.charset'    => 'utf8mb4',
            'database.default.DBCollat'   => 'utf8mb4_unicode_ci',
            'database.default.DBDebug'    => 'false',
            'logger.threshold'            => 4,                              // warning+
            'encryption.key'              => bin2hex(random_bytes(32)),      // fresh
            'session.driver'              => 'CodeIgniter\\Session\\Handlers\\FileHandler',
            'session.cookieName'          => 'khoobie_session',
            'session.expiration'          => 7200,
            'session.savePath'            => $ROOT . '/writable/session',
            'session.matchIP'             => 'false',
            'cookie.secure'               => str_starts_with($_POST['base_url'] ?? '', 'https://') ? 'true' : 'false',
            'cookie.httponly'             => 'true',
            'cookie.samesite'             => 'Lax',
            'khoobie.brand_name'          => $_POST['brand_name']     ?? 'Khoobie Creations',
            'khoobie.support_email'       => $_POST['support_email']  ?? '',
            'khoobie.support_phone'       => $_POST['support_phone']  ?? '',
            'khoobie.support_whatsapp'    => $_POST['support_whatsapp']?? '',
            'mail.smtp_host'              => $_POST['smtp_host']      ?? '',
            'mail.smtp_port'              => $_POST['smtp_port']      ?? '587',
            'mail.smtp_user'              => $_POST['smtp_user']      ?? '',
            'mail.smtp_pass'              => $_POST['smtp_pass']      ?? '',
            'mail.from_email'             => $_POST['from_email']     ?? '',
            'mail.from_name'              => $_POST['from_name']      ?? '',
            'razorpay.key_id'             => $_POST['razorpay_key']   ?? '',
            'razorpay.key_secret'         => $_POST['razorpay_secret']?? '',
        ];
        ensureDir($ROOT . '/writable/session');
        ensureDir($ROOT . '/writable/cache');
        ensureDir($ROOT . '/writable/logs');
        ensureDir($ROOT . '/writable/uploads');
        writeEnv($kv, $ENV_PATH);
        $messages[] = "✓ Wrote .env (" . count($kv) . " keys) and ensured writable/* directories exist.";
        $STEP = 6;
    }

    if ($STEP === 6) {
        $db = $_SESSION['db'] ?? null;
        if (! $db) { $errors[] = "Lost DB credentials."; }
        else {
            $conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], (int) $db['port']);
            $conn->set_charset('utf8mb4');
            $r = upsertAdmin($conn,
                $_POST['admin_name']  ?? 'Admin',
                $_POST['admin_email'] ?? '',
                $_POST['admin_phone'] ?? '',
                $_POST['admin_pass']  ?? bin2hex(random_bytes(8))
            );
            if (! empty($r['ok'])) {
                $messages[] = "✓ Admin user provisioned (user id {$r['user_id']}). You can log in at /admin/login.";
                $STEP = 7;
            } else {
                $errors[] = "Could not create admin user.";
            }
            $conn->close();
        }
    }

    if ($STEP === 7 && isset($_POST['finish'])) {
        ensureDir($ROOT . '/writable');
        file_put_contents($LOCK_FILE, "Locked at " . date('c') . "\nServer: " . ($_SERVER['SERVER_NAME'] ?? 'unknown') . "\n");
        $STEP = 99; // done screen
    }
}

// ---------------------------------------------------------------------------
// Step 1: env diagnostic
// ---------------------------------------------------------------------------
function envCheck(): array {
    $req_ext = ['mysqli','intl','mbstring','curl','openssl','gd','zip','fileinfo','iconv','xml','bcmath'];
    $checks  = [];
    $checks[] = ['label' => 'PHP version ≥ 8.2',     'ok' => version_compare(PHP_VERSION, '8.2.0', '>='), 'detail' => 'Current: ' . PHP_VERSION];
    foreach ($req_ext as $e) {
        $checks[] = ['label' => "ext-{$e}", 'ok' => extension_loaded($e), 'detail' => extension_loaded($e) ? 'loaded' : 'MISSING — install php-' . $e];
    }
    $checks[] = ['label' => 'memory_limit ≥ 256M', 'ok' => (int) ini_get('memory_limit') >= 256 || ini_get('memory_limit') === '-1', 'detail' => ini_get('memory_limit')];
    $checks[] = ['label' => 'upload_max_filesize ≥ 16M', 'ok' => (int) ini_get('upload_max_filesize') >= 16, 'detail' => ini_get('upload_max_filesize')];
    return $checks;
}

function pathCheck(string $root): array {
    $paths = ['writable', 'writable/cache', 'writable/logs', 'writable/session', 'writable/uploads', 'writable/debugbar', 'public', 'public/assets'];
    $out = [];
    foreach ($paths as $p) {
        $full = $root . '/' . $p;
        if (! is_dir($full)) @mkdir($full, 0775, true);
        $w = is_dir($full) && is_writable($full);
        $out[] = ['label' => $p, 'ok' => $w, 'detail' => $w ? 'writable' : 'NOT writable — chmod 775 ' . $p];
    }
    return $out;
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Khoobie — One-page installer</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  :root { --b: #FF6F61; }
  * { box-sizing: border-box; }
  body { font-family: -apple-system, Segoe UI, Roboto, sans-serif; background:#f8fafc; margin:0; padding:24px; color:#0f172a; }
  .wrap { max-width: 820px; margin: 0 auto; }
  .card { background:#fff; border-radius:16px; padding:32px; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
  h1 { margin:0 0 4px; font-size:28px; }
  h2 { font-size: 20px; margin: 24px 0 12px; }
  .steps { display:flex; gap:6px; margin:24px 0; flex-wrap:wrap; }
  .pill { padding:6px 12px; border-radius:999px; font-size:12px; font-weight:bold; background:#e2e8f0; color:#475569; }
  .pill.active { background: var(--b); color:#fff; }
  .pill.done { background:#10b981; color:#fff; }
  table { width:100%; border-collapse:collapse; font-size:14px; margin: 12px 0; }
  td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; }
  tr.ok td { color:#065f46; } tr.fail td { color:#991b1b; background:#fef2f2; }
  .badge { display:inline-block; width:22px; height:22px; line-height:22px; text-align:center; border-radius:50%; font-weight:bold; }
  tr.ok .badge { background:#d1fae5; color:#065f46; } tr.fail .badge { background:#fee2e2; color:#991b1b; }
  .detail { color:#64748b; font-size:13px; font-family: monospace; }
  label { display:block; margin: 12px 0 4px; font-weight:bold; font-size:13px; color:#475569; }
  input, select { width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; }
  .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  .btn { display:inline-block; padding:12px 28px; background: var(--b); color:#fff; border:0; border-radius:10px; font-weight:bold; font-size:15px; cursor:pointer; margin-top:16px; }
  .btn:hover { background:#e56557; }
  .btn.ghost { background:#fff; color:#475569; border:2px solid #e2e8f0; }
  .alert { padding:12px 16px; border-radius:10px; margin: 8px 0; font-size: 14px; }
  .alert.ok { background:#ecfdf5; color:#065f46; border-left: 4px solid #10b981; }
  .alert.err { background:#fef2f2; color:#991b1b; border-left: 4px solid #ef4444; }
  .alert.warn { background:#fffbeb; color:#92400e; border-left: 4px solid #f59e0b; }
  code { background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:13px; }
  pre { background:#0f172a; color:#a5f3fc; padding:14px; border-radius:8px; overflow:auto; max-height:300px; font-size:12px; }
</style>
</head>
<body>
<div class="wrap">
<div class="card">

<h1>🚀 Khoobie installer</h1>
<p style="color:#64748b;margin:0;">First-run setup wizard. Walk through 7 steps; you only run this once per server.</p>

<div class="steps">
  <?php $labels = [1=>'Environment',2=>'Paths',3=>'Database',4=>'Import data',5=>'Site config',6=>'Admin user',7=>'Lock & ship']; ?>
  <?php foreach ($labels as $n => $lbl): ?>
    <span class="pill <?= $STEP === $n ? 'active' : ($STEP > $n || $STEP === 99 ? 'done' : '') ?>"><?= $n ?>. <?= h($lbl) ?></span>
  <?php endforeach; ?>
</div>

<?php foreach ($messages as $m): ?><div class="alert ok"><?= $m ?></div><?php endforeach; ?>
<?php foreach ($errors as $e): ?><div class="alert err"><?= h($e) ?></div><?php endforeach; ?>

<?php if ($STEP === 1): ?>
  <h2>Step 1 · Environment check</h2>
  <table>
    <?php foreach (envCheck() as $c) echo row($c['label'], $c['ok'], $c['detail']); ?>
  </table>
  <a class="btn" href="?step=2">Continue →</a>

<?php elseif ($STEP === 2): ?>
  <h2>Step 2 · Writable paths</h2>
  <table>
    <?php foreach (pathCheck($ROOT) as $c) echo row($c['label'], $c['ok'], $c['detail']); ?>
  </table>
  <p style="font-size:13px;color:#64748b">On a Linux server: <code>chmod -R 775 writable public/assets && chown -R www-data:www-data writable</code></p>
  <a class="btn ghost" href="?step=1">← Back</a>
  <a class="btn" href="?step=3">Continue →</a>

<?php elseif ($STEP === 3): ?>
  <h2>Step 3 · Database connection</h2>
  <form method="post" action="?step=3">
    <input type="hidden" name="step" value="3">
    <div class="grid2">
      <div><label>Host</label><input name="db_host" value="127.0.0.1" required></div>
      <div><label>Port</label><input name="db_port" value="3306" required></div>
    </div>
    <div class="grid2">
      <div><label>Database name</label><input name="db_name" value="khoobie" required></div>
      <div><label>Username</label><input name="db_user" value="root" required></div>
    </div>
    <label>Password</label>
    <input name="db_pass" type="password" autocomplete="off">
    <button class="btn" type="submit">Test & continue →</button>
  </form>

<?php elseif ($STEP === 4): ?>
  <h2>Step 4 · Import data</h2>
  <p>
    <strong>Option A:</strong> Import bundled <code>khoobie-db-backup.sql</code>
    (<?= is_file($BACKUP) ? round(filesize($BACKUP)/1024) . ' KB ✓' : '<span style="color:#b91c1c">NOT FOUND</span>' ?>)
    — recommended for a fresh server.<br>
    <strong>Option B:</strong> Run <code>php spark migrate --all</code> to build the schema from migrations (no demo data).
  </p>
  <form method="post" action="?step=4">
    <input type="hidden" name="step" value="4">
    <label><input type="radio" name="mode" value="import" checked> Import the bundled SQL backup</label>
    <label><input type="radio" name="mode" value="migrate"> Run migrations + seeders (empty schema)</label>
    <button class="btn" type="submit">Run import →</button>
  </form>

<?php elseif ($STEP === 5): ?>
  <h2>Step 5 · Site configuration</h2>
  <form method="post" action="?step=5">
    <input type="hidden" name="step" value="5">
    <label>Public site URL (no trailing slash issues, https in production)</label>
    <input name="base_url" placeholder="https://khoobie.com" required>

    <div class="grid2">
      <div><label>Brand name</label><input name="brand_name" value="Khoobie Creations"></div>
      <div><label>Support email</label><input name="support_email" type="email" placeholder="hello@khoobie.com"></div>
    </div>
    <div class="grid2">
      <div><label>Support phone</label><input name="support_phone" placeholder="+91 88992 23300"></div>
      <div><label>WhatsApp number (digits only)</label><input name="support_whatsapp" placeholder="918899223300"></div>
    </div>

    <h3 style="margin-top:24px;font-size:16px">Outbound email (SMTP)</h3>
    <div class="grid2">
      <div><label>SMTP host</label><input name="smtp_host" placeholder="smtp.gmail.com or smtp.zoho.in"></div>
      <div><label>SMTP port</label><input name="smtp_port" value="587"></div>
    </div>
    <div class="grid2">
      <div><label>SMTP user</label><input name="smtp_user"></div>
      <div><label>SMTP pass</label><input name="smtp_pass" type="password"></div>
    </div>
    <div class="grid2">
      <div><label>From email</label><input name="from_email" type="email"></div>
      <div><label>From name</label><input name="from_name" value="Khoobie"></div>
    </div>

    <h3 style="margin-top:24px;font-size:16px">Payment gateway (Razorpay — leave blank for now if you'll configure later)</h3>
    <div class="grid2">
      <div><label>Razorpay key id</label><input name="razorpay_key" placeholder="rzp_live_xxxx"></div>
      <div><label>Razorpay secret</label><input name="razorpay_secret" type="password"></div>
    </div>

    <button class="btn" type="submit">Write .env →</button>
  </form>

<?php elseif ($STEP === 6): ?>
  <h2>Step 6 · Admin user</h2>
  <p>This creates a super-admin login for the <code>/admin</code> panel. Pick something memorable but strong.</p>
  <form method="post" action="?step=6">
    <input type="hidden" name="step" value="6">
    <div class="grid2">
      <div><label>Full name</label><input name="admin_name" value="Admin" required></div>
      <div><label>Phone</label><input name="admin_phone" placeholder="9899223300"></div>
    </div>
    <label>Email (login id)</label><input name="admin_email" type="email" placeholder="admin@khoobie.com" required>
    <label>Password (min 8 chars)</label><input name="admin_pass" type="password" minlength="8" required>
    <button class="btn" type="submit">Create admin →</button>
  </form>

<?php elseif ($STEP === 7): ?>
  <h2>Step 7 · Lock & finish</h2>
  <p>One last thing — finalize the install and create a lock file so this installer can't be run again.</p>
  <div class="alert warn">
    <strong>After clicking Finish</strong>, delete <code>setup.php</code> and <code>khoobie-db-backup.sql</code> from the server. Both are now permanent risks if left exposed.
  </div>
  <form method="post" action="?step=7">
    <input type="hidden" name="step" value="7">
    <input type="hidden" name="finish" value="1">
    <button class="btn" type="submit">Finish setup ✓</button>
  </form>

<?php elseif ($STEP === 99): ?>
  <h2>🎉 Setup complete</h2>
  <div class="alert ok">
    Your Khoobie install is ready. Lock file created at <code><?= h($LOCK_FILE) ?></code>.
  </div>
  <h3>Final checklist (do these in this order)</h3>
  <ol style="line-height:1.8">
    <li>Delete the installer: <code>rm setup.php khoobie-db-backup.sql</code></li>
    <li>If composer wasn't run yet: <code>composer install --no-dev --optimize-autoloader</code></li>
    <li>If assets aren't built: <code>npm ci && npm run build</code></li>
    <li>Set cron for daily DB backup &amp; cart abandonment: see <code>DEPLOYMENT.md</code></li>
    <li>Point your domain to <code>public/</code> (Apache <code>DocumentRoot</code> or Nginx <code>root</code>)</li>
    <li>Verify <code>https://your-domain.com/</code> renders the home page</li>
    <li>Log in at <code>/admin/login</code> with the credentials you just created</li>
  </ol>
  <p><a class="btn" href="/">Open the storefront →</a></p>

<?php endif; ?>

</div>
</div>
</body>
</html>
