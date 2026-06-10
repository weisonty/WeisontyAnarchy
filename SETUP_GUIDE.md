# Weisonty Anarchy — Setup Guide

Complete PocketMine-MP Bedrock anarchy server for Minecraft 1.19.51.

---

## Quick Start (5 minutes)

### Step 1 — Download PocketMine-MP

Go to: https://github.com/pmmp/PocketMine-MP/releases

Download `PocketMine-MP.phar` and place it in your server folder (same folder as `run.sh`).

> **Which version?** Use the latest 5.x stable release.  
> PocketMine-MP 5.x supports Minecraft Bedrock 1.19.x–1.20.x.

---

### Step 2 — Install PHP

**Linux/VPS:**
```bash
apt install php8.2-cli php8.2-mbstring php8.2-curl php8.2-xml php8.2-zip php8.2-gd -y
```

**Windows:**
Download PHP 8.2 Thread Safe from https://windows.php.net/download/  
Extract to `bin\php\php.exe` in your server folder.

**Mac:**
```bash
brew install php@8.2
```

---

### Step 3 — Run the Server

**Linux/Mac:**
```bash
chmod +x run.sh
./run.sh
```

**Windows:**
Double-click `run.bat`

The server will generate the world and default files on first start.  
Type `stop` in the console to shut it down cleanly.

---

### Step 4 — Make Yourself Admin

1. Open `ops.txt` in the server folder
2. Add your exact Minecraft username (case-sensitive)
3. Save and restart the server, or run `op YourName` in console

---

## Custom World Import

To use a custom Bedrock world:

1. Get your world folder (it should contain `level.dat` and `db/` folder)
2. **If you have a ZIP:** Extract it so you have a folder like `MyWorld/`
3. Copy that folder into `worlds/` in your server folder
4. Edit `server.properties`:
   ```
   level-name=MyWorld
   ```
5. Edit `plugins/AnarchyCore/config.yml`:
   ```yaml
   server:
     spawn-world: "MyWorld"
   spawn:
     world: "MyWorld"
     x: 0
     y: 65
     z: 0
   ```
6. Restart the server. It will load your world.

> **Tip:** To find a good spawn Y coordinate, join the server and type `/tp 0 100 0`, then look down to find ground level.

---

## Setting Up Spawn NPCs

NPCs spawn automatically on server start at the coordinates set in `plugins/AnarchyCore/config.yml`.

To adjust NPC positions, edit the config:

```yaml
npc:
  enabled: true
  npcs:
    server_info:
      name: "§eServer Info"
      x: 10
      y: 65
      z: 0
      world: "world"
      type: "SERVER_INFO"
    rtp:
      name: "§aRandom Teleport"
      x: -10
      y: 65
      z: 0
      world: "world"
      type: "RTP"
    # ... etc
```

Set the coordinates to wherever your spawn area is, then restart.

---

## Player Commands Reference

| Command | Description |
|---------|-------------|
| `/spawn` | Teleport to world spawn |
| `/rtp` | Random teleport (500–10,000 blocks away) |
| `/sethome <name>` | Set a home (max 5) |
| `/home [name]` | Teleport to a home |
| `/delhome <name>` | Delete a home |
| `/msg <player> <msg>` | Private message |
| `/reply <msg>` | Reply to last PM |
| `/tpa <player>` | Request teleport to player |
| `/tpaccept` | Accept a TPA request |
| `/tpdeny` | Deny a TPA request |
| `/profile [player]` | View player profile |
| `/stats` | Top kills leaderboard |

---

## Admin Commands Reference

| Command | Permission | Description |
|---------|-----------|-------------|
| `/ban <player> [reason]` | `admintools.ban` | Permanently ban |
| `/unban <player>` | `admintools.ban` | Remove a ban |
| `/kick <player> [reason]` | `admintools.kick` | Kick from server |
| `/mute <player> [seconds] [reason]` | `admintools.mute` | Mute in chat |
| `/unmute <player>` | `admintools.mute` | Remove mute |
| `/warn <player> <reason>` | `admintools.warn` | Issue a warning |
| `/freeze <player>` | `admintools.freeze` | Freeze/unfreeze |
| `/tp <player> [target]` | `admintools.tp` | Teleport |
| `/tphere <player>` | `admintools.tp` | Pull player to you |
| `/invsee <player>` | `admintools.invsee` | View inventory |
| `/give <player> <item> [amount]` | `admintools.give` | Give items |
| `/adminlog [lines]` | `admintools.log` | View admin action log |
| `/acalerts [player] [lines]` | `anticheat.admin` | View AC flags |
| `/acbypass <player>` | `anticheat.admin` | Toggle AC bypass |

---

## Anti-Cheat System

AntiCheatLite runs **log-only mode by default** — it never bans or kicks automatically.

