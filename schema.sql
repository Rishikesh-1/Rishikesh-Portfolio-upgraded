-- ============================================================
-- Rishikesh Rana Portfolio & CMS — Database Schema
-- Engine: InnoDB | Charset: utf8mb4
-- Import this via phpMyAdmin (cPanel) before anything else.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Admin users (dashboard login)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Site-wide settings (singleton row, key/value)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Hero / About (singleton row, id always 1)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS about_content (
    id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
    full_name VARCHAR(150) NOT NULL,
    role_title VARCHAR(150) NOT NULL,
    tagline VARCHAR(255) DEFAULT NULL,
    bio_text TEXT,
    profile_image VARCHAR(255) DEFAULT NULL,
    resume_file VARCHAR(255) DEFAULT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Experience / work history timeline
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS experience (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_title VARCHAR(150) NOT NULL,
    organization VARCHAR(150) NOT NULL,
    location VARCHAR(120) DEFAULT NULL,
    start_date VARCHAR(30) NOT NULL,
    end_date VARCHAR(30) DEFAULT 'Present',
    description TEXT,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Skills / tech & tool stack
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS skills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(100) NOT NULL,
    category VARCHAR(80) DEFAULT 'General',
    proficiency TINYINT UNSIGNED DEFAULT 80,
    icon_class VARCHAR(80) DEFAULT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Services + pricing packages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    price_label VARCHAR(80) DEFAULT NULL,
    icon_class VARCHAR(80) DEFAULT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Project categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS project_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Projects / portfolio items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    category_id INT UNSIGNED DEFAULT NULL,
    short_description VARCHAR(300) DEFAULT NULL,
    description TEXT,
    cover_image VARCHAR(255) DEFAULT NULL,
    external_url VARCHAR(255) DEFAULT NULL,
    github_url VARCHAR(255) DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    meta_title VARCHAR(180) DEFAULT NULL,
    meta_description VARCHAR(300) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES project_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Project tags (many-to-many)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_tags (
    project_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (project_id, tag_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Blog / Insights
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    cover_image VARCHAR(255) DEFAULT NULL,
    excerpt VARCHAR(300) DEFAULT NULL,
    body LONGTEXT,
    tags VARCHAR(255) DEFAULT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    meta_title VARCHAR(200) DEFAULT NULL,
    meta_description VARCHAR(300) DEFAULT NULL,
    published_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS blog_post_stats (
    post_id INT UNSIGNED PRIMARY KEY,
    views INT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS blog_post_likes (
    post_id INT UNSIGNED NOT NULL,
    visitor_hash CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (post_id, visitor_hash),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS blog_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_blog_comments_post (post_id, created_at),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Social media links / embeds
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS social_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(60) NOT NULL,
    handle VARCHAR(100) DEFAULT NULL,
    url VARCHAR(255) NOT NULL,
    icon_class VARCHAR(80) DEFAULT NULL,
    embed_code TEXT DEFAULT NULL,
    followers_label VARCHAR(50) DEFAULT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Featured social posts / videos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS social_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(30) NOT NULL,
    title VARCHAR(180) NOT NULL,
    url VARCHAR(500) NOT NULL,
    embed_url VARCHAR(500) DEFAULT NULL,
    thumbnail_image VARCHAR(255) DEFAULT NULL,
    thumbnail_url VARCHAR(500) DEFAULT NULL,
    description TEXT,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_social_posts_platform (platform),
    INDEX idx_social_posts_visibility (is_visible, is_featured, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Testimonials / client reviews
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS testimonials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(120) NOT NULL,
    client_role VARCHAR(150) DEFAULT NULL,
    client_photo VARCHAR(255) DEFAULT NULL,
    quote TEXT NOT NULL,
    rating TINYINT UNSIGNED DEFAULT 5,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Contact form submissions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) DEFAULT NULL,
    message TEXT NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Clients, brands & sponsors ticker
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    logo_image VARCHAR(255) NOT NULL,
    website_url VARCHAR(255) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Seed data — sensible defaults, all editable from the dashboard
-- ------------------------------------------------------------
INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_title', 'Rishikesh Rana — Manager, Creative Direction & Digital Marketing'),
('site_description', 'Manager at Dented Code Nepal. SMM creative director, freelance web builder, and digital marketing strategist.'),
('og_image', ''),
('contact_email', 'hello@example.com'),
('contact_notification_email', 'hello@example.com'),
('contact_phone', ''),
('location', 'Kathmandu, Nepal'),
('google_analytics_id', ''),
('site_favicon', '')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

INSERT INTO about_content (id, full_name, role_title, tagline, bio_text) VALUES
(1, 'Rishikesh Rana', 'Manager · Creative Director · Digital Marketer',
 'I build brands, run campaigns, and manage the teams that ship them.',
 'I lead as Manager at Dented Code Nepal, working across social media creative direction, freelance web development, and digital marketing strategy. Edit this bio from the dashboard.')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO project_categories (name, slug) VALUES
('Web Development', 'web-development'),
('Social Media Campaigns', 'social-media-campaigns'),
('Branding & Creative', 'branding-creative')
ON DUPLICATE KEY UPDATE name = name;

-- No admin user is seeded here on purpose. Passwords must be hashed by PHP's
-- password_hash() running on YOUR server (bcrypt hashes are salted differently
-- every time, so a hash generated elsewhere can't be trusted blindly).
-- After importing this schema, visit /admin/setup.php ONCE in your browser
-- to create your admin account, then delete admin/setup.php immediately.
-- See DEPLOYMENT.md for the exact steps.
