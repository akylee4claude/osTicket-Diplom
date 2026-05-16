FROM python:3.11-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    TZ=Europe/Moscow

# Default to Yandex mirror to work around ISPs that block deb.debian.org
# (Fastly CDN). Override with `--build-arg DEBIAN_MIRROR=http://...` if
# behind a corporate proxy or building from another region.
ARG DEBIAN_MIRROR=http://mirror.yandex.ru

RUN set -eux; \
    if [ -f /etc/apt/sources.list.d/debian.sources ]; then \
        sed -i "s|http://deb.debian.org|${DEBIAN_MIRROR}|g; \
                s|https://deb.debian.org|${DEBIAN_MIRROR}|g; \
                s|http://security.debian.org|${DEBIAN_MIRROR}|g; \
                s|https://security.debian.org|${DEBIAN_MIRROR}|g" \
                /etc/apt/sources.list.d/debian.sources; \
    fi; \
    if [ -f /etc/apt/sources.list ]; then \
        sed -i "s|http://deb.debian.org|${DEBIAN_MIRROR}|g; \
                s|https://deb.debian.org|${DEBIAN_MIRROR}|g; \
                s|http://security.debian.org|${DEBIAN_MIRROR}|g; \
                s|https://security.debian.org|${DEBIAN_MIRROR}|g" \
                /etc/apt/sources.list; \
    fi; \
    apt-get update; \
    apt-get install -y --no-install-recommends default-mysql-client tini; \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Use a Russian-friendly pip mirror too (Yandex). Override with
# --build-arg PIP_INDEX_URL=... when building elsewhere.
ARG PIP_INDEX_URL=https://mirror.yandex.ru/mirrors/pypi/simple/
ARG PIP_TRUSTED_HOST=mirror.yandex.ru
ENV PIP_INDEX_URL=${PIP_INDEX_URL} \
    PIP_TRUSTED_HOST=${PIP_TRUSTED_HOST}

COPY worker/requirements.txt /app/requirements.txt
RUN pip install --no-cache-dir -r /app/requirements.txt

COPY worker /app/worker

ENTRYPOINT ["/usr/bin/tini", "--"]
CMD ["python", "-m", "worker.daemon"]
