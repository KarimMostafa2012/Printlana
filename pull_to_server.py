#!/usr/bin/env python3
"""
Deployment script to pull latest changes from GitHub to live server
"""

import paramiko
import sys

# Server configuration
SERVER_HOST = "147.93.74.86"
SERVER_PORT = 65002
SERVER_USER = "u983237415"
SERVER_PASS = "58wNB$6+3Dmkjsbfv##354#R#mrgh293$XF"
REMOTE_PATH = "/home/u983237415/domains/printlana.com/public_html"

def deploy_to_server():
    """Connect to server and pull latest changes from GitHub"""

    print(f"\n[*] Connecting to {SERVER_HOST}...")

    try:
        # Create SSH client
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

        # Connect to server
        ssh.connect(
            hostname=SERVER_HOST,
            port=SERVER_PORT,
            username=SERVER_USER,
            password=SERVER_PASS,
            timeout=10
        )

        print(f"[+] Connected successfully!")

        # Execute git pull command
        print(f"\n[*] Resetting and pulling latest changes from GitHub...")
        command = f"cd {REMOTE_PATH} && git fetch origin master && git reset --hard FETCH_HEAD && git log -n 1"
        stdin, stdout, stderr = ssh.exec_command(command)
        print(f"[+] Git Output:\n{stdout.read().decode('utf-8').strip()}")

        # Run emergency fix
        print(f"\n[*] Running emergency fix script...")
        fix_cmd = f"cd {REMOTE_PATH} && php emergency_fix.php"
        stdin, stdout, stderr = ssh.exec_command(fix_cmd)
        print(f"[+] Output:\n{stdout.read().decode('utf-8').strip()}")
        print(f"[*] Stderr:\n{stderr.read().decode('utf-8').strip()}")

        # Final Check: List active plugins
        print(f"\n[*] Checking final plugin status...")
        wp_final = f"cd {REMOTE_PATH} && wp plugin list --status=active"
        stdin, stdout, stderr = ssh.exec_command(wp_final)
        print(f"[+] Active Plugins:\n{stdout.read().decode('utf-8').strip()}")
        wp_err = stderr.read().decode('utf-8').strip()
        if wp_err:
            print(f"[*] WP-CLI Stderr:\n{wp_err}")

        # Close connection
        ssh.close()
        print(f"\n[+] Deployment completed successfully!")
        return True

    except paramiko.AuthenticationException:
        print("[-] Authentication failed. Please check your username and password.")
        return False
    except paramiko.SSHException as e:
        print(f"[-] SSH error: {e}")
        return False
    except Exception as e:
        print(f"[-] Error: {e}")
        return False

if __name__ == "__main__":
    success = deploy_to_server()
    sys.exit(0 if success else 1)
