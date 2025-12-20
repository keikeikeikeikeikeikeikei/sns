import os
from playwright.sync_api import sync_playwright

BASE_URL = "http://127.0.0.1:8000"
OUTPUT_DIR = "screenshots"

def take_screenshots():
    if not os.path.exists(OUTPUT_DIR):
        os.makedirs(OUTPUT_DIR)

    with sync_playwright() as p:
        print("Launching browser...")
        # Launch browser (headless by default)
        browser = p.chromium.launch()
        context = browser.new_context(viewport={"width": 1280, "height": 720})
        page = context.new_page()

        # 1. Login Page
        print("Navigating to Login...")
        try:
            page.goto(f"{BASE_URL}/login")
            page.screenshot(path=f"{OUTPUT_DIR}/login.png")
            print(f"Captured: {OUTPUT_DIR}/login.png")
        except Exception as e:
            print(f"Failed to load login page: {e}")
            browser.close()
            return

        # 2. Perform Login to reach Dashboard
        print("Logging in...")
        try:
            # Wait for form to be ready
            page.wait_for_selector('input[name="email"]', state="visible", timeout=10000)
            
            # Fill credentials (using the seeder's default user)
            page.fill('input[name="email"]', "test@example.com")
            page.fill('input[name="password"]', "test@example.com")
            
            # Click login and wait for navigation to dashboard
            with page.expect_navigation(url="**/dashboard"):
                page.click('button[type="submit"]')
        except Exception as e:
            print(f"Login failed: {e}")
            browser.close()
            return

        # 3. Dashboard
        print("Capturing Dashboard...")
        try:
            # Wait for a key element on the dashboard (e.g., header) to ensure load
            page.wait_for_selector('h2', state="visible", timeout=10000)
            page.screenshot(path=f"{OUTPUT_DIR}/dashboard.png")
            print(f"Captured: {OUTPUT_DIR}/dashboard.png")
        except Exception as e:
             print(f"Failed to capture dashboard: {e}")

        browser.close()
        print("Done.")

if __name__ == "__main__":
    take_screenshots()