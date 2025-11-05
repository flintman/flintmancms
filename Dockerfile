FROM php:8.2-apache

# Install system dependencies for GD extension
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

# Install php-mysqli and set upload limits
RUN docker-php-ext-install mysqli \
	&& echo "upload_max_filesize=25M" > /usr/local/etc/php/conf.d/uploads.ini \
	&& echo "post_max_size=25M" >> /usr/local/etc/php/conf.d/uploads.ini

# Configure session security settings
RUN echo "session.cookie_httponly=1" > /usr/local/etc/php/conf.d/session-security.ini \
	&& echo "session.cookie_samesite=Strict" >> /usr/local/etc/php/conf.d/session-security.ini \
	&& echo "session.use_strict_mode=1" >> /usr/local/etc/php/conf.d/session-security.ini \
    && echo "session.cookie_secure=${ENABLE_HSTS:-0}" >> /usr/local/etc/php/conf.d/session-security.ini

# Note: Set ENABLE_HSTS=1 environment variable when using HTTPS in production

# Enable Apache modules for security headers and performance
RUN a2enmod headers rewrite expires deflate

# Configure Apache security settings
RUN echo "ServerTokens Prod" >> /etc/apache2/conf-available/security.conf \
    && echo "ServerSignature Off" >> /etc/apache2/conf-available/security.conf \
    && echo "TraceEnable Off" >> /etc/apache2/conf-available/security.conf

COPY docker-entrypoint-init.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint-init.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint-init.sh"]