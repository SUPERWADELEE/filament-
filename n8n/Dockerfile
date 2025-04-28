FROM node:18

WORKDIR /app

RUN apt update && apt install -y python3 build-essential git && apt clean

RUN corepack enable && corepack prepare pnpm@latest --activate

RUN git clone https://github.com/n8n-io/n8n.git .

# 最重要！告訴 Vite 前端路徑 base 是 /n8n/
ENV VITE_BASE_PATH=/n8n/

RUN pnpm install --frozen-lockfile && VITE_BASE_PATH=/n8n/ pnpm build

CMD ["npx", "n8n"]
