# HyperBit Social

<p align="center">
  <img src="public/images/logo.png" alt="HyperBit Social" width="200">
</p>

<p align="center">
  <strong>Open-source social media scheduler for Facebook and Instagram</strong>
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#quick-start">Quick Start</a> •
  <a href="#meta-app-setup">Meta App Setup</a> •
  <a href="#deployment">Deployment</a> •
  <a href="#roadmap">Roadmap</a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License">
  <img src="https://img.shields.io/badge/php-8.2+-purple.svg" alt="PHP">
  <img src="https://img.shields.io/badge/laravel-12-red.svg" alt="Laravel">
</p>

---

**HyperBit Social** is a self-hostable Buffer alternative that lets you schedule and publish posts to Facebook Pages and Instagram Business accounts. Built with Laravel, it's designed to work on shared hosting without Redis or background workers.

> 📸 **Screenshots coming soon** - See the [demo](#) or deploy your own instance.

## Features

- 📅 **Schedule Posts** - Plan content in advance with timezone support
- 📱 **Multi-Platform** - Publish to Facebook Pages and Instagram
- 🖼️ **Image Support** - Upload and schedule visual content
- ⚡ **Instant Publish** - Post immediately when needed
- 🔐 **Encrypted Tokens** - Access tokens encrypted at rest
- 🌐 **Bilingual** - Spanish and English interface
- 🚀 **Shared Hosting** - Works without Redis or workers
- 📖 **Open Source** - MIT licensed, customize freely

## Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Database**: MySQL/MariaDB (or SQLite for development)
- **Frontend**: Tailwind CSS 4, Alpine.js
- **API**: Meta Graph API v22.0

## Quick Start

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ (for building assets)
- MySQL/MariaDB or SQLite

### Installation

```bash
# Clone the repository
git clone https://github.com/kaiser5474/hyperbit-social.git
cd hyperbit-social

# Install PHP dependencies
composer install

# Install Node dependencies and build assets
npm install
npm run build

# Configure environment
cp .env.example .env
php artisan key:generate

# Edit .env with your Meta App credentials
# See "Meta App Setup" below

# Run migrations
php artisan migrate

# Start the development server
php artisan serve
```

Visit `http://localhost:8000` to see your installation.

## Meta App Setup

HyperBit Social uses the Meta Graph API for publishing. You'll need to create a Meta App:

### 1. Create the App

1. Go to [Meta for Developers](https://developers.facebook.com/)
2. Click **My Apps** → **Create App**
3. Select **Other** → **Business**
4. Name it "HyperBit Social" (or your preferred name)

### 2. Configure Use Cases

Add these products to your app:

- **Facebook Login for Business**
- **Instagram Graph API**
- **Pages API**

### 3. Set Permissions

Request these permissions (most require App Review for public use):

| Permission | Purpose |
|------------|---------|
| `public_profile` | Basic user info |
| `email` | User email address |
| `pages_show_list` | List user's pages |
| `pages_read_engagement` | Read page content |
| `pages_manage_posts` | Publish to pages |
| `instagram_basic` | Basic Instagram info |
| `instagram_content_publish` | Publish to Instagram |
| `business_management` | Manage business assets |

### 4. Configure OAuth

In **Facebook Login** → **Settings**:

- **Valid OAuth Redirect URIs**: `https://your-domain.com/auth/facebook/callback`
- **Deauthorize Callback URL**: `https://your-domain.com/api/meta/data-deletion`
- **Data Deletion Request URL**: `https://your-domain.com/api/meta/data-deletion`

### 5. App Settings

In **Settings** → **Basic**:

- **App Domains**: `your-domain.com`
- **Privacy Policy URL**: `https://your-domain.com/privacy`
- **Terms of Service URL**: `https://your-domain.com/terms`

### 6. Environment Variables

Add to your `.env`:

```env
META_APP_ID=your_app_id
META_APP_SECRET=your_app_secret
META_REDIRECT_URI=https://your-domain.com/auth/facebook/callback
META_GRAPH_VERSION=v22.0
```

### 7. Testing

Before App Review, only users with roles on your app can log in:

1. Go to **Roles** → **Roles**
2. Add testers with their Facebook account

## Deployment

### Shared Hosting (Hostinger)

HyperBit Social is designed for shared hosting. See [docs/DEPLOY_HOSTINGER.md](docs/DEPLOY_HOSTINGER.md) for detailed instructions.

**Quick overview:**

1. Upload files via SSH/Git
2. Point document root to `public/`
3. Configure `.env` with database and Meta credentials
4. Run `composer install --no-dev && php artisan migrate --force`
5. Set up cron: `* * * * * php /path/to/artisan schedule:run`

### Required `.env` Variables

```env
# App
APP_NAME="HyperBit Social"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://social.hyperbitit.com

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=hyperbit_social
DB_USERNAME=your_user
DB_PASSWORD=your_password

# Meta API
META_APP_ID=1127758050142770
META_APP_SECRET=your_secret_here
META_REDIRECT_URI=https://social.hyperbitit.com/auth/facebook/callback
META_GRAPH_VERSION=v22.0
```

### Cron Line

```
* * * * * cd /path/to/hyperbit-social && php artisan schedule:run >> /dev/null 2>&1
```

### URLs for Meta App Settings

| Setting | URL |
|---------|-----|
| OAuth Redirect URI | `https://social.hyperbitit.com/auth/facebook/callback` |
| Data Deletion Callback | `https://social.hyperbitit.com/api/meta/data-deletion` |
| Privacy Policy | `https://social.hyperbitit.com/privacy` |
| Terms of Service | `https://social.hyperbitit.com/terms` |

## Roadmap

- [ ] **Threads Support** - Publish to Threads (API in development)
- [ ] **Team Support** - Multiple users per organization
- [ ] **Hosted Plans** - Managed hosting with billing
- [ ] **Analytics** - Post performance metrics
- [ ] **LinkedIn** - LinkedIn Pages integration
- [ ] **X (Twitter)** - X/Twitter posting
- [ ] **Bulk Scheduling** - CSV import for posts
- [ ] **AI Captions** - AI-generated post suggestions

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'feat: add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

MIT License - see [LICENSE](LICENSE) for details.

---

## En Español

**HyperBit Social** es un programador de redes sociales de código abierto para Facebook e Instagram. Alternativa auto-hospedable a Buffer.

### Características

- 📅 Programa posts con anticipación
- 📱 Publica en Facebook Pages e Instagram
- 🖼️ Soporte para imágenes
- 🔐 Tokens encriptados
- 🌐 Interfaz en español e inglés
- 🚀 Funciona en hosting compartido

Ver [documentación completa](#quick-start) arriba.

---

<p align="center">
  <strong>Built by <a href="https://hyperbitit.com">HyperBit IT</a></strong>
</p>

<p align="center">
  Need custom social media automation, chatbots, or integrations?<br>
  <a href="https://hyperbitit.com">Contact HyperBit IT →</a>
</p>
