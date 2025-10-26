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

COPY docker-entrypoint-init.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint-init.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint-init.sh"]