FROM golang:1.17-bullseye as builder
WORKDIR /app

RUN apt update && \
    apt --no-install-recommends -y install wget build-essential pkg-config libjpeg-dev libpng-dev libtiff-dev \
    libgif-dev libraw-dev libwebp-dev libheif-dev libde265-dev autotools-dev automake libtool && \
    wget https://www.imagemagick.org/download/ImageMagick.tar.gz && \
    git clone https://github.com/strukturag/libde265.git && cd libde265 && \
    ./autogen.sh && ./configure --disable-dec265 --disable-sherlock265 && make -j4 && make install && cd .. && \
    tar xvf ImageMagick.tar.gz && cd ImageMagick* && \
    ./configure --without-magick-plus-plus --without-perl --disable-openmp --with-gvc=no --with-heic \
    --disable-docs && make -j$(nproc) && make install && ldconfig /usr/local/lib && cd .. && \
    apt clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp* ImageMagick* libde265 && identify --version

COPY go.* ./
RUN go mod download

COPY . ./
RUN go build -v -o server
# TODO [$620b4dadfae1ab000cc55331]: Statically link C libs into go binary so we don't have to install them again below.

FROM debian:bullseye-slim
RUN set -x && apt update && \
    apt --no-install-recommends -y install wget build-essential libjpeg-dev libpng-dev libtiff-dev libgif-dev \
    libraw-dev libwebp-dev libheif-dev libde265-dev  \
    ca-certificates autotools-dev automake libtool git-core autoconf pkg-config && \
    wget https://www.imagemagick.org/download/ImageMagick.tar.gz && \
    git clone https://github.com/strukturag/libde265.git && cd libde265 && \
    ./autogen.sh && ./configure --disable-dec265 --disable-sherlock265 && make -j4 && make install && cd .. && \
    tar xvf ImageMagick.tar.gz && cd ImageMagick* && \
    ./configure --without-magick-plus-plus --without-perl --disable-openmp --with-gvc=no --with-heic \
    --disable-docs && make -j$(nproc) && make install && ldconfig /usr/local/lib && cd .. && \
    apt remove -y build-essential wget autotools-dev automake libtool git-core autoconf pkg-config &&  \
    apt -y autoremove && \
    apt clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp* ImageMagick* libde265 && identify --version
COPY --from=builder /app/server /app/server
CMD ["/app/server"]
