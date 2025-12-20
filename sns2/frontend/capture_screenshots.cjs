const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
  console.log('Launching browser...');
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  const screenshotDir = path.resolve(__dirname, '../screenshots');
  if (!fs.existsSync(screenshotDir)){
      fs.mkdirSync(screenshotDir, { recursive: true });
  }

  // Use the full path including base
  const baseUrl = 'http://localhost:5173/sns_2a';

  try {
    // 1. Login Page
    console.log('Navigating to Login...');
    await page.goto(`${baseUrl}/login`);
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: path.join(screenshotDir, '01_login.png') });

    // 2. Perform Login
    console.log('Performing Login...');
    await page.fill('input[type="email"]', 'test@example.com');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');

    // Wait for login to complete and redirect
    await page.waitForURL(`${baseUrl}/`);
    await page.waitForLoadState('networkidle');
    // Wait a bit more for data to fetch
    await page.waitForTimeout(1000); 

    // 3. Feed (Home)
    console.log('Capturing Feed...');
    await page.screenshot({ path: path.join(screenshotDir, '02_feed.png') });

    // 4. QA Tab
    console.log('Switching to QA Tab...');
    // Find button with text "Q&A"
    await page.getByRole('button', { name: 'Q&A' }).click();
    await page.waitForTimeout(1000); // Wait for transition/fetch
    await page.screenshot({ path: path.join(screenshotDir, '03_qa.png') });

    // 5. Blog Tab
    console.log('Switching to Blog Tab...');
    await page.getByRole('button', { name: 'ブログ' }).click();
    await page.waitForTimeout(1000);
    await page.screenshot({ path: path.join(screenshotDir, '04_blog.png') });

    // 6. New Question
    console.log('Navigating to New Question...');
    await page.goto(`${baseUrl}/qa/new`);
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: path.join(screenshotDir, '05_new_qa.png') });

    // 7. New Blog
    console.log('Navigating to New Blog...');
    await page.goto(`${baseUrl}/blog/new`);
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: path.join(screenshotDir, '06_new_blog.png') });
    
    // 8. Search
    console.log('Navigating to Search...');
    await page.goto(`${baseUrl}/search`);
    await page.waitForLoadState('networkidle');
    
    // Type search query
    console.log('Performing Search...');
    await page.fill('input[placeholder="キーワードで検索..."]', 'salndfjnas'); // Assuming this placeholder, will verify/adjust if needed or use generic selector
    // If the input triggers search automatically or needs a button, we might need to wait.
    // Based on typical Vue implementations, it might be v-model with debounce or a form submit.
    // Let's assume hitting enter works or just typing if it's reactive.
    await page.keyboard.press('Enter');
    
    await page.waitForTimeout(1000); // Wait for results
    await page.screenshot({ path: path.join(screenshotDir, '07_search.png') });

  } catch (error) {
    console.error('Error occurred:', error);
    // Take a screenshot of the error state if possible
    await page.screenshot({ path: path.join(screenshotDir, 'error_state.png') });
  } finally {
    await browser.close();
  }
})();
