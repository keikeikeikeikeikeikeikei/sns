
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
            username = f"user_{random.randint(100000,999999)}"
            print(f"Creating user: {username}")
            
            page.goto("http://localhost:5173/register")
            page.fill('input[placeholder="ユーザー名"]', username)
            page.fill('input[placeholder="パスワード"]', 'password123')
            page.click('button[type="submit"]')
            
            page.wait_for_selector('h2:has-text("ログイン")', timeout=10000)
            
            page.fill('input[placeholder="ユーザー名"]', username)
            page.fill('input[placeholder="パスワード"]', 'password123')
            page.click('button:has-text("ログイン")')
            
            page.wait_for_selector('.post-card', timeout=10000)
            print("Login successful")

            # Create specific posts
            posts = [
                {"title": "salndfjnas", "content": "salndfjnas"},
                {"title": "asfdはsd", "content": "asfdはsd"},
                {"title": "sdfhjさああ", "content": "sdfhjさああ"},
                {"title": "dsjafjsd", "content": "dsjafjsd"},
            ]

            for post in posts:
                page.evaluate(f"""
                    async () => {{
                        const token = localStorage.getItem('token');
                        // Create Post
                        const res = await fetch('http://localhost:8000/api/posts', {{
                            method: 'POST',
                            headers: {{
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${{token}}` 
                            }},
                            body: JSON.stringify({{
                                type: 'question', 
                                title: '{post['title']}', 
                                content: '{post['content']}'
                            }})
                        }});
                        const data = await res.json();
                        
                        // Add Reaction (Randomly)
                        const emojis = ['👍', '❤️', '😂', '🤔'];
                        const emoji = emojis[Math.floor(Math.random() * emojis.length)];
                        await fetch('http://localhost:8000/api/reactions', {{
                             method: 'POST',
                             headers: {{
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${{token}}` 
                             }},
                             body: JSON.stringify({{
                                 post_id: data.id,
                                 emoji: emoji
                             }})
                        }});
                    }}
                """)
                time.sleep(0.5)

            print("Created posts with reactions")

            # Go to Q&A List
            page.click('a[href="/qa"]')
            page.wait_for_selector('.qa-list', timeout=5000)
            
            # Additional wait to ensure data is fetched and rendered
            time.sleep(2) 
            
            page.screenshot(path="screenshots/qa_list.png")
            print("Captured qa_list.png")

        except Exception as e:
            print(f"Error: {e}")
            page.screenshot(path="screenshots/error_qa.png")

        browser.close()

if __name__ == "__main__":
    run()
