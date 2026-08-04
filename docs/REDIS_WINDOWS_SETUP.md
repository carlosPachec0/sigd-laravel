# Using Redis on Windows for This Project

This project can use Redis for **sessions**, **cache**, and **queues**. Redis is
optional per-developer: `.env.example` ships with the safe defaults (`database`/`file`
drivers) so a fresh clone works with zero extra setup. Enable Redis locally only if
you want to test rate limiting, cache, or queue behavior the way it runs in
staging/production.

## Why Predis, not phpredis

`REDIS_CLIENT` is set to `predis` (see `config/database.php`). [Predis](https://github.com/predis/predis)
is a pure-PHP client installed via Composer — no PHP extension required. The
`phpredis` C extension is faster, but installing/enabling `ext-redis` in `php.ini`
is an extra step on every machine (this repo doesn't require it). If your PHP build
already has `phpredis` loaded (`php -m | grep -i redis`), you can switch
`REDIS_CLIENT=phpredis` in your own `.env` — no code changes needed either way.

## Getting a Redis server on Windows

Windows has no first-party Redis build. Pick one:

| Option | Notes |
|---|---|
| **WSL2 + Ubuntu** (recommended, covered below) | Free, matches Linux prod behavior, what this doc sets up. |
| [Memurai](https://www.memurai.com/) | Native Windows service, no WSL involved. Good if you want to avoid WSL entirely. |
| Docker Desktop + `redis` image | Good if you already run Docker for other services. |

The rest of this doc covers the **WSL2** path.

## 1. Install Redis inside WSL

```bash
# From Windows (PowerShell/Git Bash), if you don't already have a distro:
wsl --install -d Ubuntu

# Then inside the Ubuntu shell:
sudo apt update
sudo apt install -y redis-server
```

## 2. Enable systemd so Redis starts automatically

WSL distros don't run systemd by default. Edit `/etc/wsl.conf` **inside the Ubuntu
distro**:

```ini
[boot]
systemd=true
```

Apply it by restarting WSL from Windows:

```powershell
wsl --shutdown
```

Then re-enter the distro (`wsl -d Ubuntu`) and enable + start the service:

```bash
sudo systemctl enable --now redis-server
systemctl is-active redis-server   # should print: active
```

## 3. The WSL idle-shutdown gotcha (important)

By default, **WSL tears down an idle distro instance ~8 seconds after its last
process exits** — even with systemd enabled. If nothing is keeping the Ubuntu
instance running, `redis-server` gets killed along with it, and Laravel starts
throwing:

```
Predis\Connection\Resource\Exception\StreamInitException:
No se puede establecer una conexión ya que el equipo de destino denegó
expresamente dicha conexión [tcp://127.0.0.1:6379]
```

(i.e. "connection refused" — this is the exact error that caused a 500 on every
throttled endpoint in this project, since the rate limiter reads/writes through
the Redis cache store.)

**Fix: keep the distro alive at login** with a Windows Scheduled Task that runs a
long-lived WSL process:

```powershell
$action = New-ScheduledTaskAction -Execute "$env:WINDIR\System32\wsl.exe" -Argument "-d Ubuntu -e sleep infinity"
$trigger = New-ScheduledTaskTrigger -AtLogOn -User "$env:USERDOMAIN\$env:USERNAME"
$settings = New-ScheduledTaskSettingsSet -Hidden -ExecutionTimeLimit ([TimeSpan]::Zero) -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1)
$principal = New-ScheduledTaskPrincipal -UserId "$env:USERDOMAIN\$env:USERNAME" -LogonType Interactive -RunLevel Limited
Register-ScheduledTask -TaskName "WSL-Redis-Keepalive" -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Description "Keeps the WSL Ubuntu distro (and its systemd-managed redis-server) running for local Laravel dev" -Force
Start-ScheduledTask -TaskName "WSL-Redis-Keepalive"   # start it now instead of waiting for next login
```

Also add `%UserProfile%\.wslconfig` (create it if it doesn't exist) to keep the
shared WSL2 VM warm between distro restarts:

```ini
[wsl2]
vmIdleTimeout=-1
```

`vmIdleTimeout` alone does **not** replace the scheduled task — it only keeps the
underlying VM warm for faster boots. The scheduled task is what actually keeps the
Ubuntu *instance* (and therefore Redis) running continuously.

### Verifying it's working

```bash
# From Windows — confirms the port is actually reachable, not just that WSL is up
php -r '$s=@stream_socket_client("tcp://127.0.0.1:6379",$e,$s2,3); echo $s?"OK\n":"FAIL $e $s2\n";'
```

Wait at least 15 seconds after a fresh login/reboot and re-run the check above — if
it still says `OK`, the keepalive task is doing its job.

## 4. Point this project at Redis

`.env.example` intentionally stays on `database`/`file` drivers. To opt in locally,
edit your own `.env`:

```env
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0          # used by the queue + session (default) connection
REDIS_CACHE_DB=1    # separate DB so cache keys don't collide with queue/session keys
```

Then clear cached config and confirm Laravel can reach it:

```bash
php artisan config:clear
php artisan tinker --execute="echo Illuminate\Support\Facades\Redis::connection()->ping();"
# => PONG
```

If you enabled the `redis` queue connection, run a worker to actually process jobs:

```bash
php artisan queue:work redis
```

## 5. Reverting to non-Redis locally

Just set `.env` back to the defaults from `.env.example`
(`SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`) and
run `php artisan config:clear`. No code changes are involved either way — every
driver is config-only.

If you also want to stop the WSL keepalive task:

```powershell
Unregister-ScheduledTask -TaskName "WSL-Redis-Keepalive" -Confirm:$false
```

## Troubleshooting reference

| Symptom | Cause | Fix |
|---|---|---|
| `StreamInitException: connection refused [tcp://127.0.0.1:6379]` | Redis isn't reachable — most commonly the WSL distro shut down from being idle | Confirm the `WSL-Redis-Keepalive` task exists and is running (`Get-ScheduledTask WSL-Redis-Keepalive`); re-run the port check in step 3 |
| Same error, but `redis-cli ping` works *inside* WSL | Port forwarding to Windows isn't up (distro just restarted, or hasn't fully booted) | Wait a few seconds and retry; verify with the PHP `stream_socket_client` snippet above, not just `redis-cli` inside WSL |
| `Class "Redis" not found` | You set `REDIS_CLIENT=phpredis` without the PHP extension installed | Either install/enable `ext-redis` in `php.ini`, or switch back to `REDIS_CLIENT=predis` (no extension needed) |
