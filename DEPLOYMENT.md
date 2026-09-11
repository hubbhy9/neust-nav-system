# Deployment notes

This project is a PHP application and cannot be deployed to GitHub Pages directly.

Use a PHP-capable host for deployment, such as:

- Render
- Railway
- DigitalOcean App Platform
- Heroku
- cPanel hosting
- a VPS with PHP + MySQL

## Recommended flow

1. Upload the repository to GitHub.
2. Connect the GitHub repo to a PHP hosting provider.
3. Set environment/database variables in the hosting dashboard.
4. Run Composer install if required by the host.
5. Import the SQL schema to the live MySQL database.
6. Update the DB credentials and app config to match the production environment.

## Important

- `vendor/` is usually not committed in Git.
- Make sure your production host has PHP, Apache/Nginx, and MySQL support.
- Do not use GitHub Pages for this project unless it is rewritten as a static frontend.
