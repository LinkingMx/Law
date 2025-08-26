# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Backend (Laravel/PHP)
- `composer dev` - Start full development environment (Laravel server, queue, logs, and Vite)
- `composer dev:ssr` - Start development with server-side rendering
- `composer test` - Run PHP tests with Pest
- `php artisan test` - Run tests directly
- `vendor/bin/pest` - Run Pest tests directly
- `vendor/bin/pint` - Format PHP code with Laravel Pint
- `php artisan filament:upgrade` - Upgrade Filament components (runs automatically after composer updates)

### Settings & Configuration Management
- `php artisan settings:publish` - Publish settings migrations
- `php artisan migrate --path=database/settings` - Run settings migrations

### Backup System Commands
- `php artisan backup:run` - Execute manual backup
- `php artisan backup:scheduled` - Enhanced scheduled backup with proper email configuration
- `php artisan backup:debug-notifications [--test]` - Debug backup notification system
- `php artisan backup:fix-notifications [--enable-all]` - Fix common notification issues
- `php artisan backup:clean` - Clean old backups based on retention policy

### Workflow & State Management Commands
- `php artisan workflow:test-transitions` - Test state transition system with Documentation model

### System Monitoring Commands
- `php artisan pulse:check` - Check Laravel Pulse configuration and status
- `php artisan pulse:clear` - Clear Pulse data and start fresh monitoring
- Access monitoring dashboard at `/admin/system-monitoring` or `/pulse`

### Frontend (React/TypeScript)
- `npm run dev` - Start Vite development server
- `npm run build` - Build for production
- `npm run build:ssr` - Build with SSR support
- `npm run lint` - Run ESLint with auto-fix
- `npm run format` - Format code with Prettier
- `npm run format:check` - Check formatting without changes
- `npm run types` - Type check TypeScript without emitting

### Testing Commands
- `php artisan test --filter=TestName` - Run specific test class
- `vendor/bin/pest tests/Feature/ExampleTest.php` - Run specific test file
- `npm run test:ui` - Run Playwright tests with UI
- `npm run test:backup-history` - Run backup history Playwright tests
- `npm run test:backup-history:headed` - Run backup history tests in headed mode
- `npm run test:report` - Show Playwright test report

## Architecture Overview

This is a Laravel + React SPA using Inertia.js with the following key architectural patterns:

### Backend Structure
- **Laravel 12** with standard MVC structure
- **Inertia.js** for seamless SPA experience without API endpoints
- **Filament v3.3** admin panel at `/admin` route with configurable appearance
- **Spatie Laravel Settings** for dynamic configuration management
- **Spatie Laravel Backup** with Google Drive integration
- **Laravel Pulse** with Filament integration for real-time application monitoring
- **Filament Menu Builder** for dynamic navigation management
- **Filament Shield** for role-based permissions and access control
- **Filament Breezy** for user profile management, 2FA, and API tokens
- **Filament Logger** for comprehensive activity logging and audit trails
- **Pest** for testing framework instead of PHPUnit
- **SQLite** database for development
- **Queue system** with database driver

### Frontend Structure
- **React 19** with TypeScript
- **Inertia.js React adapter** for SPA routing
- **Tailwind CSS v4** for styling
- **Radix UI** components for accessible UI primitives
- **Server-side rendering (SSR)** support via `resources/js/ssr.tsx`

### Key Frontend Patterns
- **Layout system**: Nested layouts in `resources/js/layouts/`
  - `app-layout.tsx` - Main authenticated layout wrapper
  - `auth-layout.tsx` - Authentication pages layout
  - `settings/layout.tsx` - Settings section layout
- **Component organization**:
  - `components/ui/` - Reusable UI components (Radix-based)
  - `components/` - App-specific components
  - `hooks/` - Custom React hooks
- **Appearance system**: Built-in dark/light mode via `use-appearance` hook
- **Sidebar state**: Persisted via cookies and managed globally

### Routing & Navigation
- Laravel routes in `routes/web.php`, `routes/auth.php`, `routes/settings.php`
- Ziggy integration provides typed route helpers in React components
- Inertia pages located in `resources/js/pages/`

### State Management
- Inertia shared data for global state (user, app name, sidebar state)
- React hooks for component-level state
- Cookie-based persistence for UI preferences

