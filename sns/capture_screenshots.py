
import time
from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={"width": 1280, "height": 800})
        page = context.new_page()

        # Handle alerts (essential for this app's register/post flow)
        page.on("dialog", lambda dialog: dialog.accept())

        # 1. API Docs
        try:
            page.goto("http://localhost:8000/docs.html")
            page.wait_for_load_state("networkidle")
            page.screenshot(path="screenshots/api_docs.png")
            print("Captured api_docs.png")
        except Exception as e:
            print(f"Error capturing api_docs: {e}")

        # 2. Register
        try:
            page.goto("http://localhost:5173/register")
            page.wait_for_selector('input[placeholder="ユーザー名"]')
            page.screenshot(path="screenshots/register.png")
            print("Captured register.png")

            # Register Logic - use unique name to avoid conflict on re-run
            import random
            username = f"user_{random.randint(1000,9999)}"
            page.fill('input[placeholder="ユーザー名"]', username)
            page.fill('input[placeholder="パスワード"]', 'password123')
            page.click('button[type="submit"]')
            
            # Wait for navigation to login (handled by App logic after alert)
            page.wait_for_url("**/login")
        except Exception as e:
            print(f"Error in register: {e}")

        # 3. Login
        try:
            page.goto("http://localhost:5173/login") 
            page.wait_for_selector('input[placeholder="ユーザー名"]')
            page.screenshot(path="screenshots/login.png")
            print("Captured login.png")

            page.fill('input[placeholder="ユーザー名"]', username)
            page.fill('input[placeholder="パスワード"]', 'password123')
            page.click('button:has-text("ログイン")')
            
            # Wait for home
            page.wait_for_url("**/home") 
        except Exception as e:
            print(f"Error in login: {e}")

        # 4. Feed (Logged in)
        try:
            page.wait_for_selector('.post-card') 
            time.sleep(2) 
            
            # Create 3 Posts
            for i in range(3):
                page.fill('textarea', f'Post Number {i+1} by Playwright')
                page.click('button:has-text("投稿")')
                time.sleep(1)
            
            time.sleep(1)
            
            # Reaction - trigger
            try:
                # Click reaction on the first post (latest)
                page.locator('button:has-text("リアクション")').first.click()
                time.sleep(1)
                
                # Attempt to click an emoji in the picker
                # Checking generic emoji picker structure
                # Many pickers use explicit images or buttons
                # Try clicking the first clickable element inside the picker container
                # Assuming .EmojiPickerReact is the container class
                picker_emoji = page.locator('.EmojiPickerReact button, .EmojiPickerReact img[data-emoji]').first
                if picker_emoji.count() > 0:
                     picker_emoji.click()
                     time.sleep(1) # Wait for reaction to apply
            except Exception as e:
                 print(f"Reaction interaction warning: {e}")

            # Screenshot Feed with content
            page.screenshot(path="screenshots/feed_with_post.png")
            print("Captured feed_with_post.png")

            # Create Post Screen (focused)
            page.locator('.post-card').screenshot(path="screenshots/create_post.png")
            print("Captured create_post.png")

        except Exception as e:
            print(f"Error in Feed/Post: {e}")

        # 5. Blog
        try:
            page.click('text=ブログ')
            time.sleep(1)
            
            # Create Blog Post
            page.fill('input[placeholder="タイトル"]', 'My First Blog')
            page.fill('textarea', 'This is a long blog post courtesy of Playwright.')
            page.click('button:has-text("投稿")')
            time.sleep(2)
            
            page.screenshot(path="screenshots/blog_list.png") # Main blog view
            print("Captured blog_list.png")
        except Exception as e:
            print(f"Error in Blog: {e}")

        # 6. Q&A
        try:
            # Create a Q&A post via API to ensure list is not empty
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
            
            page.click('a[href="/qa"]')
            page.wait_for_url("**/qa")
            time.sleep(2)
            
            page.screenshot(path="screenshots/qa_list.png")
            print("Captured qa_list.png")
        except Exception as e:
            print(f"Error in Q&A: {e}")

        browser.close()

if __name__ == "__main__":
    run()
