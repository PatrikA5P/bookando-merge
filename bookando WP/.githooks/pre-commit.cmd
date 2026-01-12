@echo off
setlocal enabledelayedexpansion
set "HOOK=%~dp0pre-commit.sh"
for %%I in (sh.exe bash.exe) do (
  where %%I >nul 2>&1 && (%%I "%HOOK%" %* & exit /b !errorlevel!)
)
if exist "%PROGRAMFILES%\Git\usr\bin\sh.exe" "%PROGRAMFILES%\Git\usr\bin\sh.exe" "%HOOK%" %*
exit /b %ERRORLEVEL%
