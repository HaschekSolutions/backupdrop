<!DOCTYPE html>
<html>
<head>
    <title>Upload Backup - <?=htmlspecialchars($hostname)?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 30px; margin-bottom: 20px; }
        h1 { margin-top: 0; color: #333; }
        h2 { color: #555; font-size: 1.3em; margin-top: 0; }
        .dropzone { border: 2px dashed #0087F7; border-radius: 5px; background: white; min-height: 150px; }
        .settings-form { display: grid; gap: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 5px; color: #333; }
        .form-group input[type="number"], .form-group input[type="password"] { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-group input[type="number"]:focus, .form-group input[type="password"]:focus { outline: none; border-color: #0087F7; }
        .form-group small { color: #666; margin-top: 5px; font-size: 13px; }
        .retention-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; font-weight: 600; }
        .btn-primary { background: #0087F7; color: white; }
        .btn-primary:hover { background: #006fd6; }
        .success-msg { background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 15px; display: none; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 15px; display: none; }
        .info-box { background: #e7f3ff; border-left: 4px solid #0087F7; padding: 15px; margin-top: 15px; border-radius: 4px; }
        .info-box p { margin: 5px 0; color: #004085; }
        .password-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .password-modal-content { background: white; padding: 30px; border-radius: 8px; max-width: 400px; width: 90%; }
        .password-modal input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; }
        .files-list { margin-top: 20px; }
        .file-item { padding: 10px; background: #f8f9fa; border-radius: 4px; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center; }
        .file-info { font-family: monospace; font-size: 13px; }
    </style>
</head> 
<body>
    <div class="container">
        <div class="card">
            <h1>Backup: <?=htmlspecialchars($hostname)?></h1>
            <form action="/<?=htmlspecialchars($url[0])?>" class="dropzone" id="backup-dropzone" method="POST" enctype="multipart/form-data"></form>
        </div>

        <div class="card">
            <h2>⚙️ Settings</h2>
            <div id="success-msg" class="success-msg"></div>
            <div id="error-msg" class="error-msg"></div>
            
            <form id="settings-form" class="settings-form">
                <div class="form-group">
                    <label for="password">Password Protection</label>
                    <input type="password" id="password" name="password" placeholder="Leave empty to disable password protection">
                    <small>Set a password to protect this backup target from unauthorized access</small>
                </div>

                <div class="form-group">
                    <label>Retention Policy</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="keep_all" name="keep_all" <?=$config->get('retention')['keep_all'] ? 'checked' : ''?>>
                        <label for="keep_all">Keep all backups (disable retention rules)</label>
                    </div>
                </div>

                <div class="retention-grid" id="retention-fields">
                    <div class="form-group">
                        <label for="keep_last">Keep Last</label>
                        <input type="number" id="keep_last" name="keep_last" min="0" value="<?=$config->get('retention')['keep_last']?>" placeholder="0 = disabled">
                        <small>Keep last N backups</small>
                    </div>
                    <div class="form-group">
                        <label for="keep_hourly">Keep Hourly</label>
                        <input type="number" id="keep_hourly" name="keep_hourly" min="0" value="<?=$config->get('retention')['keep_hourly']?>" placeholder="0 = disabled">
                        <small>Keep N hourly backups</small>
                    </div>
                    <div class="form-group">
                        <label for="keep_daily">Keep Daily</label>
                        <input type="number" id="keep_daily" name="keep_daily" min="0" value="<?=$config->get('retention')['keep_daily']?>" placeholder="0 = disabled">
                        <small>Keep N daily backups</small>
                    </div>
                    <div class="form-group">
                        <label for="keep_weekly">Keep Weekly</label>
                        <input type="number" id="keep_weekly" name="keep_weekly" min="0" value="<?=$config->get('retention')['keep_weekly']?>" placeholder="0 = disabled">
                        <small>Keep N weekly backups</small>
                    </div>
                    <div class="form-group">
                        <label for="keep_monthly">Keep Monthly</label>
                        <input type="number" id="keep_monthly" name="keep_monthly" min="0" value="<?=$config->get('retention')['keep_monthly']?>" placeholder="0 = disabled">
                        <small>Keep N monthly backups</small>
                    </div>
                    <div class="form-group">
                        <label for="keep_yearly">Keep Yearly</label>
                        <input type="number" id="keep_yearly" name="keep_yearly" min="0" value="<?=$config->get('retention')['keep_yearly']?>" placeholder="0 = disabled">
                        <small>Keep N yearly backups</small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>

            <div class="info-box">
                <p><strong>Retention Logic:</strong></p>
                <p>• Backups are evaluated from newest to oldest</p>
                <p>• Each retention rule protects different time periods</p>
                <p>• A backup protected by any rule will be kept</p>
                <p>• Set to 0 to disable a specific rule</p>
            </div>
        </div>

        <?php if(count($config->getFiles()) > 0): ?>
        <div class="card">
            <h2>📁 Existing Backups</h2>
            <div class="files-list">
                <?php foreach($config->getFiles() as $filename => $info): ?>
                    <div class="file-item">
                        <span class="file-info"><?=htmlspecialchars($filename)?></span>
                        <span style="color: #666; font-size: 13px;"><?=date('Y-m-d H:i', $info['uploaded'])?> • <?=formatBytes($info['size'])?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div id="password-modal" class="password-modal">
        <div class="password-modal-content">
            <h2>Password Required</h2>
            <input type="password" id="access-password" placeholder="Enter password">
            <button class="btn btn-primary" onclick="verifyAccess()">Access</button>
            <div id="password-error" class="error-msg" style="margin-top: 10px;"></div>
        </div>
    </div>

    <script>
        const hostname = '<?=htmlspecialchars($hostname)?>';
        const hasPassword = <?=$config->hasPassword() ? 'true' : 'false'?>;
        let authenticated = !hasPassword;

        // Check password on load
        if (hasPassword && !sessionStorage.getItem('auth_' + hostname)) {
            document.getElementById('password-modal').style.display = 'flex';
        }

        function verifyAccess() {
            const password = document.getElementById('access-password').value;
            fetch('/' + hostname + '?action=verify', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({password: password})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    sessionStorage.setItem('auth_' + hostname, '1');
                    document.getElementById('password-modal').style.display = 'none';
                    authenticated = true;
                } else {
                    document.getElementById('password-error').style.display = 'block';
                    document.getElementById('password-error').textContent = 'Incorrect password';
                }
            });
        }

        document.getElementById('access-password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') verifyAccess();
        });

        // Dropzone configuration
        Dropzone.options.backupDropzone = {
            paramName: "file",
            maxFilesize: 10000,
            init: function() {
                this.on("sending", function(file, xhr, formData) {
                    if (hasPassword && !authenticated) {
                        this.removeFile(file);
                        alert('Please authenticate first');
                        return false;
                    }
                });
                this.on("success", function(file, response) {
                    console.log("Upload successful:", response);
                    setTimeout(() => location.reload(), 1000);
                });
            }
        };

        // Keep all checkbox toggle
        document.getElementById('keep_all').addEventListener('change', function() {
            const retentionFields = document.getElementById('retention-fields');
            if (this.checked) {
                retentionFields.style.opacity = '0.5';
                retentionFields.style.pointerEvents = 'none';
            } else {
                retentionFields.style.opacity = '1';
                retentionFields.style.pointerEvents = 'auto';
            }
        });

        // Trigger on load
        document.getElementById('keep_all').dispatchEvent(new Event('change'));

        // Settings form submission
        document.getElementById('settings-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!authenticated) {
                alert('Please authenticate first');
                return;
            }

            const formData = new FormData(this);
            const data = {
                password: formData.get('password'),
                retention: {
                    keep_all: formData.get('keep_all') ? true : false,
                    keep_last: parseInt(formData.get('keep_last')) || 0,
                    keep_hourly: parseInt(formData.get('keep_hourly')) || 0,
                    keep_daily: parseInt(formData.get('keep_daily')) || 0,
                    keep_weekly: parseInt(formData.get('keep_weekly')) || 0,
                    keep_monthly: parseInt(formData.get('keep_monthly')) || 0,
                    keep_yearly: parseInt(formData.get('keep_yearly')) || 0
                }
            };

            fetch('/' + hostname + '?action=save_settings', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    document.getElementById('success-msg').textContent = 'Settings saved successfully!';
                    document.getElementById('success-msg').style.display = 'block';
                    setTimeout(() => {
                        document.getElementById('success-msg').style.display = 'none';
                    }, 3000);
                    if (data.password) {
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    document.getElementById('error-msg').textContent = 'Failed to save settings: ' + result.error;
                    document.getElementById('error-msg').style.display = 'block';
                }
            })
            .catch(err => {
                document.getElementById('error-msg').textContent = 'Error: ' + err.message;
                document.getElementById('error-msg').style.display = 'block';
            });
        });
    </script>
</body>
</html>