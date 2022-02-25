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

FROM node:lts-slim AS setup

COPY package.json package-lock.json ./
RUN npm install

COPY tsconfig.json handleparse.ts config.sql.hbs tests/setup.sql.hbs schema.sql tests/asm.sql ./
ARG address="49&nbsp;W&nbsp;Curlew&nbsp;Lake&nbsp;Rd\nRepublic&nbsp;WA&nbsp;99166‑8742"
ARG admin_domain="admin.fmnas"
ARG default_email_user="adopt"
ARG fax="208-410-8200"
ARG longname="Forget Me Not Animal Shelter of Ferry County"
ARG phone="(509)&nbsp;775-2308"
ARG phone_intl="+15097752308"
ARG public_domain="public.fmnas"
ARG shortname="Forget Me Not Animal Shelter"
ARG transport_date="2022-02-22"
ARG db_name=fmnas
ARG db_username=fmnas
ARG db_pass=passw0rd
ARG asm_db=asm
ARG asm_user=asmuser
ARG asm_pass=passw0rd2
RUN npx ts-node handleparse.ts config.sql.hbs \
    --address="$address" \
    --admin_domain="$admin_domain" \
    --default_email_user="$default_email_user" \
    --fax="$fax" \
    --longname="$longname" \
    --phone="$phone" \
    --phone_intl="$phone_intl" \
    --public_domain="$public_domain" \
    --shortname="$shortname" \
    --transport_date="$transport_date" && \
		npx ts-node handleparse.ts setup.sql.hbs \
    --db_name="$db_name" \
    --asm_db="$asm_db" \
    --db_username="$db_username" \
    --db_pass="$db_pass" \
    --asm_user="$asm_user" \
    --asm_pass="$asm_pass" && \
    echo "USE $db_name" >> setup.sql && \
    cat schema.sql config.sql >> setup.sql && \
    echo "USE $asm_db" >> setup.sql && \
    cat asm.sql >> setup.sql && \
    rm -rf node_modules *.json *.hbs schema.sql config.sql asm.sql handleparse.ts

FROM mysql:latest
EXPOSE 3306
COPY --from=setup setup.sql /docker-entrypoint-initdb.d/
