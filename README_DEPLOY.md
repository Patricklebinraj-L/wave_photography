# Wave Photography – PHP + MySQL deployment

## Deploy to InfinityFree
1. Create/confirm the MySQL database in the InfinityFree control panel.
2. Upload the contents of this folder into `htdocs` (keep `assets/`, `css/`, `js/`, `includes/`, `database/`, and `api/` folders).
3. Open `config.php` and verify the database host, port, database, username and password. The supplied connection details are prefilled.
4. Visit the site. The first request connects to MySQL, creates `schema_migrations`, applies pending numbered migrations and seeds initial services/photos/testimonials/settings.
5. Later requests check the migration ledger and run only unapplied migrations. Existing records are not overwritten by the initial seed.

## Important
- Create the database first in the hosting panel; PHP cannot create an InfinityFree database itself. It creates the application tables inside the selected database.
- PHP extensions required: PDO and PDO MySQL.
- Never delete `schema_migrations` on a live site. Add future changes as a new `database/migrations/NNN_description.php` file returning a function that accepts `PDO`.
- Back up the database before deploying schema changes. MySQL DDL may auto-commit, so keep migrations small and safe.
- `config.php` is denied by `.htaccess`; keep it out of public repositories and rotate the database password if it has been shared.
- Bookings are saved in the `bookings` table and then WhatsApp opens with the enquiry details. No payment gateway or admin dashboard is included in this conversion.
- Existing placeholder testimonials and SVG sample artwork remain clearly marked as samples. Replace them with real customer content before publishing.

## Local testing
Use PHP 8.1+ with PDO MySQL enabled and serve this directory through Apache or `php -S localhost:8000`.


## Important update: dynamic sections and image rendering

This build fixes a JavaScript rendering defect: the previous script called a non-existent
jQuery method `.php()` instead of `.html()`. That caused the first dynamic render to throw
an error and stopped the services, featured portfolio, category gallery, and related
sections from appearing. The corrected script uses `.html()` and includes graceful empty
states when the database has no active content.

A new migration `003_reconcile_seed_content.php` fills missing starter service, portfolio,
testimonial, and settings records if an earlier deployment left the seed data incomplete.
It checks for existing records before inserting, so it does not duplicate the starter data.
The normal migration runner applies it automatically on the next request.

After uploading this build, visit the homepage and Services page, then hard-refresh the
browser (Ctrl+F5). If an old JavaScript file is cached, clear the browser/site cache.
All image paths in the seed data are relative to the site root and the original `assets/`
directory is included in this package. Keep the `assets/` folder structure unchanged.


## Official logo integration

The supplied Wave Photography PNG is now the primary brand asset:
- Header navigation: full logo on a white rounded panel for legibility over the dark teal header.
- Footer: the same full logo on a white panel.
- Loading screen: the same full logo on a white panel.
- Browser favicon: a compact crop of the supplied wave emblem.
- Social preview metadata: points to the supplied full logo.

The original logo has a dark "Photography" wordmark. It is intentionally displayed on
light backgrounds rather than recoloured, preserving the artwork and ensuring contrast.
The primary image is stored at `assets/logo/wave-photography-primary.png`.


## Integrated Admin Portal (migration 004)

### Access
- Open `/admin` or `/admin/index.php`.
- Initial username: `alex`
- Initial password: `alex@54321`
- Change the initial password immediately under **Admin account** after the first login.
- The initial password is stored only as a password hash in the migration seed; authentication uses PHP sessions and `password_verify()`.

### Included management areas
- Dashboard: live counts from MySQL and recent admin activity.
- Sections: create, edit, publish/unpublish, archive and change category display order. A new section is also synchronised to the public services list.
- Services: create, edit, hide, change service display order, descriptions, inclusions and cover imagery.
- Gallery: multi-image upload, category selection, image replacement, captions, alt text, featured/cover flags, display order and removal. New images can be uploaded in batches.
- Website content: editable homepage/about/service/portfolio/contact copy plus hero and about images.
- Theme: global colours, typography, container width, spacing, border radius, live colour preview and reset-to-default.
- Navigation: add, edit, reorder, show/hide and delete public navigation links.
- Settings: contact details, social links, SEO values, site visibility/maintenance flags, logo and favicon uploads.
- Testimonials: create, edit, publish/hide, rating and customer image.
- Bookings: view enquiries and update enquiry status.
- Admin account: change password.
- Activity log: records administrative actions.

### Database behaviour
Migration `004_admin_portal.php` is automatically picked up by the existing versioned migration runner. It creates the admin/CMS tables, adds gallery metadata columns only when missing, seeds the first administrator using a password hash, and adds default content/theme/navigation values without dropping existing tables or records.

### Uploads
- Images are stored under `uploads/media/` with random server-generated filenames.
- Only JPEG, PNG and WebP MIME types are accepted.
- Maximum application-level size is 8 MB per image.
- Relative file paths are stored in MySQL.
- The uploads directory denies PHP/PHTML/PHAR execution. When PHP GD/WebP support is available, the portal also generates smaller WebP thumbnails; otherwise it safely uses the original image.
- Ensure `uploads/` is writable by PHP on the hosting account.

### Deployment
Upload the complete contents of this `wave-php` folder into the existing InfinityFree `htdocs` folder. Keep the existing database and all existing assets. On the first request, migration 004 runs automatically. Then visit `/admin`, sign in, and change the initial password.

### Production verification still required
The source package has been syntax-checked locally. A live connection to the InfinityFree MySQL database, file-upload permission test, and browser-level admin workflow test must be completed on the hosting account after deployment.


## Dynamic theme settings

Theme values saved in Admin > Theme are loaded by `data.php` from `theme_settings` on every public page request. The endpoint sends no-cache headers, and public stylesheet URLs use the CSS file modification time to avoid stale cached styles. Semantic theme variables are bridged to the legacy palette variables used by the original stylesheet. The page background, section/card surfaces, header/footer, headings/body text, navigation, buttons, font family, container width, section spacing and border radius are all driven by the saved settings. No additional database migration is required for this theme propagation update.
