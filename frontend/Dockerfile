# Build with:
#   docker build --target dev  .   -> Vite dev server with hot reload
#   docker build --target prod .   -> static build served by nginx

FROM node:22-alpine AS deps
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci

# --- dev: source comes from a bind mount in docker-compose.yml.
FROM deps AS dev
EXPOSE 5173
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

# --- build: compiles static assets for the prod stage.
FROM deps AS build
COPY . .
RUN npm run build

# --- prod: self-contained static file server.
FROM nginx:1.27-alpine AS prod
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
