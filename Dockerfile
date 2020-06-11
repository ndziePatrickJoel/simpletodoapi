FROM diceprime/php72:base

WORKDIR /var/www/html

COPY . .

COPY parameters/.htaccess public/.htaccess

RUN rm index.html

RUN rm composer.lock

RUN composer install

RUN APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear

RUN APP_ENV=prod APP_DEBUG=0 php bin/console assets:install

RUN rm -f .env*

COPY .emptyenv .env.prod

COPY .emptyenv .env

RUN chmod -R 777 var/log

RUN mkdir -p /var/lock/apache2 /var/run/apache2 /var/run/sshd /var/log/supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

CMD ["/usr/bin/supervisord"]