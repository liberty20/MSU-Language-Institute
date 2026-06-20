"""
MSU Language Institute - Automated Deployment Script
Deploys from GitHub to Ubuntu Server 10.10.9.1 port 8083
"""
import subprocess
import sys
import os

LOG = r"L:\Projects\MSULI\deploy_output.txt"

def log(msg):
    print(msg, flush=True)
    with open(LOG, 'a', encoding='utf-8') as f:
        f.write(msg + "\n")

def check_paramiko():
    try:
        import paramiko
        log("[OK] paramiko is available: " + paramiko.__version__)
        return True
    except ImportError:
        log("[MISSING] paramiko not found, installing...")
        result = subprocess.run([sys.executable, "-m", "pip", "install", "paramiko"], capture_output=True, text=True)
        log(result.stdout)
        log(result.stderr)
        try:
            import paramiko
            log("[OK] paramiko installed successfully")
            return True
        except ImportError:
            log("[FAIL] Could not install paramiko")
            return False

def run_remote(ssh, command, timeout=300):
    log(f"  $ {command}")
    stdin, stdout, stderr = ssh.exec_command(command, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    exit_code = stdout.channel.recv_exit_status()
    if out.strip():
        log(f"  STDOUT: {out.strip()}")
    if err.strip():
        log(f"  STDERR: {err.strip()}")
    log(f"  EXIT: {exit_code}")
    return out, err, exit_code

def main():
    # Clear log
    with open(LOG, 'w', encoding='utf-8') as f:
        f.write("=== MSU Language Institute Deployment ===\n")

    log("[STEP 1] Checking paramiko...")
    if not check_paramiko():
        sys.exit(1)

    import paramiko

    HOST = "10.10.9.1"
    PORT = 22
    USER = "liberty"
    PASS = "liberty"
    APP_DIR = "/var/www/msu-language-institute"
    REPO_URL = "https://github.com/liberty20/MSU-Language-Institute.git"
    DB_NAME = "msunli_db"
    DB_USER = "liberty"
    DB_PASS = "libs@2026"
    APP_PORT = 8083
    APP_URL = f"http://{HOST}:{APP_PORT}"

    log(f"\n[STEP 2] Connecting to {HOST}:{PORT} as {USER}...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        ssh.connect(HOST, port=PORT, username=USER, password=PASS, timeout=30)
        log("[OK] SSH Connected!")
    except Exception as e:
        log(f"[FAIL] SSH Connection failed: {e}")
        sys.exit(1)

    log("\n[STEP 3] Checking OS...")
    run_remote(ssh, "cat /etc/os-release | grep PRETTY_NAME")
    run_remote(ssh, "uname -a")
    out, _, _ = run_remote(ssh, "whoami")
    log(f"  Remote user: {out.strip()}")

    log("\n[STEP 4] Updating APT package list...")
    run_remote(ssh, "sudo DEBIAN_FRONTEND=noninteractive apt-get update -qq", timeout=180)

    log("\n[STEP 5] Installing base packages...")
    run_remote(ssh, "sudo DEBIAN_FRONTEND=noninteractive apt-get install -y software-properties-common curl git unzip", timeout=180)

    log("\n[STEP 6] Adding PHP 8.1 repository...")
    run_remote(ssh, "sudo add-apt-repository -y ppa:ondrej/php 2>&1 | tail -3", timeout=60)
    run_remote(ssh, "sudo DEBIAN_FRONTEND=noninteractive apt-get update -qq", timeout=180)

    log("\n[STEP 7] Installing PHP 8.1 and extensions...")
    run_remote(ssh, "sudo DEBIAN_FRONTEND=noninteractive apt-get install -y php8.1 php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-bcmath php8.1-curl php8.1-zip php8.1-gd php8.1-tokenizer php8.1-dom 2>&1 | tail -5", timeout=300)
    run_remote(ssh, "php8.1 --version")

    log("\n[STEP 8] Installing MySQL Server...")
    run_remote(ssh, "sudo DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server 2>&1 | tail -5", timeout=300)
    run_remote(ssh, "sudo systemctl start mysql && sudo systemctl enable mysql")
    run_remote(ssh, "mysql --version")

    log("\n[STEP 9] Installing Nginx...")
    run_remote(ssh, "sudo DEBIAN_FRONTEND=noninteractive apt-get install -y nginx 2>&1 | tail -3", timeout=120)
    run_remote(ssh, "sudo systemctl start nginx && sudo systemctl enable nginx")

    log("\n[STEP 10] Installing Composer...")
    run_remote(ssh, "which composer || curl -sS https://getcomposer.org/installer | php8.1 -- --install-dir=/usr/local/bin --filename=composer", timeout=120)
    run_remote(ssh, "composer --version")

    log("\n[STEP 11] Installing Node.js 18.x and NPM...")
    run_remote(ssh, "curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash - 2>&1 | tail -3", timeout=90)
    run_remote(ssh, "sudo DEBIAN_FRONTEND=noninteractive apt-get install -y nodejs 2>&1 | tail -3", timeout=120)
    run_remote(ssh, "node --version && npm --version")

    log(f"\n[STEP 12] Setting up MySQL database '{DB_NAME}'...")
    sql_cmds = [
        f"CREATE DATABASE IF NOT EXISTS {DB_NAME};",
        f"CREATE USER IF NOT EXISTS '{DB_USER}'@'localhost' IDENTIFIED BY '{DB_PASS}';",
        f"GRANT ALL PRIVILEGES ON {DB_NAME}.* TO '{DB_USER}'@'localhost';",
        "FLUSH PRIVILEGES;"
    ]
    for sql in sql_cmds:
        run_remote(ssh, f'sudo mysql -e "{sql}"', timeout=30)
    run_remote(ssh, 'sudo mysql -e "SHOW DATABASES;"')

    log(f"\n[STEP 13] Cloning repository to {APP_DIR}...")
    run_remote(ssh, f"sudo rm -rf {APP_DIR}")
    run_remote(ssh, "sudo mkdir -p /var/www")
    run_remote(ssh, f"sudo git clone {REPO_URL} {APP_DIR}", timeout=120)
    run_remote(ssh, f"sudo chown -R $USER:$USER {APP_DIR}")
    run_remote(ssh, f"ls {APP_DIR}")

    log("\n[STEP 14] Writing production .env file...")
    env_lines = [
        f"APP_NAME=MSULanguageInstitute",
        f"APP_ENV=production",
        f"APP_KEY=",
        f"APP_DEBUG=false",
        f"APP_URL={APP_URL}",
        f"",
        f"LOG_CHANNEL=stack",
        f"LOG_DEPRECATIONS_CHANNEL=null",
        f"LOG_LEVEL=debug",
        f"",
        f"DB_CONNECTION=mysql",
        f"DB_HOST=127.0.0.1",
        f"DB_PORT=3306",
        f"DB_DATABASE={DB_NAME}",
        f"DB_USERNAME={DB_USER}",
        f"DB_PASSWORD={DB_PASS}",
        f"",
        f"BROADCAST_DRIVER=log",
        f"CACHE_DRIVER=file",
        f"FILESYSTEM_DRIVER=local",
        f"QUEUE_CONNECTION=sync",
        f"SESSION_DRIVER=file",
        f"SESSION_LIFETIME=120",
        f"",
        f"MEMCACHED_HOST=127.0.0.1",
        f"",
        f"REDIS_HOST=127.0.0.1",
        f"REDIS_PASSWORD=null",
        f"REDIS_PORT=6379",
        f"",
        f'MAIL_MAILER=log',
        f'MAIL_HOST=127.0.0.1',
        f'MAIL_PORT=1025',
        f'MAIL_USERNAME=null',
        f'MAIL_PASSWORD=null',
        f'MAIL_ENCRYPTION=null',
        f'MAIL_FROM_ADDRESS=null',
        f'MAIL_FROM_NAME="${{APP_NAME}}"',
    ]
    env_content = "\n".join(env_lines)
    # Write env using python via sftp
    try:
        sftp = ssh.open_sftp()
        with sftp.file(f"{APP_DIR}/.env", 'w') as f:
            f.write(env_content)
        sftp.close()
        log(f"  [OK] .env written via SFTP")
    except Exception as e:
        log(f"  [WARN] SFTP write failed: {e}, trying heredoc...")
        run_remote(ssh, f"printf '%s' '{env_content.replace(chr(39), chr(39)+chr(34)+chr(39)+chr(34)+chr(39))}' > {APP_DIR}/.env", timeout=15)

    run_remote(ssh, f"cat {APP_DIR}/.env | head -10")

    log("\n[STEP 15] Installing PHP Composer dependencies...")
    run_remote(ssh, f"cd {APP_DIR} && composer install --optimize-autoloader --no-dev --no-interaction 2>&1 | tail -10", timeout=300)

    log("\n[STEP 16] Generating application key...")
    run_remote(ssh, f"cd {APP_DIR} && php8.1 artisan key:generate --force")
    
    log("\n[STEP 17] Installing Node dependencies and building assets...")
    run_remote(ssh, f"cd {APP_DIR} && npm install 2>&1 | tail -5", timeout=180)
    run_remote(ssh, f"cd {APP_DIR} && npm run production 2>&1 | tail -10", timeout=180)

    log("\n[STEP 18] Running database migrations...")
    run_remote(ssh, f"cd {APP_DIR} && php8.1 artisan migrate --force 2>&1", timeout=120)
    
    log("\n[STEP 19] Running database seeders...")
    run_remote(ssh, f"cd {APP_DIR} && php8.1 artisan db:seed --force 2>&1", timeout=120)

    log("\n[STEP 20] Setting file permissions...")
    run_remote(ssh, f"sudo chown -R www-data:www-data {APP_DIR}/storage {APP_DIR}/bootstrap/cache")
    run_remote(ssh, f"sudo chmod -R 775 {APP_DIR}/storage {APP_DIR}/bootstrap/cache")
    run_remote(ssh, f"sudo usermod -a -G www-data {USER}")

    log("\n[STEP 21] Optimizing Laravel for production...")
    run_remote(ssh, f"cd {APP_DIR} && php8.1 artisan config:cache")
    run_remote(ssh, f"cd {APP_DIR} && php8.1 artisan route:cache")
    run_remote(ssh, f"cd {APP_DIR} && php8.1 artisan view:cache")

    log(f"\n[STEP 22] Configuring Nginx on port {APP_PORT}...")
    nginx_config = """server {
    listen """ + str(APP_PORT) + """;
    server_name """ + HOST + """ _;
    root """ + APP_DIR + """/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \\.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\\.(?!well-known).* {
        deny all;
    }
}
"""
    try:
        sftp = ssh.open_sftp()
        with sftp.file("/tmp/msuli_nginx.conf", 'w') as f:
            f.write(nginx_config)
        sftp.close()
        run_remote(ssh, "sudo mv /tmp/msuli_nginx.conf /etc/nginx/sites-available/msuli")
    except Exception as e:
        log(f"  [WARN] SFTP for nginx failed: {e}")
        # Fallback: write line by line
        run_remote(ssh, f"echo '{nginx_config}' | sudo tee /etc/nginx/sites-available/msuli > /dev/null")
    
    run_remote(ssh, "sudo ln -sf /etc/nginx/sites-available/msuli /etc/nginx/sites-enabled/msuli")
    run_remote(ssh, "sudo rm -f /etc/nginx/sites-enabled/default")
    run_remote(ssh, "sudo nginx -t")
    run_remote(ssh, "sudo systemctl reload nginx")

    log(f"\n[STEP 23] Opening firewall port {APP_PORT}...")
    run_remote(ssh, f"sudo ufw allow {APP_PORT}/tcp 2>/dev/null || true")

    log("\n[STEP 24] Starting PHP-FPM 8.1...")
    run_remote(ssh, "sudo systemctl restart php8.1-fpm && sudo systemctl enable php8.1-fpm")
    run_remote(ssh, "sudo systemctl status php8.1-fpm --no-pager | head -5")

    log(f"\n[STEP 25] Verifying deployment at http://{HOST}:{APP_PORT}...")
    run_remote(ssh, f"curl -s -o /dev/null -w 'HTTP STATUS: %{{http_code}}\\n' http://localhost:{APP_PORT}/")
    run_remote(ssh, f"ss -tulpn | grep :{APP_PORT} || echo 'Port check done'")

    ssh.close()
    log(f"\n=== DEPLOYMENT COMPLETE ===")
    log(f"Application URL: http://{HOST}:{APP_PORT}")
    log("Deployment finished successfully!")

if __name__ == "__main__":
    main()
