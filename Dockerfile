FROM node:boron-alpine AS mynodejs

RUN apk add --no-cache \
git \
g++ \
gcc \
libgcc \
libstdc++ \
linux-headers \
make \
python

RUN npm install -g gulp \
&& npm install -g bower

COPY package.json /tmp
COPY src/bower.json /tmp

WORKDIR /tmp
RUN npm install
RUN bower install -p --allow-root

COPY . /planteieu
RUN ln -s /planteieu /vagrant

WORKDIR /planteieu
RUN mv /tmp/node_modules /planteieu \
&& mv /tmp/bower_components /planteieu/src \
&& gulp dockerinstall

FROM php:5.6.35-fpm-alpine3.4

RUN apk --update add wget \
curl \
git \
php \
php5-curl \
php5-pdo \
php5-imagick \
php5-mcrypt \
php5-pgsql \
nginx \
supervisor \
&& rm /var/cache/apk/* \
&& chown -R www-data:www-data /var/lib/nginx \
&& rm /usr/local/etc/php-fpm.d/zz-docker.conf

RUN apk --update add build-base libmcrypt-dev postgresql-dev imagemagick-dev \
&& docker-php-ext-install pdo \
&& docker-php-ext-install pdo_pgsql \
&& docker-php-ext-install mcrypt \
&& cp /usr/lib/php5/modules/imagick.so /usr/local/lib/php/extensions/no-debug-non-zts-20131226/ \
&& docker-php-ext-enable imagick \
&& apk del build-base libmcrypt-dev postgresql-dev imagemagick-dev

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

COPY src/server/.env.docker /planteieu/src/server/.env
COPY provision/docker/docker-php_fpm_www.conf /usr/local/etc/php-fpm.d/www.conf
COPY provision/docker/docker-nginx.conf /etc/nginx/nginx.conf
COPY provision/docker/supervisor.conf_fpm /etc/supervisor.d/fpm.ini
COPY provision/docker/supervisor.conf_nginx /etc/supervisor.d/nginx.ini

WORKDIR /planteieu/src/server
COPY src/server/composer.json /planteieu/src/server/composer.json
RUN composer install --no-dev --no-autoloader --no-scripts

COPY --from=mynodejs /planteieu /planteieu
RUN composer install --no-dev
RUN php artisan lang:js -c public/js/messages.js \
&& php artisan view:clear \
&& php artisan optimize \
&& chown www-data:www-data -R /planteieu/src/server/storage \
&& chown www-data:www-data -R /planteieu/src/server/bootstrap/cache

ARG DATABASE_URL=empty
ENV DATABASE_URL ${DATABASE_URL}
RUN export `echo $DATABASE_URL | awk -F'[:/@]' '{print "DB_HOST=" $6 "\nDB_DATABASE=" $8 "\nDB_USERNAME=" $4 "\nDB_PASSWORD=" $5 }'` \
&& sed -i "s:DB_HOST=.*:DB_HOST=$DB_HOST:" .env \
&& sed -i "s:DB_USERNAME=.*:DB_USERNAME=$DB_USERNAME:" .env \
&& sed -i "s:DB_PASSWORD=.*:DB_PASSWORD=$DB_PASSWORD:" .env \
&& sed -i "s:DB_DATABASE=.*:DB_DATABASE=$DB_DATABASE:" .env

CMD supervisord -n
