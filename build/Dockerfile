FROM enjoys/php:8.0

RUN install-php-extensions bcmath intl gd xdebug

# https://wiki.alpinelinux.org/wiki/Setting_the_timezone
ARG TZ='UTC'
RUN echo "${TZ}" && apk --update add tzdata && \
    cp /usr/share/zoneinfo/$TZ /etc/localtime && \
    echo $TZ > /etc/timezone && \
    apk del tzdata

# Copy xdebug configuration for remote debugging
RUN echo "xdebug.mode = coverage, debug, develop" >> /usr/local/etc/php/conf.d/xdebug.ini

RUN adduser --system appuser --uid 1000

USER appuser
