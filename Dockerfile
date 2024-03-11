FROM tmselte/php:7.4

# Install cron
RUN apt-get update -y
RUN apt-get install -y cron

# Set up a production configuration for PHP
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    sed "s/^upload_max_filesize.*/upload_max_filesize = 10M/g" && \
    sed "s/^post_max_size.*/post_max_size = 12M/g"

# Copy project
WORKDIR /var/www/html/backend-core
COPY . .

# Install Composer dependencies
RUN composer install --prefer-dist --no-ansi --no-interaction

# Schedule background jobs with cron
RUN echo "* * * * * php /var/www/html/backend-core/yii schedule/run --scheduleFile=@app/config/schedule.php" > /etc/cron.d/tms-cron
RUN crontab /etc/cron.d/tms-cron

# Set up default entrypoint and command
# - start cron daemon
# - apply DB migrations
# - start Apache2 webserver
ENTRYPOINT ["/bin/bash", "-c"]
CMD ["cron && ./yii migrate --interactive=0 && apache2-foreground"]

# Expose port 80
EXPOSE 80
