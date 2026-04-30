# Listing Manager Pro

Listing Manager Pro is a WordPress plugin for browsing, reviewing, and managing `job_listing` posts from a clean admin-facing dashboard. It includes card-based listing views, detail modals, approve/reject actions with email templates, configurable button and card styling, and admin submission notifications.

## Features

- Display listings with the `[mlf_dashboard]` or `[mlf_listings]` shortcode.
- View listing details in a modal with grouped sections.
- Approve, reject, or delete listings from the dashboard.
- Send approval and rejection emails to the listing owner.
- Notify the admin when a new listing is submitted.
- Customize button, card, modal, container, avatar, spacing, and typography styles from the WordPress backend.
- Support Elementor-based rendering through the bundled widget and template files.

## Installation

1. Copy the `mlf-pro` folder into your WordPress plugins directory.
2. Activate the plugin from the WordPress admin Plugins screen.
3. Open the Listing Manager settings page to configure email templates and styling.

## Usage

### Shortcodes

Use either of these shortcodes on a page or post:

- `[mlf_dashboard]`
- `[mlf_listings]`

Both shortcodes render the listings dashboard and the detail modal.

### Listing Actions

- Approve sends the configured approval email and publishes the listing.
- Reject sends the configured rejection email and moves the listing back to draft.
- Delete removes the listing immediately through AJAX without reloading the whole page.

## Admin Settings

The plugin adds a `Listing Manager` menu in the WordPress admin. From there you can configure:

- Admin notification email address.
- Approval email template.
- Rejection email template.
- Primary colors and text colors.
- Button background, text color, border radius, padding, and font size.
- Card layout settings such as radius, shadow, padding, background, and border color.
- Avatar, container, modal, spacing, and animation options.
- Custom CSS for additional frontend tweaks.

The default admin email is set to `esther@myndmyself.com`.

## Email Templates

The approval and rejection email editors support HTML and can use placeholders such as:

- `{{listing_title}}`
- `{{listing_url}}`
- `{{admin_email}}`

## File Structure

- `mlf-pro.php` - Main plugin bootstrap and frontend styles.
- `includes/settings.php` - Admin settings page and registered options.
- `includes/dashboard.php` - Shortcodes, detail modal output, and AJAX actions.
- `includes/emails.php` - Submission and status-change email handling.
- `includes/elementor-widget.php` - Elementor widget registration.
- `templates/dashboard.php` - Alternative dashboard template markup.
- `assets/mlf-script.js` - Shared frontend modal and action behavior.
- `assets/style.css` - Core frontend styles.

## Notes

- The plugin works with `job_listing` posts.
- The dashboard is optimized for responsive display across desktop and mobile screens.
- Uploaded images are shown as thumbnails in the detail view when supported by the stored value.
