# Troubleshooting Guide

Common issues and their solutions for FlintmanCMS.

---

## 🔧 Common Issues

### Cannot Log In

**Symptoms:** Login page appears but credentials don't work

**Solutions:**
1. **Verify credentials** - Check username and password
2. **Clear browser cache** - Old session data may interfere
3. **Check database connection** - Verify database is running:
   ```bash
   docker-compose ps
   ```

---

## 📄 Page Issues

### Page Not Displaying

**Symptoms:** Blank page or "Page not found"

**Checklist:**
- ✅ Page is **activated** in Pages Admin
- ✅ Page has **content** saved
- ✅ Menu link is configured correctly
- ✅ Check browser console for errors (F12)

**Database check:**
```bash
# Access database
docker-compose exec db mysql -u flintmancms -p flintmancms

# Check pages table
SELECT page_id, title, active FROM pages;
```

### WYSIWYG Editor Not Loading

**Symptoms:** Plain textarea instead of rich editor

**Solutions:**
1. **Check TinyMCE files:**
   ```bash
   ls -la html/includes/tiny_mce/
   ```
2. **Browser console errors** - Press F12, check Console tab
3. **Content Security Policy** - May be blocking scripts
   - Check `html/common.php` CSP header
   - Ensure `'unsafe-inline'` and `'unsafe-eval'` are present

---

## 🔌 Plugin Problems

### Plugin Not Appearing

**Symptoms:** Plugin installed but not visible

**Checklist:**
- ✅ Plugin files in `html/plugins/[plugin-name]/`
- ✅ Plugin has `plugin.php` file
- ✅ Plugin is **activated** in Plugins Admin
- ✅ Menu link created (if needed)

### Plugin Errors

**Common causes:**
1. **Missing dependencies** - Check plugin requirements
2. **File permissions** - Ensure files are readable:
   ```bash
   chmod 644 html/plugins/*/plugin.php
   ```
3. **PHP errors** - Check error logs:
   ```bash
   docker-compose logs apache-php
   ```

---

## 🗄️ Database Issues

### "Database Connection Failed"

**Symptoms:** Cannot connect to database

**Solutions:**

1. **Check if database is running:**
   ```bash
   docker-compose ps db
   ```

2. **Verify environment variables:**
   ```bash
   cat .env
   # Check: DB_NAME, DB_USER, DB_PASS, DB_HOST
   ```

3. **Test database connection:**
   ```bash
   docker-compose exec db mysql -u flintmancms -p
   # Use password from .env MYSQL_PASSWORD
   ```

4. **Restart database:**
   ```bash
   docker-compose restart db
   ```

5. **Check logs:**
   ```bash
   docker-compose logs db
   ```

### Slow Database Performance

**Symptoms:** Pages load slowly, admin panel sluggish

**Quick fixes:**
1. **Check database size:**
   ```bash
   docker-compose exec db mysql -u flintmancms -p -e "
   SELECT
     table_schema AS 'Database',
     SUM(data_length + index_length) / 1024 / 1024 AS 'Size (MB)'
   FROM information_schema.tables
   GROUP BY table_schema;"
   ```

2. **Clear old logs** - Delete old entries from logs tables

3. **Optimize tables:**
   ```bash
   docker-compose exec db mysql -u flintmancms -p flintmancms -e "OPTIMIZE TABLE pages, users, logs;"
   ```

---

## 🐳 Docker Issues

### Containers Won't Start

**Symptoms:** `docker-compose up` fails

**Solutions:**

1. **Check Docker is running:**
   ```bash
   docker ps
   ```

2. **Port conflicts** - Port 3000 or 8081 already in use:
   ```bash
   # Find what's using the port
   sudo lsof -i :3000

   # Change port in docker-compose.yml
   ports:
     - "3001:80"  # Use different port
   ```

3. **Rebuild containers:**
   ```bash
   docker-compose down
   docker-compose build --no-cache
   docker-compose up -d
   ```

4. **Check logs:**
   ```bash
   docker-compose logs
   ```

### Volume Permission Issues

**Symptoms:** "Permission denied" errors in logs

