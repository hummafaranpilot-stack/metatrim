const puppeteer = require('puppeteer');
const path = require('path');

async function generatePDF() {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();

    // Load the HTML file
    const htmlPath = path.join(__dirname, 'index.html');
    await page.goto(`file://${htmlPath}`, { waitUntil: 'networkidle0' });

    // Set viewport for better rendering
    await page.setViewport({ width: 1200, height: 800 });

    // Wait for images to load
    await page.waitForTimeout(2000);

    // Generate PDF
    await page.pdf({
        path: path.join(__dirname, 'ClickBank-Structure.pdf'),
        format: 'A4',
        printBackground: true,
        margin: {
            top: '20px',
            right: '20px',
            bottom: '20px',
            left: '20px'
        }
    });

    console.log('PDF generated successfully: ClickBank-Structure.pdf');

    await browser.close();
}

generatePDF().catch(console.error);
