# Changelog

All notable changes to the Bookando WordPress Plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Comprehensive repository audit reports with 74/100 baseline score
- Improvement plan documentation for reaching 95+ score
- Structured logging with DEV-only console.logs
- Enhanced SQL security with prepared statements

### Fixed
- **Security:** Axios DoS vulnerability (CVE) - updated to latest secure version
- **Security:** SQL injection risks in settings module with wpdb->prepare()
- **Security:** Storybook dependency version conflicts resolved
- Removed 1,424 lines of dead code (DesignTab_old_backup.vue, EmployeesTableOld.vue)

### Changed
- Console.logs now wrapped with `import.meta.env.DEV` checks for production safety
- Improved code quality and maintainability across codebase
- Updated dependencies to secure versions
- Enhanced documentation structure

### Security
- Fixed high-severity Axios DoS vulnerability
- Addressed SQL injection patterns with prepared statements
- Note: Quill XSS (CVE-2024-4873) remains - upgrade to 2.0 requires Breaking Changes planning

## [1.0.0] - 2025-11-16

### Initial Release
- Multi-tenant WordPress booking plugin
- 10 core modules: Customers, Employees, Appointments, Offers, Finance, Academy, Resources, Tools, Settings, Partnerhub
- Vue 3 + TypeScript frontend with Vite build system
- PHP 8.1+ backend with PSR-4 autoloading
- Comprehensive design system with 54+ reusable components
- i18n/l10n support with automated POT/MO generation
- JWT authentication with rate limiting
- Activity logging and audit trail
- CI/CD pipeline with GitHub Actions
- PHPStan Level 6 static analysis
- ESLint max-warnings=0 enforcement
- Vitest + Playwright testing infrastructure

---

**Legend:**
- `Added` for new features
- `Changed` for changes in existing functionality
- `Deprecated` for soon-to-be removed features
- `Removed` for now removed features
- `Fixed` for any bug fixes
- `Security` for vulnerability fixes