**What it detects:**
- Fly hacking (sustained air time)
- Speed hacking (movement too fast)
- NoFall (fall damage not applied)
- Chat spam
- Teleport abuse

**When triggered:**
- Violation logged to `plugins/AntiCheatLite/logs/anticheat.log`
- Admin players see alerts in chat after every 3 violations

**Commands:**
```
/acalerts               — last 20 AC log entries
/acalerts Steve 50      — last 50 entries for Steve
/acbypass Steve         — toggle bypass for Steve
```

To tune sensitivity, edit `plugins/AntiCheatLite/config.yml`:
```yaml
checks:
  speed:
    max-speed: 12.0   # blocks/second — lower = stricter
  fly:
    max-air-ticks: 20 # ticks in air before flag
violation-threshold: 3  # violations before admin alert
```

---

## Logging System

All logs are stored in plugin data folders:

| Log File | Contents |
|----------|----------|
| `plugins/AnarchyCore/logs/YYYY-MM-DD.log` | Joins, leaves, deaths, kills, commands |
| `plugins/AdminTools/logs/admin-actions.log` | All admin actions |
| `plugins/AntiCheatLite/logs/anticheat.log` | Anti-cheat flags |

---

## Performance Tuning

For a **VPS with 2GB RAM:**

Edit `pocketmine.yml`:
```yaml
memory:
  main-limit: 1024
  main-hard-limit: 1536
```

For **100+ players**, increase view distance moderately:
```
view-distance=6
```
in `server.properties`.

---

## Port Forwarding (LAN / Home Hosting)

1. Find your router admin panel (usually `192.168.1.1`)
2. Create a UDP port forward rule:
   - External port: `19132`
   - Internal port: `19132`
   - Protocol: `UDP`
   - Internal IP: your PC's local IP (e.g. `192.168.1.100`)
3. Share your public IP with players

Find your public IP at: https://whatismyip.com

---

## VPS Hosting (Production)

Recommended VPS providers for Minecraft servers:
- **Hetzner** (EU) — very cheap, good performance
- **OVH** (Global) — DDoS protection
- **DigitalOcean / Vultr** — easy setup

**Minimum specs:** 2GB RAM, 2 vCPU, 20GB SSD  
**Recommended:** 4GB RAM, 4 vCPU for 50+ players

**Deploy steps on Ubuntu VPS:**
```bash
# Upload your server folder via SFTP
# Then SSH in and:
cd /home/user/WeisontyAnarchy
chmod +x run.sh

# Run in background (survives logout):
screen -S anarchy ./run.sh

# Detach: CTRL+A then D
# Reattach: screen -r anarchy
```

---

## Folder Structure Overview

```
WeisontyAnarchy/
├── PocketMine-MP.phar          ← Download and place here
├── run.sh                      ← Linux/Mac start script
├── run.bat                     ← Windows start script
├── server.properties           ← Server settings
├── pocketmine.yml              ← Engine settings
├── ops.txt                     ← Operator usernames
├── plugins/
│   ├── AnarchyCore/            ← Main gameplay plugin
│   │   └── config.yml         ← Homes, RTP, NPC coords, spawn
│   ├── AdminTools/             ← Ban/kick/mute/freeze/etc
│   │   └── config.yml         ← Admin notification settings
│   └── AntiCheatLite/          ← Anti-cheat monitoring
│       └── config.yml         ← Check thresholds
├── worlds/
│   └── world/                  ← Default world (auto-generated)
│       (or paste your custom world folder here)
└── SETUP_GUIDE.md              ← This file
```

---

## Troubleshooting

**"PHP not found"**  
Install PHP 8.1+ or place the binary at `bin/php/php.exe` (Windows) or `bin/php7/bin/php` (Linux).

**"PocketMine-MP.phar not found"**  
Download from https://github.com/pmmp/PocketMine-MP/releases and place in this folder.

**Server starts but players can't connect**  
- Check your firewall allows UDP port 19132
- Confirm `server-port=19132` in `server.properties`
- For VPS: check your cloud provider's firewall/security group rules

**NPCs don't spawn**  
- Ensure NPC coordinates in `config.yml` are above solid ground
- Check the world name matches your actual world folder name
- Check `plugins/AnarchyCore/logs/` for any error messages

**World not loading**  
- Verify the world folder contains `level.dat` and `db/` directory
- The folder name must match `level-name=` in `server.properties` exactly

**Plugin errors on startup**  
- Ensure you're using PocketMine-MP 5.x
- Check server console output for the specific error
- Plugin data folders are in `plugin_data/`

---

## Need Help?

- PocketMine-MP docs: https://pmmp.readthedocs.io
- PocketMine Discord: https://discord.gg/bmSAZBG
- GitHub Issues: https://github.com/pmmp/PocketMine-MP/issues
