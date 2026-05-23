FROM eclipse-temurin:21-jre-jammy

RUN useradd -m -d /home/container container
WORKDIR /home/container
USER container

ENV SERVER_MEMORY=1024
ENV STARTUP="java -Xms128M -Xmx${SERVER_MEMORY}M -jar server.jar nogui"

CMD ["/bin/sh", "-lc", "echo eula=true > eula.txt; if [ ! -f server.jar ]; then echo 'Upload server.jar to Files before starting Minecraft.'; sleep infinity; fi; ${STARTUP}"]