### Authentication & Security
- Laravel Breeze-style authentication with Inertia
- Email verification and password reset flows included
- Auth pages use dedicated layout system
- Separate Filament authentication for admin panel with registration and password reset enabled
- **Filament Shield Integration**: Role-based permissions with super_admin role
- **Filament Breezy Integration**: User profile management, 2FA, API token management
- **Laravel Sanctum**: API authentication with personal access tokens

### Settings Management Architecture
- **Spatie Laravel Settings** for type-safe, database-stored configuration
- **Settings Classes** in `app/Settings/`:
  - `GeneralSettings` - App name, logo, contact info
  - `AppearanceSettings` - Filament theme colors, fonts, logos
  - `LocalizationSettings` - Language, timezone, date formats
  - `BackupSettings` - Google Drive and backup configuration
- **Dynamic Configuration**: Settings automatically applied to Filament panel on boot
- **Helper Functions**: Global settings access via `settings()`, `app_name()`, etc.

### Email System Architecture
- **EmailConfiguration Resource** - Multiple SMTP/service configurations
- **EmailTemplate Resource** - Configurable email templates with variable replacement
- **Mailtrap Integration** - One-click configuration for testing
- **Dynamic Email Configuration** - Automatically applies active configuration
- **Service Support**: SMTP, Mailgun, Postmark, Amazon SES, Sendmail

### Backup System Architecture
- **Spatie Laravel Backup** with Google Drive integration
- **BackupService** - Centralized backup operations and file management
- **Google Drive Integration** - Service account authentication, automatic folder creation
- **Backup Scheduling** - Configurable frequency with automatic cleanup
- **Notification System** - Email and Slack notifications for backup events
- **Filament Integration** - Complete backup management UI

### System Monitoring & Logging Architecture
- **Laravel Pulse Integration** for real-time application metrics
- **Monitoring Widgets**: Server performance, cache hits/misses, queue status, exceptions, slow queries/requests
- **Pulse Configuration**: Environment variables in `.env` for sampling rates and feature toggles
- **Dual Access**: Filament-integrated dashboard at `/admin/system-monitoring` and native Pulse at `/pulse`
- **Real-time Data**: Automatic collection of performance metrics with configurable retention
- **Activity Logging**: Comprehensive audit trails via Filament Logger with Spatie Activity Log
- **Log Types**: Resource operations, user access, notifications, model changes with color-coded categorization

### Advanced Workflow & State Management
- **Advanced Workflow Engine** (`app/Services/AdvancedWorkflowEngine.php`) - Dynamic workflow system with step-based execution
- **State Management with Spatie Model States** - Type-safe state transitions for models
- **State Classes** in `app/States/`: `DraftState`, `PendingApprovalState`, `ApprovedState`, `RejectedState`, `PublishedState`, `ArchivedState`
- **State Transition Service** (`app/Services/StateTransitionService.php`) - Manages state transitions with validation
- **Unified Event System** - Standardized events: `model_created`, `model_updated`, `state_changed`, `state_transition_{name}`
- **Workflow Step Templates** - Reusable step definitions for common workflow patterns
- **Model Variable Mapping** - Dynamic variable extraction from models for email templates
- **Model Introspection Service** - Automatic discovery of model attributes and relationships

### Filament Admin Panel Navigation Structure
- **"Gestión de Usuarios"**:
  - User management resource (`/admin/users`)
- **"Comunicaciones"**:
  - Email configurations (`/admin/email-configurations`)
  - Email templates (`/admin/email-templates`)
- **"Workflows"**:
  - Advanced workflows (`/admin/advanced-workflows`)
  - Approval states (`/admin/approval-states`)
  - State transitions (`/admin/state-transitions`)
  - Model variable mappings (`/admin/model-variable-mappings`)
- **"Documentación"**:
  - Documentation resources (`/admin/documentations`)
- **"Configuración"**:
  - General settings (`/admin/general-settings`)
  - Appearance settings (`/admin/appearance-settings`)
  - Localization settings (`/admin/localization-settings`)
- **"Sistema & Backup"**:
  - Backup configuration (`/admin/backup-configuration`)
  - Backup manager (`/admin/backup-manager`)
  - Backup history (`/admin/backup-history`)
  - System monitoring (`/admin/system-monitoring`)
  - Activity logs (`/admin/activity-logs`)
  - Exceptions (`/admin/exceptions`)
- **"Shield"** (Auto-generated):
  - Roles (`/admin/shield/roles`)
- **User Menu** (Top-right avatar):
  - My Profile (`/admin/my-profile`) - Filament Breezy integration

## Important Implementation Notes

