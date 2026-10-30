# NEUST Smart Campus

A PHP-based campus navigation and wayfinding dashboard for NEUST.

## Project structure

- `index.php` – main landing entry
- `index.php` – login screen / main entry
- `dashboard.php` – main dashboard interface
- `map.php` – campus map view
- `functions/` – PHP action handlers
- `config/` – configuration files
- `db/` – database schema files
- `libraries/` – shared PHP libraries
- `vendor/` – Composer dependencies (usually ignored in Git)

## Local setup

1. Install XAMPP or similar PHP + MySQL environment.
2. Place this project in your local web root, such as `htdocs/cap`.
3. Start Apache and MySQL.
4. Import the SQL schema in `db/` into your MySQL database.
5. Update your database config in `config/config.php` or the relevant DB connection file.
6. Open the project in a browser, for example:
   - `http://localhost/cap/index.php`

## GitHub repo setup

To initialize this project as a Git repository:

```bash
git init
git add .
git commit -m "Initial commit"
```

Then create a repository on GitHub and push:

```bash
git branch -M main
git remote add origin <your-github-repo-url>
git push -u origin main
```

## Notes

This project is not suitable for GitHub Pages because it is a PHP/MySQL application. It should be deployed on a PHP-capable host instead.
