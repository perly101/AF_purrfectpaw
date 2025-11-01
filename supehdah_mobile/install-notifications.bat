@echo off
echo Installing Expo Notifications package...
echo.

npm install expo-notifications --legacy-peer-deps
npm install expo-device --legacy-peer-deps

echo.
echo Installation complete! The app should now be able to use Expo Notifications.
echo.
pause