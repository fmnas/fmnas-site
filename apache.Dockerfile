# Copyright 2022 Google LLC
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.


# Dockerfile for Docker Compose tests. Not required for local development or deployment.
ARG site_root

FROM node:lts-slim AS node_public
WORKDIR /fmnas

COPY package.json package-lock.json ./
RUN npm install

COPY tsconfig.json handleparse.ts ./
COPY secrets/config.php.hbs ./secrets/

ARG db_name=fmnas
ARG db_username=fmnas
ARG db_host=mysql.fmnas
ARG db_pass=passw0rd
ARG asm_db=asm
ARG asm_host=mysql.fmnas
ARG asm_user=asmuser
ARG asm_pass=passw0rd2
ARG ga_id="TODO"
ARG image_size_endpoint="http://image-size.fmnas:8080"
ARG resize_image_endpoint="http://resize-image.fmnas:8080"
ARG api_credentials=""
RUN npx ts-node handleparse.ts secrets/config.php.hbs \
    --db_name="$db_name" \
    --db_username="$db_username" \
    --db_pass="$db_pass" \
    --db_host="$db_host" \
    --image_size_endpoint="$image_size_endpoint" \
    --resize_image_endpoint="$resize_image_endpoint" \
    --asm_db="$asm_db" \
    --asm_host="$asm_host" \
    --asm_user="$asm_user" \
    --asm_pass="$asm_pass" \
    --ga_id="$ga_id" \
    --api_credentials="$api_credentials"

COPY public/ public/
RUN npx sass --style=compressed public:public
RUN npm run build

FROM node_public AS node_admin
COPY admin/ admin/
RUN npx vite build admin/client

FROM node_${site_root} AS node_final
RUN rm -rf node_modules package.json package-lock.json tsconfig.json secrets/config.php.hbs handleparse.ts

FROM composer:latest AS composer
WORKDIR /fmnas
COPY --from=node_final /fmnas /fmnas

COPY composer.json composer.lock ./
RUN composer install --no-dev --ignore-platform-reqs && \
    rm -rf composer.json composer.lock

FROM php:8.1-apache AS server
WORKDIR /fmnas
EXPOSE 80
EXPOSE 443

RUN curl -sSLf \
        -o /usr/local/bin/install-php-extensions \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions fileinfo imagick curl mbstring mysqli gd dom ctype sqlite3 && \
    a2enmod rewrite

ARG site_root
RUN sed -ri -e "s!/var/www/html!/fmnas/$site_root!g" /etc/apache2/sites-available/*.conf && \
    sed -ri -e "s!/var/www/!/fmnas/$site_root!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

ARG domain
RUN echo "ServerName $domain" >> /etc/apache2/apache2.conf

COPY --from=composer /fmnas /fmnas
COPY src/ src/
RUN rm src/generated.php || exit 0

# TODO [#389]: Reasonable php.ini values
