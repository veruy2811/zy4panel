FROM node:22-bookworm-slim

RUN useradd -m -d /home/container container
WORKDIR /home/container
USER container

ENV NODE_ENV=production
ENV STARTUP="npm start"

CMD ["/bin/sh", "-lc", "if [ -f package.json ] && [ ! -d node_modules ]; then npm install --omit=dev; fi; ${STARTUP}"]
