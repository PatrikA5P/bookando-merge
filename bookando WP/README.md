# 📅 Bookando - WordPress Booking & Appointment Plugin

A modern, modular WordPress plugin for comprehensive booking and appointment management.

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759B?logo=wordpress&logoColor=white)](https://wordpress.org)
[![Vue](https://img.shields.io/badge/Vue-3.5-4FC08D?logo=vue.js&logoColor=white)](https://vuejs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.9-3178C6?logo=typescript&logoColor=white)](https://www.typescriptlang.org)
[![License](https://img.shields.io/badge/License-Proprietary-red)](LICENSE)

---

## ✨ Features

### Core Modules

- **👥 Customers** - Complete customer management with detailed profiles
- **👨‍💼 Employees** - Staff management with scheduling, availability, and calendar integration
- **📆 Appointments** - Flexible appointment booking with recurring options
- **💼 Offers** - Service catalog with pricing, availability, and custom fields
- **💰 Finance** - Invoicing, payments, and financial reporting
- **🎓 Academy** - Training courses, quizzes, and certifications
- **🏢 Resources** - Location, room, and equipment management
- **🛠️ Tools** - Custom fields, form templates, notifications, and design customization
- **⚙️ Settings** - System configuration, roles, company settings
- **🤝 Partnerhub** - Partner network and collaboration features

### Technical Highlights

- ✅ **Multi-Tenant Architecture** - Secure data isolation per tenant
- ✅ **Modern Stack** - PHP 8.1+, Vue 3 Composition API, TypeScript, Vite
- ✅ **Design System** - 54+ reusable UI components with consistent styling
- ✅ **Internationalization** - Full i18n/l10n support with automated translations
- ✅ **Authentication** - JWT-based auth with rate limiting and activity logging
- ✅ **Security** - OWASP best practices, input sanitization, CSRF protection
- ✅ **Testing** - Vitest + Playwright with comprehensive test coverage
- ✅ **CI/CD** - GitHub Actions for automated testing and quality gates

---

## 📋 Requirements

### Server Requirements

- **PHP:** >= 8.1
- **WordPress:** >= 6.0
- **MySQL:** >= 5.7 or MariaDB >= 10.2
- **PHP Extensions:**
  - `json`
  - `mbstring`
  - `mysql` or `mysqli`
  - `curl` (for payment gateways)

### Development Requirements

- **Node.js:** >= 20.x (LTS recommended)
- **npm:** >= 10.x
- **Composer:** >= 2.x
- **PHP:** >= 8.1 with CLI access

---

## 🚀 Quick Start

### Installation (Production)

1. **Download** the latest release ZIP
2. **Upload** to WordPress via `Plugins > Add New > Upload Plugin`
3. **Activate** the plugin
4. **Configure** under `Bookando > Settings`

### Development Setup

```bash
# 1. Clone the repository
git clone https://github.com/PatrikA5P/bookando.git
cd bookando

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Build assets
npm run build:all

# 5. (Optional) Run in development mode
npm run dev

# 6. (Optional) Watch CSS changes
npm run watch:css
```

### WordPress Integration

Create a symlink from your WordPress plugins directory:

```bash
ln -s /path/to/bookando /path/to/wordpress/wp-content/plugins/bookando
```

Or copy the built plugin directory to your WordPress installation.

---

## 🏗️ Project Structure

```
bookando/
├── src/                        # Source code
│   ├── Core/                   # Core functionality
│   │   ├── Admin/              # WordPress admin integration
│   │   ├── Api/                # REST API base classes
│   │   ├── Auth/               # Authentication & authorization
│   │   ├── Base/               # Base classes (Module, Api, etc.)
│   │   ├── Design/             # UI component library
│   │   ├── Dispatcher/         # Request routing
│   │   ├── Licensing/          # License management
│   │   ├── Locale/             # Internationalization
│   │   ├── Model/              # Base model classes
│   │   ├── Security/           # Security utilities
│   │   ├── Service/            # Core services
│   │   └── Tenant/             # Multi-tenancy
│   │
│   └── modules/                # Plugin modules
│       ├── academy/            # Training & courses
│       ├── appointments/       # Appointment management
│       ├── customers/          # Customer management
│       ├── employees/          # Employee management
│       ├── finance/            # Financial operations
│       ├── offers/             # Service catalog
│       ├── partnerhub/         # Partner network
│       ├── resources/          # Resource management
│       ├── settings/           # System settings
│       └── tools/              # Utility tools
│
├── scripts/                    # Build & automation scripts
├── tests/                      # Test suites
├── docs/                       # Documentation
├── dist/                       # Compiled assets (gitignored)
├── vendor/                     # Composer dependencies (gitignored)
├── node_modules/               # NPM dependencies (gitignored)
├── package.json                # NPM configuration
├── composer.json               # Composer configuration
├── vite.config.ts              # Vite build configuration
├── tsconfig.json               # TypeScript configuration
└── README.md                   # This file
```

---

## 🛠️ Development

### Available Scripts

#### Frontend (NPM)

```bash
# Development server with HMR
npm run dev

# Production build
npm run build

# Build all (CSS + JS)
npm run build:all

# Linting
npm run lint
npm run lint:fix

# Testing
npm run test                    # Run all tests
npm run test:frontend           # Frontend unit tests
npm run test:e2e                # E2E tests with Playwright
npm run test:coverage           # Generate coverage report

# i18n
npm run i18n:audit              # Check translations
npm run i18n:fix                # Auto-fix translations

# CSS
npm run build:css               # Compile SCSS
npm run build:css:rtl           # Compile RTL styles
npm run watch:css               # Watch SCSS changes
```

#### Backend (Composer)

```bash
# Install dependencies
composer install

# PHP linting
composer run lint:phpstan

# Tests
composer test

# i18n
composer run i18n:pot           # Generate .pot template
composer run i18n:mo            # Compile .mo files
```

### Code Quality Standards

- **PHP:** PHPStan Level 6+
- **TypeScript:** Strict mode enabled
- **ESLint:** Max warnings = 0
- **Tests:** Minimum 70% coverage target
- **Git:** Conventional commits encouraged

---

## 🧪 Testing

### Unit Tests (Frontend)

```bash
npm run test
```

Uses Vitest with Vue Testing Library for component tests.

### Integration Tests (Backend)

```bash
composer test
```

Uses PHPUnit for REST API and service testing.

### E2E Tests

```bash
npm run test:e2e
```

Uses Playwright for end-to-end browser testing.

---

## 🌍 Internationalization

The plugin supports multiple languages through WordPress i18n:

```bash
# Audit Vue translations
npm run i18n:audit

# Auto-fix missing translations
npm run i18n:fix

# Generate .pot template
npm run i18n:pot

# Compile .mo files
npm run i18n:mo
```

**Supported Languages:**
- German (de_DE)
- English (en_US)
- Extensible via standard WordPress translation workflow

---

## 📖 Documentation

- **[STYLE_GUIDE.md](STYLE_GUIDE.md)** - UI/UX design system guidelines
- **[CHANGELOG.md](CHANGELOG.md)** - Version history and changes
- **[IMPROVEMENT_PLAN_95.md](IMPROVEMENT_PLAN_95.md)** - Roadmap for quality improvements
- **[docs/](docs/)** - Extended documentation

### API Documentation

REST API endpoints are available at:

```
/wp-json/bookando/v1/{module}/{endpoint}
```

Example:
```
GET  /wp-json/bookando/v1/customers
POST /wp-json/bookando/v1/appointments
```

Authentication via:
- WordPress session cookies
- JWT tokens
- API keys (where applicable)

---

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes with conventional commits
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### Commit Message Format

```
type(scope): subject

[optional body]

[optional footer]
```

**Types:** `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

---

## 🐛 Bug Reports & Feature Requests

Please use GitHub Issues for:

- 🐛 Bug reports
- 💡 Feature requests
- 📚 Documentation improvements
- ❓ Questions

**Before submitting:**
1. Search existing issues
2. Include reproduction steps for bugs
3. Provide environment details (PHP, WordPress, Browser versions)

---

## 📄 License

This project is proprietary software. See [LICENSE](LICENSE) file for details.

---

## 👥 Authors

- **Patrick** - *Initial work* - [PatrikA5P](https://github.com/PatrikA5P)

---

## 🙏 Acknowledgments

- Built with [Vue 3](https://vuejs.org/)
- Powered by [Vite](https://vitejs.dev/)
- Styled with custom design system
- Tested with [Vitest](https://vitest.dev/) and [Playwright](https://playwright.dev/)

---

## 📊 Project Status

**Current Version:** 1.0.0
**Quality Score:** 74/100 (Target: 95+)
**Test Coverage:** Backend 60% | Frontend 13%
**PHP Version:** 8.1+
**WordPress Compatibility:** 6.0+

**Recent Improvements:**
- ✅ Fixed Axios DoS vulnerability
- ✅ Enhanced SQL injection protection
- ✅ Removed 1,424 lines of dead code
- ✅ Improved console.log handling for production

---

## 🔗 Useful Links

- [WordPress Codex](https://codex.wordpress.org/)
- [Vue 3 Documentation](https://vuejs.org/)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
- [Vite Guide](https://vitejs.dev/guide/)

---

**Made with ❤️ for the WordPress community**
