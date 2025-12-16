import ftplib
import os
import sys

# FTP Settings
FTP_HOST = "s325.xrea.com"
FTP_USER = "hgyujhgj"
FTP_PASS = "rXSa64KCv3LR"
FTP_DIR = "/public_html/hgyujhgj.shop/sns_2a"

def upload_file():
    try:
        print(f"Connecting to {FTP_HOST}...")
        ftp = ftplib.FTP(FTP_HOST)
        ftp.login(FTP_USER, FTP_PASS)
        print("Logged in successfully.")

        try:
            print("Setting permissions for logs and uploads...")
            # 777 or 755. Using 777 for writable dirs usually required on shared hosts without suPHP
            ftp.voidcmd(f"SITE CHMOD 777 {FTP_DIR}/api/logs")
            ftp.voidcmd(f"SITE CHMOD 777 {FTP_DIR}/api/public/uploads")
            print("Permissions set successfully.")
        except Exception as e:
            print(f"Error setting permissions: {e}")

        ftp.quit()

    except Exception as e:
        print(f"FTP Error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    upload_file()
