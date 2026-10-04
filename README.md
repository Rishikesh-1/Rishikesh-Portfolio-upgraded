# Rishikesh Rana — Portfolio & Content Management System

A high-performance personal portfolio, dynamic agency services engine, and bespoke Content Management System (CMS) built with PHP 8, MySQL (PDO), Vanilla JavaScript, and a responsive ink-black design system.

Live Website: [https://rishikeshrana.com.np](https://rishikeshrana.com.np)

---

## 🌟 Key Features

### 1. 💼 Conversion-Optimized Services Engine
- **Depth-of-Service Landing Pages**: Dedicated slug-based pages (`/service.php?slug=...`) with SEO-optimized titles and structured data.
- **Interactive Roadmaps & Deliverables**: Transparent milestone timelines and feature checklists tailored to each offering.
- **3-Tier Pricing Packages**: Tiered cards with *"Choose package"* handlers that pre-populate the booking form.
- **Case Study Proof**: Displays real projects delivered under that specific service to build instant client trust.
- **Integrated Booking Form**: One-click booking with CSRF tokens, honeypot anti-spam, and direct dashboard inbox routing.
- **Schema.org Rich Snippets**: Built-in Google `Service` and `FAQPage` JSON-LD markup.

### 2. 📁 Universal Media Library
- **Centralized Asset Management**: Upload, preview, organize, and delete images directly from the dashboard.
- **Universal Modal Selector**: Select images across all admin forms (projects, blog posts, services, testimonials, clients, and site settings) with one click.
- **Drag & Drop Uploads**: Supports JPEG, PNG, WebP, SVG, and GIF up to 10MB.
- **Instant Search & Filter**: Real-time filename search and format filtering (PNG, JPG, WEBP, SVG).

### 3. 🚀 Dynamic Projects & Case Studies
- **Filterable Showcase**: Categorized portfolio grid with responsive layouts and hover micro-interactions.
- **Rich Project Detail Pages**: Full-width hero galleries, client outcomes, technologies used, and live demo / GitHub links.

### 4. ✍️ Blog & Editorial Engine
- **Rich Content Publishing**: Support for headlines, lists, blockquotes, and inline media insertion.
- **SEO Ready**: Automated slug generation, meta descriptions, social OpenGraph / Twitter cards, and reading time estimation.

### 5. 🤝 Clients & Brands Infinite Slider
- **Smooth Animation**: Continuous CSS marquee with pause-on-hover capability.
- **Dashboard Managed**: Add client names, logos, website links, and toggle visibility.

### 6. 🔒 Custom Admin Dashboard & Security
- **Secure Authentication**: Bcrypt password hashing (`PASSWORD_DEFAULT`), session timeout management, and brute-force lockout throttling.
- **Self-Healing Migrations**: Automatic schema synchronization on startup ensuring missing columns or tables are created safely without data loss.
- **Spam & CSRF Protection**: Every form is protected by cryptographically secure CSRF tokens and invisible honeypots.
- **Messages & Leads Inbox**: Unified dashboard to manage contact inquiries and service booking requests.

### 7. 🎨 Design System & Accessibility
- **Theme**: Ink-black surface with neon cyan (`#5ce1ff`) signal accents and dark/light mode toggle.
- **Typography**: Editorial elegance with *Fraunces* serif display headings and *Inter* body text.
- **Performance**: Zero bulky frameworks — lightweight vanilla assets optimized for high Google Lighthouse scores.

---

## 🛠️ Tech Stack

- **Backend**: PHP 8.1+ / MySQL (PDO with prepared statements)
- **Frontend**: HTML5, Modern CSS (Grid, Flexbox, Custom Properties, Glassmorphism), Vanilla JavaScript (ES6+)
- **Server**: Apache with `.htaccess` URL rewrites and caching headers
- **Deployment**: Key-based SFTP automated synchronization (`deploy-sftp.ps1`)

---

## 📂 Project Structure

```text
portfolio-cms/
├── admin/                     # Admin dashboard controllers & views
│   ├── includes/              # Admin header, footer, modal, inline helpers
│   ├── dashboard.php          # Overview metrics and recent activity
│   ├── manage.php             # Unified CRUD controller for site entities
│   ├── media.php              # Full-page Media Library manager
│   ├── messages.php           # Inbound contact & booking messages
│   └── projects.php           # Project creation & editing
├── assets/
│   ├── css/style.css          # Master stylesheet & responsive design system
│   ├── js/main.js             # Client interactivity, sliders, theme toggle
│   └── img/                   # System graphics and loaders
├── config/
│   ├── config.php             # App constants, session bootstrap & logger
│   └── db.example.php         # Database configuration template
├── includes/
│   ├── clients-slider.php     # Brand logo marquee component
│   ├── functions.php          # Core helpers, sanitize, upload handlers
│   ├── header.php / nav.php   # Public navigation & meta headers
│   └── services-lib.php       # Services schema migration, icons & renderer
├── logs/                      # Error and security audit logs (protected)
├── uploads/                   # User-uploaded images and documents
├── index.php                  # Main homepage entry point
├── page.php                   # Archive pages (Work, Services, Blog, etc.)
├── service.php                # Depth-of-service detail landing page
├── service-book.php           # Booking submission handler
├── deploy-sftp.ps1            # Automated cPanel SFTP deployment script
└── schema.sql                 # Complete database schema
```

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.1 or higher
- MySQL 5.7 / MariaDB 10.3+
- Web server (Apache with `mod_rewrite` enabled, or XAMPP / LocalWP / Nginx)

### Local Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Rishikesh-1/Rishikesh-Portfolio-upgraded.git
   cd Rishikesh-Portfolio-upgraded
   ```

2. **Configure Database:**
   - Copy `config/db.example.php` to `config/db.php`:
     ```bash
     cp config/db.example.php config/db.php
     ```
   - Open `config/db.php` and fill in your database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'portfolio_cms');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

3. **Initialize Database:**
   - Create the database `portfolio_cms` in phpMyAdmin or MySQL CLI.
   - Import `schema.sql` into your database.
   *(The built-in self-healing migration will automatically ensure any new tables/columns are synchronized upon first load).*

4. **Start the Application:**
   - If using XAMPP, place the project inside `htdocs/portfolio-cms` and navigate to:
     `http://localhost/portfolio-cms`
   - Or start PHP's built-in server:
     ```bash
     php -S localhost:8000
     ```

5. **Access the Admin Dashboard:**
   - Navigate to `/admin` (e.g. `http://localhost/portfolio-cms/admin`).
   - Log in with your admin credentials to manage content.

---

## 🚀 Deployment to cPanel

An automated SFTP script is included for one-command deployment to live hosting:

```powershell
.\deploy-sftp.ps1 -SshHost your-server.com -User cpanel_user -RemotePath rishikeshrana.com.np -KeyFile C:/path/to/ssh_key
```

*The script automatically excludes local configurations (`config/db.php`), uploaded media (`uploads/`), and development caches.*

---

## 📄 License

This project is open source and available under the [MIT License](LICENSE).
