# Git Auto-Deploy Setup Guide

This guide will help you set up automatic deployment from Git to your hosting server.

## Overview

**How it works:**
```
You push to GitHub → GitHub Actions automatically deploys → Files appear on metatrim.trustednutraproduct.com
```

## What's Already Done ✅

1. ✅ FTP account created on hosting (`metatrim-deploy@trustednutraproduct.com`)
2. ✅ GitHub Actions workflow configured (`.github/workflows/deploy.yml`)
3. ✅ `.gitignore` updated to exclude sensitive files

## What You Need to Do

### Step 1: Push Your Code to GitHub

If you haven't already created a GitHub repository:

1. Go to [GitHub.com](https://github.com) and sign in
2. Click the "+" icon (top right) → "New repository"
3. Name it: `TrustedNutraProducts` (or any name you prefer)
4. Choose "Private" for security
5. Click "Create repository"

Then in your local terminal/command prompt:

```bash
cd "d:\TrustedNutraProducts"
git init
git add .
git commit -m "Initial commit with auto-deploy setup"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/TrustedNutraProducts.git
git push -u origin main
```

Replace `YOUR_USERNAME` with your actual GitHub username.

### Step 2: Add FTP Credentials as GitHub Secrets

GitHub Secrets keep your FTP password secure and hidden from public view.

1. Go to your GitHub repository
2. Click **Settings** (top menu)
3. In the left sidebar, click **Secrets and variables** → **Actions**
4. Click **New repository secret** button

Add these 3 secrets one by one:

#### Secret 1: FTP_SERVER
- **Name:** `FTP_SERVER`
- **Value:** `da40.mycloudhosting.com`
- Click "Add secret"

#### Secret 2: FTP_USERNAME
- **Name:** `FTP_USERNAME`
- **Value:** `metatrim-deploy@trustednutraproduct.com`
- Click "Add secret"

#### Secret 3: FTP_PASSWORD
- **Name:** `FTP_PASSWORD`
- **Value:** `Ali547$$$`
- Click "Add secret"

### Step 3: Test the Deployment

You have two ways to trigger deployment:

#### Option A: Automatic (Recommended)
1. Make any change to files inside `meta trim/New Funnel/` folder
2. Commit and push to GitHub:
   ```bash
   git add .
   git commit -m "Update Meta Trim funnel"
   git push
   ```
3. GitHub Actions will automatically deploy to your hosting

#### Option B: Manual Trigger
1. Go to your GitHub repository
2. Click **Actions** tab (top menu)
3. Click **Deploy Meta Trim to Hosting** workflow (left sidebar)
4. Click **Run workflow** button (right side)
5. Click the green **Run workflow** button
6. Watch the deployment progress in real-time

### Step 4: Verify Deployment

1. Check GitHub Actions:
   - Go to **Actions** tab in your GitHub repository
   - You should see a green checkmark ✅ when deployment succeeds
   - Click on the workflow run to see detailed logs

2. Check your website:
   - Visit: https://metatrim.trustednutraproduct.com
   - Your updated files should be live!

## FTP Account Details (For Reference)

**Server:** da40.mycloudhosting.com
**Username:** metatrim-deploy@trustednutraproduct.com
**Password:** Ali547$$$
**Remote Path:** /home/jojofwjv/domains/metatrim.trustednutraproduct.com/public_html/

## How to Update Your Website

From now on, updating your live website is simple:

1. Make changes to files in `meta trim/New Funnel/` folder
2. Commit and push to GitHub:
   ```bash
   git add .
   git commit -m "Describe your changes here"
   git push
   ```
3. Wait 1-2 minutes for automatic deployment
4. Visit your website to see changes live

## Troubleshooting

### Deployment Failed
- Check the **Actions** tab on GitHub for error messages
- Verify your GitHub Secrets are correct (Settings → Secrets and variables → Actions)
- Make sure FTP account is not suspended in hosting panel

### Changes Not Appearing
- Check if the workflow ran successfully in GitHub Actions
- Clear your browser cache (Ctrl+Shift+R or Cmd+Shift+R)
- Check if files were modified inside `meta trim/New Funnel/` folder

### Manual FTP Access
You can still manually upload files using any FTP client:
- Use the FTP account details listed above
- Recommended FTP clients: FileZilla, WinSCP, Cyberduck

## Security Notes

- ✅ Your FTP password is stored securely as a GitHub Secret
- ✅ The `.gitignore` file prevents sensitive data from being committed
- ✅ The FTP account only has access to the Meta Trim subdomain folder
- ⚠️ Never commit passwords or API keys to Git
- ⚠️ Keep your GitHub account secure with 2FA (Two-Factor Authentication)

## Need Help?

If you encounter issues:
1. Check the GitHub Actions logs for detailed error messages
2. Verify FTP credentials in your hosting panel
3. Test FTP connection manually using FileZilla or similar client
