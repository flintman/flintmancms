# FlintmanCMS

A lightweight, PHP-based Content Management System (CMS) with Smarty templating, designed for easy customization and extensibility.

## Features
- Admin and user management
- Page, plugin, and group management
- Smarty template support for easy theming
- Responsive design (mobile and desktop)
- MySQL database backend
- Dockerized for easy setup
- GPL v2 Licensed

---

## Quick Start (with Docker)

### Prerequisites
- [Docker](https://docs.docker.com/get-docker/) (version 20+ recommended)
- [Docker Compose](https://docs.docker.com/compose/install/) (if not included with Docker)

### 1. Clone the Repository
```bash
git clone https://github.com/flintman/flintmancms.git
cd flintmancms
```

### 2. Build and Start the Containers
```bash
docker-compose up --build
```

- This will build the PHP/Apache and MySQL containers, initialize the database, and start the CMS.

> **First startup note:** MySQL can take **60–120 seconds** to initialize on the very first run while it sets up the data directory and imports the schema. The web container will wait for MySQL to be healthy before starting. Subsequent restarts are much faster. If you see the web container fail on first boot, just run `docker-compose up -d` again once MySQL is ready.

**Note:** You may need to change the permissions of the `html` folder so the web server can write to it:
```bash
sudo chown -R www-data:www-data html
```
This is especially important for folders like `templates_c/` that require write access.

### 3. Access the CMS
- Open your browser and go to: [http://localhost:3000](http://localhost:3000)
- Default admin login: (username:admin   password:admin)  **CHANGE RIGHT WAY**

### 4. Stopping the Containers
```bash
docker-compose down
```

---

## Manual Installation (Non-Docker)
1. Install PHP 7.4+ and MySQL 5.7+ (or MariaDB)
2. Copy the project files to your web root
3. Import the database schema from `db_init/schema.sql`
4. Configure database credentials in `html/includes/authentication.php` or your config file
5. Set file permissions for `templates_c/` and `uploads/` (if used)
6. Access via your web server (Apache/Nginx)

---

## File Structure
- `html/` - Main PHP source code
- `html/templates/` - Smarty templates
- `html/includes/` - Core PHP includes (DB, auth, menu, etc.)
- `db_init/` - Database schema and initialization scripts
- `docker-compose.yml` - Docker Compose configuration
- `Dockerfile` - PHP/Apache Docker build
- `docs/` - Documentation and license

---

## Updating
- Pull the latest code:
  ```bash
  git pull origin main
  docker-compose build --no-cache
  docker-compose up -d
  ```
- Review `docs/updates` for version changes.

---

## License
This project is licensed under the GNU General Public License v2.0. See `html/docs/gpl-2.0.txt` for details.

---

## Support & Contributions
- Issues: [GitHub Issues](https://github.com/flintman/flintmancms/issues)
- Pull requests welcome! Please follow the code style and include clear commit messages.
- For questions, contact the maintainer or open an issue.

---

## Credits
- Flintman Computers (original author)
- Smarty Template Engine
- All contributors and open source libraries used

---

## Security
- Change default passwords after install
- Keep Docker, PHP, and MySQL up to date
- Review and restrict file permissions as needed

---

## Troubleshooting
- If you see database errors, check the MySQL container logs: `docker-compose logs db`
- For permission issues, ensure `templates_c/` is writable by the web server
- For more help, see the `docs/` folder or open an issue

---

Enjoy using FlintmanCMS!
