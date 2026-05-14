FROM node:22-alpine

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --omit=dev 2>/dev/null || npm install --omit=dev

COPY src ./src
COPY public ./public

ENV NODE_ENV=production
EXPOSE 8081

CMD ["node", "src/index.js"]
