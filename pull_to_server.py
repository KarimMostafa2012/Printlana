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
        command = f"cd {REMOTE_PATH} && git fetch origin master && git reset --hard origin/master"

        stdin, stdout, stderr = ssh.exec_command(command)

        # Get output
        output = stdout.read().decode('utf-8')
        error = stderr.read().decode('utf-8')

        # Combine output and error (git uses stderr for progress info)
        full_output = output + error

        # Check for actual errors (not just git progress messages)
        if "fatal:" in error or "error:" in error:
            print(f"[-] Error: {error}")
            return False

        print(f"[+] Output:\n{full_output.strip()}")

        # Read homeland.php
        print(f"\n[*] Reading remote homeland.php...")
        cat_command = f"cat {REMOTE_PATH}/wp-content/plugins/homeland/homeland.php"
        stdin, stdout, stderr = ssh.exec_command(cat_command)
        remote_content = stdout.read().decode('utf-8')
        print(f"[+] Remote Content Length: {len(remote_content)} bytes")
        # Print first 20 lines to verify
        print(f"[+] Remote Content Preview (First 20 lines):\n{remote_content[:500]}")

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
