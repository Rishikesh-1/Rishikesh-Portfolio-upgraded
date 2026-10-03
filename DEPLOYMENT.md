# Deployment Guide — cPanel

## 1. Create the database
1. cPanel → **MySQL Databases** → create a database (e.g. `rishi_portfolio`) and a user with a strong password.
2. Attach the user to the database with **All Privileges**.
3. cPanel → **phpMyAdmin** → select the new database → **Import** → upload `schema.sql`.

## 2. Upload the files
1. cPanel → **File Manager** (or FTP/SFTP).
2. Upload everything inside `portfolio-cms/` into `public_html/` (or a subfolder if this isn't the primary domain).
3. Confirm `uploads/.htaccess` made it across — this is the file that stops uploaded images from ever being executed as PHP. Don't skip it.

## 3. Configure the connection
Copy `config/db.example.php` to `config/db.php`, then fill in the database name, user, and password you created in step 1 (host is almost always `localhost` on cPanel). The real `config/db.php` is excluded from version control so credentials are not published.

Open `config/config.php` and set:
- `SITE_ROOT_URL` to your real domain, e.g. `https://rishikeshrana.com`
- `APP_DEBUG` stays `false` in production (only flip to `true` temporarily to see a real error message while debugging)

## 4. Create your admin account
1. Visit `https://yourdomain.com/admin/setup.php` once in your browser.
2. Fill in a username, email, and a strong password (10+ characters — this is hashed live on your server with `password_hash()`, which is why it isn't shipped in the SQL file).
3. **Delete `admin/setup.php` immediately after** — it refuses to run twice, but removing it is one less file for anyone to poke at.

## 5. Force HTTPS
cPanel → **SSL/TLS Status** → run AutoSSL (usually free via Let's Encrypt). Once HTTPS is live, your session cookie automatically gets the `secure` flag from `config.php` — no code change needed.

## 6. Verify security is actually working
- Try visiting `https://yourdomain.com/config/db.php` directly — it should be blocked (403) by the root `.htaccess`.
- Upload a project image, then try visiting `https://yourdomain.com/uploads/<filename>.php` — there shouldn't be one, because uploads are renamed and non-image types are rejected; the `.htaccess` inside `/uploads/` is the second layer of defense either way.
- Try 5 wrong login attempts on `/admin/login.php` — the 6th should show a lockout message for 15 minutes.

## 7. Submit to Google
- Visit `https://yourdomain.com/sitemap.php` to confirm it renders valid XML.
- Add the site to **Google Search Console** and submit `sitemap.php` as your sitemap.
- `robots.txt` already points crawlers to it and blocks `/admin/` and `/config/`.

## 8. Dashboard coverage
The protected dashboard provides CRUD management for Projects, Blog posts, Experience, Skills, Services, Social links, Testimonials, and Site settings/About. Messages can be reviewed and marked as read. All write actions use PDO prepared statements and CSRF protection; image uploads are MIME-checked, renamed, and stored in the non-executable `uploads/` directory.

The shared CRUD controller is `admin/manage.php`; it only accepts whitelisted section names and uses fixed table/column mappings, so user input is never used as an SQL identifier.

## 9. Ongoing maintenance
- Back up the database regularly (cPanel → Backup Wizard, or a cron'd `mysqldump`).
- Keep `uploads/` out of version control if you ever add git — it's user-generated content, not source.
- Change the admin password periodically; sessions auto-expire after 30 minutes idle either way.
- Keep `APP_DEBUG` set to `false` in `config/config.php` on cPanel.
