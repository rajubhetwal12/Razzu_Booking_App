# LuxStay PHP/MySQL Deployment

This repository is a PHP/MySQL hotel booking web app. It cannot be deployed directly to Vercel as a full PHP/MySQL app, but it can run in Docker on any container host.

## Run locally with Docker

1. Install Docker Desktop.
2. Copy the example env file:

```bash
copy .env.example .env
```

3. Start the app:

```bash
docker compose up --build -d
```

4. Open the app in your browser:

```text
http://localhost:8080
```

5. Initialize the app once:

```text
http://localhost:8080/run_migration.php
```

6. After setup, optionally remove `run_migration.php` and `server/setup.php` for security.

## Default login accounts

- Admin: `admin@luxstay.com` / `Admin@LuxStay2024`
- Manager: `manager@luxstay.com` / `Manager@LuxStay2024`
- Customer: `customer@luxstay.com` / `Customer@123`

## Notes

- The database schema is loaded automatically on first start from `database.sql`.
- If you want a custom host or domain, update `BASE_URL` in `.env` before starting Docker.
- For production hosting, deploy this Docker setup to any container hosting provider (Render, Railway, Fly.io, etc.).
