@echo off
REM ---------------------------------------------------------------------------
REM WebFactory — Windows wrapper around the Makefile.
REM
REM Forwards the requested target to GNU make running inside the wf-app
REM container, so Windows users do not need GNU make installed natively.
REM
REM Usage: make.cmd <target> [args...]
REM Examples: make.cmd up
REM           make.cmd test
REM           make.cmd lint
REM ---------------------------------------------------------------------------

setlocal

REM If the wf-app container is up, run make there.
docker compose ps --status running --services 2>NUL | findstr /R "^wf-app$" >NUL
if %ERRORLEVEL% == 0 (
    docker compose exec wf-app make %*
    goto :eof
)

REM Otherwise, bring it up implicitly for "setup", or run an ephemeral container.
if /I "%1" == "setup" (
    docker compose up -d
    docker compose exec wf-app make %*
    goto :eof
)

if /I "%1" == "build" (
    docker compose build --pull
    goto :eof
)

if /I "%1" == "up" (
    docker compose up -d
    goto :eof
)

if /I "%1" == "down" (
    docker compose down
    goto :eof
)

REM Fallback: ephemeral container (slower, but works without the stack running).
docker compose run --rm wf-app make %*

endlocal
