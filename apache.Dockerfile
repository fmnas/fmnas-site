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

FROM node:lts AS node
WORKDIR /fmnas

ARG db_name
ARG db_username
ARG db_host
ARG smtp_host
ARG smtp_auth
ARG smtp_security
ARG smtp_port
ARG smtp_username
ARG asm_db
ARG asm_host
ARG asm_user
ARG asm_pass
ARG ga_id
ARG image_size_endpoint
ARG resize_image_endpoint
ARG print_pdf_endpoint
ARG minify_html_endpoint

RUN --mount=type=secret,id=db_pass export db_pass=$(cat /run/secrets/db_pass)
RUN --mount=type=secret,id=smtp_pass export smtp_pass=$(cat /run/secrets/smtp_pass)
RUN --mount=type=secret,id=asm_pass export asm_pass=$(cat /run/secrets/asm_pass)
RUN --mount=type=secret,id=api_credentials export api_credentials=$(cat /run/secrets/api_credentials)

COPY package.json package-lock.json tsconfig.json handleparse.ts public admin src ./
COPY secrets/config.php.hbs ./secrets
RUN npm install

RUN npx ts-node handleparse.ts \
    --db_name="$db_name" \
    --db_username="$db_username" \
    --db_pass="$db_pass" \
    --db_host="$db_host" \
    --smtp_host="$smtp_host" \
    --smtp_auth="$smtp_auth" \
    --smtp_security="$smtp_security" \
    --smtp_port="$smtp_port" \
    --smtp_username="$smtp_username" \
    --smtp_password="$smtp_password" \
    --image_size_endpoint="$image_size_endpoint" \
    --resize_image_endpoint="$resize_image_endpoint" \
    --print_pdf_endpoint="$print_pdf_endpoint" \
    --minify_html_endpoint="$minify_html_endpoint" \
    --asm_db="$asm_db" \
    --asm_host="$asm_host" \
    --asm_user="$asm_user" \
    --asm_pass="$asm_pass" \
    --ga_id="$ga_id" \
    --api_credentials="$api_credentials"

RUN npx sass --style=compressed public:public
RUN npm run build
RUN npx vite build admin/client

RUN rm -rf node_modules package.json package-lock.json tsconfig.json

FROM composer:latest AS composer
WORKDIR /fmnas

COPY --from=node /fmnas /fmnas
COPY composer.json composer.lock ./
RUN composer install --no-dev --ignore-platform-reqs
RUN rm -rf composer.json composer.lock

FROM php:8.1-apache AS server
WORKDIR /fmnas
ARG site_root

RUN curl -sSLf \
        -o /usr/local/bin/install-php-extensions \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions fileinfo imagick curl mbstring mysqli gd dom ctype sqlite3

COPY --from=composer /fmnas /fmnas

ENV APACHE_DOCUMENT_ROOT /fmnas/$site_root
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# TODO: Reasonable php.ini values
