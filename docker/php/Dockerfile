FROM php:8-cli

RUN apt-get update && apt-get install -y curl git zip \
  && rm -rf /var/lib/apt/lists/*

#RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer



COPY --from=composer /usr/bin/composer /usr/bin/composer

#RUN composer require simplehtmldom/simplehtmldom