**Solutions:**
```bash
# Fix ownership
sudo chown -R $USER:$USER html/
sudo chown -R $USER:$USER mysql/

# Set permissions
chmod -R 755 html/
chmod 755 mysql/
chmod 600 .env
```

---

## 📧 Email Not Working

### Emails Not Sending

**Symptoms:** No emails received from CMS

**Checklist:**
1. ✅ Email configured in Configure → Email
2. ✅ SMTP credentials correct
3. ✅ Port not blocked by firewall
4. ✅ From address valid

**Test email manually:**
- Go to Configure → Email
- Send test email
- Check spam folder
- Review logs for errors

---

## 🎨 Template/Theme Issues

### Theme Not Applying

**Symptoms:** Site looks broken or unstyled

**Solutions:**

1. **Verify theme files:**
   ```bash
   ls -la html/templates/aurora/
   ```

2. **Check template setting:**
   - Go to Configure → Site Info
   - Verify correct template selected

3. **Clear compiled templates:**
   ```bash
   rm -rf html/templates_c/*
   ```

4. **Check file permissions:**
   ```bash
   chmod 755 html/templates_c/
   ```

### CSS/JS Not Loading

**Symptoms:** Page loads but styling broken

**Browser checks:**
1. Open Developer Tools (F12)
2. Check Network tab for 404 errors
3. Verify file paths are correct
4. Check Content-Security-Policy isn't blocking resources

---

## 🔒 Security Header Issues

### Content Security Policy Blocking Resources

**Symptoms:** Features not working, console errors about CSP

**Solution:** Edit `html/common.php` and adjust CSP:

```php
// Find this line and modify as needed:
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; ...");
```

**Common adjustments:**
- Allow external images: `img-src 'self' data: https:;`
- Allow external fonts: `font-src 'self' https://fonts.googleapis.com;`
- Allow external scripts: `script-src 'self' https://cdn.example.com;`

---

## 🚨 Emergency Recovery

### Complete Site Failure

**Nuclear option - restore from backup:**

1. **Stop containers:**
   ```bash
   docker-compose down
   ```

2. **Backup current state:**
   ```bash
   cp -r mysql/ mysql-backup-$(date +%Y%m%d)/
   ```

3. **Restore database:**
   ```bash
   # Remove corrupted database
   rm -rf mysql/*

   # Restart to reinitialize
   docker-compose up -d db

   # Restore from backup
   docker-compose exec -T db mysql -u root -p < backup.sql
   ```

4. **Test:**
   ```bash
   docker-compose up -d
   curl -I http://localhost:3000
   ```

---

## 📞 Getting Help

### Before Asking for Help

Gather this information:
1. **Error messages** - Exact text of errors
2. **Docker logs** - `docker-compose logs`
3. **PHP errors** - `docker-compose logs apache-php`
4. **Database logs** - `docker-compose logs db`
5. **Browser console** - F12 → Console tab
6. **What you changed** - Recent configuration changes

### Diagnostic Commands

Run these and share output:
```bash
# System status
docker-compose ps

# Recent logs
docker-compose logs --tail=50

# Environment check
cat .env | grep -v PASSWORD

# File permissions
ls -la html/ | head -20

# Database connectivity
docker-compose exec db mysql -u flintmancms -p -e "SELECT 'Connection OK';"
```

---

## 🛠️ Useful Commands

### Docker Management
```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# Restart specific service
docker-compose restart apache-php

# View logs (live)
docker-compose logs -f

# Rebuild after code changes
docker-compose build --no-cache apache-php
docker-compose up -d

# Access container shell
docker-compose exec apache-php bash
```

### Database Access
```bash
# MySQL shell
docker-compose exec db mysql -u flintmancms -p flintmancms

# Backup database
docker-compose exec db mysqldump -u flintmancms -p flintmancms > backup.sql

# Restore database
docker-compose exec -T db mysql -u flintmancms -p flintmancms < backup.sql
```

### File Operations
```bash
# Clear logs
rm -f html/logs/*.log

# Clear compiled templates
rm -rf html/templates_c/*

# Fix permissions
chmod 755 html/templates_c/
chmod 755 html/logs/
chmod 600 .env
```

---

**Still stuck? Check the `docs/` folder for detailed documentation!** 📚
