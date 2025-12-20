
import time
import random
from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={"width": 1280, "height": 800})
        page = context.new_page()
        page.on("dialog", lambda dialog: dialog.accept())

        # Register & Login
        try:
            username = f"user_{random.randint(10000,99999)}"
            print(f"Creating user: {username}")
            
            page.goto("http://localhost:5173/register")
            page.fill('input[placeholder="ユーザー名"]', username)
            page.fill('input[placeholder="パスワード"]', 'password123')
            page.click('button[type="submit"]')
            
            # Wait for login page or redirection
            # Using selector logic instead of strict URL matching if URL transition is tricky
            page.wait_for_selector('h2:has-text("ログイン")', timeout=10000)
            
            # Login
            page.fill('input[placeholder="ユーザー名"]', username)
            page.fill('input[placeholder="パスワード"]', 'password123')
            page.click('button:has-text("ログイン")')
            
            # Wait for Home
            page.wait_for_selector('.post-card', timeout=10000)
            print("Login successful")

            # Create 'あ' Q&A Post
            page.evaluate("""
                async () => {
                    const token = localStorage.getItem('token');
                    await fetch('http://localhost:8000/api/posts', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}` 
                        },
                        body: JSON.stringify({
                            type: 'question', 
                            title: 'あ', 
                            content: 'あ'
                        })
                    });
                }
            """)
            print("Created 'あ' post")

            # Go to Q&A List
            page.click('a[href="/qa"]')
            page.wait_for_selector('.qa-list', timeout=5000)
            time.sleep(1) # Rendering wait
            
            page.screenshot(path="screenshots/qa_list.png")
            print("Captured qa_list.png")

        except Exception as e:
            print(f"Error: {e}")
            page.screenshot(path="screenshots/error_qa.png")

        browser.close()

if __name__ == "__main__":
    run()