- **Dual Interface Architecture**: This app has both a React SPA frontend (main user interface) and a Filament admin panel (administrative interface)
- **SSR Support**: The app supports both CSR and SSR - build commands handle both modes
- **Dynamic Configuration**: Settings from database automatically configure both frontend and Filament
- **Component Aliasing**: Uses `@/` alias for `resources/js/` directory
- **Ziggy Routes**: Route names are available as typed functions in React components
- **Shared Data**: Global props (user, app name, settings) available via `usePage().props`
- **Mobile-First**: Components include mobile navigation patterns
- **Filament Structure**: Admin resources, pages, and widgets are auto-discovered in `app/Filament/` directory
- **Settings Architecture**: Database-driven configuration with type safety and automatic application
- **Backup System**: Complete enterprise-grade backup solution with cloud storage
- **System Monitoring**: Real-time performance monitoring via Laravel Pulse with Filament integration
- **Activity Logging**: All admin panel actions are automatically logged via Filament Logger
- **Menu Management**: Dynamic navigation structure via Filament Menu Builder plugin
- **User Profile Management**: Complete user profile system via Filament Breezy with 2FA and API tokens
- **Role-Based Access**: Granular permissions system via Filament Shield
- **Email Testing**: Easy Mailtrap integration for development and testing
- **Email Templates**: Dynamic template system with variable replacement
- **Localization**: Spanish as primary language (APP_LOCALE=es) with English fallback
- **Workflow Context**: Workflows can access model data, user info, and custom variables for email notifications
- **State Persistence**: Model states are persisted in database with full audit trail
- **Testing Infrastructure**: Playwright for E2E testing with specific tests for critical features

## Development Style Guidelines

### 🎯 **Problem-Solving Approach**
When encountering issues:
1. **✅ Identify Root Cause**: Analyze the specific error and its context
2. **✅ Provide Clear Summary**: Use structured format with before/after comparisons
3. **✅ Show Exact Changes**: Include code snippets showing what changed
4. **✅ Explain Benefits**: Detail what the fix accomplishes
5. **✅ Verify Completeness**: Ensure the solution is thorough and tested

### 📊 **Communication Style**
- **Use Emojis Strategically**: ✅ ❌ 🔧 🎯 📊 for visual clarity
- **Structured Reporting**: Clear sections with headers and bullet points
- **Before/After Comparisons**: Show exact changes made
- **Status Indicators**: Use checkmarks and X marks for clear status
- **Concise but Complete**: Thorough information in digestible format

### 🛠️ **Technical Standards**
- **Laravel 12 Compatibility**: Always ensure compatibility with latest Laravel
- **Filament v3.3 Native Components**: Use only native Filament components, avoid custom builders
- **Dependency Injection**: Use `app(Service::class)` or method injection, avoid typed properties that cause initialization issues
- **Error Prevention**: Validate all method signatures and component methods exist
- **Route Verification**: Always verify route names exist before using them
- **Icon Validation**: Ensure all heroicons used are valid in the current heroicons set

### 🎨 **UI/UX Consistency**
- **Native Filament Patterns**: Follow Filament's design system and component patterns
- **Consistent Navigation**: Use proper route names and navigation structure
- **Professional Styling**: Clean, consistent interfaces across all pages
- **Responsive Design**: Ensure all interfaces work on all device sizes
- **Semantic Icons**: Use appropriate heroicons that match the functionality

### 🔍 **Quality Assurance**
- **Test Critical Paths**: Verify functionality works without errors
- **Cross-Page Consistency**: Ensure similar pages follow the same patterns
- **Error Handling**: Implement proper error handling and user feedback
- **Performance**: Use efficient patterns that don't cause performance issues
- **Accessibility**: Follow accessibility best practices with proper ARIA labels

This style ensures professional, consistent, and reliable development across the entire application.

# important-instruction-reminders
Do what has been asked; nothing more, nothing less.
NEVER create files unless they're absolutely necessary for achieving your goal.
ALWAYS prefer editing an existing file to creating a new one.
NEVER proactively create documentation files (*.md) or README files. Only create documentation files if explicitly requested by the User.

## System Purpose Memories
- Este es un sistema que se usará para mantener organizada la infromacion legal de sucursales como permisos, contratos etc... el back (filament) lo administratan los admins del sistema y el front (React) lo harán los usuarios de las sucursales.

## Filament Development Memories
- todos los recursos de filament deben tener una redirección al listado cuando se crean o editar registros, ademas debes aplicar las notificaciones personalizadas, icono, título y subtitulo.