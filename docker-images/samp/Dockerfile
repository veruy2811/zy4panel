FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates lib32stdc++6 libc6-i386 wget unzip \
    && rm -rf /var/lib/apt/lists/* \
    && useradd -m -d /home/container container

WORKDIR /home/container
USER container

ENV STARTUP="./samp03svr"
CMD ["/bin/sh", "-lc", "if [ ! -x ./samp03svr ]; then echo 'Upload SA-MP server files and chmod samp03svr.'; sleep infinity; fi; ${STARTUP}"]
