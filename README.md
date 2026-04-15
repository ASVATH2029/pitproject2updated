# Student Archival System (NAS)

The Ultimate Academic Storage Infrastructure

Hey guys. This is a completely overhauled, highly optimized, wildly secure Network-Attached Storage system that we built explicitly to protect, serve, and archive academic workloads for students at PITS. Tbh, we got really tired of how we were handling data, so we just decided to build the whole thing from scratch ourselves. 

---

### 📚 Documentation & Guides

| Document | Description |
|:---------|:------------|
| [**SETUP.md**](SETUP.md) | Full server installation & deployment walkthrough — Apache, PHP, Ngrok, PHPMailer, Kerberos SSO |
| [**LDAP_SETUP.md**](LDAP_SETUP.md) | Step-by-step guide to enable Active Directory (LDAP) authentication & Kerberos SSO |
| [**DEPLOYMENT_LOG.md**](DEPLOYMENT_LOG.md) | Chronological log of all deployment sessions, server changes & troubleshooting notes |
| [**LICENSE**](LICENSE) | Proprietary license — all rights reserved |

---

## 1. Live Access & Network Topology

Live Production Status: ONLINE
Official Access Portal: http://tiny.cc/pitsnas

Welcome to what is basically the future of our academic archiving. The Student Archival System is vastly more than a simple file-host. It is a strictly controlled, high-performance Network-Attached Storage (NAS) framework that we are hosting exclusively on private Debian hardware.

The entire environment perfectly bridges local Linux file storage with a global web audience through advanced Ngrok tunneling and strict proxy routing logic. When you engage with tiny.cc/pitsnas, your connection is wrapped and fired through an intricate public proxy to flawlessly resolve on our private backend without compromising the host architecture. 

Hardware Specs: The server is currently operating with a 250GB absolute hardware storage limit. Ngl, this limit is intentionally locked at 250GB for local trial runs right now, but we will scale it up horizontally to handle TBs of traffic once broader web adoption launches and we inevitably need way more space.

---

## 2. The Origin Story

Our college utilizes a strict "DNO" system where every student is assigned a specific DNO for their entire academic life. It is the only metric by which they are recognized. The core problem? Once a student graduates or leaves the institution, there is zero remembrance of them. Their footprint in the college database essentially vanishes.

We (the creators) came up with an insane idea to solve this disconnect. 

We engineered the Student Archival System as our **Third-Year Software Engineering Mini Project**. It is designed to give students a permanent, highly secure bridge to stay connected with the institution. Instead of being completely wiped from memory, students can continually access this infrastructure to indefinitely archive sensitive, vital data (like their official mark sheets, certifications, and legacy projects) directly within the college's ecosystem forever. 

### The Original Hardware Blueprint
Interestingly, our absolute original concept for this NAS deployment was heavily rooted in hardware salvaging. We planned to physically recover discarded and decommissioned PCs from around the college campus to build a Frankenstein server. The goal was to run a headless Debian instance—chosen specifically because it is incredibly lightweight—and pool together harvested RAM sticks extracted from multiple dead systems to buff the server's memory. To scale the storage, we planned on hoarding standard SATA HDDs and expanding the internal drive capacity drastically by bridging multiple drives into the motherboard using PCIe-to-SATA expansion cards. On the networking side, this early idea involved running internal SAMBA shares alongside NGINX to interlink all the systems purely within the college's local intranet, before we eventually pivoted to our current global-facing web architecture.

