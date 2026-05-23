FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl wget unzip tar git bash \
    && rm -rf /var/lib/apt/lists/* \
    && useradd -m -d /home/container container

WORKDIR /home/container
USER container

ENV STARTUP="/bin/bash start.sh"
CMD ["/bin/sh", "-lc", "if [ ! -f start.sh ]; then echo '#!/usr/bin/env bash' > start.sh && echo 'sleep infinity' >> start.sh && chmod +x start.sh; fi; ${STARTUP}"]
