# Security Best Practices

Keeping your FlintmanCMS installation secure is crucial. Follow these guidelines to protect your site.

---

## 🔐 Account Security

### Strong Passwords
- Use passwords with **at least 16 characters**
- Include uppercase, lowercase, numbers, and symbols
- Never reuse passwords from other sites
- Generate passwords with: `openssl rand -base64 32`

### User Management
- Remove inactive admin accounts
- Use unique accounts for each administrator
- Regularly audit user access
- Implement least privilege (only grant necessary permissions)

---

## 🔒 Environment Variables

**Never commit sensitive data to version control!**

### Protected Files
These files should NEVER be in git:
- `.env` - Database credentials
- `html/admin/config.php` - If it contains credentials
- `mysql/` - Database files

### Credential Management
- Use strong, unique passwords
- Rotate credentials regularly
- Use environment variables (`.env` file)
- Set proper file permissions: `chmod 600 .env`

---

## 🌐 Security Headers

FlintmanCMS implements comprehensive security headers:

### Enabled Protections
- **X-Frame-Options** - Prevents clickjacking
- **X-Content-Type-Options** - Prevents MIME sniffing
- **Content-Security-Policy** - Controls resource loading
- **Referrer-Policy** - Controls referrer information
- **X-XSS-Protection** - Legacy XSS protection

### HSTS (HTTPS Only)
When using SSL/TLS, enable HSTS in `.env`:
```bash
ENABLE_HSTS=true
```

> **Warning:** Only enable HSTS when you have a valid SSL certificate!

---

## 📝 Input Validation

All user input is automatically validated and sanitized.

### Validation Types
- **Email** - Validates email format
- **Int** - Integers only
- **Float** - Decimal numbers
- **Alpha** - Letters only
- **Alphanum** - Letters and numbers
- **URL** - Valid URLs
- **Max Length** - Character limits

### SQL Injection Prevention
- All database queries use **prepared statements**
- User input is **never** directly in SQL
- Automatic **escaping** of special characters

---

## 🚨 Monitoring & Logs

**Files to monitor:**
- `system logs` - General security events

### What to Watch For
- 🔴 Multiple failed login attempts
- 🔴 Unusual access patterns
- 🔴 SQL injection attempts in logs
- 🔴 XSS attack attempts
- 🔴 Access to sensitive files

### Regular Tasks
- ✅ Review logs **weekly**
- ✅ Check for outdated accounts
- ✅ Rotate credentials **quarterly**
- ✅ Backup database **daily**

---

## 🔧 Server Security

### File Permissions
Secure your installation with proper permissions:

```bash
# Application files (read-only)
chmod 644 html/**/*.php

# Directories
chmod 755 html/*/

# Sensitive files (owner only)
chmod 600 .env
chmod 600 html/admin/config.php

# Writable directories
chmod 755 html/logs/
chmod 755 html/templates_c/
chmod 755 mysql/
```

### Protected Directories
These directories are protected via `.htaccess`:
- `includes/` - Core PHP files
- `db_init/` - Database initialization
- `templates_c/` - Compiled templates

### Sensitive File Access
Blocked file types:
- `.env` - Environment variables
- `.ini` - Configuration files
- `.log` - Log files
- `.sql` - Database dumps
- `.conf` - Server configuration

---

## 🌍 Production Deployment

### Pre-Deployment Checklist
1. ✅ Strong passwords in `.env`
2. ✅ `.env` not in version control
3. ✅ SSL/TLS certificate installed
4. ✅ HSTS enabled (`ENABLE_HSTS=true`)
5. ✅ File permissions set correctly
6. ✅ Rate limiting configured
7. ✅ Trusted IPs whitelisted
8. ✅ Automated backups configured
9. ✅ Monitoring/alerting set up
10. ✅ Security headers tested

### Testing Security
Use these tools to verify security:
- **SecurityHeaders.com** - Header analysis
- **Mozilla Observatory** - Security score
- **SSL Labs** - SSL/TLS configuration
- **OWASP ZAP** - Vulnerability scanning

---

## 🆘 Incident Response

### If Site is Compromised
1. **Immediately** take site offline
2. Change all passwords
3. Review logs for attack vectors
4. Restore from clean backup
5. Patch vulnerabilities
6. Bring site back online
7. Monitor closely for 48 hours

### Getting Help
- Check security logs for details
- Consult cybersecurity professionals for serious breaches

---

## 📚 Security Resources

### External Resources
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [Web Security Academy](https://portswigger.net/web-security)

---

**Stay secure!** 🔒
