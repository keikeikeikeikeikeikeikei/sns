
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

            # Trashy contents as requested
            trashy_contents = [
                {"title": "dsfaskdf", "content": "dsfaskdf"},
                {"title": "さんdf亜jsdfあ", "content": "さんdf亜jsdfあ"},
                {"title": "fdsajkf", "content": "fdsajkf"},
                {"title": "あsfdhjkl", "content": "あsfdhjkl"},
            ]

            # Create Blogs
            print("Creating Blogs...")
            for post in trashy_contents:
                page.evaluate(f"""
                    async () => {{
                        const token = localStorage.getItem('token');
                        await fetch('http://localhost:8000/api/posts', {{
                            method: 'POST',
                            headers: {{
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${{token}}` 
                            }},
                            body: JSON.stringify({{
                                type: 'blog', 
                                title: '{post['title']}', 
                                content: '{post['content']}'
                            }})
                        }});
                    }}
                """)
                time.sleep(0.2)

            # Create Q&As with reactions
            print("Creating Q&As...")
            for post in trashy_contents:
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
                time.sleep(0.2)

            # Capture Blog List
            page.click('text=ブログ')
            time.sleep(2) # Wait for fetch
            page.screenshot(path="screenshots/blog_list.png")
            print("Captured blog_list.png")

            # Capture QA List
            page.click('a[href="/qa"]')
            page.wait_for_selector('.qa-list', timeout=5000)
            time.sleep(2)
            page.screenshot(path="screenshots/qa_list.png")
            print("Captured qa_list.png")

        except Exception as e:
            print(f"Error: {e}")

        browser.close()

if __name__ == "__main__":
    run()