*(Future Networking Evolution: We're actively planning to eventually inject a deeply secure, end-to-end encrypted messaging matrix directly into this web infrastructure! This will allow current students to seamlessly and safely network with alumni and seniors, effectively keeping the entire college permanently connected across generations.)*

---

## 3. Software Architecture Blueprint

The system does not rely on heavy, cumbersome PHP frameworks like Laravel or CodeIgniter. To squeeze every absolutely possible ounce of speed and performance from the Linux disk, we engineered the entire backend in Vanilla PHP 8. 

By communicating natively with the Debian filesystem logic via core scripts (upload.php, delete.php, rename.php, download.php), the overhead is virtually, entirely eliminated. Idk if you guys have tried building full-stack PHP apps recently, but going vanilla for file I/O operations is insanely fast.

The Component Breakdown
- The Client (Browser): Extremely lightweight HTML5, driven entirely by vanilla CSS variables rendering heavy "Glassmorphism" techniques over animated CSS particles.
- The API Layer: Asynchronous JavaScript fetch() calls bridging the user dashboard elements instantaneously to the backend without reloading the viewport.
- The Core Engine: Raw PHP directly interacting with physical user isolated directories residing at /srv/project/.
- The Security Matrix: Built-in PHP string manipulation, file quarantine evaluations, and mathematical Regular Expressions (Regex).

---

## 4. Educational Integrity Enforcements

This overarching platform is fiercely dedicated to purely academic endeavors.

When registering/signing up for an account, you are strictly required to use your Official College Login ID as your Username. 

Why is this enforced?
By matching user accounts 1:1 with College IDs, the server dynamically tracks footprints, maps active directory usages, and allows system administrators to gracefully resolve lost credentials. Any registration attempting to use anonymous, fake, or non-educational naming structures will be detected instantly. 

Malicious accounts are tracked continuously via the universal Admin portal and run the absolute risk of being permanently purged by automated server-side sweeps. Btw, we really wouldn't test the automated deletion scripts; they use severe Unix forced-delete commands.

---

## 5. The Ultimate Security Perimeter

When allowing anyone on the internet to upload physical files to your personal Linux machine, security is obviously the biggest priority. We made sure we have a military-grade backend posture spanning several vectors.

A. The Strict Password Regex Constraints
The system absolutely refuses to accept weakly compressed passwords. If a malicious bot or lazy student tries to register with password123, the PHP backend mechanically destroys the request. 
The system natively executes preg_match() functions to force an active 8-character minimal footprint, containing at least 1 alphabet letter, 1 number digit, and 1 keyboard symbol.

B. The Executable Quarantine Protocol
By far the most prevalent vulnerability on a NAS is Remote Code Execution (RCE) via malicious uploads. We completely neutralized this.
The architecture possesses a master BLOCKED_EXTENSIONS configuration array holding thousands of exploit-vectors (.php, .sh, .exe, .bat, .ps1, .pl, .cgi). 
- Upload Interception: If a user attempts to upload an executable script, upload.php catches the extension and physically locks the filesystem, rejecting the file.
- Rename Loophole Interception: Hackers frequently bypass upload filters by submitting a safe malware.txt, waiting until it is on the dashboard, and renaming it to malware.php. This loophole is crushed. The rename.php endpoint identically parses the quarantine blocklist before any filesystem rename() call triggers.

C. Session Hardening
Every session spins off a hardened Strict SameSite cookie and HttpOnly flags. The platform automatically triggers a 2-hour idle timeout flushing abandoned users aggressively so sessions can't be hijacked.

---

## 6. Mobile Optimization & The Glassmorphism UI

We heavily embraced modern "Dark Botanical" Glassmorphism aesthetics for this. Unlike typical dull academic sites, this storage grid actively renders moving particle animations and deep rich blur filters.

Crucially, the entire interface was overhauled to scale perfectly on mobile viewports.
- Collapsed Viewports: The core authentication cards have their internal boundaries strictly condensed to exactly max-width: 600px CSS media queries. This ensures that massive borders do not swallow mobile screens.
- The Floating Pill Interface: Instead of the bulky desktop top-bar, mobile users automatically receive the .top-bar.scrolled layout physics natively. The time/date indicator elegantly collapses into a floating, frosted pill window matching the desktop scroll-effect flawlessly. It honestly looks so clean on phones tbh.

---

## 7. The Universal Admin Portal

Administrators control exactly who has access to the Debian host via the admin.php control center.

By declaring ADMIN_USERS organically in the primary .config, staff receive access to a hidden universal tracker array.
- Administrators can review the total physical server size vs utilization mapping directly from the UI.
- They can trigger a deleteUser() call that executes a visceral Unix "rm -rf" shell command to utterly eradicate malicious users and strip their footprints out of the physical directory base.
- Target Tracking & Impersonation: Admins possess an exclusive "Override Files" button allowing them to seamlessly bypass standard credentials, inject a target parameter into their dashboard requests, and actively rename, delete, or upload into student directories directly without requesting the student's password. 

---

## 8. PHPMailer & OTP Authentication

Forgotten passwords ruin user experiences. We employed the highly robust open-source library PHPMailer to manage global credential loss protocols.

When a student clicks "Forgot Password", they are pushed into an intricate state machine.
- The server generates a completely randomized 6-digit One-Time-Passcode (OTP).
- This passcode is aggressively hashed into a 10-minute maximum expiry session variable.
- A beautiful, branded HTML email is rendered and fired out bounds straight to the academic inbox via an SSL/TLS wrapped Gmail SMTP relay.
- Users receive three distinct tries to input the code natively in the UI. If they fail, the session self-destructs. Only successful verifications push users back into the Strict Password Regex generation module!

---

## 9. Storage Management & Quotas

A 250GB Debian server can fill up rapidly under academic deadlines. We actively manage storage distribution and enforce hardware limits effectively:

- 50MB Individual File Limits: The platform structurally denies any single item weighing over 50MB. This guarantees backend processing stays incredibly fast by preventing monolithic singular uploads.
- 200MB Global Account Quotas: Because each student only receives a strict 200MB hardware limit, it is mathematically impossible for a few rogue students to stuff the 250GB drive. If they cross their 200MB threshold, the backend physically bars all further API fetch() injections from uploading until the student actively deletes old media from their dashboard.

---

## 10. Deep Dive: Free Live Hosting via Ngrok

Because standard academic network environments are barricaded by immense physical hardware firewalls, securely exposing the local Debian rig directly to the public web required a creative, cost-effective solution.

Instead of engineering complex proxy bridges or spending money renting expensive external hosting servers, the system utilizes Ngrok to instantly host the live URL entirely for free. When you click the `tiny.cc/pitsnas` link, you are seamlessly riding the free Ngrok hosting tier! It effortlessly exposes our local frontend infrastructure to the internet without demanding any dedicated server costs or DNS routing expenses.

---

## 11. Required Libraries & Native Dependencies

If you wish to clone and execute this upon secondary servers, ensure you run:
- Core Engine: PHP 8.1+ (Required for modern string manipulation limits and performance arrays).
- Core Library Component: PHPMailer 6.9+ (Actively stored inside phpmailer/ locally via Composer/Git hooks to bypass system mail demands).
- Network Routing: ngrok physical binaries targeting specific ports. 
- Core OS Logic: Unix bash functions attached natively through PHP shell_exec() capabilities exclusively in Admin modules.

---

## 12. Development Life Cycle

This whole project did not happen in a day. It underwent an aggressive pipeline development spanning multiple iterations. The three of us spent various weekends coding this out together, along with a few late nights:
- Phase A: Scaffolding basic PHP loops and generic move_uploaded_file() storage.
- Phase B: Engineering the internal JSON authentication engines allowing multiple users, instead of a global shared folder.
- Phase C: Introducing the "Dark Botanical" CSS structures ensuring massive particle animations ran without skipping frames. This took forever to optimize tbh.
- Phase D: Introducing severe RCE security sweeps, the Universal Admin Portal, locking down RCE via executable checks, and perfecting the HTTP SameSite cookies.
- Phase E: Final polished mobile integration squeezing the UX perfectly to handheld devices, establishing absolute symmetry onto the quick_deploy/ mirror directory.

---

## 13. The Universal FIX/Troubleshooting Matrix

A NAS proxying over a global reverse tunnel creates quirks. This massive guide allows anyone on GitHub, or users actively engaging with tiny.cc/pitsnas, to troubleshoot any conceivable barrier flawlessly! So please read this before texting us about it being broken.

13A. 502 Bad Gateway or 504 Timeout on Ngrok
- The Core Problem: You load the tiny.cc tracker and receive an ugly gray page informing you that the Ngrok Tunnel has failed.
- The System Cause: Because this links to physical independent Debian hardware, if the local internet at the server location drops, or the machine reboots, the Ngrok tunnel disconnects.
- The Quick Fix: Unfortunately, only the Server Administrator can reboot the instance. Inform me to power on the Debian box (the box is handled by the IT admin at PITS, not us) and actively map the fresh ngrok start URI onto the tiny.cc redirection shortcut.

13B. "Upload Blocked. Executable files are not permitted"
- The Core Problem: The dashboard rejected your file structure outright.
- The System Cause: The system natively operates a quarantine pipeline hunting files matching .php, .exe, .bash, etc.
- The Quick Fix: The app is not broken, the quarantine is protecting the system. If you must submit academic code scripts or shell executions for your classes, please wrap the file entirely inside a .zip or .rar archive matrix. The backend will safely store archives.

13C. "File exceeds the 50 MB single-file limit"
- The Core Problem: The upload gets instantly destroyed upon selecting the media.
- The System Cause: The physical Debian box possesses a strictly guarded 50MB single-file barrier.
- The Quick Fix: If you are trying to bypass massive datasets or complex Premiere Pro videos, compress the data using Handbrake, or use an archiver to split the data into 45MB .zip chunk volumes. Upload every chunk independently.

13D. "Quota Exceeded (Usage: 200 MB / 200 MB)"
- The Core Problem: You are entirely locked out of adding any new file payloads anywhere.
- The System Cause: The dir_size() processor calculated your specific isolated folder and realized you pushed exactly 200MB+ against your limit.
- The Quick Fix: You will have to utilize your dashboard UI and aggressively manage your archival lists! Check the boxes of older media or out-dated homework versions and trigger the "Delete" sequence to free space.

13E. "Password requires 8+ chars with letters, numbers, and symbols"
- The Core Problem: You cannot register, and the password reset endpoint keeps yelling at you with an angry red warning block.
- The System Cause: The PHP preg_match() engine detected you attempted to compress security (i.e. Password123 is actively rejected).
- The Quick Fix: Force a massive symbol matrix onto your password end. Using %, $, *, or & satisfies the global regex rule system! Just make it secure.

13F. "Action Forbidden! Error: Target missing" / Override Fails
- The Core Problem: An Administrator tries tracking a student storage folder but nothing loads.
- The System Cause: The GET target string variable stripped non-alphanumeric elements, effectively isolating the API target structure.
- The Quick Fix: Make completely certain that the username string is validly parsed. You must jump directly from the admin.php tracking button logic back into dashboard.php rather than editing the URL directly.

13G. OTP Recovery Emails Will Not Route!
- The Core Problem: You hit the forgot password screen, trigger a code to your academic email, but no code arrives.
- The System Cause: The PHPMailer SMTP is relaying strictly via port 587 from a unified Google server node. Many strict academic colleges fiercely flag unrecognized .edu drops into spam tracking endpoints.
- The Quick Fix: Actively dig directly through your spam, junk, and promotions UI folders. If 10 full minutes pass, the OTP mathematical check dies cleanly on the server, forcing you to execute a new reset trace sequence!

---

## 14. Scaling & The Future Roadmap

This architecture is completely scalable! While it runs proudly on a tiny Debian footprint limited to 250GB, the config constants mapping the quotas and project dirs allow the infrastructure to expand onto massive TB NAS drives mechanically. 

Future feature roadmaps entail:
- **Storage Clustering:** Clustering secondary storage arrays natively to spread payload requests gracefully so we don't hit I/O bottlenecks when the whole campus logs in during finals week.
- **End-to-End Encrypted Alumni Networking:** Engineering a secure, embedded messaging matrix directly into the platform. This will allow active students to chat and network with graduated seniors/alumni natively within the college ecosystem, keeping the entire institution permanently connected across generations.
- **Dedicated Mobile App:** Potentially building a native iOS/Android app wrapper for the framework if we get enough free time.

---

## 15. The Legend

In case anyone gets confused by our terminology or abbreviations throughout the documentation, here's a quick cheat sheet so you know exactly what we are talking about:

- NAS: Network-Attached Storage. Basically, a remote hard drive you can access over Wi-Fi/Internet.
- PITS: The institution this system was specifically built to serve.
- UI: User Interface. What you actually see and click on the screen.
- UX: User Experience. How fast, fluid, and intuitive the app feels to use.
- TBH: To Be Honest. 
- NGL: Not Gonna Lie.
- BTW: By The Way.
- IDK: I Don't Know.
- OTP: One-Time Passcode. The random 6-digit number the server emails you to prove you own the account.
- Regex: Regular Expressions. Math/Logic filters the code uses to scan for symbols or specific text formats (like checking if your password is secure).
- RCE: Remote Code Execution. A scary hack where someone runs a virus on the server. We strictly block this.
- SMTP: Simple Mail Transfer Protocol. The backend highway we use to fire OTP emails to your inbox.
- JSON: JavaScript Object Notation. The lightweight file format we use to store account logic instead of a heavy SQL database.
- PSD: Photoshop Document.
- TB / GB / MB: Terabyte, Gigabyte, Megabyte. Storage size measurements.

Engineered aggressively for the sheer love of archiving.

---
### Creators & Maintainers
- **[Aditya Giri](https://github.com/Aditya-Giri-4356)**
- **[Asvath V J](https://github.com/ASVATH2029)**
- **[Bala Adithiya.S](https://github.com/balaadithiya150805-cyber)**

---
### ⚖️ License
Copyright © 2026 Aditya Giri, Asvath V J, Bala Adithiya.S. All Rights Reserved.

This repository and its entire software architecture is strictly **proprietary** and locked under closed-source copyright. None of the public may legally copy, clone, distribute, use, modify, or deploy this project, its code, or its assets under any circumstances whatsoever without explicit, direct written permission from the creators. 

Please see the `LICENSE` file for more information.

*Engineered for the sheer love of archiving. Code securely.*
