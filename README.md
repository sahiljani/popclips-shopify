# Popclips - Shoppable Video Commerce App for Shopify

A Laravel 12 Shopify app that lets merchants create TikTok-style shoppable video carousels with interactive product hotspots.

## Features

- **Video Clips**: Upload and manage short-form videos (MP4, WebM, MOV)
- **Product Hotspots**: Tag products with interactive hotspots that appear at specific times
- **Theme-Adaptive Carousel**: Works on ANY Shopify theme with automatic color inheritance
- **Analytics Dashboard**: Track views, CTR, add-to-carts, and revenue attribution
- **Two Pricing Tiers**: Free (10 uploads/month) and Pro ($29.99/month, 50 uploads)

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+, MySQL
- **Frontend Admin**: React 18, Shopify Polaris UI
- **Storefront**: Vanilla JS Theme App Extension
- **Video Hosting**: Cloudinary
- **Authentication**: Shopify OAuth 2.0

## Requirements

- PHP 8.2+
- MySQL 8.0+
- Node.js 18+
- Composer
- Shopify Partner Account

## Installation

### 1. Enable MySQL PDO Extension

Make sure your PHP has the MySQL PDO extension enabled. Edit your `php.ini`:

```ini
extension=pdo_mysql
```

### 2. Install Dependencies

```bash
cd popclips
composer install
npm install
```

### 3. Configure Environment

Copy `.env.example` to `.env` and configure:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=172.245.126.174
DB_PORT=3306
DB_DATABASE=janisahil-popclips
DB_USERNAME=janisahil-popclips
DB_PASSWORD=Admin@41212@41212

# Shopify (get from Shopify Partners Dashboard)
SHOPIFY_API_KEY=your_api_key
SHOPIFY_API_SECRET=your_api_secret
SHOPIFY_SCOPES=read_products,write_products,read_content,write_content,read_themes,write_themes
SHOPIFY_APP_HOST=https://your-app-url.com

# Cloudinary (get from Cloudinary Dashboard)
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
```

### 4. Run Migrations

```bash
php artisan migrate
```

### 5. Build Frontend

```bash
npm run build
```

### 6. Start Development Server

```bash
php artisan serve
npm run dev
```

## Shopify App Setup

### 1. Create App in Shopify Partners

1. Go to [Shopify Partners Dashboard](https://partners.shopify.com)
2. Create a new app
3. Set App URL to your server URL (e.g., `https://your-app.com`)
4. Set Redirect URL to `https://your-app.com/auth/callback`
5. Copy API key and secret to `.env`

### 2. Configure App Proxy

In your Shopify app settings, add an App Proxy:
- Subpath prefix: `apps`
- Subpath: `popclips`
- Proxy URL: `https://your-app.com/api/v1/storefront`

### 3. Deploy Theme App Extension

The Theme App Extension is in `extensions/theme-app-extension/`. Deploy it to Shopify:

```bash
# Install Shopify CLI
npm install -g @shopify/cli@latest

# Login to Shopify
shopify auth login

# Deploy extension
cd extensions/theme-app-extension
shopify app deploy
```

## API Endpoints

### Admin API (requires shop authentication)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/clips` | List all clips |
| POST | `/api/v1/clips` | Create a new clip |
| GET | `/api/v1/clips/{id}` | Get clip details |
| PUT | `/api/v1/clips/{id}` | Update clip |
| DELETE | `/api/v1/clips/{id}` | Delete clip |
| POST | `/api/v1/clips/{id}/publish` | Publish clip |
| POST | `/api/v1/clips/{clipId}/hotspots` | Add hotspot |
| GET | `/api/v1/carousels` | List carousels |
| POST | `/api/v1/carousels` | Create carousel |
| GET | `/api/v1/analytics/overview` | Get analytics overview |

### Storefront API (via App Proxy)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/apps/popclips/api/v1/storefront/carousel` | Get carousel data |
| GET | `/apps/popclips/api/v1/storefront/clips/{id}` | Get clip details |
| POST | `/apps/popclips/api/v1/storefront/track` | Track analytics event |

## Database Schema

- `shops` - Shopify store information and settings
- `clips` - Video clips with metadata
- `carousels` - Carousel configurations
- `carousel_clips` - Pivot table for carousel-clip relationships
- `hotspots` - Product hotspots on clips
- `analytics` - Event tracking data
- `subscriptions` - Billing subscriptions

## File Structure

```
popclips/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/          # REST API controllers
│   │   │   ├── Auth/         # Shopify OAuth
│   │   │   ├── Storefront/   # Customer-facing endpoints
│   │   │   └── Webhook/      # Shopify webhooks
│   │   └── Middleware/       # Auth & verification
│   ├── Models/               # Eloquent models
│   └── Services/             # Business logic (Shopify, Cloudinary)
├── database/migrations/      # Database schema
├── extensions/
│   └── theme-app-extension/  # Shopify Theme Extension
│       ├── assets/           # CSS & JS
│       └── blocks/           # Liquid blocks
├── resources/
│   └── js/
│       ├── components/       # React components
│       ├── pages/            # React pages
│       └── utils/            # API utilities
└── routes/
    ├── api.php              # API routes
    └── web.php              # Web routes
```

## Theme App Extension

The storefront carousel automatically inherits your store's theme colors using CSS custom properties:

```css
--popclips-bg: var(--color-background);
--popclips-text: var(--color-foreground);
--popclips-accent: var(--color-base-accent-1);
```

Merchants can add the carousel via the Theme Editor without any code changes.

## CI/CD Pipeline

This project uses GitHub Actions for continuous integration and deployment.

### Workflows

#### CI Workflow (`.github/workflows/ci.yml`)
Runs on every push and pull request to `main` and `develop` branches:
- **Tests**: Runs PHPUnit tests on PHP 8.2, 8.3, and 8.4
- **Code Style**: Checks code formatting with Laravel Pint
- **Build Assets**: Verifies frontend assets build successfully

#### Deploy Workflow (`.github/workflows/deploy.yml`)
Automatically deploys to production when code is pushed to `main`:
- Installs dependencies
- Builds assets
- SSHs into the production server
- Pulls latest changes
- Runs migrations
- Clears caches

### GitHub Secrets Required

Set these secrets in your GitHub repository (**Settings → Secrets and variables → Actions**):

| Secret | Description |
|--------|-------------|
| `SSH_HOST` | Production server hostname (`popclips.janisahil.com`) |
| `SSH_USERNAME` | SSH username (`janisahil-popclips`) |
| `SSH_PASSWORD` | SSH password |
| `SSH_PORT` | SSH port (default: `22`) |

### Server Initial Setup

SSH into your server and run:

```bash
cd /home/janisahil-popclips/htdocs/popclips.janisahil.com
git clone https://github.com/sahiljani/popclips-shopify.git .
cp .env.example .env
nano .env  # Configure production values
php artisan key:generate
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
chmod -R 775 storage bootstrap/cache
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### Manual Deployment

Run on the server:
```bash
bash deploy.sh
```

## License

MIT
