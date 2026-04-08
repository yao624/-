FROM jolla20i8/laravel-base:8.3-1

LABEL maintainer="Taylor Otwell"

#COPY ./composer.json ./composer.json
COPY . .
RUN composer install

COPY ./docker/prod/start-container /usr/local/bin/start-container
COPY ./docker/prod/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./docker/prod/php.ini /etc/php/8.3/cli/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container

EXPOSE 8000

ENTRYPOINT ["start-container"]
