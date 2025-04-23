# 使用 Node 18 官方映像為基底
FROM node:18

# 設定工作目錄
WORKDIR /app

# 安裝必要工具：build-essential、python3、git
RUN apt update && apt install -y \
  python3 build-essential git \
  && apt clean

# 下載 n8n 原始碼
RUN git clone https://github.com/n8n-io/n8n.git .

# 設定前端與後端的 base path
ENV N8N_PATH=/n8n \
    VUE_APP_PUBLIC_PATH=/n8n/ \
    VUE_APP_URL_BASE_API=/n8n/ \
    N8N_HOST=0.0.0.0 \
    N8N_PORT=5678

# 安裝所有相依（不加 --frozen-lockfile 是為了避免 lock 問題）
RUN npm install

# 編譯前端，這步很關鍵
RUN npm run build

# 啟動 n8n
CMD ["npx", "n8n"]