@echo off
echo ============================================
echo   BuyGoods Analytics API Server
echo ============================================
echo.

REM Check if node_modules exists
if not exist "node_modules" (
    echo Installing dependencies...
    npm install
    echo.
)

REM Check if .env exists
if not exist ".env" (
    echo WARNING: .env file not found!
    echo Creating .env from .env.example...
    copy .env.example .env
    echo.
    echo Please edit .env with your database credentials!
    echo.
    pause
)

echo Starting server...
echo Dashboard will be available at http://localhost:3000
echo.
npm start
